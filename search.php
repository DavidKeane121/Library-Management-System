<?php
session_start(); //Start session
require_once "config.php"; // Database connection

// Ensure user is logged in
if (!isset($_SESSION['username'])) 
{
    header("Location: login.php"); //Redirect to login
    exit();
}

require_once "header.php";
$username = $_SESSION['username']; //Logged in username
$message = "";

// Reservation confirmation
if (isset($_GET['msg']) && $_GET['msg'] === 'reserved') 
{
    $message = "✅ Book reserved successfully!";
}

// Load categories for dropdown
$categories = $conn->query("SELECT CategoryID, CategoryName FROM Category");

// Pagination setup
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search input
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
// SQL conditions
$where = []; // Holds SQL WHERE conditions
$params = []; // Holds the bound parameters
$types = ""; // Holds the bind_param type string

// If user entered search text
if (!empty($search)) 
{
    $where[] = "(B.BookTitle LIKE ? OR B.Author LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}
// If user selected a category
if (!empty($category)) 
{
    $where[] = "B.CategoryID = ?";
    $params[] = $category;
    $types .= "i";
}
// Combine where conditions together
$whereSQL = "";
if (count($where) > 0) 
{
    $whereSQL = "WHERE " . implode(" AND ", $where);
}

// Count total rows for pagination
$countSQL = "
    SELECT COUNT(*) AS total
    FROM Books B
    INNER JOIN Category C ON B.CategoryID = C.CategoryID
    $whereSQL
";

$countStmt = $conn->prepare($countSQL);
if ($types) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Main book query
$sql = "
    SELECT B.ISBN, B.BookTitle, B.Author, B.Edition, B.Year, B.Available, C.CategoryName
    FROM Books B
    INNER JOIN Category C ON B.CategoryID = C.CategoryID
    $whereSQL
    ORDER BY B.BookTitle ASC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
if ($types) 
{
    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;
    $stmt->bind_param($types, ...$params);
} 
else 
{
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search Books</title>
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

<h2>Search Books</h2>

<?php if (!empty($message)) { ?>
    <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
<?php } ?>

<form action="search.php" method="GET">
    <label>Search by Title or Author:</label>
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Enter book title or author">

    <label>Category:</label>
    <select name="category">
        <option value="">All Categories</option>
        <?php while ($row = $categories->fetch_assoc()) { ?>
            <option value="<?php echo $row['CategoryID']; ?>" 
                <?php if ($category == $row['CategoryID']) echo "selected"; ?>>
                <?php echo htmlspecialchars($row['CategoryName']); ?>
            </option>
        <?php } ?>
    </select>

    <button type="submit">Search</button>
</form>

<hr>

<?php if ($results->num_rows > 0) 
{ ?>
    <table>
        <tr>
            <th>ISBN</th>
            <th>Title</th>
            <th>Author</th>
            <th>Edition</th>
            <th>Year</th>
            <th>Category</th>
            <th>Availability</th>
            <th>Action</th>
        </tr>

        <?php while ($book = $results->fetch_assoc())
         { ?>
            <tr>
                <td><?php echo htmlspecialchars($book['ISBN']); ?></td>
                <td><?php echo htmlspecialchars($book['BookTitle']); ?></td>
                <td><?php echo htmlspecialchars($book['Author']); ?></td>
                <td><?php echo htmlspecialchars($book['Edition']); ?></td>
                <td><?php echo htmlspecialchars($book['Year']); ?></td>
                <td><?php echo htmlspecialchars($book['CategoryName']); ?></td>
                <td><?php echo $book['Available'] ? "Available" : "<span style='color:red;'>Reserved</span>"; ?></td>
                <td>
                    <?php if ($book['Available']) 
                    { ?>
                        <form action="reserve.php" method="POST" style="display:inline;">
                            <input type="hidden" name="isbn" value="<?php echo htmlspecialchars($book['ISBN']); ?>">
                            <!-- Preserve search and page context -->
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                            <input type="hidden" name="page" value="<?php echo $page; ?>">
                            <button type="submit" onclick="return confirm('Are you sure you want to reserve this book?');">
                                Reserve
                            </button>
                        </form>
                    <?php } 
                    else { ?>
                        <span>Not Available</span>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </table>
     <!-- Pagination -->                   
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&page=<?php echo $page - 1; ?>
            ">&laquo; Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&page=<?php echo $i; ?>"
               class="<?php echo ($i == $page) ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&page=<?php echo $page + 1; ?>
            ">Next &raquo;</a>
        <?php endif; ?>
    </div>

<?php } 
else { ?>
    <p>No books found matching your search.</p>
<?php } ?>
<?php require_once "footer.php"; ?>
</body>
</html>
