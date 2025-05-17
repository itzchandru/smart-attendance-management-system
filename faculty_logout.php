<?php
session_start();
session_destroy(); // Destroy all session data


// Remove cookies
setcookie('remember_faculty', '', time() - 3600, "/");
setcookie('faculty_id', '', time() - 3600, "/");
setcookie('faculty_name', '', time() - 3600, "/");

header("Location: faculty_login.php"); // Redirect to the login page
exit();
?>
