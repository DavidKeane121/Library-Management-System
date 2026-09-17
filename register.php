<?php
session_start(); //Start session
require_once "config.php"; // Database connection

$error = "";
$success = "";

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") 
{   // Take in form inputs and trim them
    $username   = trim($_POST['username']);
    $password   = trim($_POST['password']);
    $confirm_pw = trim($_POST['confirm_pw']);
    $firstname  = trim($_POST['firstname']);
    $surname    = trim($_POST['surname']);
    $address1   = trim($_POST['address1']);
    $address2   = trim($_POST['address2']);
    $email      = trim($_POST['email']);
    $city       = trim($_POST['city']);
    $mobile     = trim($_POST['mobile']);

    // Server-side validation
    // Make sure these required fields are not empty
    if (empty($username) || empty($password) || empty($confirm_pw) || empty($firstname) || empty($surname) || empty($mobile)) 
    {
        $error = "All required fields must be filled.";
    } 
    elseif ($password !== $confirm_pw) //Check password confirmation
    {
        $error = "Passwords do not match.";
    } 
    elseif (strlen($password) < 6) //Ensure password length
    {
        $error = "Password must be at least 6 characters long.";
    } 
    elseif 
    (!preg_match('/^[0-9]{10}$/', $mobile)) //Ensure mobile number is 10 digits
    {
        $error = "Mobile number must be 10 digits.";
    }
    else 
    {
        // Check if username already exists
        $check = $conn->prepare("SELECT Username FROM Users WHERE Username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) 
        {
            $error = "Username already exists. Choose another."; // Username must be unique
        } 
        else 
        {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO Users 
                (Username, Password, FirstName, Surname, AddressLine1, AddressLine2, Email, City, Mobile) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $username, $password, $firstname, $surname, $address1, $address2, $email, $city, $mobile);

            if ($stmt->execute()) //Execute insert query
            {
                $success = "✅ Registration successful! Redirecting to login..."; //Success message and redirect to login
                header("refresh:2; url=login.php");
            } 
            else 
            {
                $error = "Something went wrong. Try again.";//Database error
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>

<?php require_once "header.php"; ?>

<h2>Register</h2>
<!-- Display error or success messages -->
<?php 
if (!empty($error)) echo "<p style='color:red;'>$error</p>";
if (!empty($success)) echo "<p style='color:green;'>$success</p>";
?>
<!-- Registration Form -->
<form action="register.php" method="POST">
    Username*: <input type="text" name="username" required><br><br>
    Password*: <input type="password" minlength ="6" placeholder ="Minimum 6 characters" name="password" required><br><br>
    Confirm Password*: <input type="password" minlength ="6" placeholder ="Re-enter Password" name="confirm_pw" required><br><br>
    First Name*: <input type="text" name="firstname" required><br><br>
    Surname*: <input type="text" name="surname" required><br><br>
    Address Line 1: <input type="text" name="address1"><br><br>
    Address Line 2: <input type="text" name="address2"><br><br>
    Email: <input type="email" name="email"><br><br>
    City: <input type="text" name="city"><br><br>
    Mobile*: <input type="text" name="mobile" maxlength="10" required><br><br>
    <button type="submit">Register</button>
</form>

<p>Already have an account? <a href="login.php">Login here</a></p>

<?php require_once "footer.php"; ?> 
