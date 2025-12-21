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
    if (isset($_POST['tambah_periode'])) {
        $nama_periode = $db->escapeString($_POST['nama_periode']);
        $tanggal_mulai = $db->escapeString($_POST['tanggal_mulai']);
        $tanggal_selesai = $db->escapeString($_POST['tanggal_selesai']);
        
        // Validasi tanggal
        if (strtotime($tanggal_selesai) <= strtotime($tanggal_mulai)) {
            flashMessage('error', 'Tanggal selesai harus setelah tanggal mulai!');
        } else {
            // Jika mengaktifkan periode baru, non-aktifkan periode lain
            if (isset($_POST['status']) && $_POST['status'] == 'aktif') {
                $conn->query("UPDATE periode_penilaian SET status = 'non-aktif'");
            }
            
            $status = isset($_POST['status']) ? $db->escapeString($_POST['status']) : 'non-aktif';
            
            $query = "INSERT INTO periode_penilaian (nama_periode, tanggal_mulai, tanggal_selesai, status) 
                     VALUES ('$nama_periode', '$tanggal_mulai', '$tanggal_selesai', '$status')";
            
            if ($conn->query($query)) {
                flashMessage('success', 'Periode penilaian berhasil ditambahkan!');
                redirect('periode.php');
            } else {
                flashMessage('error', 'Gagal menambahkan periode penilaian: ' . $conn->error);
            }
        }
    }
    
    if (isset($_POST['edit_periode'])) {
        $id = $db->escapeString($_POST['id']);
        $nama_periode = $db->escapeString($_POST['nama_periode']);
        $tanggal_mulai = $db->escapeString($_POST['tanggal_mulai']);
        $tanggal_selesai = $db->escapeString($_POST['tanggal_selesai']);
        $status = $db->escapeString($_POST['status']);
        
        // Validasi tanggal
        if (strtotime($tanggal_selesai) <= strtotime($tanggal_mulai)) {
            flashMessage('error', 'Tanggal selesai harus setelah tanggal mulai!');
        } else {
            // Jika mengaktifkan periode ini, non-aktifkan periode lain
            if ($status == 'aktif') {
                $conn->query("UPDATE periode_penilaian SET status = 'non-aktif' WHERE id != '$id'");
            }
            
            $query = "UPDATE periode_penilaian SET 
                     nama_periode = '$nama_periode',
                     tanggal_mulai = '$tanggal_mulai',
                     tanggal_selesai = '$tanggal_selesai',
                     status = '$status'
                     WHERE id = '$id'";
            
            if ($conn->query($query)) {
                flashMessage('success', 'Periode penilaian berhasil diperbarui!');
                redirect('periode.php');
            } else {
                flashMessage('error', 'Gagal memperbarui periode penilaian: ' . $conn->error);
            }
        }
    }
}

// Handle actions
if ($action == 'aktifkan' && $id) {
    // Non-aktifkan semua periode dulu
    $conn->query("UPDATE periode_penilaian SET status = 'non-aktif'");
    
    // Aktifkan periode yang dipilih
    $query = "UPDATE periode_penilaian SET status = 'aktif' WHERE id = '$id'";
    if ($conn->query($query)) {
        flashMessage('success', 'Periode berhasil diaktifkan!');
    } else {
        flashMessage('error', 'Gagal mengaktifkan periode: ' . $conn->error);
    }
    redirect('periode.php');
}

if ($action == 'delete' && $id) {
    // Cek apakah periode sudah digunakan dalam penilaian
    $cek_query = "SELECT id FROM penilaian WHERE periode_id = '$id'";
    $cek_result = $conn->query($cek_query);
    
    if ($cek_result->num_rows > 0) {
        flashMessage('error', 'Tidak dapat menghapus periode yang sudah digunakan dalam penilaian!');
    } else {
        $query = "DELETE FROM periode_penilaian WHERE id = '$id'";
        if ($conn->query($query)) {
            flashMessage('success', 'Periode berhasil dihapus!');
        } else {
            flashMessage('error', 'Gagal menghapus periode: ' . $conn->error);
        }
    }
    redirect('periode.php');
}

// Get data for edit
$periode_data = null;
if ($action == 'edit' && $id) {
    $query = "SELECT * FROM periode_penilaian WHERE id = '$id'";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        $periode_data = $result->fetch_assoc();
    }
}

