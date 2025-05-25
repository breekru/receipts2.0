
<?php
session_start();

// If the user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include the external database configuration file
require('/home/blkfarms/secure/db_config.php');

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Display alert if there are emails without attachments
if (isset($_GET['no_attachments'])) {
    $no_attachments = intval($_GET['no_attachments']);
    
    // Show the alert if there are emails without attachments
    if ($no_attachments > 0) {
        echo "<script>
            alert('There are $no_attachments emails in the box that do not have attachments. Please check the email box and verify you are not missing receipts.');
        </script>";
    }

    // Clean the URL only after the alert has been shown
    echo "<script>
        setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.delete('no_attachments');
            window.history.replaceState({}, document.title, url.toString());
        }, 1000); // Delay to ensure the alert is handled first
    </script>";
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission to update 'logged' status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email_id'])) {
    $email_id = $_POST['email_id'];
    $action = $_POST['action'];

    if ($action == 'mark_logged') {
        // Update the email's logged status to TRUE
        $update_sql = "UPDATE Emails SET logged = 1 WHERE email_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('i', $email_id);
        $stmt->execute();
        $stmt->close();
    } elseif ($action == 'unmark_logged') {
        // Update the email's logged status to FALSE
        $update_sql = "UPDATE Emails SET logged = 0 WHERE email_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('i', $email_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Handle filtering
$filter_subject = isset($_GET['filter_subject']) ? $_GET['filter_subject'] : '';
$filter_logged = isset($_GET['filter_logged']) ? $_GET['filter_logged'] : '';
$filter_start_date = isset($_GET['filter_start_date']) ? $_GET['filter_start_date'] : '';
$filter_end_date = isset($_GET['filter_end_date']) ? $_GET['filter_end_date'] : '';

// Build the SQL query with filtering
$sql = "SELECT * FROM `Emails` WHERE `env` = 'receipt'";

if ($filter_subject) {
    $sql .= " AND subject LIKE '%" . $conn->real_escape_string($filter_subject) . "%'";
}
if ($filter_logged !== '') {
    $sql .= " AND logged = " . ($filter_logged == '1' ? '1' : '0');
}
if ($filter_start_date) {
    // If the start and end date are the same, only filter by that single day
    if ($filter_start_date == $filter_end_date) {
        $sql .= " AND DATE(received_time) = '" . $conn->real_escape_string($filter_start_date) . "'";
    } else {
        // Include the full date range (start to end date)
        $sql .= " AND DATE(received_time) >= '" . $conn->real_escape_string($filter_start_date) . "'";
    }
}
if ($filter_end_date && $filter_start_date != $filter_end_date) {
    // End date range comparison (consider only the date portion)
    $sql .= " AND DATE(received_time) <= '" . $conn->real_escape_string($filter_end_date) . "'";
}


$sql .= " ORDER BY received_time DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt Logger</title>
    <link rel="icon" href="icons/ReceiptLogger.png" type="image/png">
    <link rel="stylesheet" href="stylesheet.css">
</head>
<body>
    <div class="header-container">
        <img class="logo" width="180px" src="icons/ReceiptLogger.png"/?>
    <div class="header-right"><a href="logout.php" class="right-aligned-link"> <button class="right-aligned-button">Log Out</button></a></div>
    
    

<div class="header-info">
    <div class="center">
<h1>Receipt Logger</h1>
<p>Remember to Always send receipts with attachments to receipts@blkfarms.com</p>
<h3>STEP 1: <a href="https://receipts.blkfarms.com/process.php">Click Here</a> to pull a current sync with mailbox</h3></div></div></div>
<br><br>
<div class="center">
<!-- Filter Form -->
<form method="GET" class="compact-form">
    <div class="form-group">
    <label for="filter_subject">Filter by Subject:</label>
    <input type="text" name="filter_subject" id="filter_subject" value="<?php echo htmlspecialchars($filter_subject); ?>">
    </div>
    <div class="form-group">
    <label for="filter_logged">Filter by Logged:</label>
    <select name="filter_logged" id="filter_logged">
        <option value="">--Select--</option>
        <option value="1" <?php echo ($filter_logged == '1') ? 'selected' : ''; ?>>Logged</option>
        <option value="0" <?php echo ($filter_logged == '0') ? 'selected' : ''; ?>>Not Logged</option>
    </select>
    </div>
    <div class="form-group">
    <label for="filter_start_date">Start Date:</label>
    <input type="date" name="filter_start_date" id="filter_start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>">
    </div>
    <div class="form-group">
    <label for="filter_end_date">End Date:</label>
    <input type="date" name="filter_end_date" id="filter_end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>">
    </div>
    <div class="form-group">
    <button type="submit">Apply Filters</button>
    <!-- Clear Filters Button -->
    <button type="reset" onclick="window.location.href = window.location.pathname;">Clear Filters</button>
    </div>
</form><br>

</div>

<?php
if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Preview</th><th>Subject</th><th>Date</th><th>Logged</th><th>Actions</th></tr>";

    while ($row = $result->fetch_assoc()) {
        $email_id = $row['email_id'];
        $subject = $row['subject'];
        $received_time = $row['received_time'];
        $timeStamp = date( "m/d/Y", strtotime($received_time));
        $logged = $row['logged']; // Assuming 'logged' is a column in the Emails table

        // Get attachments for this email
        $attachment_sql = "SELECT * FROM Attachments WHERE email_id = $email_id";
        $attachments = $conn->query($attachment_sql);

        while ($attachment = $attachments->fetch_assoc()) {
            $file_path = $attachment['file_path'];
            $filename = $attachment['filename'];
            $file_ext = pathinfo($filename, PATHINFO_EXTENSION);

            $image_preview = '';
            if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif'])) {
                $image_preview = "<img src='$file_path' alt='$filename' class='thumbnail' style='height:150px;max-width:500px;' onclick='openModal(this)'>";
            } else {
                $image_preview = "No Preview Available";
            }

            // Display logged status (True/False)
            $logged_status = $logged ? 'Logged' : 'Not Logged';

            echo "<tr>
                    <td>$image_preview</td>
                    <td>$subject</td>
                    <td>$timeStamp</td>
                    <td>$logged_status</td>
                    <td>
                    <a href='$file_path' download='$filename'>Download Receipt</a><br><br>
                        <form method='POST'>
                            <input type='hidden' name='email_id' value='$email_id'>
                            <button type='submit' name='action' value='mark_logged' ".($logged ? 'disabled' : '')." >Mark As Logged</button>
                            <button type='submit' name='action' value='unmark_logged' ".(!$logged ? 'disabled' : '').">Unmark As Logged</button>
                        </form>
                    </td>
                  </tr>";
        }
    }
    echo "</table>";
} else {
    echo "<p>No emails found.</p>";
}
$conn->close();
?>

