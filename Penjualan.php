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

// Class to handle Penjualan-related database operations
class PenjualanManager {
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

   public function getPenjualanList($searchTerm = '') {
    if ($searchTerm) {
        $query = "SELECT p.IdPenjualan, p.JumlahPenjualan, p.HargaJual, p.IdPengguna, p.IdBarang, b.NamaBarang, 
                  (p.JumlahPenjualan * p.HargaJual) AS total
                  FROM tugas_kelompok_4.Penjualan p
                  JOIN tugas_kelompok_4.barang b ON p.IdBarang = b.IdBarang
                  WHERE p.IdPenjualan LIKE ? OR p.IdBarang LIKE ? OR b.NamaBarang LIKE ?";
        $searchTerm = '%' . $searchTerm . '%';
        $stmt = $this->mysqli->prepare($query);
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    } else {
        $query = "SELECT p.IdPenjualan, p.JumlahPenjualan, p.HargaJual, p.IdPengguna, p.IdBarang, b.NamaBarang, 
                  (p.JumlahPenjualan * p.HargaJual) AS total
                  FROM Penjualan p
                  JOIN barang b ON p.IdBarang = b.IdBarang
                  LIMIT 10";
        $stmt = $this->mysqli->prepare($query);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $PenjualanList = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $PenjualanList[] = $row;
        }
    }
    return $PenjualanList;
}


    public function deletePenjualan($idToDelete) {
        $deleteQuery = "DELETE FROM tugas_kelompok_4.Penjualan WHERE IdPenjualan = ?";
        $stmt = $this->mysqli->prepare($deleteQuery);
        $stmt->bind_param("s", $idToDelete);
        $stmt->execute();
    }

    public function updatePenjualan($editId, $newJumlah, $newHargaJual, $newIdPengguna, $newIdBarang) {
        $editQuery = "UPDATE tugas_kelompok_4.Penjualan SET JumlahPenjualan = ?, HargaJual = ?, IdPengguna = ?, IdBarang = ? WHERE IdPenjualan = ?";
        $stmt = $this->mysqli->prepare($editQuery);
        $stmt->bind_param("sssss", $newJumlah, $newHargaJual, $newIdPengguna, $newIdBarang, $editId);
        $stmt->execute();
    }


    public function insertPenjualan($newJumlah, $newHargaJual, $newIdPengguna, $newIdBarang) {
        // Logika generate ID tetap
        $countQuery = "SELECT COUNT(*) AS total FROM Penjualan";
        $countResult = $this->mysqli->query($countQuery);
        $row = $countResult->fetch_assoc();
        $rowNumber = $row['total'] + 1;
        $newItemId = 'PB_' . $rowNumber;

        $insertQuery = "INSERT INTO Penjualan (IdPenjualan, JumlahPenjualan, HargaJual, IdPengguna, IdBarang) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->mysqli->prepare($insertQuery);
        $stmt->bind_param("sssss", $newItemId, $newJumlah, $newHargaJual, $newIdPengguna, $newIdBarang);
        $stmt->execute();
    }
}

// Instantiate PenjualanManager outside the class definition
$PenjualanManager = new PenjualanManager($mysqli);

// Handle Search
$searchTerm = isset($_GET['search']) ? $mysqli->real_escape_string($_GET['search']) : '';
$PenjualanList = $PenjualanManager->getPenjualanList($searchTerm);

