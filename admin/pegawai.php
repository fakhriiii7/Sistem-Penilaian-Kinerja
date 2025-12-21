<?php
require_once '../config/database.php';

// Check jika user adalah admin
checkRole(['admin']);

$db = new Database();
$conn = $db->getConnection();

$action = isset($_GET['action']) ? $db->escapeString($_GET['action']) : '';
$id = isset($_GET['id']) ? $db->escapeString($_GET['id']) : '';

// Fungsi helper untuk generate username dari nama depan
function generateUsername($nama_depan)
{
    // Convert ke lowercase
    $username = strtolower(trim($nama_depan));

    // Handle karakter khusus Indonesia (transliterasi)
    $username = str_replace(
        ['à', 'á', 'â', 'ã', 'ä', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'ñ'],
        ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'n'],
        $username
    );

    // Hapus spasi dan karakter khusus, hanya huruf dan angka
    $username = preg_replace('/[^a-z0-9]/', '', $username);

    // Pastikan tidak kosong
    if (empty($username)) {
        $username = 'user';
    }

    return $username;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah_pegawai'])) {
        $nip = $db->escapeString($_POST['nip']);
        $nama_depan = $db->escapeString($_POST['nama_depan']);
        $nama_belakang = $db->escapeString($_POST['nama_belakang']);
        $jabatan = $db->escapeString($_POST['jabatan']);
        $unit_kerja = $db->escapeString($_POST['unit_kerja']);
        $email = $db->escapeString($_POST['email']);
        $no_telp = $db->escapeString($_POST['no_telp']);

        // Generate username dari nama depan
        $username_base = generateUsername($nama_depan);
        $username = $username_base;
        $counter = 1;

        // Cek NIP apakah sudah ada
        $cek_query = "SELECT id FROM pegawai WHERE nip = '$nip'";
        $cek_result = $conn->query($cek_query);

        if ($cek_result->num_rows > 0) {
            flashMessage('error', 'NIP sudah terdaftar!');
        } else {
            // Cek username, jika sudah ada tambahkan angka
            $cek_username = "SELECT id FROM users WHERE username = '$username'";
            $cek_username_result = $conn->query($cek_username);
            while ($cek_username_result->num_rows > 0) {
                $username = $username_base . $counter;
                $cek_username = "SELECT id FROM users WHERE username = '$username'";
                $cek_username_result = $conn->query($cek_username);
                $counter++;
            }

            // Generate password: username + "123"
            $password = $username . '123';
            $nama_lengkap = trim($nama_depan . ' ' . $nama_belakang);

            // Mulai transaction
            $conn->begin_transaction();

            try {
                // Insert ke tabel users dulu
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query_user = "INSERT INTO users (username, password, role, nama_lengkap) 
                              VALUES ('$username', '$hashed_password', 'pegawai', '$nama_lengkap')";
                $conn->query($query_user);
                $user_id = $conn->insert_id;

                // Insert ke tabel pegawai dengan user_id
                $query_pegawai = "INSERT INTO pegawai (user_id, nip, nama_depan, nama_belakang, nama_lengkap, jabatan, unit_kerja, email, no_telp) 
                                 VALUES ('$user_id', '$nip', '$nama_depan', '$nama_belakang', '$nama_lengkap', '$jabatan', '$unit_kerja', '$email', '$no_telp')";
                $conn->query($query_pegawai);
                $pegawai_id = $conn->insert_id;

                $conn->commit();
                flashMessage('success', 'Data pegawai berhasil ditambahkan! Username: <strong>' . $username . '</strong>, Password: <strong>' . $password . '</strong>');
                redirect('pegawai.php');
            } catch (Exception $e) {
                $conn->rollback();
                flashMessage('error', 'Gagal menambahkan data pegawai: ' . $e->getMessage());
            }
        }
    }

    if (isset($_POST['edit_pegawai'])) {
        $id = $db->escapeString($_POST['id']);
        $nip = $db->escapeString($_POST['nip']);
        $nama_depan = $db->escapeString($_POST['nama_depan']);
        $nama_belakang = $db->escapeString($_POST['nama_belakang']);
        $jabatan = $db->escapeString($_POST['jabatan']);
        $unit_kerja = $db->escapeString($_POST['unit_kerja']);
        $email = $db->escapeString($_POST['email']);
        $no_telp = $db->escapeString($_POST['no_telp']);

        $nama_lengkap = trim($nama_depan . ' ' . $nama_belakang);

        // Cek NIP apakah sudah ada (kecuali untuk data ini)
        $cek_query = "SELECT id FROM pegawai WHERE nip = '$nip' AND id != '$id'";
        $cek_result = $conn->query($cek_query);

        if ($cek_result->num_rows > 0) {
            flashMessage('error', 'NIP sudah terdaftar untuk pegawai lain!');
        } else {
            $conn->begin_transaction();
            try {
                // Update pegawai
                $query = "UPDATE pegawai SET 
                         nip = '$nip',
                         nama_depan = '$nama_depan',
                         nama_belakang = '$nama_belakang',
                         nama_lengkap = '$nama_lengkap',
                         jabatan = '$jabatan',
                         unit_kerja = '$unit_kerja',
                         email = '$email',
                         no_telp = '$no_telp'
                         WHERE id = '$id'";
                $conn->query($query);

                // Update nama_lengkap di users juga jika ada user_id
                $pegawai_data = $conn->query("SELECT user_id FROM pegawai WHERE id = '$id'")->fetch_assoc();
                if ($pegawai_data['user_id']) {
                    $query_user = "UPDATE users SET nama_lengkap = '$nama_lengkap' WHERE id = '{$pegawai_data['user_id']}'";
                    $conn->query($query_user);
                }

                $conn->commit();
                flashMessage('success', 'Data pegawai berhasil diperbarui!');
                redirect('pegawai.php');
            } catch (Exception $e) {
                $conn->rollback();
                flashMessage('error', 'Gagal memperbarui data pegawai: ' . $e->getMessage());
            }
        }
    }
}

