<?php
session_start(); //Start session
require_once "config.php"; // Database connection

$error = "";

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    //Retrieve and trim user input
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    //Validate required fields
    if (empty($username) || empty($password)) 
    {
        $error = "Please enter both username and password.";
    } 
    else 
    {
        //SQL query to get Username from Users
        $stmt = $conn->prepare("SELECT Username, Password FROM Users WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result(); //Get result from executed statement
        //Check if User with that username exists
        if ($result->num_rows == 1) 
        {
            $row = $result->fetch_assoc();
            // Plain-text password comparison 
            if ($password === $row['Password']) 
            {   
                $_SESSION['username'] = $username; // Credentials valid → save user info in session
                header("Location: search.php"); // Redirect user to the search page
                exit();
            } 
            else 
            {
                $error = "Incorrect password."; // Password does not match
            }
        } 
        else 
        {
            $error = "Username not found."; // Username does not exist in database
        }
        $stmt->close();
    }
}
?>

<?php require_once "header.php"; ?>

<h2>Login</h2>
<!-- Display error message if present -->
<?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
<!-- Login Form -->
<form action="login.php" method="POST">
    Username: <input type="text" name="username" required><br><br>
    Password: <input type="password" name="password" required><br><br>
    <button type="submit">Login</button>
</form>
<!-- Link to registration page -->
<p>Don't have an account? <a href="register.php">Register here</a></p>

<?php require_once "footer.php"; ?>
