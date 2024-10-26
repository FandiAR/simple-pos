<?php
// Start the session
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'db_connection.php'; // Include the database connection class file

// Create a new Database instance
$db = new Database("localhost", "root", "", "tugas_kelompok_4");
$conn = $db->getConnection(); // Get the connection

// Fetch form data
$inputUsername = trim($_POST['username']);
$inputPassword = trim($_POST['password']);

// Prepare and execute SQL statement to get the user along with access details
$sql = $conn->prepare("
    SELECT p.idpengguna, p.namapengguna, p.password, p.IDAKSES, h.NamaAkses 
    FROM pengguna p 
    INNER JOIN hakakses h ON p.IDAKSES = h.IDAKSES 
    WHERE p.namapengguna = ?");

if (!$sql) {
    die("SQL statement preparation failed: " . $conn->error);
}

$sql->bind_param("s", $inputUsername);
$sql->execute();
$result = $sql->get_result();

if ($result->num_rows > 0) {
    // Fetch the user from the database
    $user = $result->fetch_assoc();

    // Verify the password (you might want to use password_hash and password_verify in real applications)
    if ($inputPassword === $user['password']) {
        // Store user information in the session
        $_SESSION['user_id'] = $user['idpengguna'];
        $_SESSION['username'] = $user['namapengguna'];
        $_SESSION['access_level'] = $user['NamaAkses']; // Store access level if needed

        // Redirect to the menu page after successful login
        header('Location: menu.php');
        exit; // Always use exit after a header redirect
    } else {
        // Set an error message and redirect back to the login page
        $_SESSION['error'] = "Incorrect password!";
        header('Location: login.php');
        exit;
    }
} else {
    // Set an error message for username not found and redirect
    $_SESSION['error'] = "Username not found!";
    header('Location: login.php');
    exit;
}

// Close the SQL statement
$sql->close();
?>
