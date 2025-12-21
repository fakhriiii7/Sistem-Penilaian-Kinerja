<?php
require_once '../config/database.php';

// Check jika user adalah admin
checkRole(['admin']);

$db = new Database();
$conn = $db->getConnection();

$action = isset($_GET['action']) ? $db->escapeString($_GET['action']) : '';
$id = isset($_GET['id']) ? $db->escapeString($_GET['id']) : '';

// Handle toggle status
if ($action == 'toggle_status' && $id) {
    $new_status = isset($_GET['status']) ? $db->escapeString($_GET['status']) : '';
    if (in_array($new_status, ['Aktif', 'Tidak Aktif'])) {
        $query = "UPDATE kriteria SET status = '$new_status' WHERE id = '$id'";
        if ($conn->query($query)) {
            flashMessage('success', 'Status kriteria berhasil diubah!');
        } else {
            flashMessage('error', 'Gagal mengubah status kriteria: ' . $conn->error);
        }
    } else {
        flashMessage('error', 'Status tidak valid!');
    }
    redirect('kriteria.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah_kriteria'])) {
        $kode_kriteria = $db->escapeString($_POST['kode_kriteria']);
        $nama_kriteria = $db->escapeString($_POST['nama_kriteria']);
        $deskripsi = $db->escapeString($_POST['deskripsi']);
        $bobot = $db->escapeString($_POST['bobot']);
        
        // Cek kode kriteria apakah sudah ada
        $cek_query = "SELECT id FROM kriteria WHERE kode_kriteria = '$kode_kriteria'";
        $cek_result = $conn->query($cek_query);
        
        if ($cek_result->num_rows > 0) {
            flashMessage('error', 'Kode Kriteria sudah terdaftar!');
        } else {
            // Validasi total bobot tidak lebih dari 100 (hitung yang aktif saja)
            $total_bobot_query = "SELECT SUM(bobot) as total FROM kriteria WHERE status = 'Aktif' OR status IS NULL";
            $total_bobot_result = $conn->query($total_bobot_query);
            $total_bobot = $total_bobot_result->fetch_assoc()['total'] ?? 0;
            
            if (($total_bobot + $bobot) > 100) {
                flashMessage('error', 'Total bobot semua kriteria tidak boleh lebih dari 100%!');
            } else {
                $query = "INSERT INTO kriteria (kode_kriteria, nama_kriteria, deskripsi, bobot) 
                         VALUES ('$kode_kriteria', '$nama_kriteria', '$deskripsi', '$bobot')";
                
                if ($conn->query($query)) {
                    flashMessage('success', 'Kriteria berhasil ditambahkan!');
                    redirect('kriteria.php');
                } else {
                    flashMessage('error', 'Gagal menambahkan kriteria: ' . $conn->error);
                }
            }
        }
    }
    
    if (isset($_POST['edit_kriteria'])) {
        $id = $db->escapeString($_POST['id']);
        $kode_kriteria = $db->escapeString($_POST['kode_kriteria']);
        $nama_kriteria = $db->escapeString($_POST['nama_kriteria']);
        $deskripsi = $db->escapeString($_POST['deskripsi']);
        $bobot = $db->escapeString($_POST['bobot']);
        
        // Cek kode kriteria apakah sudah ada (kecuali untuk data ini)
        $cek_query = "SELECT id FROM kriteria WHERE kode_kriteria = '$kode_kriteria' AND id != '$id'";
        $cek_result = $conn->query($cek_query);
        
        if ($cek_result->num_rows > 0) {
            flashMessage('error', 'Kode Kriteria sudah terdaftar untuk kriteria lain!');
        } else {
            // Validasi total bobot tidak lebih dari 100
            $current_bobot_query = "SELECT bobot FROM kriteria WHERE id = '$id'";
            $current_bobot_result = $conn->query($current_bobot_query);
            $current_bobot = $current_bobot_result->fetch_assoc()['bobot'];
            
            $total_bobot_query = "SELECT SUM(bobot) as total FROM kriteria WHERE (status = 'Aktif' OR status IS NULL) AND id != '$id'";
            $total_bobot_result = $conn->query($total_bobot_query);
            $total_bobot = $total_bobot_result->fetch_assoc()['total'] ?? 0;
            
            if (($total_bobot + $bobot) > 100) {
                flashMessage('error', 'Total bobot semua kriteria tidak boleh lebih dari 100%!');
            } else {
                $query = "UPDATE kriteria SET 
                         kode_kriteria = '$kode_kriteria',
                         nama_kriteria = '$nama_kriteria',
                         deskripsi = '$deskripsi',
                         bobot = '$bobot'
                         WHERE id = '$id'";
                
                if ($conn->query($query)) {
                    flashMessage('success', 'Kriteria berhasil diperbarui!');
                    redirect('kriteria.php');
                } else {
                    flashMessage('error', 'Gagal memperbarui kriteria: ' . $conn->error);
                }
            }
        }
    }
}

