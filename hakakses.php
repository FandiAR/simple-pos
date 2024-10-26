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
$userId = $_SESSION['user_id'];

// Include database connection
include 'db_connection.php';

// Class to handle Hak Akses-related database operations
class HakAksesManager {
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    public function getHakAksesList($searchTerm = '') {
        if ($searchTerm) {
            $query = "SELECT IdAkses, NamaAkses, Keterangan FROM tugas_kelompok_4.hakakses WHERE IdAkses LIKE ? OR NamaAkses LIKE ?";
            $searchTerm = '%' . $searchTerm . '%';
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
        } else {
            $query = "SELECT IdAkses, NamaAkses, Keterangan FROM tugas_kelompok_4.hakakses LIMIT 10";
            $stmt = $this->mysqli->prepare($query);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $hakAksesList = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $hakAksesList[] = $row;
            }
        }
        return $hakAksesList;
    }

    public function deleteHakAkses($idToDelete) {
        $deleteQuery = "DELETE FROM tugas_kelompok_4.hakakses WHERE IdAkses = ?";
        $stmt = $this->mysqli->prepare($deleteQuery);
        $stmt->bind_param("s", $idToDelete);
        $stmt->execute();
    }

    public function updateHakAkses($editId, $newNama, $newKeterangan) {
        $editQuery = "UPDATE tugas_kelompok_4.hakakses SET NamaAkses = ?, Keterangan = ? WHERE IdAkses = ?";
        $stmt = $this->mysqli->prepare($editQuery);
        $stmt->bind_param("sss", $newNama, $newKeterangan, $editId);
        $stmt->execute();
    }

    public function insertHakAkses($newNama, $newKeterangan) {
        $insertQuery = "INSERT INTO tugas_kelompok_4.hakakses (NamaAkses, Keterangan) VALUES (?, ?)";
        $stmt = $this->mysqli->prepare($insertQuery);
        $stmt->bind_param("ss", $newNama, $newKeterangan);
        $stmt->execute();
    }
}

// Instantiate HakAksesManager
$hakAksesManager = new HakAksesManager($mysqli);

// Handle Search
$searchTerm = isset($_GET['search']) ? $mysqli->real_escape_string($_GET['search']) : '';
$hakAksesList = $hakAksesManager->getHakAksesList($searchTerm);

// Handle POST requests for Edit, Delete, and Insert
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $hakAksesManager->deleteHakAkses($_POST['delete_id']);
        header("Location: hakakses.php");
        exit;
    } elseif (isset($_POST['edit_id'])) {
        $hakAksesManager->updateHakAkses($_POST['edit_id'], $_POST['nama'], $_POST['keterangan']);
        header("Location: hakakses.php");
        exit;
    } elseif (isset($_POST['add_item'])) {
        $hakAksesManager->insertHakAkses($_POST['nama'], $_POST['keterangan']);
        header("Location: hakakses.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hak Akses List</title>
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
            max-width: 800px;
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

    <div class="bottom-bar">
        <p>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong></p>
        <p>Your access level is: <strong><?php echo htmlspecialchars($accessLevel); ?></strong></p>
    </div>

    <div class="menu-container">
        <h2>Hak Akses List</h2>

        <!-- Button to open the Insert Item Modal and Search Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <button id="addHakAksesBtn">Add Hak Akses</button>
            
            <!-- Search Form -->
            <form action="" method="get" style="display: inline;">
                <input type="text" name="search" placeholder="Search Hak Akses..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="width: 200px; padding: 5px;">
                <button type="submit">Search</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Id Akses</th>
                    <th>Nama Akses</th>
                    <th>Keterangan</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hakAksesList as $hakAkses): ?>
                <tr>
                    <td><?php echo htmlspecialchars($hakAkses['IdAkses']); ?></td>
                    <td><?php echo htmlspecialchars($hakAkses['NamaAkses']); ?></td>
                    <td><?php echo htmlspecialchars($hakAkses['Keterangan']); ?></td>
                    <td>
                        <button class="edit-btn" data-id="<?php echo htmlspecialchars($hakAkses['IdAkses']); ?>" data-nama="<?php echo htmlspecialchars($hakAkses['NamaAkses']); ?>" data-keterangan="<?php echo htmlspecialchars($hakAkses['Keterangan']); ?>">Edit</button>
                        <form action="" method="post" style="display: inline;">
                            <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($hakAkses['IdAkses']); ?>">
                        <button type="submit" class="deleteBtn" onclick="return confirm('Are you sure you want to delete this item?');">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for adding new Hak Akses -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeAddModal">&times;</span>
            <h2>Add Hak Akses</h2>
            <form action="" method="post">
                <input type="text" name="nama" placeholder="Nama Akses" required>
                <input type="text" name="keterangan" placeholder="Keterangan" required>
                <button type="submit" name="add_item">Add</button>
            </form>
        </div>
    </div>

    <!-- Modal for editing Hak Akses -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeEditModal">&times;</span>
            <h2>Edit Hak Akses</h2>
            <form id="editForm" action="" method="post">
                <input type="hidden" name="edit_id" id="editId" required>
                <input type="text" name="nama" id="editNama" placeholder="Nama Akses" required>
                <input type="text" name="keterangan" id="editKeterangan" placeholder="Keterangan" required>
                <button type="submit">Update</button>
            </form>
        </div>
    </div>



    <script>
        // Open the modal for adding new Hak Akses
        document.getElementById('addHakAksesBtn').onclick = function() {
            document.getElementById('addModal').style.display = "block";
        }

        // Close the modal for adding new Hak Akses
        document.getElementById('closeAddModal').onclick = function() {
            document.getElementById('addModal').style.display = "none";
        }

        // Open the modal for editing Hak Akses
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(button => {
            button.onclick = function() {
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');
                const keterangan = this.getAttribute('data-keterangan');
                document.getElementById('editId').value = id;
                document.getElementById('editNama').value = nama;
                document.getElementById('editKeterangan').value = keterangan;
                document.getElementById('editModal').style.display = "block";
            }
        });

        // Close the modal for editing Hak Akses
        document.getElementById('closeEditModal').onclick = function() {
            document.getElementById('editModal').style.display = "none";
        }

        // Close modals when clicking outside of them
        window.onclick = function(event) {
            if (event.target == document.getElementById('addModal') || event.target == document.getElementById('editModal')) {
                document.getElementById('addModal').style.display = "none";
                document.getElementById('editModal').style.display = "none";
            }
        }
    </script>
</body>
</html>
