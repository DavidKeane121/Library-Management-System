<?php
session_start();
require_once "config.php";


// Redirect if not logged in
if (!isset($_SESSION['username'])) 
{
    header("Location: login.php");
    exit();
}

require_once "header.php";
$username = $_SESSION['username']; //Logged in username
$message = "";


//Handles removing a reservation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['isbn'])) {
    $isbn = $_POST['isbn'];

    $conn->begin_transaction();
    try 
    {
        // Delete reservation for user and the book
        $delete = $conn->prepare("DELETE FROM Reservations WHERE ISBN = ? AND Username = ?");
        $delete->bind_param("ss", $isbn, $username);
        $delete->execute();

        // Mark book as available again
        if ($delete->affected_rows > 0) 
        {
            $update = $conn->prepare("UPDATE Books SET Available = 1 WHERE ISBN = ?");
            $update->bind_param("s", $isbn);
            $update->execute();
            $message = "✅ Reservation removed successfully.";
        }
        else 
        {
            $message = "❌ Reservation not found or already removed."; //No Reservation found
        }
        
        $conn->commit();// Save changes
    }
    catch (Exception $e) 
    {
        $conn->rollback(); //Error message and revert changes to database
        $message = "❌ Error removing reservation.";
    }
}


// Pagination setup
$limit = 5; // 5 rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; //Current page
$offset = ($page - 1) * $limit;

// Get total reservation count for user
$countQuery = $conn->prepare("SELECT COUNT(*) AS total FROM Reservations WHERE Username = ?");
$countQuery->bind_param("s", $username);
$countQuery->execute();
$totalResult = $countQuery->get_result()->fetch_assoc();
$totalRows = $totalResult['total']; // Total reservation
$totalPages = ceil($totalRows / $limit); //Number of pages needed


// Fetch paginated reservations
$query = $conn->prepare("
    SELECT B.ISBN, B.BookTitle, B.Author, B.Year, C.CategoryName, R.ReservationDate
    FROM Reservations R
    INNER JOIN Books B ON R.ISBN = B.ISBN
    INNER JOIN Category C ON B.CategoryID = C.CategoryID
    WHERE R.Username = ?
    ORDER BY R.ReservationDate DESC
    LIMIT ? OFFSET ?
");
$query->bind_param("sii", $username, $limit, $offset);
$query->execute();
$result = $query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Reserved Books</title>
<link rel="stylesheet" href="../css/style.css">
<style>
    table { border-collapse: collapse; width: 100%; }
    th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    .pagination { margin-top: 15px; text-align: center; }
    .pagination a 
    {
        display: inline-block;
        padding: 8px 12px;
        margin: 2px;
        border: 1px solid #ccc;
        text-decoration: none;
        border-radius: 4px;
        color: #333;
    }
    .pagination a.active 
    {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
    }
    .pagination a:hover 
    {
        background-color: #0056b3;
        color: white;
    }
</style>
</head>
<body>

<h2>My Reserved Books</h2>

<?php if (!empty($message)) { ?>
    <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
<?php } ?>

<?php if ($result->num_rows > 0) { ?>
    <table>
        <tr>
            <th>ISBN</th>
            <th>Title</th>
            <th>Author</th>
            <th>Year</th>
            <th>Category</th>
            <th>Reserved On</th>
            <th>Action</th>
        </tr>

        <?php while ($book = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($book['ISBN']); ?></td>
                <td><?php echo htmlspecialchars($book['BookTitle']); ?></td>
                <td><?php echo htmlspecialchars($book['Author']); ?></td>
                <td><?php echo htmlspecialchars($book['Year']); ?></td>
                <td><?php echo htmlspecialchars($book['CategoryName']); ?></td>
                <td><?php echo htmlspecialchars($book['ReservationDate']); ?></td>
                <td>
                    <form action="view_reserved.php" method="POST" style="display:inline;">
                        <input type="hidden" name="isbn" value="<?php echo $book['ISBN']; ?>">
                        <button type="submit" onclick="return confirm('Are you sure you want to remove this reservation?');">
                            Remove
                        </button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>">&laquo; Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>

<?php } 
else { ?>
    <p>You currently have no reserved books.</p>
<?php } ?>
<?php require_once "footer.php"; ?>
</body>
</html>