// Handle delete
if ($action == 'delete' && $id) {
    // Cek apakah pegawai sudah memiliki penilaian
    $cek_query = "SELECT id FROM penilaian WHERE pegawai_id = '$id'";
    $cek_result = $conn->query($cek_query);

    if ($cek_result->num_rows > 0) {
        flashMessage('error', 'Tidak dapat menghapus pegawai yang sudah memiliki data penilaian!');
    } else {
        $query = "DELETE FROM pegawai WHERE id = '$id'";
        if ($conn->query($query)) {
            flashMessage('success', 'Data pegawai berhasil dihapus!');
        } else {
            flashMessage('error', 'Gagal menghapus data pegawai: ' . $conn->error);
        }
    }
    redirect('pegawai.php');
}

// Get data for edit
$pegawai_data = null;
if ($action == 'edit' && $id) {
    $query = "SELECT * FROM pegawai WHERE id = '$id'";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        $pegawai_data = $result->fetch_assoc();

        // Handle data lama yang belum punya nama_depan dan nama_belakang
        if (empty($pegawai_data['nama_depan']) && !empty($pegawai_data['nama_lengkap'])) {
            $nama_parts = explode(' ', $pegawai_data['nama_lengkap'], 2);
            $pegawai_data['nama_depan'] = $nama_parts[0];
            $pegawai_data['nama_belakang'] = isset($nama_parts[1]) ? $nama_parts[1] : '';
        }
    }
}

