<?php
require_once '../config/database.php';

// Check jika user adalah admin
checkRole(['admin']);

$db = new Database();
$conn = $db->getConnection();

// Query untuk statistik
$total_pegawai = $conn->query("SELECT COUNT(*) as total FROM pegawai")->fetch_assoc()['total'];
$total_penilai = $conn->query("SELECT COUNT(*) as total FROM penilai")->fetch_assoc()['total'];
$total_kriteria = $conn->query("SELECT COUNT(*) as total FROM kriteria")->fetch_assoc()['total'];

// Penilaian bulan ini (contoh: bulan 10/Oktober)
$bulan_ini = date('m');
$tahun_ini = date('Y');
$penilaian_bulan_ini = $conn->query("
    SELECT COUNT(*) as total 
    FROM penilaian 
    WHERE MONTH(tanggal_penilaian) = '$bulan_ini' 
    AND YEAR(tanggal_penilaian) = '$tahun_ini'
")->fetch_assoc()['total'];

// Periode aktif
$periode_aktif = $conn->query("
    SELECT * FROM periode_penilaian 
    WHERE status = 'aktif' 
    ORDER BY id DESC LIMIT 1
")->fetch_assoc();

// Data penilaian terbaru
$penilaian_terbaru = $conn->query("
    SELECT 
        p.nama_lengkap,
        p.jabatan,
        per.nama_periode,
        pen.tanggal_penilaian,
        pen.status
    FROM penilaian pen
    JOIN pegawai p ON pen.pegawai_id = p.id
    JOIN periode_penilaian per ON pen.periode_id = per.id
    ORDER BY pen.created_at DESC 
    LIMIT 5
");

$page_title = "Dashboard Admin";
require_once '../includes/header.php';
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_pegawai; ?></h3>
            <p>Total Pegawai</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-user-tie"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_penilai; ?></h3>
            <p>Total Penilai</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-list-alt"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_kriteria; ?></h3>
            <p>Kriteria Penilaian</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $penilaian_bulan_ini; ?></h3>
            <p>Penilaian Bulan ini</p>
        </div>
    </div>
</div>

<div class="content-section">
    <div class="main-section">
        <?php if ($periode_aktif): ?>
        <div class="section-header">
            <h2>Periode Penilaian Aktif</h2>
            <span class="status-badge status-aktif">Aktif</span>
        </div>
        
        <div class="period-info">
            <h4><?php echo $periode_aktif['nama_periode']; ?></h4>
            <div class="period-dates">
                <div class="date-item">
                    <i class="far fa-calendar-alt"></i>
                    <?php echo date('d M Y', strtotime($periode_aktif['tanggal_mulai'])); ?>
                </div>
                <div class="date-item">
                    <i class="far fa-calendar-check"></i>
                    <?php echo date('d M Y', strtotime($periode_aktif['tanggal_selesai'])); ?>
                </div>
            </div>
            <p>Sisa waktu: <?php 
                $tanggal_selesai = new DateTime($periode_aktif['tanggal_selesai']);
                $sekarang = new DateTime();
                $sisa = $sekarang->diff($tanggal_selesai);
                echo $sisa->days . ' hari';
            ?></p>
        </div>
        <?php endif; ?>
        
        <div class="section-header" style="margin-top: 30px;">
            <h2>Penilaian Terbaru</h2>
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
                    </tr>
                </thead>
                <tbody>
                    <?php if ($penilaian_terbaru->num_rows > 0): ?>
                        <?php while ($row = $penilaian_terbaru->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['nama_lengkap']; ?></td>
                                <td><?php echo $row['jabatan']; ?></td>
                                <td><?php echo $row['nama_periode']; ?></td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal_penilaian'])); ?></td>
                                <td>
                                    <?php 
                                        $status_class = '';
                                        switch($row['status']) {
                                            case 'selesai':
                                                $status_class = 'status-selesai';
                                                $status_text = 'Selesai';
                                                break;
                                            case 'draft':
                                                $status_class = 'status-draft';
                                                $status_text = 'Draft';
                                                break;
                                            default:
                                                $status_class = 'status-draft';
                                                $status_text = $row['status'];
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Belum ada data penilaian</td>
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
            <a href="pegawai.php?action=tambah" class="quick-action-btn">
                <div class="quick-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div>
                    <strong>Tambah Pegawai</strong>
                    <p style="font-size: 12px; margin-top: 5px; opacity: 0.7;">Tambah data pegawai baru</p>
                </div>
            </a>
            
            <a href="kriteria.php?action=tambah" class="quick-action-btn">
                <div class="quick-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div>
                    <strong>Tambah Kriteria</strong>
                    <p style="font-size: 12px; margin-top: 5px; opacity: 0.7;">Tambah kriteria penilaian</p>
                </div>
            </a>
            
            <a href="laporan.php" class="quick-action-btn">
                <div class="quick-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div>
                    <strong>Lihat Laporan</strong>
                    <p style="font-size: 12px; margin-top: 5px; opacity: 0.7;">Lihat laporan penilaian</p>
                </div>
            </a>
            
            <a href="periode.php" class="quick-action-btn">
                <div class="quick-icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div>
                    <strong>Atur Periode</strong>
                    <p style="font-size: 12px; margin-top: 5px; opacity: 0.7;">Atur periode penilaian</p>
                </div>
            </a>
            
            <a href="penilaian.php" class="quick-action-btn">
                <div class="quick-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div>
                    <strong>Monitor Penilaian</strong>
                    <p style="font-size: 12px; margin-top: 5px; opacity: 0.7;">Pantau progress penilaian</p>
                </div>
            </a>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h4 style="margin-bottom: 15px; color: #2c3e50;">Statistik Singkat</h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                <div>
                    <small style="color: #7f8c8d;">Pegawai Aktif</small>
                    <p style="font-size: 18px; font-weight: bold; margin: 5px 0;"><?php echo $total_pegawai; ?></p>
                </div>
                <div>
                    <small style="color: #7f8c8d;">Periode Aktif</small>
                    <p style="font-size: 18px; font-weight: bold; margin: 5px 0;"><?php echo $periode_aktif ? 1 : 0; ?></p>
                </div>
                <div>
                    <small style="color: #7f8c8d;">Penilai</small>
                    <p style="font-size: 18px; font-weight: bold; margin: 5px 0;"><?php echo $total_penilai; ?></p>
                </div>
                <div>
                    <small style="color: #7f8c8d;">Kriteria</small>
                    <p style="font-size: 18px; font-weight: bold; margin: 5px 0;"><?php echo $total_kriteria; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>