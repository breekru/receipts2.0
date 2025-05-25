<head>
        <link rel="icon" href="icons/ReceiptLogger.png" type="image/png">
        <title>REGISTRATION DISABLED - Receipt Logger</title>
        
        
                   <style>
        /* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body and Background */
body {
    font-family: 'Arial', sans-serif;
    height: 100vh;
    background: linear-gradient(45deg, #6cc1ff, #8a2be2);
    display: flex;
    justify-content: center;
    align-items: center;
    color: #fff;
}

/* Login Container */
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
    width: 100%;
}

/* Login Box */
.login-box {
    background: rgba(255, 255, 255, 0.2);
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    width: 100%;
    max-width: 400px;
    text-align: center;
    backdrop-filter: blur(10px);
}

/* Heading */
.login-box h2 {
    margin-bottom: 30px;
    font-size: 2rem;
}

/* Textbox Input */
.textbox {
    margin-bottom: 20px;
}

.textbox input {
    width: 100%;
    padding: 10px;
    background: #fff;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    color: #333;
    transition: 0.3s;
}

.textbox input:focus {
    outline: none;
    border: 2px solid #6cc1ff;
    box-shadow: 0 0 8px rgba(108, 193, 255, 0.8);
}

/* Button */
.btn {
    width: 100%;
    padding: 12px;
    background: #6cc1ff;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 1.2rem;
    cursor: pointer;
    transition: background 0.3s, transform 0.2s;
}

.btn:hover {
    background: #5bb7e0;
    transform: scale(1.05);
}

.btn:active {
    background: #4a99cc;
}

/* Mobile Responsiveness */
@media screen and (max-width: 480px) {
    .login-box {
        padding: 30px;
    }
    .login-box h2 {
        font-size: 1.8rem;
    }
    .textbox input {
        font-size: 0.9rem;
    }
    .btn {
        font-size: 1rem;
    }
}
</style>
</head>
<body>
    
     <img class="login-container" width="150px" src="icons/ReceiptLogger.png"/?>
          <div class="login-container">
         <div class="login-box">
    <h1>REGISTRATION DISABLED</h1><br>
    <p>Contact Site Administrator to Enable New User Registration</p>
</div>
</div>

</body>
