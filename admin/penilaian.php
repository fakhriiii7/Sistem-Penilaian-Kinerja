<?php
require_once '../config/database.php';

// Check jika user adalah admin
checkRole(['admin']);

$db = new Database();
$conn = $db->getConnection();

$action = isset($_GET['action']) ? $db->escapeString($_GET['action']) : '';
$id = isset($_GET['id']) ? $db->escapeString($_GET['id']) : '';
$periode_id = isset($_GET['periode_id']) ? $db->escapeString($_GET['periode_id']) : '';
$penilai_id = isset($_GET['penilai_id']) ? $db->escapeString($_GET['penilai_id']) : '';

// Handle actions
if ($action == 'delete' && $id) {
    $query = "DELETE FROM penilaian WHERE id = '$id'";
    if ($conn->query($query)) {
        // Juga hapus detail penilaian
        $conn->query("DELETE FROM detail_penilaian WHERE penilaian_id = '$id'");
        flashMessage('success', 'Data penilaian berhasil dihapus!');
    } else {
        flashMessage('error', 'Gagal menghapus data penilaian: ' . $conn->error);
    }
    redirect('penilaian.php');
}

// Get active period
$active_period = $conn->query("
    SELECT * FROM periode_penilaian 
    WHERE status = 'aktif' 
    ORDER BY id DESC LIMIT 1
")->fetch_assoc();

// Get all penilaian data with filters
$where_conditions = [];
if ($periode_id) {
    $where_conditions[] = "pn.periode_id = '$periode_id'";
} else if ($active_period) {
    $where_conditions[] = "pn.periode_id = '{$active_period['id']}'";
}

if ($penilai_id) {
    $where_conditions[] = "pn.penilai_id = '$penilai_id'";
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// PERBAIKAN QUERY DI SINI:
$query = "
    SELECT 
        pn.*,
        pg.nama_lengkap as nama_pegawai,
        pg.jabatan as jabatan_pegawai,
        pl.nama_penilai,
        pl.jabatan as jabatan_penilai,
        pr.nama_periode,
        pr.tanggal_mulai,
        pr.tanggal_selesai,
        (SELECT COUNT(*) FROM detail_penilaian WHERE penilaian_id = pn.id) as total_kriteria,
        (SELECT AVG(nilai) FROM detail_penilaian WHERE penilaian_id = pn.id) as rata_rata_nilai
    FROM penilaian pn
    JOIN pegawai pg ON pn.pegawai_id = pg.id
    JOIN penilai pl ON pn.penilai_id = pl.id
    JOIN periode_penilaian pr ON pn.periode_id = pr.id
    $where_clause
    ORDER BY pn.tanggal_penilaian DESC
";

$result = $conn->query($query);

// Get all periods for filter
$periods = $conn->query("SELECT * FROM periode_penilaian ORDER BY tanggal_mulai DESC");

// Get all penilai for filter
$penilai_list = $conn->query("SELECT * FROM penilai ORDER BY nama_penilai ASC");

$page_title = 'Penilaian';
require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Data Penilaian</h2>
        <div style="display: flex; gap: 10px; align-items: center;">
            <!-- Tombol Tambah Penilaian dihilangkan -->
        </div>
    </div>
    
    <div class="card-body">
        <!-- Filter Section -->
        <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h4 style="margin-bottom: 15px;">Filter Data</h4>
            <form method="GET" action="" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px;">
                <div class="form-group">
                    <label for="periode_id">Periode Penilaian</label>
                    <select id="periode_id" name="periode_id" class="form-control">
                        <option value="">Semua Periode</option>
                        <?php 
                        // Reset pointer query periods
                        $periods->data_seek(0);
                        while ($period = $periods->fetch_assoc()): ?>
                            <option value="<?php echo $period['id']; ?>" 
                                <?php echo ($periode_id == $period['id']) ? 'selected' : ''; ?>>
                                <?php echo $period['nama_periode']; ?>
                                <?php if ($period['status'] == 'aktif'): ?> (Aktif)<?php endif; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="penilai_id">Penilai</label>
                    <select id="penilai_id" name="penilai_id" class="form-control">
                        <option value="">Semua Penilai</option>
                        <?php 
                        // Reset pointer query penilai
                        $penilai_list->data_seek(0);
                        while ($penilai = $penilai_list->fetch_assoc()): ?>
                            <option value="<?php echo $penilai['id']; ?>" 
                                <?php echo ($penilai_id == $penilai['id']) ? 'selected' : ''; ?>>
                                <?php echo $penilai['nama_penilai']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary" style="height: 42px;">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <?php if ($periode_id || $penilai_id): ?>
                        <a href="penilaian.php" class="btn btn-warning" style="height: 42px;">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Pegawai</th>
                            <th>Penilai</th>
                            <th>Periode</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Rata-rata</th>
                            <th>Detail</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            // Calculate grade
                            $rata_rata = $row['rata_rata_nilai'] ?? 0;
                            $grade = '';
                            $grade_color = '';
                            
                            if ($rata_rata >= 90) {
                                $grade = 'A';
                                $grade_color = '#27ae60';
                            } elseif ($rata_rata >= 80) {
                                $grade = 'B';
                                $grade_color = '#3498db';
                            } elseif ($rata_rata >= 70) {
                                $grade = 'C';
                                $grade_color = '#f39c12';
                            } elseif ($rata_rata >= 60) {
                                $grade = 'D';
                                $grade_color = '#e67e22';
                            } else {
                                $grade = 'E';
                                $grade_color = '#e74c3c';
                            }
                        ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo $row['nama_pegawai']; ?></strong><br>
                                    <small style="color: #7f8c8d;"><?php echo $row['jabatan_pegawai']; ?></small>
                                </td>
                                <td>
                                    <?php echo $row['nama_penilai']; ?><br>
                                    <small style="color: #7f8c8d;"><?php echo $row['jabatan_penilai']; ?></small>
                                </td>
                                <td>
                                    <?php echo $row['nama_periode']; ?><br>
                                    <small style="color: #7f8c8d;">
                                        <?php echo date('d M Y', strtotime($row['tanggal_mulai'])); ?> - 
                                        <?php echo date('d M Y', strtotime($row['tanggal_selesai'])); ?>
                                    </small>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal_penilaian'])); ?></td>
                                <td>
                                    <?php 
                                        $status_class = $row['status'] == 'selesai' ? 'status-active' : 'status-pending';
                                        $status_text = $row['status'] == 'selesai' ? 'Selesai' : 'Draft';
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="text-align: center;">
                                        <div style="font-size: 18px; font-weight: bold; color: <?php echo $grade_color; ?>;">
                                            <?php echo number_format($rata_rata, 1); ?>
                                        </div>
                                        <div style="font-size: 12px; color: <?php echo $grade_color; ?>; font-weight: bold;">
                                            <?php echo $grade; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info" 
                                       onclick="showPenilaianDetail(<?php echo $row['id']; ?>)">
                                        <i class="fas fa-eye"></i> Lihat
                                    </a>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="?action=delete&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus penilaian ini?')"
                                           title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="laporan.php?action=detail&id=<?php echo $row['id']; ?>" 
                                           target="_blank"
                                           class="btn btn-sm btn-warning" title="Cetak">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary Statistics -->
            <div style="margin-top: 30px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <?php
                // Calculate statistics - PERBAIKAN QUERY STATS
                $stats_query = "
                    SELECT
                        COUNT(*) as total,
                        SUM(CASE WHEN pn.status = 'selesai' THEN 1 ELSE 0 END) as selesai,
                        SUM(CASE WHEN pn.status = 'draft' THEN 1 ELSE 0 END) as draft,
                        (
                            SELECT AVG(dn.nilai) 
                            FROM detail_penilaian dn
                            JOIN penilaian pn2 ON dn.penilaian_id = pn2.id
                            WHERE pn2.status = 'selesai'
                            " . ($where_clause ? ' AND ' . str_replace('pn.', 'pn2.', implode(' AND ', $where_conditions)) : '') . "
                        ) as avg_nilai
                    FROM penilaian pn
                ";
                
                if ($where_clause) {
                    // Replace pn. with empty string for stats query
                    $stats_conditions = str_replace('pn.', '', $where_conditions);
                    $stats_query .= " WHERE " . implode(' AND ', $stats_conditions);
                }
                
                $stats_result = $conn->query($stats_query);
                $stats = $stats_result->fetch_assoc();
                ?>
                
                <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                    <div style="font-size: 36px; color: #3498db; font-weight: bold;">
                        <?php echo $stats['total']; ?>
                    </div>
                    <div style="color: #7f8c8d;">Total Penilaian</div>
                </div>
                
                <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                    <div style="font-size: 36px; color: #2ecc71; font-weight: bold;">
                        <?php echo $stats['selesai']; ?>
                    </div>
                    <div style="color: #7f8c8d;">Selesai</div>
                </div>
                
                <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                    <div style="font-size: 36px; color: #f39c12; font-weight: bold;">
                        <?php echo $stats['draft']; ?>
                    </div>
                    <div style="color: #7f8c8d;">Draft</div>
                </div>
                
                <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                    <div style="font-size: 36px; color: #9b59b6; font-weight: bold;">
                        <?php echo number_format($stats['avg_nilai'] ?? 0, 1); ?>
                    </div>
                    <div style="color: #7f8c8d;">Rata-rata Nilai</div>
                </div>
            </div>
            
        <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-clipboard-list" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                <p>Belum ada data penilaian</p>
                <?php if ($active_period): ?>
                    <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 20px;">
                        Periode aktif: <strong><?php echo $active_period['nama_periode']; ?></strong>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Detail Penilaian -->
<div id="detailPenilaianModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; width: 90%; max-width: 800px; border-radius: 10px; padding: 30px; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Detail Penilaian</h3>
            <button onclick="closeDetailPenilaianModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #7f8c8d;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div id="detailPenilaianContent">
            <div style="text-align: center; padding: 40px;">
                <div class="spinner"></div>
                <p>Memuat data...</p>
            </div>
        </div>
        
        <div style="margin-top: 20px; text-align: right;">
            <button onclick="closeDetailPenilaianModal()" class="btn btn-primary">Tutup</button>
        </div>
    </div>
</div>

<script>
function showPenilaianDetail(penilaianId) {
    const modal = document.getElementById('detailPenilaianModal');
    const content = document.getElementById('detailPenilaianContent');
    
    modal.style.display = 'flex';
    
    // Load data via AJAX
    // First, create a simple detail view without AJAX
    // In production, you would create a separate PHP file for AJAX
    content.innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <p>Fitur detail sedang dalam pengembangan...</p>
            <p>ID Penilaian: ${penilaianId}</p>
        </div>
    `;
    
    // Untuk versi produksi, gunakan ini:
    /*
    fetch(`ajax/get_penilaian_detail.php?id=${penilaianId}`)
        .then(response => response.text())
        .then(data => {
            content.innerHTML = data;
        })
        .catch(error => {
            content.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #e74c3c;">
                    <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 20px;"></i>
                    <p>Gagal memuat data penilaian</p>
                </div>
            `;
        });
    */
}

function closeDetailPenilaianModal() {
    document.getElementById('detailPenilaianModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('detailPenilaianModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailPenilaianModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>