// Get all data periode
$search = isset($_GET['search']) ? $db->escapeString($_GET['search']) : '';
$query = "SELECT *, 
          DATEDIFF(tanggal_selesai, CURDATE()) as sisa_hari,
          CASE 
            WHEN status = 'aktif' THEN 'Aktif'
            WHEN tanggal_selesai < CURDATE() THEN 'Berakhir'
            ELSE 'Akan Datang'
          END as status_display
          FROM periode_penilaian";
if ($search) {
    $query .= " WHERE nama_periode LIKE '%$search%'";
}
$query .= " ORDER BY tanggal_mulai DESC";
$result = $conn->query($query);

$page_title = $action == 'tambah' ? 'Tambah Periode' : ($action == 'edit' ? 'Edit Periode' : 'Periode Penilaian');
require_once '../includes/header.php';
?>

<?php if ($action == 'tambah' || $action == 'edit'): ?>
<!-- Form Tambah/Edit Periode -->
<div class="card">
    <div class="card-header">
        <h2><?php echo $action == 'tambah' ? 'Tambah Periode Penilaian' : 'Edit Periode Penilaian'; ?></h2>
        <a href="periode.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $periode_data['id']; ?>">
                <input type="hidden" name="edit_periode" value="1">
            <?php else: ?>
                <input type="hidden" name="tambah_periode" value="1">
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="nama_periode">Nama Periode *</label>
                    <input type="text" id="nama_periode" name="nama_periode" class="form-control" 
                           value="<?php echo $periode_data['nama_periode'] ?? ''; ?>" required
                           placeholder="Contoh: Penilaian Semester 1 2025">
                    <small style="color: #7f8c8d;">Gunakan format yang jelas dan konsisten</small>
                </div>
                
                <div class="form-group">
                    <label for="tanggal_mulai">Tanggal Mulai *</label>
                    <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control"
                           value="<?php echo $periode_data['tanggal_mulai'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="tanggal_selesai">Tanggal Selesai *</label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="form-control"
                           value="<?php echo $periode_data['tanggal_selesai'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="non-aktif" <?php echo ($periode_data['status'] ?? 'non-aktif') == 'non-aktif' ? 'selected' : ''; ?>>
                            Non-Aktif
                        </option>
                        <option value="aktif" <?php echo ($periode_data['status'] ?? '') == 'aktif' ? 'selected' : ''; ?>>
                            Aktif
                        </option>
                    </select>
                    <small style="color: #7f8c8d;">Hanya satu periode yang bisa aktif</small>
                </div>
            </div>
            
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h4 style="margin-bottom: 10px; color: #2c3e50;">Informasi Periode</h4>
                <div id="periodeInfo">
                    <p>Masukkan tanggal untuk melihat detail periode</p>
                </div>
            </div>
            
            <div style="margin-top: 30px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="periode.php" class="btn btn-warning">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tanggalMulai = document.getElementById('tanggal_mulai');
    const tanggalSelesai = document.getElementById('tanggal_selesai');
    const periodeInfo = document.getElementById('periodeInfo');
    
    function updatePeriodeInfo() {
        if (tanggalMulai.value && tanggalSelesai.value) {
            const mulai = new Date(tanggalMulai.value);
            const selesai = new Date(tanggalSelesai.value);
            const hari = Math.floor((selesai - mulai) / (1000 * 60 * 60 * 24)) + 1;
            
            const mulaiFormatted = mulai.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
            
            const selesaiFormatted = selesai.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
            
            let statusClass = 'status-active';
            let statusText = 'Akan Berlangsung';
            
            const sekarang = new Date();
            if (selesai < sekarang) {
                statusClass = 'status-inactive';
                statusText = 'Telah Berakhir';
            } else if (mulai <= sekarang && selesai >= sekarang) {
                statusClass = 'status-active';
                statusText = 'Sedang Berlangsung';
            }
            
            periodeInfo.innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                    <div>
                        <strong>Tanggal Mulai:</strong><br>
                        ${mulaiFormatted}
                    </div>
                    <div>
                        <strong>Tanggal Selesai:</strong><br>
                        ${selesaiFormatted}
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div>
                        <strong>Durasi:</strong><br>
                        ${hari} hari
                    </div>
                    <div>
                        <strong>Status:</strong><br>
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </div>
                </div>
            `;
        }
    }
    
    tanggalMulai.addEventListener('change', updatePeriodeInfo);
    tanggalSelesai.addEventListener('change', updatePeriodeInfo);
    
    // Initial update if dates are pre-filled
    updatePeriodeInfo();
});
</script>

<?php else: ?>
<!-- List Data Periode -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Periode Penilaian</h2>
        <div style="display: flex; gap: 10px;">
            <form method="GET" action="" style="display: flex; gap: 10px;">
                <input type="text" name="search" class="form-control" placeholder="Cari periode..." 
                       value="<?php echo $search; ?>" style="width: 250px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Cari
                </button>
                <?php if ($search): ?>
                    <a href="periode.php" class="btn btn-warning">
                        <i class="fas fa-times"></i> Reset
                    </a>
                <?php endif; ?>
            </form>
            <a href="?action=tambah" class="btn btn-success">
                <i class="fas fa-plus"></i> Tambah Periode
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
                            <th>Nama Periode</th>
                            <th>Tanggal Mulai</th>
                            <th>Tanggal Selesai</th>
                            <th>Durasi</th>
                            <th>Status</th>
                            <th>Sisa Waktu</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $mulai = new DateTime($row['tanggal_mulai']);
                            $selesai = new DateTime($row['tanggal_selesai']);
                            $sekarang = new DateTime();
                            $durasi = $mulai->diff($selesai)->days + 1;
                            
                            $status_class = '';
                            $status_text = '';
                            
                            if ($row['status'] == 'aktif') {
                                $status_class = 'status-active';
                                $status_text = 'Aktif';
                            } elseif ($selesai < $sekarang) {
                                $status_class = 'status-inactive';
                                $status_text = 'Berakhir';
                            } else {
                                $status_class = 'status-pending';
                                $status_text = 'Akan Datang';
                            }
                            
                            $sisa_hari = $row['sisa_hari'];
                            $sisa_text = '';
                            $sisa_class = '';
                            
                            if ($sisa_hari > 30) {
                                $sisa_text = floor($sisa_hari / 30) . ' bulan';
                                $sisa_class = 'status-active';
                            } elseif ($sisa_hari > 0) {
                                $sisa_text = $sisa_hari . ' hari';
                                $sisa_class = $sisa_hari > 7 ? 'status-active' : 'status-pending';
                            } elseif ($sisa_hari == 0) {
                                $sisa_text = 'Hari terakhir';
                                $sisa_class = 'status-pending';
                            } else {
                                $sisa_text = abs($sisa_hari) . ' hari lalu';
                                $sisa_class = 'status-inactive';
                            }
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo $row['nama_periode']; ?></strong>
                                    <?php if ($row['status'] == 'aktif'): ?>
                                        <span style="color: #27ae60; font-size: 12px; margin-left: 5px;">
                                            <i class="fas fa-star"></i> Aktif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal_mulai'])); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal_selesai'])); ?></td>
                                <td><?php echo $durasi; ?> hari</td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $sisa_class; ?>">
                                        <?php echo $sisa_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <?php if ($row['status'] != 'aktif'): ?>
                                            <a href="?action=aktifkan&id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-success" 
                                               title="Aktifkan"
                                               onclick="return confirm('Aktifkan periode <?php echo $row['nama_periode']; ?>?')">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=edit&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus periode <?php echo $row['nama_periode']; ?>?')"
                                           title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="penilaian.php?periode_id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Lihat Penilaian">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Periode Aktif Info -->
            <?php 
            $periode_aktif = $conn->query("
                SELECT *, DATEDIFF(tanggal_selesai, CURDATE()) as sisa_hari 
                FROM periode_penilaian 
                WHERE status = 'aktif'
            ")->fetch_assoc();
            
            if ($periode_aktif): 
            ?>
                <div style="margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                     color: white; border-radius: 10px;">
                    <h3 style="margin-bottom: 15px;">Periode Aktif Saat Ini</h3>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                        <div>
                            <strong>Periode</strong><br>
                            <?php echo $periode_aktif['nama_periode']; ?>
                        </div>
                        <div>
                            <strong>Tanggal Mulai</strong><br>
                            <?php echo date('d M Y', strtotime($periode_aktif['tanggal_mulai'])); ?>
                        </div>
                        <div>
                            <strong>Tanggal Selesai</strong><br>
                            <?php echo date('d M Y', strtotime($periode_aktif['tanggal_selesai'])); ?>
                        </div>
                        <div>
                            <strong>Sisa Waktu</strong><br>
                            <span style="font-size: 24px; font-weight: bold;">
                                <?php echo $periode_aktif['sisa_hari'] > 0 ? $periode_aktif['sisa_hari'] : 0; ?> hari
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-calendar-alt" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                <p>Belum ada data periode penilaian</p>
                <a href="?action=tambah" class="btn btn-success">
                    <i class="fas fa-plus"></i> Tambah Periode Pertama
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>