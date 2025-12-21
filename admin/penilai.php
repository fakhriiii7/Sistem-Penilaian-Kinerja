<?php
require_once '../config/database.php';

// Check jika user adalah admin
checkRole(['admin']);

$db = new Database();
$conn = $db->getConnection();

$action = isset($_GET['action']) ? $db->escapeString($_GET['action']) : '';
$id = isset($_GET['id']) ? $db->escapeString($_GET['id']) : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah_penilai'])) {
        $nama_penilai = $db->escapeString($_POST['nama_penilai']);
        $jabatan = $db->escapeString($_POST['jabatan']);
        $username = $db->escapeString($_POST['username']);
        $password = $db->escapeString($_POST['password']);
        
        // Cek username apakah sudah ada
        $cek_query = "SELECT id FROM users WHERE username = '$username'";
        $cek_result = $conn->query($cek_query);
        
        if ($cek_result->num_rows > 0) {
            flashMessage('error', 'Username sudah terdaftar!');
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Mulai transaction
            $conn->begin_transaction();
            
            try {
                // Insert ke tabel users
                $query_user = "INSERT INTO users (username, password, role, nama_lengkap) 
                              VALUES ('$username', '$hashed_password', 'pimpinan', '$nama_penilai')";
                $conn->query($query_user);
                $user_id = $conn->insert_id;
                
                // Insert ke tabel penilai
                $query_penilai = "INSERT INTO penilai (user_id, nama_penilai, jabatan) 
                                 VALUES ('$user_id', '$nama_penilai', '$jabatan')";
                $conn->query($query_penilai);
                
                $conn->commit();
                flashMessage('success', 'Data penilai berhasil ditambahkan!');
                redirect('penilai.php');
                
            } catch (Exception $e) {
                $conn->rollback();
                flashMessage('error', 'Gagal menambahkan data penilai: ' . $e->getMessage());
            }
        }
    }
    
    if (isset($_POST['edit_penilai'])) {
        $id = $db->escapeString($_POST['id']);
        $nama_penilai = $db->escapeString($_POST['nama_penilai']);
        $jabatan = $db->escapeString($_POST['jabatan']);
        $username = $db->escapeString($_POST['username']);
        $password = $db->escapeString($_POST['password']);
        
        // Get user_id
        $query = "SELECT user_id FROM penilai WHERE id = '$id'";
        $result = $conn->query($query);
        $penilai = $result->fetch_assoc();
        $user_id = $penilai['user_id'];
        
        // Cek username apakah sudah ada (kecuali untuk user ini)
        $cek_query = "SELECT id FROM users WHERE username = '$username' AND id != '$user_id'";
        $cek_result = $conn->query($cek_query);
        
        if ($cek_result->num_rows > 0) {
            flashMessage('error', 'Username sudah terdaftar untuk penilai lain!');
        } else {
            // Update data penilai
            $query_penilai = "UPDATE penilai SET 
                             nama_penilai = '$nama_penilai',
                             jabatan = '$jabatan'
                             WHERE id = '$id'";
            
            // Update data user
            $query_user = "UPDATE users SET 
                          username = '$username',
                          nama_lengkap = '$nama_penilai'
                          WHERE id = '$user_id'";
            
            // Jika password diisi, update password juga
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query_user = "UPDATE users SET 
                              username = '$username',
                              nama_lengkap = '$nama_penilai',
                              password = '$hashed_password'
                              WHERE id = '$user_id'";
            }
            
            if ($conn->query($query_penilai) && $conn->query($query_user)) {
                flashMessage('success', 'Data penilai berhasil diperbarui!');
                redirect('penilai.php');
            } else {
                flashMessage('error', 'Gagal memperbarui data penilai: ' . $conn->error);
            }
        }
    }
}

// Handle delete
if ($action == 'delete' && $id) {
    // Get user_id
    $query = "SELECT user_id FROM penilai WHERE id = '$id'";
    $result = $conn->query($query);
    $penilai = $result->fetch_assoc();
    $user_id = $penilai['user_id'];
    
    // Cek apakah penilai sudah melakukan penilaian
    $cek_query = "SELECT id FROM penilaian WHERE penilai_id = '$id'";
    $cek_result = $conn->query($cek_query);
    
    if ($cek_result->num_rows > 0) {
        flashMessage('error', 'Tidak dapat menghapus penilai yang sudah melakukan penilaian!');
    } else {
        // Mulai transaction
        $conn->begin_transaction();
        
        try {
            // Hapus dari tabel penilai
            $conn->query("DELETE FROM penilai WHERE id = '$id'");
            
            // Hapus dari tabel users
            $conn->query("DELETE FROM users WHERE id = '$user_id'");
            
            $conn->commit();
            flashMessage('success', 'Data penilai berhasil dihapus!');
            
        } catch (Exception $e) {
            $conn->rollback();
            flashMessage('error', 'Gagal menghapus data penilai: ' . $e->getMessage());
        }
    }
    redirect('penilai.php');
}

