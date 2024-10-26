<?php

class Database {
    private $connection;

    // Constructor to create a database connection
    public function __construct($servername, $username, $password, $database) {
        $this->connection = new mysqli($servername, $username, $password, $database);

        // Check the connection
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
    }

    // Method to get the connection
    public function getConnection() {
        return $this->connection;
    }

    // Destructor to close the connection
    public function __destruct() {
        $this->connection->close();
    }
}

// Usage
$db = new Database("localhost", "root", "", "tugas_kelompok_4");
$mysqli = $db->getConnection();

// Example query (optional)
// $result = $mysqli->query("SELECT * FROM barang");

?>
