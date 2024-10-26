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

    public function getBarangList($searchTerm = '') {
        if ($searchTerm) {
            $query = "SELECT IdBarang, NamaBarang, Satuan, Status FROM barang WHERE NamaBarang LIKE ? OR IdBarang LIKE ?";
            $searchTerm = '%' . $searchTerm . '%';
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
        } else {
            $query = "SELECT IdBarang, NamaBarang, Satuan, Status FROM barang LIMIT 10";
            $stmt = $this->mysqli->prepare($query);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $barangList = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $barangList[] = $row;
            }
        }
        return $barangList;
    }

    public function deleteBarang($idToDelete) {
        $deleteQuery = "DELETE FROM barang WHERE IdBarang = ?";
        $stmt = $this->mysqli->prepare($deleteQuery);
        $stmt->bind_param("s", $idToDelete);
        $stmt->execute();
    }

    public function updateBarang($editId, $newName, $newSatuan, $newStatus) {
        $editQuery = "UPDATE barang SET NamaBarang = ?, Satuan = ?, Status = ? WHERE IdBarang = ?";
        $stmt = $this->mysqli->prepare($editQuery);
        $stmt->bind_param("ssss", $newName, $newSatuan, $newStatus, $editId);
        $stmt->execute();
    }

    public function insertBarang($newItemName, $newItemSatuan) {
        // Generate new ID
        $countQuery = "SELECT COUNT(*) AS total FROM barang";
        $countResult = $this->mysqli->query($countQuery);
        $row = $countResult->fetch_assoc();
        $rowNumber = $row['total'] + 1; 
        $newItemId = 'BR_' . $rowNumber;

        $insertQuery = "INSERT INTO barang (IdBarang, NamaBarang, Satuan, Status) VALUES (?, ?, ?, 'Ready')";
        $stmt = $this->mysqli->prepare($insertQuery);
        $stmt->bind_param("sss", $newItemId, $newItemName, $newItemSatuan);
        $stmt->execute();
    }
}

// Instantiate BarangManager
$barangManager = new BarangManager($mysqli);

// Handle Search
$searchTerm = isset($_GET['search']) ? $mysqli->real_escape_string($_GET['search']) : '';
$barangList = $barangManager->getBarangList($searchTerm);

