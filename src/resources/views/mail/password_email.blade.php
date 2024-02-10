<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Password Reset Code</title>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 40px; /* Increased padding for body to give card more space */
     
        background-size: cover; /* Cover the entire page */
        background-position: center; /* Center the background image */
        color: #fff; /* Light text color for readability */
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .container {
        background-color: rgba(68, 68, 68, 0.9); /* Dark with opacity for readability */
        padding: 20px;
        margin: 0 auto;
        width: 100%;
        max-width: 600px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        border: 1px solid #555;
        text-align: center;
    }
    .logo {
    max-width: 75;
    max-height: 35;
    }
    .code {
        font-size: 24px;
        font-weight: bold;
        letter-spacing: 3px;
        background-color: rgb(248, 0, 51);
        color: #ffffff;
        padding: 10px;
        border-radius: 5px;
        display: inline-block;
        margin: 20px 0;
    }
    .instructions, .footer {
        color: #ccc; /* Lighter text for better readability against dark background */
    }
    .footer {
        margin-top: 20px;
        font-size: 0.8em;
        text-align: center;
    }
</style>
</head>
<body>
<div class="container">
    <img src="https://playrivalz.com/images/Playrivalz%20logo.png" alt="Logo" class="logo">
    <h2>Password Reset Code</h2>
    <p class="instructions">You recently requested to reset your password for your account. Use the code below to complete the process:</p>
    <div class="code">{{ $mail_content['code'] }}</div>
    <p class="instructions">Enter this code on the password reset page. This code will expire in 30 minutes.</p>
    <p class="instructions">If you did not request a password reset, please ignore this email or contact support if you have questions.</p>
    <div class="footer">
        <p>This is an automated message, please do not reply.</p>
    </div>
</div>
</body>
</html>