// Get data for edit
$penilai_data = null;
if ($action == 'edit' && $id) {
    $query = "SELECT p.*, u.username FROM penilai p 
              JOIN users u ON p.user_id = u.id 
              WHERE p.id = '$id'";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        $penilai_data = $result->fetch_assoc();
    }
}

// Get all data penilai
$search = isset($_GET['search']) ? $db->escapeString($_GET['search']) : '';
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM penilaian WHERE penilai_id = p.id) as total_penilaian
          FROM penilai p";
if ($search) {
    $query .= " WHERE p.nama_penilai LIKE '%$search%' OR p.jabatan LIKE '%$search%'";
}
$query .= " ORDER BY p.nama_penilai ASC";
$result = $conn->query($query);

$page_title = $action == 'tambah' ? 'Tambah Penilai' : ($action == 'edit' ? 'Edit Penilai' : 'Data Penilai');
require_once '../includes/header.php';
?>

<?php if ($action == 'tambah' || $action == 'edit'): ?>
<!-- Form Tambah/Edit Penilai -->
<div class="card">
    <div class="card-header">
        <h2><?php echo $action == 'tambah' ? 'Tambah Data Penilai' : 'Edit Data Penilai'; ?></h2>
        <a href="penilai.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $penilai_data['id']; ?>">
                <input type="hidden" name="edit_penilai" value="1">
            <?php else: ?>
                <input type="hidden" name="tambah_penilai" value="1">
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="form-group">
                    <label for="nama_penilai">Nama Lengkap *</label>
                    <input type="text" id="nama_penilai" name="nama_penilai" class="form-control" 
                           value="<?php echo $penilai_data['nama_penilai'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="jabatan">Jabatan *</label>
                    <input type="text" id="jabatan" name="jabatan" class="form-control"
                           value="<?php echo $penilai_data['jabatan'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" class="form-control"
                           value="<?php echo $penilai_data['username'] ?? ''; ?>" required>
                    <small style="color: #7f8c8d;">Digunakan untuk login</small>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <?php echo $action == 'tambah' ? 'Password *' : 'Password (kosongkan jika tidak diubah)'; ?>
                    </label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control"
                               <?php echo $action == 'tambah' ? 'required' : ''; ?>>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" 
                                    onclick="togglePasswordVisibility('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <?php if ($action == 'tambah'): ?>
                        <small style="color: #7f8c8d;">Minimal 6 karakter</small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h4 style="margin-bottom: 10px; color: #2c3e50;">Informasi Akun</h4>
                <p style="margin: 0;">
                    <strong>Role:</strong> Pimpinan<br>
                    <strong>Hak Akses:</strong> Dapat melakukan penilaian terhadap pegawai yang ditugaskan
                </p>
            </div>
            
            <div style="margin-top: 30px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="penilai.php" class="btn btn-warning">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling.querySelector('button');
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php else: ?>
<!-- List Data Penilai -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Data Penilai (Pimpinan)</h2>
        <div style="display: flex; gap: 10px;">
            <form method="GET" action="" style="display: flex; gap: 10px;">
                <input type="text" name="search" class="form-control" placeholder="Cari penilai..." 
                       value="<?php echo $search; ?>" style="width: 250px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Cari
                </button>
                <?php if ($search): ?>
                    <a href="penilai.php" class="btn btn-warning">
                        <i class="fas fa-times"></i> Reset
                    </a>
                <?php endif; ?>
            </form>
            <a href="?action=tambah" class="btn btn-success">
                <i class="fas fa-plus"></i> Tambah Penilai
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Penilai</th>
                            <th>Jabatan</th>
                            <th>Total Penilaian</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo $row['nama_penilai']; ?></strong>
                                    <br>
                                    <small style="color: #7f8c8d;">
                                        <i class="fas fa-user-tie"></i> Pimpinan
                                    </small>
                                </td>
                                <td><?php echo $row['jabatan']; ?></td>
                                <td>
                                    <div style="text-align: center;">
                                        <span style="display: inline-block; width: 30px; height: 30px; 
                                              background: <?php echo $row['total_penilaian'] > 0 ? '#2ecc71' : '#f39c12'; ?>;
                                              color: white; border-radius: 50%; line-height: 30px; font-weight: bold;">
                                            <?php echo $row['total_penilaian']; ?>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="?action=edit&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus penilai <?php echo $row['nama_penilai']; ?>?')"
                                           title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="penilaian.php?penilai_id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Lihat Penilaian">
                                            <i class="fas fa-clipboard-list"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-warning" title="Reset Password"
                                           onclick="resetPassword(<?php echo $row['id']; ?>, '<?php echo $row['nama_penilai']; ?>')">
                                            <i class="fas fa-key"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 30px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <?php
                // Hitung statistik
                $stats_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN p2.total_penilaian > 0 THEN 1 ELSE 0 END) as aktif,
                    SUM(CASE WHEN p2.total_penilaian = 0 THEN 1 ELSE 0 END) as belum_aktif,
                    MAX(p2.total_penilaian) as max_penilaian
                    FROM (
                        SELECT p.id, 
                        (SELECT COUNT(*) FROM penilaian WHERE penilai_id = p.id) as total_penilaian
                        FROM penilai p
                    ) p2";
                $stats_result = $conn->query($stats_query);
                $stats = $stats_result->fetch_assoc();
                ?>
                
                <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                    <div style="font-size: 36px; color: #3498db; font-weight: bold;">
                        <?php echo $stats['total']; ?>
                    </div>
                    <div style="color: #7f8c8d;">Total Penilai</div>
                </div>
                
                <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                    <div style="font-size: 36px; color: #2ecc71; font-weight: bold;">
                        <?php echo $stats['aktif']; ?>
                    </div>
                    <div style="color: #7f8c8d;">Sudah Menilai</div>
                </div>
                
                <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                    <div style="font-size: 36px; color: #f39c12; font-weight: bold;">
                        <?php echo $stats['belum_aktif']; ?>
                    </div>
                    <div style="color: #7f8c8d;">Belum Menilai</div>
                </div>
                
                <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                    <div style="font-size: 36px; color: #9b59b6; font-weight: bold;">
                        <?php echo $stats['max_penilaian']; ?>
                    </div>
                    <div style="color: #7f8c8d;">Penilaian Terbanyak</div>
                </div>
            </div>
            
        <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-user-tie" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                <p>Belum ada data penilai</p>
                <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 20px;">
                    Penilai (Pimpinan) adalah orang yang bertugas melakukan penilaian terhadap pegawai
                </p>
                <a href="?action=tambah" class="btn btn-success">
                    <i class="fas fa-plus"></i> Tambah Penilai Pertama
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Reset Password -->
<div id="resetPasswordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; width: 90%; max-width: 400px; border-radius: 10px; padding: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Reset Password</h3>
            <button onclick="closeResetPasswordModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #7f8c8d;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="resetPasswordContent">
            <p>Memuat...</p>
        </div>
        
        <div style="margin-top: 20px; text-align: right;">
            <button onclick="closeResetPasswordModal()" class="btn btn-warning" style="margin-right: 10px;">Batal</button>
            <button onclick="submitResetPassword()" class="btn btn-success">Reset Password</button>
        </div>
    </div>
