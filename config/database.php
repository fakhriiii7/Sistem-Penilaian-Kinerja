<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Database {
    private $host = "localhost";
    private $user = "root";
    private $password = "";
    private $database = "sistem_penilaian_kinerja";
    private $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->password, $this->database);
        
        if ($this->conn->connect_error) {
            die("Koneksi database gagal: " . $this->conn->connect_error);
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function escapeString($string) {
        return $this->conn->real_escape_string($string);
    }
}

// Fungsi untuk redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Fungsi untuk menampilkan pesan
function flashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function showFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'];
        $message = $_SESSION['flash_message']['message'];
        
        echo "<div class='alert alert-$type'>$message</div>";
        
        unset($_SESSION['flash_message']);
    }
}

// Fungsi untuk mendapatkan data user yang login
function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        $db = new Database();
        $conn = $db->getConnection();
        
        $user_id = $db->escapeString($_SESSION['user_id']);
        $query = "SELECT * FROM users WHERE id = '$user_id'";
        $result = $conn->query($query);
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    }
    return null;
}

// Fungsi untuk check role
function checkRole($allowedRoles) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        redirect('../login.php');
    }
    
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        flashMessage('error', 'Akses ditolak!');
        redirect('../login.php');
    }
}

// Fungsi untuk mendapatkan jumlah data
function getTotalData($table) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $table = $db->escapeString($table);
    $query = "SELECT COUNT(*) as total FROM $table";
    $result = $conn->query($query);
    
    if ($result) {
        return $result->fetch_assoc()['total'];
    }
    
    return 0;
}

// Fungsi untuk mendapatkan periode aktif
function getActivePeriod() {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT * FROM periode_penilaian WHERE status = 'aktif' ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Fungsi untuk mendapatkan opsi select
function getSelectOptions($table, $value_field, $text_field, $selected = '', $where = '') {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT $value_field, $text_field FROM $table";
    if ($where) {
        $query .= " WHERE $where";
    }
    $query .= " ORDER BY $text_field";
    
    $result = $conn->query($query);
    $options = '';
    
    while ($row = $result->fetch_assoc()) {
        $value = $row[$value_field];
        $text = $row[$text_field];
        $is_selected = ($value == $selected) ? 'selected' : '';
        $options .= "<option value=\"$value\" $is_selected>$text</option>";
    }
    
    return $options;
}

// Fungsi untuk validasi akses data
function checkDataOwnership($table, $id, $user_id_field, $user_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $id = $db->escapeString($id);
    $query = "SELECT id FROM $table WHERE id = '$id' AND $user_id_field = '$user_id'";
    $result = $conn->query($query);
    
    return $result->num_rows > 0;
}

// Fungsi untuk mendapatkan periode aktif
function getActivePeriodWithDetail() {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT *, 
              DATEDIFF(tanggal_selesai, CURDATE()) as sisa_hari,
              DATEDIFF(tanggal_selesai, tanggal_mulai) + 1 as durasi
              FROM periode_penilaian 
              WHERE status = 'aktif' 
              ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Fungsi untuk cek apakah periode sedang aktif
function isPeriodActive($period_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $period_id = $db->escapeString($period_id);
    $query = "SELECT status FROM periode_penilaian WHERE id = '$period_id'";
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        $period = $result->fetch_assoc();
        return $period['status'] == 'aktif';
    }
    
    return false;
}

// Fungsi untuk mendapatkan statistik penilai
function getPenilaiStats($penilai_id) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $penilai_id = $db->escapeString($penilai_id);
    $query = "SELECT 
              (SELECT COUNT(*) FROM penilaian WHERE penilai_id = '$penilai_id') as total_penilaian,
              (SELECT COUNT(*) FROM penilaian WHERE penilai_id = '$penilai_id' AND status = 'selesai') as selesai,
              (SELECT COUNT(*) FROM penilaian WHERE penilai_id = '$penilai_id' AND status = 'draft') as draft";
    
    $result = $conn->query($query);
    return $result->fetch_assoc();
}
?>