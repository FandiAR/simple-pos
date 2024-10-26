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

// Class to handle Pengguna-related database operations
class PenggunaManager {
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    public function getPenggunaList($searchTerm = '') {
        if ($searchTerm) {
            $query = "SELECT IdPengguna, NamaPengguna, Password, NamaDepan, IdAkses FROM tugas_kelompok_4.pengguna WHERE NamaPengguna LIKE ? OR IdPengguna LIKE ?";
            $searchTerm = '%' . $searchTerm . '%';
            $stmt = $this->mysqli->prepare($query);
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
        } else {
            $query = "SELECT IdPengguna, NamaPengguna, Password, NamaDepan, IdAkses FROM tugas_kelompok_4.pengguna LIMIT 10";
            $stmt = $this->mysqli->prepare($query);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $penggunaList = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $penggunaList[] = $row;
            }
        }
        return $penggunaList;
    }

    public function deletePengguna($idToDelete) {
        $deleteQuery = "DELETE FROM tugas_kelompok_4.pengguna WHERE IdPengguna = ?";
        $stmt = $this->mysqli->prepare($deleteQuery);
        $stmt->bind_param("s", $idToDelete);
        $stmt->execute();
    }

    public function updatePengguna($editId, $newNamaPengguna, $newPassword, $newNamaDepan, $newIdAkses) {
        $editQuery = "UPDATE tugas_kelompok_4.pengguna SET NamaPengguna = ?, Password = ?, NamaDepan = ?, IdAkses = ? WHERE IdPengguna = ?";
        $stmt = $this->mysqli->prepare($editQuery);
        $stmt->bind_param("sssss", $newNamaPengguna, $newPassword, $newNamaDepan, $newIdAkses, $editId);
        $stmt->execute();
    }

    public function insertPengguna($newNamaPengguna, $newPassword, $newNamaDepan, $newIdAkses) {
        $newId = 'USR_' . (time() % 1000000);
        $insertQuery = "INSERT INTO tugas_kelompok_4.pengguna (IdPengguna, NamaPengguna, Password, NamaDepan, IdAkses) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->mysqli->prepare($insertQuery);
        $stmt->bind_param("sssss", $newId, $newNamaPengguna, $newPassword, $newNamaDepan, $newIdAkses);
        $stmt->execute();
    }

       public function getHakAksesOptions() {
        $query = "SELECT IdAkses, NamaAkses FROM tugas_kelompok_4.hakakses";
        $result = $this->mysqli->query($query);

        $hakAksesOptions = [];
        while ($row = $result->fetch_assoc()) {
            $hakAksesOptions[] = $row;
        }
        return $hakAksesOptions;
    }



}

    // Instantiate PenggunaManager
$penggunaManager = new PenggunaManager($mysqli);

// Get pengguna list and hak akses options
$searchTerm = isset($_GET['search']) ? $mysqli->real_escape_string($_GET['search']) : '';
$penggunaList = $penggunaManager->getPenggunaList($searchTerm);
$hakAksesOptions = $penggunaManager->getHakAksesOptions();