// Handle delete
if ($action == 'delete' && $id) {
    // Cek apakah kriteria sudah digunakan dalam penilaian
    $cek_query = "SELECT id FROM detail_penilaian WHERE kriteria_id = '$id'";
    $cek_result = $conn->query($cek_query);
    
    if ($cek_result->num_rows > 0) {
        flashMessage('error', 'Tidak dapat menghapus kriteria yang sudah digunakan dalam penilaian!');
    } else {
        $query = "DELETE FROM kriteria WHERE id = '$id'";
        if ($conn->query($query)) {
            flashMessage('success', 'Kriteria berhasil dihapus!');
        } else {
            flashMessage('error', 'Gagal menghapus kriteria: ' . $conn->error);
        }
    }
    redirect('kriteria.php');
}

// Get data for edit
$kriteria_data = null;
if ($action == 'edit' && $id) {
    $query = "SELECT * FROM kriteria WHERE id = '$id'";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        $kriteria_data = $result->fetch_assoc();
    }
}

// Get all data kriteria
$search = isset($_GET['search']) ? $db->escapeString($_GET['search']) : '';
$query = "SELECT *, FORMAT(bobot, 2) as bobot_format, 
          CASE WHEN status = 'Aktif' THEN 1 ELSE 2 END as status_order 
          FROM kriteria";
if ($search) {
    $query .= " WHERE (nama_kriteria LIKE '%$search%' OR kode_kriteria LIKE '%$search%')";
} else {
    $query .= " WHERE 1=1";
}
$query .= " ORDER BY status_order ASC, kode_kriteria ASC";
$result = $conn->query($query);

// Hitung total bobot (hanya yang aktif)
$total_bobot_query = "SELECT SUM(bobot) as total FROM kriteria WHERE status = 'Aktif' OR status IS NULL";
$total_bobot_result = $conn->query($total_bobot_query);
$total_bobot = $total_bobot_result->fetch_assoc()['total'] ?? 0;

$page_title = $action == 'tambah' ? 'Tambah Kriteria' : ($action == 'edit' ? 'Edit Kriteria' : 'Kriteria Penilaian');
require_once '../includes/header.php';
?>