<!-- Modal HTML -->
<div id="imageModal" class="modal">
    <span class="close">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<!-- Modal Script -->
<script>
    function openModal(element) {
        var modal = document.getElementById('imageModal');
        var modalImg = document.getElementById('modalImage');
        modal.style.display = "block";
        modalImg.src = element.src;
    }

    // Close modal
    document.querySelector('.close').onclick = function() {
        document.getElementById('imageModal').style.display = "none";
    };

    // Close modal on outside click
    window.onclick = function(event) {
        var modal = document.getElementById('imageModal');
        if (event.target === modal) {
            modal.style.display = "none";
        }
    };
    
    const modalContent = document.querySelector('.modal-content');
let isDragging = false;
let startX, startY, initialX, initialY;

let scale = 1;
let zoomStep = 0.1;

// Dragging functionality
modalContent.addEventListener('mousedown', (e) => {
    isDragging = true;
    startX = e.clientX;
    startY = e.clientY;
    initialX = modalContent.offsetLeft;
    initialY = modalContent.offsetTop;
    modalContent.style.cursor = 'grabbing';
});

document.addEventListener('mousemove', (e) => {
    if (!isDragging) return;
    const dx = e.clientX - startX;
    const dy = e.clientY - startY;
    modalContent.style.transform = `translate(${initialX + dx}px, ${initialY + dy}px) scale(${scale})`;
});

document.addEventListener('mouseup', () => {
    isDragging = false;
    modalContent.style.cursor = 'grab';
});

// Zoom functionality
modalContent.addEventListener('wheel', (e) => {
    e.preventDefault();
    scale += e.deltaY > 0 ? -zoomStep : zoomStep;
    scale = Math.min(Math.max(scale, 0.5), 3); // Limit zoom level
    modalContent.style.transform = `scale(${scale})`;
});

</script>

</body>
</html>
