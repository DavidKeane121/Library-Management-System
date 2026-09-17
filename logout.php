<?php
session_start(); //Start session
session_unset(); //Clear data stored in $_SESSION
session_destroy(); // End session
header("Location: login.php"); //Redirect back to login
exit();