<?php if ($action == 'tambah' || $action == 'edit'): ?>
<!-- Form Tambah/Edit Kriteria -->
<div class="card">
    <div class="card-header">
        <h2><?php echo $action == 'tambah' ? 'Tambah Kriteria Penilaian' : 'Edit Kriteria Penilaian'; ?></h2>
        <a href="kriteria.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $kriteria_data['id']; ?>">
                <input type="hidden" name="edit_kriteria" value="1">
            <?php else: ?>
                <input type="hidden" name="tambah_kriteria" value="1">
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div class="form-group">
                    <label for="kode_kriteria">Kode Kriteria *</label>
                    <input type="text" id="kode_kriteria" name="kode_kriteria" class="form-control" 
                           value="<?php echo $kriteria_data['kode_kriteria'] ?? ''; ?>" required
                           pattern="[A-Za-z0-9]+" title="Hanya huruf dan angka">
                    <small style="color: #7f8c8d;">Contoh: K01, K02, dsb.</small>
                </div>
                
                <div class="form-group">
                    <label for="nama_kriteria">Nama Kriteria *</label>
                    <input type="text" id="nama_kriteria" name="nama_kriteria" class="form-control"
                           value="<?php echo $kriteria_data['nama_kriteria'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group" style="grid-column: span 2;">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3"
                              placeholder="Deskripsi kriteria penilaian..."><?php echo $kriteria_data['deskripsi'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="bobot">Bobot (%) *</label>
                    <input type="number" id="bobot" name="bobot" class="form-control" step="0.01" min="0" max="100"
                           value="<?php echo $kriteria_data['bobot'] ?? ''; ?>" required>
                    <small style="color: #7f8c8d;">Bobot dalam persentase (0-100)</small>
                </div>
                
                <div class="form-group">
                    <label>Total Bobot Saat Ini</label>
                    <div style="padding: 10px; background: #f8f9fa; border-radius: 5px; font-weight: bold; color: <?php echo $total_bobot == 100 ? '#27ae60' : '#e74c3c'; ?>;">
                        <?php echo number_format($total_bobot, 2); ?>%
                        <?php if ($total_bobot == 100): ?>
                            <span style="color: #27ae60;">(✓ Optimal)</span>
                        <?php elseif ($total_bobot > 100): ?>
                            <span style="color: #e74c3c;">(⚠ Melebihi 100%)</span>
                        <?php else: ?>
                            <span style="color: #f39c12;">(↻ Perlu penyesuaian)</span>
                        <?php endif; ?>
                    </div>
                    <small style="color: #7f8c8d;">Total bobot semua kriteria harus = 100%</small>
                </div>
            </div>
            
            <div style="margin-top: 30px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Simpan
                </button>
                <a href="kriteria.php" class="btn btn-warning">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- List Data Kriteria -->
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Kriteria Penilaian</h2>
        <div style="display: flex; gap: 10px; align-items: center;">
            <div style="padding: 8px 15px; background: <?php echo $total_bobot == 100 ? '#d4edda' : '#f8d7da'; ?>; 
                 color: <?php echo $total_bobot == 100 ? '#155724' : '#721c24'; ?>; 
                 border-radius: 5px; font-weight: 500;">
                Total Bobot: <?php echo number_format($total_bobot, 2); ?>%
            </div>
            <form method="GET" action="" style="display: flex; gap: 10px;">
                <input type="text" name="search" class="form-control" placeholder="Cari kriteria..." 
                       value="<?php echo $search; ?>" style="width: 250px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Cari
                </button>
                <?php if ($search): ?>
                    <a href="kriteria.php" class="btn btn-warning">
                        <i class="fas fa-times"></i> Reset
                    </a>
                <?php endif; ?>
            </form>
            <a href="?action=tambah" class="btn btn-success">
                <i class="fas fa-plus"></i> Tambah Kriteria
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
                            <th>Kode</th>
                            <th>Nama Kriteria</th>
                            <th>Deskripsi</th>
                            <th>Bobot</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <span style="font-weight: bold; color: #3498db;"><?php echo $row['kode_kriteria']; ?></span>
                                </td>
                                <td><?php echo $row['nama_kriteria']; ?></td>
                                <td><?php echo $row['deskripsi'] ?: '-'; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 100px; background: #f1f1f1; border-radius: 5px; overflow: hidden;">
                                            <div style="width: <?php echo $row['bobot']; ?>%; height: 8px; background: <?php echo $row['bobot'] > 20 ? '#2ecc71' : ($row['bobot'] > 10 ? '#3498db' : '#f39c12'); ?>;"></div>
                                        </div>
                                        <span style="font-weight: bold;"><?php echo $row['bobot_format']; ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo ($row['status'] ?? 'Aktif') == 'Aktif' ? 'badge-success' : 'badge-secondary'; ?>" style="padding: 5px 10px;">
                                        <?php echo $row['status'] ?? 'Aktif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="?action=edit&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus kriteria <?php echo $row['nama_kriteria']; ?>?')"
                                           title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="?action=toggle_status&id=<?php echo $row['id']; ?>&status=<?php echo ($row['status'] ?? 'Aktif') == 'Aktif' ? 'Tidak%20Aktif' : 'Aktif'; ?>" 
                                           class="btn btn-sm <?php echo ($row['status'] ?? 'Aktif') == 'Aktif' ? 'btn-warning' : 'btn-success'; ?>"
                                           title="<?php echo ($row['status'] ?? 'Aktif') == 'Aktif' ? 'Nonaktifkan' : 'Aktifkan'; ?>"
                                           onclick="return confirm('Apakah Anda yakin ingin <?php echo ($row['status'] ?? 'Aktif') == 'Aktif' ? 'menonaktifkan' : 'mengaktifkan'; ?> kriteria <?php echo $row['nama_kriteria']; ?>?')">
                                            <i class="fas <?php echo ($row['status'] ?? 'Aktif') == 'Aktif' ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
                <h4 style="margin-bottom: 10px;">Distribusi Bobot Kriteria</h4>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="flex: 1;">
                        <div style="height: 20px; background: linear-gradient(90deg, 
                            <?php 
                            $result2 = $conn->query("SELECT bobot FROM kriteria ORDER BY bobot DESC");
                            $colors = ['#2ecc71', '#3498db', '#9b59b6', '#e67e22', '#e74c3c', '#f1c40f'];
                            $color_index = 0;
                            $previous_width = 0;
                            while ($row2 = $result2->fetch_assoc()): ?>
                                <?php echo $colors[$color_index % count($colors)] . ' ' . $previous_width . '%, ' . $colors[$color_index % count($colors)] . ' ' . ($previous_width + $row2['bobot']) . '%, ';
                                $previous_width += $row2['bobot'];
                                $color_index++;
                                ?>
                            <?php endwhile; ?>
                            #f1f1f1 100%); border-radius: 10px;">
                        </div>
                    </div>
                    <div style="font-size: 14px; color: #7f8c8d;">
                        <?php if ($total_bobot == 100): ?>
                            <span style="color: #27ae60;"><i class="fas fa-check-circle"></i> Bobot optimal</span>
                        <?php elseif ($total_bobot < 100): ?>
                            <span style="color: #f39c12;"><i class="fas fa-exclamation-triangle"></i> Kurang <?php echo 100 - $total_bobot; ?>%</span>
                        <?php else: ?>
                            <span style="color: #e74c3c;"><i class="fas fa-exclamation-circle"></i> Lebih <?php echo $total_bobot - 100; ?>%</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-list-alt" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                <p>Belum ada data kriteria</p>
                <a href="?action=tambah" class="btn btn-success">
                    <i class="fas fa-plus"></i> Tambah Kriteria Pertama
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
// Real-time bobot validation
document.addEventListener('DOMContentLoaded', function() {
    const bobotInput = document.getElementById('bobot');
    const totalBobotEl = document.querySelector('[style*="Total Bobot Saat Ini"]');
    
    if (bobotInput && totalBobotEl) {
        bobotInput.addEventListener('input', function() {
            const currentTotal = <?php echo $action == 'edit' ? ($total_bobot - ($kriteria_data['bobot'] ?? 0)) : $total_bobot; ?>;
            const newValue = parseFloat(this.value) || 0;
            const newTotal = currentTotal + newValue;
            
            // Update display
            const display = totalBobotEl.nextElementSibling;
            if (display) {
                display.textContent = newTotal.toFixed(2) + '%';
                display.style.color = newTotal === 100 ? '#27ae60' : (newTotal > 100 ? '#e74c3c' : '#f39c12');
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>