// Handle create account for existing pegawai (untuk data lama yang belum punya akun)
if ($action == 'create_account' && $id) {
    // Cek apakah pegawai sudah punya akun
    $cek_pegawai = "SELECT user_id, nama_depan, nama_lengkap FROM pegawai WHERE id = '$id'";
    $cek_pegawai_result = $conn->query($cek_pegawai);
    $pegawai_data_check = $cek_pegawai_result->fetch_assoc();

    if ($pegawai_data_check['user_id']) {
        flashMessage('error', 'Pegawai ini sudah memiliki akun!');
        redirect('pegawai.php');
        exit();
    }

    // Generate username dari nama_depan atau nama_lengkap (untuk data lama)
    $nama_depan = $pegawai_data_check['nama_depan'] ?? '';
    if (empty($nama_depan)) {
        // Jika belum ada nama_depan, split dari nama_lengkap
        $nama_lengkap = $pegawai_data_check['nama_lengkap'] ?? '';
        $nama_parts = explode(' ', $nama_lengkap, 2);
        $nama_depan = $nama_parts[0];
    }

    $username_base = generateUsername($nama_depan);

    // Ambil semua username yang mirip (budi, budi1, budi2, dst)
    $query = "
    SELECT username 
    FROM users 
    WHERE username REGEXP '^{$username_base}[0-9]*$'
";
    $result = $conn->query($query);

    $existing_usernames = [];
    while ($row = $result->fetch_assoc()) {
        $existing_usernames[] = $row['username'];
    }

    // Tentukan username final
    if (!in_array($username_base, $existing_usernames)) {
        $username = $username_base;
    } else {
        $counter = 1;
        while (in_array($username_base . $counter, $existing_usernames)) {
            $counter++;
        }
        $username = $username_base . $counter;
    }

    // Generate password: username + "123"
    $password = $username . '123';
    $nama_lengkap = $pegawai_data_check['nama_lengkap'] ?? '';

    $conn->begin_transaction();
    try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query_user = "INSERT INTO users (username, password, role, nama_lengkap) 
                      VALUES ('$username', '$hashed_password', 'pegawai', '$nama_lengkap')";
        $conn->query($query_user);
        $user_id = $conn->insert_id;

        $query_update = "UPDATE pegawai SET user_id = '$user_id' WHERE id = '$id'";
        $conn->query($query_update);

        $conn->commit();
        flashMessage('success', 'Akun login berhasil dibuat! Username: <strong>' . $username . '</strong>, Password: <strong>' . $password . '</strong>');
        redirect('pegawai.php');
    } catch (Exception $e) {
        $conn->rollback();
        flashMessage('error', 'Gagal membuat akun: ' . $e->getMessage());
    }
}

// Get all data pegawai with user status
$search = isset($_GET['search']) ? $db->escapeString($_GET['search']) : '';
$query = "SELECT p.*, u.id as user_account_id 
          FROM pegawai p 
          LEFT JOIN users u ON p.user_id = u.id";
if ($search) {
    $query .= " WHERE (p.nama_depan LIKE '%$search%' OR p.nama_belakang LIKE '%$search%' OR p.nama_lengkap LIKE '%$search%' OR p.nip LIKE '%$search%' OR p.jabatan LIKE '%$search%')";
}
$query .= " ORDER BY p.nama_depan ASC, p.nama_belakang ASC";
$result = $conn->query($query);

$page_title = $action == 'tambah' ? 'Tambah Pegawai' : ($action == 'edit' ? 'Edit Pegawai' : 'Data Pegawai');
require_once '../includes/header.php';
?>

