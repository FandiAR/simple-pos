<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Display welcome message and access level
$username = $_SESSION['username'];
$accessLevel = $_SESSION['access_level']; // Fetching access level from session

// Include database connection
include 'db_connection.php'; // Include the database connection class file

// Close database connection
// Do not close the connection here. It will be closed automatically in the destructor
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu</title>
    <style>
        /* Your existing CSS styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #e8f5e9; /* Light green background */
            margin: 0;
            padding: 0;
        }
        /* Top Navigation Bar Styles */
        .top-bar {
            background-color: #388e3c; /* Dark green background color */
            color: white; /* Text color */
            padding: 10px 20px;
            display: flex;
            justify-content: space-between; /* Space between logo/text and nav links */
            align-items: center; /* Centering items vertically */
        }
        .top-bar h1 {
            margin: 0; /* Remove default margin */
        }
        /* Navigation links styles */
        .top-bar nav {
            display: flex; /* Horizontal layout for nav links */
        }
        .top-bar nav a {
            color: white; /* Link color */
            text-decoration: none; /* Remove underline */
            margin-left: 15px; /* Space between links */
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s; /* Smooth background color transition */
        }
        .top-bar nav a:hover {
            background-color: #66bb6a; /* Lighter green on hover */
        }
        /* Bottom Navigation Bar Styles */
        .bottom-bar {
            background-color: #388e3c; /* Dark green background color */
            color: white; /* Text color */
            padding: 10px 20px;
            display: flex;
            justify-content: space-between; /* Space between welcome message and access level */
            position: fixed; /* Fixed position at the bottom */
            left: 0;
            right: 0;
            bottom: 0; /* Align to bottom */
        }
        .bottom-bar p {
            margin: 0; /* Remove default margin */
        }
        /* Menu Container Styles */
        .menu-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px auto; /* Centering the menu container */
        }
    </style>
</head>
<body>

<div class="top-bar">
    <h1>Group 6 - Introduction to Data & Information Management</h1>
    <nav>
        <?php if ($accessLevel === 'SuperAdmin'): ?>
            <a href="barang.php" id="viewBarangBtn">Barang</a>
            <a href="Pembelian.php">Pembelian</a>
            <a href="penjualan.php">Penjualan</a>
            <a href="report.php">Report</a>
            <a href="pengguna.php">Pengguna</a>
            <a href="hakakses.php">Hak Akses</a>
            <a href="logout.php">Logout</a>
        <?php elseif ($accessLevel === 'Admin'): ?>
            <a href="barang.php" id="viewBarangBtn">Barang</a>
            <a href="Pembelian.php">Pembelian</a>
            <a href="penjualan.php">Penjualan</a>
            <a href="report.php">Report</a>
            <a href="pengguna.php">Pengguna</a>
            <a href="logout.php">Logout</a>
        <?php elseif ($accessLevel === 'Manager'): ?>
            <a href="barang.php" id="viewBarangBtn">Barang</a>
            <a href="Pembelian.php">Pembelian</a>
            <a href="penjualan.php">Penjualan</a>
            <a href="report.php">Report</a>
            <a href="logout.php">Logout</a>
        <?php elseif ($accessLevel === 'Kasir'): ?>
            <a href="Pembelian.php">Pembelian</a>
            <a href="penjualan.php">Penjualan</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </nav>
</div>

    <div class="menu-container">
        <h2>Menu</h2>
        <p>Select an option from the navigation above.</p>
        <!-- Additional menu content can go here -->
    </div>

    <div class="bottom-bar">
        <p>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong></p>
        <p>Your access level is: <strong><?php echo htmlspecialchars($accessLevel); ?></strong></p>
    </div>
</body>
</html>
