<?php
// pimpinan/dashboard.php
require_once '../config/database.php';
checkRole(['pimpinan']);

$db = new Database();
$conn = $db->getConnection();

// Get penilai data
$user_id = $_SESSION['user_id'];
$penilai_query = "SELECT * FROM penilai WHERE user_id = '$user_id'";
$penilai_result = $conn->query($penilai_query);
$penilai = $penilai_result->fetch_assoc();

// Get active period
$active_period = $conn->query("
    SELECT * FROM periode_penilaian 
    WHERE status = 'aktif' 
    ORDER BY id DESC LIMIT 1
")->fetch_assoc();

// Get statistics for this penilai
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
    FROM penilaian 
    WHERE penilai_id = '{$penilai['id']}'
    " . ($active_period ? "AND periode_id = '{$active_period['id']}'" : "");

$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get recent penilaian
$recent_query = "
    SELECT 
        pn.*,
        pg.nama_lengkap as nama_pegawai,
        pg.jabatan as jabatan_pegawai,
        pr.nama_periode
    FROM penilaian pn
    JOIN pegawai pg ON pn.pegawai_id = pg.id
    JOIN periode_penilaian pr ON pn.periode_id = pr.id
    WHERE pn.penilai_id = '{$penilai['id']}'
    ORDER BY pn.tanggal_penilaian DESC
    LIMIT 5
";

$recent_result = $conn->query($recent_query);

$page_title = 'Dashboard Pimpinan';
require_once '../includes/header.php';
?>

<div class="dashboard-header">
    <div class="header-title">
        <h1>Dashboard Pimpinan</h1>
        <div class="user-info">
            <span class="user-name"><?php echo $_SESSION['nama_lengkap']; ?></span>
            <span class="user-role">Pimpinan</span>
        </div>
    </div>
    <p>Sistem Informasi Penilaian Kinerja Pegawai</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['total'] ?? 0; ?></h3>
            <p>Total Penilaian</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['selesai'] ?? 0; ?></h3>
            <p>Selesai</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-edit"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['draft'] ?? 0; ?></h3>
            <p>Draft</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <?php if ($active_period): 
                $end_date = new DateTime($active_period['tanggal_selesai']);
                $today = new DateTime();
                $days_left = $today->diff($end_date)->days;
            ?>
                <h3><?php echo $days_left; ?></h3>
                <p>Hari Tersisa</p>
            <?php else: ?>
                <h3>0</h3>
                <p>Periode Tidak Aktif</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="content-section">
    <div class="main-section">
        <?php if ($active_period): ?>
        <div class="section-header">
            <h2>Periode Penilaian Aktif</h2>
            <span class="status-badge status-aktif">Aktif</span>
        </div>
        
        <div class="period-info">
            <h4><?php echo $active_period['nama_periode']; ?></h4>
            <div class="period-dates">
                <div class="date-item">
                    <i class="far fa-calendar-alt"></i>
                    <?php echo date('d M Y', strtotime($active_period['tanggal_mulai'])); ?>
                </div>
                <div class="date-item">
                    <i class="far fa-calendar-check"></i>
                    <?php echo date('d M Y', strtotime($active_period['tanggal_selesai'])); ?>
                </div>
            </div>
            <p>Sisa waktu: <?php echo $days_left; ?> hari</p>
        </div>
        <?php endif; ?>
        
        <div class="section-header" style="margin-top: 30px;">
            <h2>Penilaian Terbaru Anda</h2>
            <a href="penilaian.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Tambah Penilaian
            </a>
        </div>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pegawai</th>
                        <th>Jabatan</th>
                        <th>Periode</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_result->num_rows > 0): ?>
                        <?php while ($row = $recent_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['nama_pegawai']; ?></td>
                                <td><?php echo $row['jabatan_pegawai']; ?></td>
                                <td><?php echo $row['nama_periode']; ?></td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal_penilaian'])); ?></td>
                                <td>
                                    <?php 
                                        $status_class = $row['status'] == 'selesai' ? 'status-selesai' : 'status-draft';
                                        $status_text = $row['status'] == 'selesai' ? 'Selesai' : 'Draft';
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="penilaian.php?action=edit&id=<?php echo $row['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">
                                Belum ada penilaian
                                <?php if ($active_period): ?>
                                    <br>
                                    <a href="penilaian.php?action=tambah" class="btn btn-sm btn-success" style="margin-top: 10px;">
                                        <i class="fas fa-plus"></i> Buat Penilaian Pertama
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="quick-actions-section">
        <div class="section-header">
            <h2>Aksi Cepat</h2>
        </div>
        
        <div class="quick-actions-grid">
            <a href="penilaian.php?action=tambah" class="quick-action-btn">
                <div class="quick-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div>
                    <strong>Buat Penilaian</strong>
                    <p style="font-size: 12px; margin-top: 5px; opacity: 0.7;">Buat penilaian baru</p>
                </div>
            </a>
            
            <a href="penilaian.php" class="quick-action-btn">
                <div class="quick-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div>
                    <strong>Lihat Semua</strong>
                    <p style="font-size: 12px; margin-top: 5px; opacity: 0.7;">Lihat semua penilaian</p>
                </div>
            </a>
            
            <a href="penilaian.php?status=draft" class="quick-action-btn">
                <div class="quick-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div>
                    <strong>Edit Draft</strong>
                    <p style="font-size: 12px; margin-top: 5px; opacity: 0.7;">Lanjutkan draft penilaian</p>
                </div>
            </a>
            
            <a href="laporan.php" class="quick-action-btn">
                <div class="quick-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div>
                    <strong>Lihat Laporan</strong>
                    <p style="font-size: 12px; margin-top: 5px; opacity: 0.7;">Lihat laporan penilaian</p>
                </div>
            </a>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h4 style="margin-bottom: 15px; color: #2c3e50;">Informasi Pimpinan</h4>
            <div style="display: grid; grid-template-columns: 1fr; gap: 10px;">
                <div>
                    <small style="color: #7f8c8d;">Nama</small>
                    <p style="font-weight: bold; margin: 5px 0;"><?php echo $penilai['nama_penilai']; ?></p>
                </div>
                <div>
                    <small style="color: #7f8c8d;">Jabatan</small>
                    <p style="font-weight: bold; margin: 5px 0;"><?php echo $penilai['jabatan']; ?></p>
                </div>
                <div>
                    <small style="color: #7f8c8d;">Total Penilaian (Seluruh Periode)</small>
                    <?php
                    $total_all_query = "SELECT COUNT(*) as total FROM penilaian WHERE penilai_id = '{$penilai['id']}'";
                    $total_all_result = $conn->query($total_all_query);
                    $total_all = $total_all_result->fetch_assoc()['total'];
                    ?>
                    <p style="font-size: 18px; font-weight: bold; margin: 5px 0; color: #3498db;">
                        <?php echo $total_all; ?> penilaian
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>