// Handle POST requests for Edit, Delete, and Insert
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $barangManager->deleteBarang($_POST['delete_id']);
        header("Location: barang.php");
        exit;
    } elseif (isset($_POST['edit_id'])) {
        $barangManager->updateBarang($_POST['edit_id'], $_POST['NamaBarang'], strtoupper($_POST['Satuan']), $_POST['Status']);
        header("Location: barang.php");
        exit;
    } elseif (isset($_POST['add_item'])) {
        $barangManager->insertBarang($_POST['NamaBarang'], strtoupper($_POST['Satuan']));
        header("Location: barang.php");
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
    <title>Barang List</title>
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
            max-width: 600px;
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
            <h2>Barang List</h2>


        <!-- Button to open the Insert Item Modal and Search Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <button id="addItemBtn">Add New Item</button>
            
            <!-- Search Form -->
            <form action="" method="get" style="display: inline;">
                <input type="text" id="searchBar" name="search" placeholder="Search Barang..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="width: 200px; padding: 10px; margin-left: 10px; border: 1px solid #ccc; border-radius: 5px;">
                <button type="submit" style="padding: 10px; margin-left: 5px;">Search</button>
            </form>
        </div>


            <!-- Insert Item Modal -->
            <div id="addBarangModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Add New Item</h2>
                    <form action="" method="post">
                        <label for="NamaBarang">Nama Barang:</label>
                        <input type="text" id="NamaBarang" name="NamaBarang" required>
                        <label for="Satuan">Satuan:</label>
                        <input type="text" id="Satuan" name="Satuan" required oninput="this.value = this.value.toUpperCase();">
                        <button type="submit" name="add_item">Add Item</button>
                    </form>
                </div>
            </div>

            <table id="barangTable">
                <thead>
                    <tr>
                        <th>ID Barang</th>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>

                <?php foreach ($barangList as $barang): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($barang['IdBarang']); ?></td>
                        <td><?php echo htmlspecialchars($barang['NamaBarang']); ?></td>
                        <td><?php echo htmlspecialchars($barang['Satuan']); ?></td>
                        <td><?php echo htmlspecialchars($barang['Status']); ?></td>
                        <td>
                            <button class="editBtn" data-id="<?php echo $barang['IdBarang']; ?>" data-name="<?php echo htmlspecialchars($barang['NamaBarang']); ?>" data-satuan="<?php echo htmlspecialchars($barang['Satuan']); ?>" data-status="<?php echo htmlspecialchars($barang['Status']); ?>">Edit</button>
                            <form action="" method="post" style="display:inline;">
                                <!-- 
                                     <input type="hidden" name="delete_id" value="<?php echo $barang['IdBarang']; ?>">
                                     <button type="submit" class="deleteBtn" onclick="return confirm('Are you sure you want to delete this item?');">Delete</button> 
                                 -->
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="bottom-bar">
        <p>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong></p>
        <p>Your access level is: <strong><?php echo htmlspecialchars($accessLevel); ?></strong></p>
    </div>

    <!-- Modal for Editing Barang -->
    <div id="editBarangModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Barang</h2>
            <form action="" method="post">
                <label for="IdBarang">ID Barang:</label>
                <input type="text" id="editId" name="edit_id" readonly>
                <label for="NamaBarang">Nama Barang:</label>
                <input type="text" id="editName" name="NamaBarang" required>
                <label for="Satuan">Satuan:</label>
                <input type="text" id="editSatuan" name="Satuan" required>
                <label for="Status">Status:</label>
                <select id="editStatus" name="Status" required>
                    <option value="Ready">Ready</option>
                    <option value="Not Available">Not Available</option>
                </select>
                <button type="submit" class="button" style="margin-top: 10px;">Update</button>
            </form>
        </div>
    </div>

    <script>
        // Handle edit button clicks
        var editButtons = document.querySelectorAll('.editBtn');
        editButtons.forEach(function(button) {
            button.onclick = function() {
                var id = this.getAttribute('data-id');
                var name = this.getAttribute('data-name');
                var satuan = this.getAttribute('data-satuan');
                var status = this.getAttribute('data-status');

                // Populate the edit modal with current data
                document.getElementById('editId').value = id; 
                document.getElementById('editName').value = name;
                document.getElementById('editSatuan').value = satuan;

                // Set the selected status in the dropdown
                var statusDropdown = document.getElementById('editStatus');
                for (var i = 0; i < statusDropdown.options.length; i++) {
                    if (statusDropdown.options[i].value === status) {
                        statusDropdown.selectedIndex = i;
                        break;
                    }
                }

                // Show the modal
                document.getElementById('editBarangModal').style.display = 'block';
            };
        });

        // Close modal when clicking outside or on the close button
        document.querySelectorAll('.close').forEach(function(closeButton) {
            closeButton.onclick = function() {
                document.getElementById('editBarangModal').style.display = 'none';
            };
        });

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == document.getElementById('editBarangModal')) {
                document.getElementById('editBarangModal').style.display = 'none';
            }
        };


        // Get the modal
        var addItemModal = document.getElementById("addBarangModal");
        var editItemModal = document.getElementById("editBarangModal");


        // Get the button that opens the modal
        var addItemBtn = document.getElementById("addItemBtn");

        // Get the <span> element that closes the modal
        var closeBtns = document.querySelectorAll('.close');

        // When the user clicks the "Add New Item" button, open the modal 
        addItemBtn.onclick = function() {
            addItemModal.style.display = "block";
        };

        // Close modals when clicking on close buttons
        closeBtns.forEach(function(closeBtn) {
            closeBtn.onclick = function() {
                addItemModal.style.display = "none";
                editItemModal.style.display = "none";
            };
        });
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == addItemModal) {
                addItemModal.style.display = "none";
            }
        };
    </script>
</body>
</html>