<?php if ($action == 'tambah' || $action == 'edit'): ?>
    <!-- Form Tambah/Edit Pegawai -->
    <div class="card">
        <div class="card-header">
            <h2><?php echo $action == 'tambah' ? 'Tambah Data Pegawai' : 'Edit Data Pegawai'; ?></h2>
            <a href="pegawai.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?php if ($action == 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $pegawai_data['id']; ?>">
                    <input type="hidden" name="edit_pegawai" value="1">
                <?php else: ?>
                    <input type="hidden" name="tambah_pegawai" value="1">
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                    <div class="form-group">
                        <label for="nip">NIP *</label>
                        <input type="text" id="nip" name="nip" class="form-control"
                            value="<?php echo $pegawai_data['nip'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="nama_depan">Nama Depan *</label>
                        <input type="text" id="nama_depan" name="nama_depan" class="form-control"
                            value="<?php echo $pegawai_data['nama_depan'] ?? ''; ?>" required
                            placeholder="Contoh: Budi">
                        <small style="color: #7f8c8d;">Akan digunakan sebagai username (lowercase)</small>
                    </div>

                    <div class="form-group">
                        <label for="nama_belakang">Nama Belakang *</label>
                        <input type="text" id="nama_belakang" name="nama_belakang" class="form-control"
                            value="<?php echo $pegawai_data['nama_belakang'] ?? ''; ?>" required
                            placeholder="Contoh: Santoso">
                    </div>

                    <div class="form-group">
                        <label for="jabatan">Jabatan *</label>
                        <input type="text" id="jabatan" name="jabatan" class="form-control"
                            value="<?php echo $pegawai_data['jabatan'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="unit_kerja">Unit Kerja</label>
                        <input type="text" id="unit_kerja" name="unit_kerja" class="form-control"
                            value="<?php echo $pegawai_data['unit_kerja'] ?? ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control"
                            value="<?php echo $pegawai_data['email'] ?? ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="no_telp">No. Telepon</label>
                        <input type="text" id="no_telp" name="no_telp" class="form-control"
                            value="<?php echo $pegawai_data['no_telp'] ?? ''; ?>">
                    </div>
                </div>

                <?php if ($action == 'tambah'): ?>
                    <div style="margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 8px; border-left: 4px solid #4caf50;">
                        <p style="margin: 0; color: #2e7d32;">
                            <i class="fas fa-info-circle"></i>
                            <strong>Info:</strong> Akun login akan dibuat otomatis dengan username dari nama depan (lowercase) dan password: <strong>username123</strong>
                        </p>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 30px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                    <a href="pegawai.php" class="btn btn-warning">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- List Data Pegawai -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Data Pegawai</h2>
            <div style="display: flex; gap: 10px;">
                <form method="GET" action="" style="display: flex; gap: 10px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari pegawai..."
                        value="<?php echo $search; ?>" style="width: 250px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Cari
                    </button>
                    <?php if ($search): ?>
                        <a href="pegawai.php" class="btn btn-warning">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    <?php endif; ?>
                </form>
                <a href="?action=tambah" class="btn btn-success">
                    <i class="fas fa-plus"></i> Tambah Pegawai
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
                                <th>NIP</th>
                                <th>Nama Depan</th>
                                <th>Nama Belakang</th>
                                <th>Jabatan</th>
                                <th>Unit Kerja</th>
                                <th>Email</th>
                                <th>Status Akun</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php while ($row = $result->fetch_assoc()):
                                // Handle data lama
                                $nama_depan = $row['nama_depan'] ?? '';
                                $nama_belakang = $row['nama_belakang'] ?? '';
                                if (empty($nama_depan) && !empty($row['nama_lengkap'])) {
                                    $nama_parts = explode(' ', $row['nama_lengkap'], 2);
                                    $nama_depan = $nama_parts[0];
                                    $nama_belakang = isset($nama_parts[1]) ? $nama_parts[1] : '';
                                }
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $row['nip']; ?></td>
                                    <td><?php echo $nama_depan; ?></td>
                                    <td><?php echo $nama_belakang; ?></td>
                                    <td><?php echo $row['jabatan']; ?></td>
                                    <td><?php echo $row['unit_kerja']; ?></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td>
                                        <?php if ($row['user_account_id']): ?>
                                            <span style="padding: 5px 10px; background: #d4edda; color: #155724; border-radius: 15px; font-size: 12px; font-weight: 500;">
                                                <i class="fas fa-check-circle"></i> Sudah Punya Akun
                                            </span>
                                        <?php else: ?>
                                            <span style="padding: 5px 10px; background: #fff3cd; color: #856404; border-radius: 15px; font-size: 12px; font-weight: 500;">
                                                <i class="fas fa-exclamation-circle"></i> Belum Punya Akun
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <a href="?action=edit&id=<?php echo $row['id']; ?>"
                                                class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if (!$row['user_account_id']): ?>
                                                <a href="#" class="btn btn-sm btn-success" title="Buat Akun"
                                                    onclick="showCreateAccountModal(<?php echo htmlspecialchars(json_encode($row)); ?>); return false;">
                                                    <i class="fas fa-user-plus"></i> Buat Akun
                                                </a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?php echo $row['id']; ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus pegawai <?php echo $row['nama_lengkap']; ?>?')"
                                                title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-info" title="Detail"
                                                onclick="showDetail(<?php echo htmlspecialchars(json_encode($row)); ?>); return false;">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 20px; color: #7f8c8d; font-size: 14px;">
                    Total: <?php echo $result->num_rows; ?> pegawai
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-users" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                    <p>Belum ada data pegawai</p>
                    <a href="?action=tambah" class="btn btn-success">
                        <i class="fas fa-plus"></i> Tambah Pegawai Pertama
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Detail -->
<div id="detailModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; width: 90%; max-width: 500px; border-radius: 10px; padding: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Detail Pegawai</h3>
            <button onclick="closeDetail()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #7f8c8d;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="detailContent"></div>

        <div style="margin-top: 30px; text-align: right;">
            <button onclick="closeDetail()" class="btn btn-primary">Tutup</button>
        </div>
    </div>
