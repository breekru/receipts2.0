<?php
// Include the external database configuration file
require('/home/blkfarms/secure/db_config.php');

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Connect to the IMAP server
$inbox = imap_open($mailbox, $username, $password) or die("Can't connect to email server: " . imap_last_error());

// Get all emails
$emails = imap_search($inbox, 'ALL');

// Initialize a counter for emails without attachments
$emails_without_attachments = 0;

// Process each email
if ($emails) {
    foreach ($emails as $email_number) {
        // Get the email structure and overview
        $overview = imap_fetch_overview($inbox, $email_number, 0);
        $structure = imap_fetchstructure($inbox, $email_number);

        // Extract email metadata
        $email_sender = $overview[0]->from;
        $email_subject = $overview[0]->subject ?? '(No Subject)';
        $email_received_time = $overview[0]->date;

        // Convert the email's received date to your desired timezone (CST)
        $date = new DateTime($email_received_time, new DateTimeZone('UTC'));
        $timezone = new DateTimeZone('America/Chicago'); // Central Standard Time
        $date->setTimezone($timezone);
        $email_received_time = $date->format("Y-m-d H:i:s");

        // Check if the email with this timestamp already exists in the database
        $stmt = $conn->prepare("SELECT email_id FROM Emails WHERE received_time = ?");
        $stmt->bind_param("s", $email_received_time);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            continue;
        }
        $stmt->close();

        // Insert the email into the Emails table
        $stmt = $conn->prepare("INSERT INTO Emails (sender_email, subject, received_time, logged, env) VALUES (?, ?, ?, 0, 'receipt')");
        $stmt->bind_param("sss", $email_sender, $email_subject, $email_received_time);

        if (!$stmt->execute()) {
            error_log("Failed to insert email into database: " . $stmt->error);
            $stmt->close();
            continue;
        }

        // Get the last inserted email ID for linking attachments
        $email_id = $stmt->insert_id;
        $stmt->close();

        // Check for attachments
        $has_attachments = false;
        if (isset($structure->parts) && count($structure->parts)) {
            for ($i = 0; $i < count($structure->parts); $i++) {
                $part = $structure->parts[$i];

                // Check if the part is an attachment
                if (isset($part->disposition) && strtolower($part->disposition) == 'attachment') {
                    $has_attachments = true;

                    $attachment = imap_fetchbody($inbox, $email_number, $i + 1);

                    // Decode the attachment if necessary
                    if ($part->encoding == 3) {
                        $attachment = base64_decode($attachment);
                    } elseif ($part->encoding == 4) {
                        $attachment = quoted_printable_decode($attachment);
                    }

                    // Get the filename
                    $filename = isset($part->dparameters[0]->value) ? $part->dparameters[0]->value : "attachment_" . time() . "_" . $i;

                    // Save the file to the uploads directory
                    $filepath = "uploads/" . $filename;
                    if (file_put_contents($filepath, $attachment) === false) {
                        error_log("Failed to save attachment: $filename");
                        continue;
                    }

                    // Get file properties
                    $file_size = filesize($filepath);
                    $file_type = pathinfo($filename, PATHINFO_EXTENSION);
                    $uploaded_time = date("Y-m-d H:i:s");

                    // Insert the attachment into the database
                    $stmt = $conn->prepare("INSERT INTO Attachments (email_id, filename, file_path, file_size, file_type, uploaded_time, env) VALUES (?, ?, ?, ?, ?, ?, 'receipt')");
                    $stmt->bind_param("isssis", $email_id, $filename, $filepath, $file_size, $file_type, $uploaded_time);

                    if (!$stmt->execute()) {
                        error_log("Failed to insert attachment into database: " . $stmt->error);
                    }
                    $stmt->close();
                }
            }
        }

        // Increment the counter if no attachments were found
        if (!$has_attachments) {
            $emails_without_attachments++;
        }
    }
}

// Close IMAP connection
imap_close($inbox);

// Close the database connection
$conn->close();

// Redirect to the index page and pass the number of emails without attachments
header("Location: https://receipts.blkfarms.com/index.php?no_attachments=$emails_without_attachments");
exit();
?>
