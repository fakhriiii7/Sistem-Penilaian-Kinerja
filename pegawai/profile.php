<?php
require_once '../config/database.php';

// Check if user is logged in and is an employee
checkRole(['pegawai']);

$db = new Database();
$conn = $db->getConnection();

// Get current user ID from session
$user_id = $_SESSION['user_id'];

// Get employee data
$query = "SELECT u.*, p.* 
          FROM users u 
          JOIN pegawai p ON u.id = p.user_id 
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    flashMessage('danger', 'Data pengguna tidak ditemukan');
    redirect('index.php');
}

$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $db->escapeString($_POST['username'] ?? '');
    $email = $db->escapeString($_POST['email'] ?? '');
    $no_telp = $db->escapeString($_POST['no_telp'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    // Validate username
    if (empty($username)) {
        $errors[] = "Username tidak boleh kosong";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username minimal 3 karakter";
    }
    
    // Validate email
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }

    // Check if username already exists (except current user)
    $check_username = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $check_username->bind_param("si", $username, $user_id);
    $check_username->execute();
    if ($check_username->get_result()->num_rows > 0) {
        $errors[] = "Username sudah digunakan oleh akun lain";
    }

    // If changing password
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Password saat ini tidak sesuai";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Password baru minimal 6 karakter";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Konfirmasi password tidak sesuai";
        }
    }

    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Update user data
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_user = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                $update_user->bind_param("ssi", $username, $hashed_password, $user_id);
            } else {
                $update_user = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                $update_user->bind_param("si", $username, $user_id);
            }
            $update_user->execute();

            // Update employee data
            $update_pegawai = $conn->prepare("UPDATE pegawai SET email = ?, no_telp = ? WHERE user_id = ?");
            $update_pegawai->bind_param("ssi", $email, $no_telp, $user_id);
            $update_pegawai->execute();

            $conn->commit();
            flashMessage('success', 'Profil berhasil diperbarui');
            redirect('profile.php');
        } catch (Exception $e) {
            $conn->rollback();
            flashMessage('danger', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    } else {
        foreach ($errors as $error) {
            flashMessage('danger', $error);
        }
    }
}

$page_title = "Profil Saya";
require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Profil Saya</h2>
    </div>
    <div class="card-body">
        <?php showFlashMessage(); ?>
        
        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="form-section">
                <h4>Informasi Pribadi</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama_lengkap" 
                                   value="<?php echo htmlspecialchars($user['nama_lengkap'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nip">NIP</label>
                            <input type="text" class="form-control" id="nip" 
                                   value="<?php echo htmlspecialchars($user['nip'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="jabatan">Jabatan</label>
                            <input type="text" class="form-control" id="jabatan" 
                                   value="<?php echo htmlspecialchars($user['jabatan'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="unit_kerja">Unit Kerja</label>
                            <input type="text" class="form-control" id="unit_kerja" 
                                   value="<?php echo htmlspecialchars($user['unit_kerja'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section mt-4">
                <h4>Informasi Kontak</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="username">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required
                                   minlength="3" maxlength="50">
                            <div class="invalid-feedback">
                                Username minimal 3 karakter dan maksimal 50 karakter
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            <small class="form-text text-muted">Contoh: nama@example.com</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="no_telp">No. Telepon</label>
                            <input type="tel" class="form-control" id="no_telp" name="no_telp" 
                                   value="<?php echo htmlspecialchars($user['no_telp'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section mt-4">
                <h4>Ganti Password</h4>
                <p class="text-muted">Kosongkan jika tidak ingin mengubah password</p>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="current_password">Password Saat Ini</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="new_password">Password Baru</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <small class="form-text text-muted">Minimal 6 karakter</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </form>
    </div>
</div>

<style>
    .form-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #e9ecef;
    }
    
    .form-section h4 {
        color: #495057;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }
    
    .form-control:read-only {
        background-color: #e9ecef;
        opacity: 1;
    }
</style>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Show/hide password fields based on current password
document.addEventListener('DOMContentLoaded', function() {
    const currentPassword = document.getElementById('current_password');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function togglePasswordFields() {
        if (currentPassword.value.trim() !== '') {
            newPassword.required = true;
            confirmPassword.required = true;
        } else {
            newPassword.required = false;
            confirmPassword.required = false;
            newPassword.value = '';
            confirmPassword.value = '';
        }
    }
    
    currentPassword.addEventListener('input', togglePasswordFields);
    newPassword.addEventListener('input', togglePasswordFields);
});
</script>

<?php require_once '../includes/footer.php'; ?>
