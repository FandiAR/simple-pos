<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$accessLevel = $_SESSION['access_level'];

// Include database connection
include 'db_connection.php'; 

// Class to handle Barang-related database operations
class BarangManager {
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    public function getBarangStats() {
        $query = "
                        SELECT 
                b.IdBarang,
                b.NamaBarang,
                COALESCE(SUM(p.JumlahPembelian), 0) AS TotalPurchased,
                COALESCE(SUM(s.JumlahPenjualan), 0) AS TotalSold,
                (COALESCE(SUM(p.JumlahPembelian), 0) - COALESCE(SUM(s.JumlahPenjualan), 0)) AS FinalStock,
                (COALESCE(SUM(s.JumlahPenjualan * s.HargaJual), 0) - COALESCE(SUM(s.JumlahPenjualan * p.HargaBeli), 0)) AS Profit 
               FROM 
                tugas_kelompok_4.barang b
            LEFT JOIN 
                tugas_kelompok_4.pembelian p ON b.IdBarang = p.IdBarang
            LEFT JOIN 
                tugas_kelompok_4.penjualan s ON b.IdBarang = s.IdBarang
            GROUP BY 
                b.IdBarang, b.NamaBarang
            ORDER BY 
                b.IdBarang;

        ";

        $result = $this->mysqli->query($query);

        $barangStats = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $barangStats[] = $row;
            }
        }
        return $barangStats;
    }
}

// Instantiate BarangManager
$barangManager = new BarangManager($mysqli);

// Get the barang statistics
$barangStats = $barangManager->getBarangStats();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Statistics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e8f5e9;
            margin: 0;
            padding: 0;
        }
        .top-bar {
            background-color: #388e3c; 
            color: white; 
            padding: 10px 20px;
            display: flex;
            justify-content: space-between; 
            align-items: center; 
        }
        .top-bar h1 {
            margin: 0; 
            cursor: pointer; 
        }
        .top-bar h1 a {
            color: white; 
            text-decoration: none; 
        }
        .top-bar nav {
            display: flex; 
        }
        .top-bar nav a {
            color: white; 
            text-decoration: none; 
            margin-left: 15px; 
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s; 
        }
        .top-bar nav a:hover {
            background-color: #66bb6a; 
        }
        .menu-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 1500px;
            margin: 20px auto; 
        }
        h2 {
            color: #388e3c; /* Match header color with the top bar */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .bottom-bar {
            background-color: #388e3c; 
            color: white; 
            padding: 10px 20px;
            display: flex;
            justify-content: space-between; 
            position: fixed; 
            left: 0;
            right: 0;
            bottom: 0; 
        }
        .bottom-bar p {
            margin: 0; 
        }
        /* Modal styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0, 0, 0, 0.5); 
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
            max-width: 500px; 
            border-radius: 10px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        button {
            background-color: #388e3c; 
            color: white; 
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s; 
        }
        button:hover {
            background-color: #66bb6a; 
        }
        input[type="text"], select {
            width: calc(100% - 22px);
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        /* Alert for Delete Action */
        .alert {
            color: red;
            font-weight: bold;
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
        <h2>Barang Statistics</h2>

        <table id="barangTable">
            <thead>
                <tr>
                    <th>ID Barang</th>
                    <th>Nama Barang</th>
                    <th>Total Purchased</th>
                    <th>Total Sold</th>
                    <th>Final Stock</th>
                    <th>Profit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($barangStats as $barang): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($barang['IdBarang']); ?></td>
                        <td><?php echo htmlspecialchars($barang['NamaBarang']); ?></td>
                        <td><?php echo htmlspecialchars($barang['TotalPurchased']); ?></td>
                        <td><?php echo htmlspecialchars($barang['TotalSold']); ?></td>
                        <td><?php echo htmlspecialchars($barang['FinalStock']); ?></td>
                        <td><?php echo htmlspecialchars($barang['Profit']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="bottom-bar">
        <p>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong></p>
        <p>Your access level is: <strong><?php echo htmlspecialchars($accessLevel); ?></strong></p>
    </div>

</body>
</html>