// Handle POST requests for Edit, Delete, and Insert
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $penggunaManager->deletePengguna($_POST['delete_id']);
        header("Location: pengguna.php");
        exit;
    } elseif (isset($_POST['update_user'])) {
        // Edit user
        $penggunaManager->updatePengguna($_POST['edit_id'], $_POST['NamaPengguna'], $_POST['Password'], $_POST['NamaDepan'], $_POST['IdAkses']);
        header("Location: pengguna.php");
        exit;
    } elseif (isset($_POST['add_user'])) {
        // Insert new user
        $penggunaManager->insertPengguna($_POST['NamaPengguna'], $_POST['Password'], $_POST['NamaDepan'], $_POST['IdAkses']);
        header("Location: pengguna.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pengguna List</title>
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
                input[type="Password"], select {
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
    <h2>Pengguna List</h2>

        <!-- Button to open the Insert Item Modal and Search Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <button id="addItemBtn">Add New pengguna</button>
            
            <!-- Search Form -->
            <form action="" method="get" style="display: inline;">
                <input type="text" name="search" placeholder="Search Pengguna..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="width: 200px; padding: 10px; margin-left: 10px; border: 1px solid #ccc; border-radius: 5px;">
                <button type="submit" style="padding: 10px; margin-left: 5px;">Search</button>
            </form>
        </div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeAddUserModal">&times;</span>
        <h2>Add New User</h2>
        <form action="" method="post"> 
            <label for="newNamaPengguna">Nama Pengguna:</label>
            <input type="text" id="newNamaPengguna" name="NamaPengguna" required>

            <label for="newPassword">Password:</label>
            <input type="password" id="newPassword" name="Password" required>

            <label for="newNamaDepan">Nama Depan:</label>
            <input type="text" id="newNamaDepan" name="NamaDepan" required>

            <label for="newIdAkses">Id Akses:</label>
            <select id="newIdAkses" name="IdAkses" required>
                <option value="">Select Access Level</option>
                <?php foreach ($hakAksesOptions as $option): ?>
                    <option value="<?php echo htmlspecialchars($option['IdAkses']); ?>">
                        <?php echo htmlspecialchars($option['NamaAkses']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="add_user">Add User</button>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editPenggunaModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeEditUserModal">&times;</span>
        <h2>Edit User</h2>
        <form action="" method="post"> 
            <input type="hidden" id="editId" name="edit_id" required>
            
            <label for="editNamaPengguna">Nama Pengguna:</label>
            <input type="text" id="editNamaPengguna" name="NamaPengguna" required>

            <label for="editPassword">Password:</label>
            <input type="password" id="editPassword" name="Password" required>

            <label for="editNamaDepan">Nama Depan:</label>
            <input type="text" id="editNamaDepan" name="NamaDepan" required>

            <label for="editIdAkses">Id Akses:</label>
            <select id="editIdAkses" name="IdAkses" required>
                <option value="">Select Access Level</option>
                <?php foreach ($hakAksesOptions as $option): ?>
                    <option value="<?php echo htmlspecialchars($option['IdAkses']); ?>">
                        <?php echo htmlspecialchars($option['NamaAkses']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" name="update_user">Update User</button>
        </form>
    </div>
</div>



    <!-- Display Pengguna Table -->
    <table>
        <thead>
            <tr>
                <th>ID Pengguna</th>
                <th>Nama Pengguna</th>
                <th>Password</th>
                <th>Nama Depan</th>
                <th>Id Akses</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($penggunaList as $pengguna): ?>
                <tr>
                    <td><?php echo htmlspecialchars($pengguna['IdPengguna']); ?></td>
                    <td><?php echo htmlspecialchars($pengguna['NamaPengguna']); ?></td>
                    <td>********</td>
                    <td><?php echo htmlspecialchars($pengguna['NamaDepan']); ?></td>
                    <td><?php echo htmlspecialchars($pengguna['IdAkses']); ?></td>
                    <td>
 <button class="editBtn" data-id="<?php echo $pengguna['IdPengguna']; ?>" data-nama="<?php echo htmlspecialchars($pengguna['NamaPengguna']); ?>" data-password="<?php echo htmlspecialchars($pengguna['Password']); ?>" data-depan="<?php echo htmlspecialchars($pengguna['NamaDepan']); ?>" data-akses="<?php echo htmlspecialchars($pengguna['IdAkses']); ?>">Edit</button>
    <form action="" method="post" style="display:inline;">
        <input type="hidden" name="delete_id" value="<?php echo $pengguna['IdPengguna']; ?>">
        <button type="submit" class="deleteBtn" onclick="return confirmDelete();">Delete</button>
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

<script>
    function confirmDelete() {
        return confirm("Are you sure you want to delete this user?");
    }

    // Handle edit button clicks
    var editButtons = document.querySelectorAll('.editBtn');
    editButtons.forEach(function(button) {
        button.onclick = function() {
            var id = button.getAttribute("data-id");
            var nama = button.getAttribute("data-nama");
            var password = button.getAttribute("data-password");
            var depan = button.getAttribute("data-depan");
            var akses = button.getAttribute("data-akses");

            document.getElementById("editId").value = id;
            document.getElementById("editNamaPengguna").value = nama;
            document.getElementById("editPassword").value = password;
            document.getElementById("editNamaDepan").value = depan;
            document.getElementById("editIdAkses").value = akses;

            document.getElementById('editPenggunaModal').style.display = 'block';
        };
    });

    var addUserModal = document.getElementById("addUserModal");
    var addUserBtn = document.getElementById("addItemBtn");
    var closeAddUserModal = document.getElementById("closeAddUserModal");
    var editPenggunaModal = document.getElementById("editPenggunaModal");
    var closeEditUserModal = document.getElementById("closeEditUserModal");

    // Open the Add User Modal
    addUserBtn.onclick = function() {
        addUserModal.style.display = "block";
    };

    // Close modals when clicking on the close button
    closeAddUserModal.onclick = function() {
        addUserModal.style.display = "none";
    };

    closeEditUserModal.onclick = function() {
        editPenggunaModal.style.display = "none";
    };

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        if (event.target === addUserModal) {
            addUserModal.style.display = 'none';
        }
        if (event.target === editPenggunaModal) {
            editPenggunaModal.style.display = 'none';
        }
    };
</script>



</body>
</html>