</div>

<script>
let currentPenilaiId = null;

function resetPassword(id, nama) {
    currentPenilaiId = id;
    
    const modal = document.getElementById('resetPasswordModal');
    const content = document.getElementById('resetPasswordContent');
    
    content.innerHTML = `
        <p>Anda akan mereset password untuk:</p>
        <div style="padding: 10px; background: #f8f9fa; border-radius: 5px; margin: 10px 0;">
            <strong>${nama}</strong>
        </div>
        <p>Password baru akan di-generate secara otomatis:</p>
        <div style="padding: 15px; background: #e8f4fc; border-radius: 5px; margin: 10px 0;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span id="generatedPassword" style="font-family: monospace; font-size: 18px; letter-spacing: 2px;"></span>
                <button type="button" class="btn btn-sm btn-primary" onclick="generatePassword()">
                    <i class="fas fa-redo"></i> Generate Ulang
                </button>
            </div>
        </div>
        <p style="font-size: 12px; color: #7f8c8d;">
            <i class="fas fa-info-circle"></i> Password akan diubah setelah Anda klik "Reset Password"
        </p>
    `;
    
    modal.style.display = 'flex';
    generatePassword();
}

function generatePassword() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
    let password = '';
    for (let i = 0; i < 10; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('generatedPassword').textContent = password;
}

function closeResetPasswordModal() {
    document.getElementById('resetPasswordModal').style.display = 'none';
    currentPenilaiId = null;
}

function submitResetPassword() {
    const password = document.getElementById('generatedPassword').textContent;
    
    if (!password) {
        alert('Silakan generate password terlebih dahulu!');
        return;
    }
    
    if (confirm('Reset password untuk penilai ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id';
        inputId.value = currentPenilaiId;
        
        const inputPassword = document.createElement('input');
        inputPassword.type = 'hidden';
        inputPassword.name = 'password';
        inputPassword.value = password;
        
        const inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'reset_password';
        inputAction.value = '1';
        
        form.appendChild(inputId);
        form.appendChild(inputPassword);
        form.appendChild(inputAction);
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.getElementById('resetPasswordModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeResetPasswordModal();
    }
});
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>