</div>


<script>
    function showDetail(data) {
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('detailContent');

        // Handle data lama
        let nama_depan = data.nama_depan || '';
        let nama_belakang = data.nama_belakang || '';
        if (!nama_depan && data.nama_lengkap) {
            const nama_parts = data.nama_lengkap.split(' ', 2);
            nama_depan = nama_parts[0];
            nama_belakang = nama_parts[1] || '';
        }

        content.innerHTML = `
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px;">
            <div style="font-weight: 500; color: #2c3e50;">NIP:</div>
            <div>${data.nip}</div>
            
            <div style="font-weight: 500; color: #2c3e50;">Nama Depan:</div>
            <div>${nama_depan}</div>
            
            <div style="font-weight: 500; color: #2c3e50;">Nama Belakang:</div>
            <div>${nama_belakang}</div>
            
            <div style="font-weight: 500; color: #2c3e50;">Nama Lengkap:</div>
            <div>${data.nama_lengkap || (nama_depan + ' ' + nama_belakang)}</div>
            
            <div style="font-weight: 500; color: #2c3e50;">Jabatan:</div>
            <div>${data.jabatan}</div>
            
            <div style="font-weight: 500; color: #2c3e50;">Unit Kerja:</div>
            <div>${data.unit_kerja || '-'}</div>
            
            <div style="font-weight: 500; color: #2c3e50;">Email:</div>
            <div>${data.email || '-'}</div>
            
            <div style="font-weight: 500; color: #2c3e50;">No. Telepon:</div>
            <div>${data.no_telp || '-'}</div>
            
            <div style="font-weight: 500; color: #2c3e50;">Status Akun:</div>
            <div>${data.user_account_id ? '<span style="padding: 5px 10px; background: #d4edda; color: #155724; border-radius: 15px; font-size: 12px;"><i class="fas fa-check-circle"></i> Sudah Punya Akun</span>' : '<span style="padding: 5px 10px; background: #fff3cd; color: #856404; border-radius: 15px; font-size: 12px;"><i class="fas fa-exclamation-circle"></i> Belum Punya Akun</span>'}</div>
            
            ${data.created_at ? `<div style="font-weight: 500; color: #2c3e50;">Tanggal Dibuat:</div>
            <div>${new Date(data.created_at).toLocaleDateString('id-ID', { 
                day: '2-digit', 
                month: 'long', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })}</div>` : ''}
        </div>
    `;

        modal.style.display = 'flex';
    }

    function closeDetail() {
        document.getElementById('detailModal').style.display = 'none';
    }

    function showCreateAccountModal(data) {
        // Langsung submit tanpa modal (untuk data lama yang belum punya akun)
        if (confirm('Apakah Anda yakin ingin membuat akun untuk pegawai ini? Username akan dibuat dari nama depan dan password: username123')) {
            window.location.href = '?action=create_account&id=' + data.id;
        }
    }

    // Close modal when clicking outside
    document.getElementById('detailModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDetail();
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>