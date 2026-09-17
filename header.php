<?php
// This file contains the common header for all pages.It should be included at the top of every page. The header contains site title and navigation links
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Knocklyn Library</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        nav a /* Navigation styling */
        {
            margin-right: 10px;
            text-decoration: none;
            color: #007bff;
        }
        nav a:hover  /* Hover effect */
        {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<header>
    <h1>Knocklyn Library</h1>
    <nav>
    <?php 
        // Display different navigation links depending on login status.
        // If a session username exists → user is logged in.
        ?>
        <?php if (isset($_SESSION['username'])): ?>
            <!-- Links visible to logged-in users -->
            <a href="search.php">Search Books</a>
            <a href="view_reserved.php">My Reservations</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <!-- Links visible to guests -->
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
    <hr>
</header>
<main>