// Handle POST requests for Edit, Delete, and Insert
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $PenjualanManager->deletePenjualan($_POST['delete_id']);
        header("Location: Penjualan.php");
        exit;
    } elseif (isset($_POST['edit_id'])) {
        $PenjualanManager->updatePenjualan($_POST['edit_id'], $_POST['JumlahPenjualan'], $_POST['HargaJual'], $_POST['IdPengguna'], $_POST['IdBarang']);
        header("Location: Penjualan.php");
        exit;
    } elseif (isset($_POST['add_item'])) {
        $PenjualanManager->insertPenjualan($_POST['JumlahPenjualan'], $_POST['HargaJual'], $_POST['IdPengguna'], $_POST['IdBarang']);
        header("Location: Penjualan.php");
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
    <title>Penjualan List</title>
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
        <h2>Penjualan List</h2>

        <!-- Button to open the Insert Item Modal and Search Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <button id="addItemBtn">Add New Penjualan</button>
            
            <!-- Search Form -->
            <form action="" method="get" style="display: inline;">
                <input type="text" name="search" placeholder="Search Penjualan..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="width: 200px; padding: 10px; margin-left: 10px; border: 1px solid #ccc; border-radius: 5px;">
                <button type="submit" style="padding: 10px; margin-left: 5px;">Search</button>
            </form>
        </div>

<?php
// Fetch barang list for the combo box
$barangQuery = "SELECT IdBarang, NamaBarang FROM barang";
$barangResult = $mysqli->query($barangQuery);
?>

<!-- Insert Penjualan Modal -->
<div id="addPenjualanModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add New Penjualan</h2>
        <form action="" method="post">
            <label for="JumlahPenjualan">Jumlah Penjualan:</label>
            <input type="text" id="JumlahPenjualan" name="JumlahPenjualan" required>
            
            <label for="HargaJual">Harga Jual:</label>
            <input type="text" id="HargaJual" name="HargaJual" required>

            <!-- Hidden field for IdPengguna, set from session -->
            <input type="hidden" id="IdPengguna" name="IdPengguna" value="<?php echo htmlspecialchars($userId); ?>">

            <!-- Combo box for NamaBarang -->
            <label for="NamaBarang">Nama Barang:</label>
            <select id="NamaBarang" name="NamaBarang" required>
                <option value="">Select Nama Barang</option>
                <?php
                // Fetch barang list for the combo box
                $barangQuery = "SELECT IdBarang, NamaBarang FROM barang";
                $barangResult = $mysqli->query($barangQuery);
                while ($barang = $barangResult->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($barang['IdBarang']); ?>">
                        <?php echo htmlspecialchars($barang['NamaBarang']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <!-- Hidden field for IdBarang -->
            <input type="hidden" id="IdBarang" name="IdBarang" value="">

            <button type="submit" name="add_item">Add Penjualan</button>
        </form>
    </div>
</div>

  <table id="PenjualanTable">
    <thead>
        <tr>
            <th>ID Penjualan</th>
            <th>Nama Barang</th> <!-- Tambahkan kolom Nama Barang -->
            <th>Jumlah Penjualan</th>
            <th>Harga Jual</th>
            <th>Total</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($PenjualanList as $Penjualan): ?>
            <tr>
                <td><?php echo htmlspecialchars($Penjualan['IdPenjualan']); ?></td>
                <td><?php echo htmlspecialchars($Penjualan['NamaBarang']); ?></td> <!-- Tampilkan Nama Barang -->
                <td><?php echo htmlspecialchars($Penjualan['JumlahPenjualan']); ?></td>
                <td><?php echo htmlspecialchars($Penjualan['HargaJual']); ?></td>
                <td><?php echo htmlspecialchars($Penjualan['total']); ?></td>
                <td>
                    <button class="editBtn" data-id="<?php echo $Penjualan['IdPenjualan']; ?>" data-jumlah="<?php echo htmlspecialchars($Penjualan['JumlahPenjualan']); ?>" data-harga="<?php echo htmlspecialchars($Penjualan['HargaJual']); ?>" data-pengguna="<?php echo htmlspecialchars($Penjualan['IdPengguna']); ?>" data-barang="<?php echo htmlspecialchars($Penjualan['IdBarang']); ?>">Edit</button>
                    <form action="" method="post" style="display:inline; margin-left: 5px;">
                        <input type="hidden" name="delete_id" value="<?php echo $Penjualan['IdPenjualan']; ?>">
                        <button type="submit" class="deleteBtn" onclick="return confirm('Are you sure you want to delete this item?');">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>



    </div>

 <!-- Edit Modal Structure -->
<div id="editPenjualanModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Edit Penjualan</h2>
        <form action="" method="post">
            <input type="hidden" name="edit_id" id="editId">
            <label for="editJumlahPenjualan">Jumlah Penjualan:</label>
            <input type="text" id="editJumlahPenjualan" name="JumlahPenjualan" required>
            <label for="editHargaJual">Harga Jual:</label>
            <input type="text" id="editHargaJual" name="HargaJual" required>
            <label for="editIdPengguna">ID Pengguna:</label>
            <input type="text" id="editIdPengguna" name="IdPengguna" value="<?php echo htmlspecialchars($userId); ?>" readonly>
            <label for="editIdBarang">Nama Barang:</label>
            <select id="editIdBarang" name="IdBarang" required>
                <option value="">Select Nama Barang</option>
                <?php
                // Fetch barang list for the combo box
                $barangQuery = "SELECT IdBarang, NamaBarang FROM barang";
                $barangResult = $mysqli->query($barangQuery);
                while ($barang = $barangResult->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($barang['IdBarang']); ?>" 
                        <?php if (isset($barang['IdBarang']) && $barang['IdBarang'] === $Penjualan['IdBarang']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($barang['NamaBarang']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit">Save Changes</button>
        </form>
    </div>
</div>

<script>
// Handle edit button clicks for Penjualan items
var editButtons = document.querySelectorAll('.editBtn');
editButtons.forEach(function(button) {
    button.onclick = function() {
        var id = this.getAttribute('data-id');
        var jumlah = this.getAttribute('data-jumlah');
        var harga = this.getAttribute('data-harga');
        var pengguna = this.getAttribute('data-pengguna');
        var barang = this.getAttribute('data-barang');

        // Populate the edit modal with current data
        document.getElementById('editId').value = id;
        document.getElementById('editJumlahPenjualan').value = jumlah;
        document.getElementById('editHargaJual').value = harga;
        document.getElementById('editIdPengguna').value = pengguna;
        document.getElementById('editIdBarang').value = barang; // Set selected value for Barang

        // Show the modal
        document.getElementById('editPenjualanModal').style.display = 'block';
    };
});

// Close modal when clicking on close buttons
document.querySelectorAll('.close').forEach(function(closeButton) {
    closeButton.onclick = function() {
        document.getElementById('editPenjualanModal').style.display = 'none';
        document.getElementById('addPenjualanModal').style.display = 'none';
    };
});

// Close modal when clicking outside of it
window.onclick = function(event) {
    if (event.target == document.getElementById('editPenjualanModal')) {
        document.getElementById('editPenjualanModal').style.display = 'none';
    }
    if (event.target == document.getElementById('addPenjualanModal')) {
        document.getElementById('addPenjualanModal').style.display = 'none';
    }
};

// Get the modals
var addItemModal = document.getElementById("addPenjualanModal");
var editItemModal = document.getElementById("editPenjualanModal");

// Open modal when "Add New Penjualan" button is clicked
var addItemBtn = document.getElementById("addItemBtn");
var addPenjualanModal = document.getElementById("addPenjualanModal");

addItemBtn.onclick = function() {
    addPenjualanModal.style.display = "block";
};


// Close modal when clicking on close buttons
document.querySelectorAll('.close').forEach(function(closeBtn) {
    closeBtn.onclick = function() {
        addPenjualanModal.style.display = "none";
        document.getElementById('editPenjualanModal').style.display = 'none';
    };
});

 // Update hidden field 'IdBarang' when a NamaBarang is selected
    document.getElementById('NamaBarang').addEventListener('change', function() {
        var selectedBarangId = this.value;
        document.getElementById('IdBarang').value = selectedBarangId;
    });

    </script>

</body>
</html>
