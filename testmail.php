<?php
$to = "itzchandru1@gmail.com ";  // Put your actual email here
$subject = "Test Email";
$message = "This is a test email from your server.";
$headers = "From: no-reply@atteandance-system.great-site.net";

if(mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully!";
} else {
    echo "Email sending failed.";
}
?>
