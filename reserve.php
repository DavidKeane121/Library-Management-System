<?php
session_start(); //Start session
require_once "config.php"; //Database Connection
require_once "header.php"; // Include header
if (!isset($_SESSION['username'])) // Check if user is logged in
{
    header("Location: login.php"); //If not logged in redirect to login
    exit();
}
$username = $_SESSION['username'];//Store username of current logged in user

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['isbn'])) // Get POST data 
{
    $isbn = trim($_POST['isbn']);
    $search = isset($_POST['search']) ? urlencode($_POST['search']) : "";
    $category = isset($_POST['category']) ? urlencode($_POST['category']) : "";
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;

    // Check if the book is available
    $checkBook = $conn->prepare("SELECT Available FROM Books WHERE ISBN = ?");
    $checkBook->bind_param("s", $isbn);
    $checkBook->execute();
    $result = $checkBook->get_result();

    if ($result->num_rows > 0) 
    {
        $book = $result->fetch_assoc();
        //If book is available reserve it
        if ($book['Available'] == 1) 
        {
            // Reserve the book (insert reservation)
            $reserve = $conn->prepare("INSERT INTO Reservations (ISBN, Username, ReservationDate) VALUES (?, ?, CURDATE())");
            $reserve->bind_param("ss", $isbn, $username);
            $reserve->execute();

            // Mark book as reserved
            $update = $conn->prepare("UPDATE Books SET Available = 0 WHERE ISBN = ?");
            $update->bind_param("s", $isbn);
            $update->execute();

            // Redirect back with success message and same page/search
            header("Location: search.php?search=$search&category=$category&page=$page&msg=reserved");
            exit();
        } 
        else 
        {
            // Book already reserved
            header("Location: search.php?search=$search&category=$category&page=$page&msg=not_available");
            exit();
        }
    } 
    else 
    {
        // Invalid ISBN
        header("Location: search.php?search=$search&category=$category&page=$page&msg=invalid");
        exit();
    }
} 
else 
{
    // No ISBN provided
    header("Location: search.php");
    exit();
}
?>
