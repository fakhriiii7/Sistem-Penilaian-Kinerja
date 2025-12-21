<?php
require_once '../config/database.php';
checkRole(['pegawai']);

$db = new Database();
$conn = $db->getConnection();

// Get pegawai data - try multiple methods to find pegawai
$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$pegawai = null;

// Method 1: Try to find by user_id (if column exists)
$pegawai_query = "SELECT * FROM pegawai WHERE user_id = '$user_id'";
$pegawai_result = $conn->query($pegawai_query);
if ($pegawai_result && $pegawai_result->num_rows > 0) {
    $pegawai = $pegawai_result->fetch_assoc();
} else {
    // Method 2: Try to find by nama_lengkap
    if ($nama_lengkap) {
        $nama_lengkap_escaped = $db->escapeString($nama_lengkap);
        $pegawai_query2 = "SELECT * FROM pegawai WHERE nama_lengkap = '$nama_lengkap_escaped' LIMIT 1";
        $pegawai_result2 = $conn->query($pegawai_query2);
        if ($pegawai_result2 && $pegawai_result2->num_rows > 0) {
            $pegawai = $pegawai_result2->fetch_assoc();
        }
    }
}

// If pegawai still not found, show error
if (!$pegawai) {
    $page_title = 'Dashboard Pegawai';
    require_once '../includes/header.php';
    echo '<div class="alert alert-error" style="margin: 20px; padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px;">
        <h3>Data Pegawai Tidak Ditemukan</h3>
        <p>Data pegawai Anda tidak ditemukan dalam sistem. Silakan hubungi administrator untuk mengaktifkan akun Anda.</p>
        <p><strong>User ID:</strong> ' . htmlspecialchars($user_id) . '</p>
        <p><strong>Nama:</strong> ' . htmlspecialchars($nama_lengkap) . '</p>
    </div>';
    require_once '../includes/footer.php';
    exit();
}

$pegawai_id = $pegawai['id'];

// Get latest penilaian for this pegawai
$latest_query = "
    SELECT 
        pn.*,
        pl.nama_penilai,
        pl.jabatan as jabatan_penilai,
        pr.nama_periode,
        (SELECT AVG(nilai) FROM detail_penilaian WHERE penilaian_id = pn.id) as rata_rata_nilai
    FROM penilaian pn
    JOIN penilai pl ON pn.penilai_id = pl.id
    JOIN periode_penilaian pr ON pn.periode_id = pr.id
    WHERE pn.pegawai_id = '$pegawai_id'
    AND pn.status = 'selesai'
    ORDER BY pn.tanggal_penilaian DESC
    LIMIT 1
";

$latest_result = $conn->query($latest_query);
$latest_penilaian = $latest_result ? $latest_result->fetch_assoc() : null;

// Get all penilaian history
$history_query = "
    SELECT 
        pn.*,
        pl.nama_penilai,
        pr.nama_periode,
        (SELECT AVG(nilai) FROM detail_penilaian WHERE penilaian_id = pn.id) as rata_rata_nilai
    FROM penilaian pn
    JOIN penilai pl ON pn.penilai_id = pl.id
    JOIN periode_penilaian pr ON pn.periode_id = pr.id
    WHERE pn.pegawai_id = '$pegawai_id'
    AND pn.status = 'selesai'
    ORDER BY pn.tanggal_penilaian DESC
    LIMIT 10
";

$history_result = $conn->query($history_query);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_penilaian,
        AVG(d.nilai) as rata_rata_keseluruhan,
        MIN(d.nilai) as nilai_terendah,
        MAX(d.nilai) as nilai_tertinggi
    FROM penilaian pn
    JOIN detail_penilaian d ON pn.id = d.penilaian_id
    WHERE pn.pegawai_id = '$pegawai_id'
    AND pn.status = 'selesai'
";

$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : [
    'total_penilaian' => 0,
    'rata_rata_keseluruhan' => 0,
    'nilai_terendah' => 0,
    'nilai_tertinggi' => 0
];

$page_title = 'Dashboard Pegawai';
require_once '../includes/header.php';
?>

<div class="dashboard-header">
    <div class="header-title">
        <h1>Dashboard Pegawai</h1>
        <div class="user-info">
            <span class="user-name"><?php echo $_SESSION['nama_lengkap']; ?></span>
            <span class="user-role">Pegawai</span>
        </div>
    </div>
    <p>Sistem Informasi Penilaian Kinerja Pegawai</p>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $stats['total_penilaian'] ?? 0; ?></h3>
            <p>Total Penilaian</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['rata_rata_keseluruhan'] ?? 0, 1); ?></h3>
            <p>Rata-rata Nilai</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-trophy"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['nilai_tertinggi'] ?? 0, 1); ?></h3>
            <p>Nilai Tertinggi</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-chart-area"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['nilai_terendah'] ?? 0, 1); ?></h3>
            <p>Nilai Terendah</p>
        </div>
    </div>
</div>

<div class="content-section">
    <div class="main-section">
        <!-- Latest Penilaian -->
        <?php if ($latest_penilaian): 
            $rata_rata = $latest_penilaian['rata_rata_nilai'] ?? 0;
            $grade = '';
            $grade_color = '';
            
            if ($rata_rata >= 90) {
                $grade = 'A (Excellent)';
                $grade_color = '#27ae60';
            } elseif ($rata_rata >= 80) {
                $grade = 'B (Good)';
                $grade_color = '#3498db';
            } elseif ($rata_rata >= 70) {
                $grade = 'C (Average)';
                $grade_color = '#f39c12';
            } elseif ($rata_rata >= 60) {
                $grade = 'D (Below Average)';
                $grade_color = '#e67e22';
            } else {
                $grade = 'E (Poor)';
                $grade_color = '#e74c3c';
            }
        ?>
            <div class="section-header">
                <h2>Penilaian Terakhir</h2>
                <span class="status-badge status-selesai">Selesai</span>
            </div>
            
            <div style="background: white; border-radius: 10px; padding: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <h3 style="margin-bottom: 10px; color: #2c3e50;"><?php echo $latest_penilaian['nama_periode']; ?></h3>
                        <div style="color: #7f8c8d; margin-bottom: 15px;">
                            <i class="far fa-calendar-alt"></i> 
                            <?php echo date('d F Y', strtotime($latest_penilaian['tanggal_penilaian'])); ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <small style="color: #7f8c8d;">Penilai</small>
                                <p style="font-weight: bold; margin: 5px 0;"><?php echo $latest_penilaian['nama_penilai']; ?></p>
                                <p style="color: #7f8c8d; font-size: 14px; margin: 0;"><?php echo $latest_penilaian['jabatan_penilai']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center;">
                        <div style="font-size: 48px; font-weight: bold; color: <?php echo $grade_color; ?>; margin-bottom: 10px;">
                            <?php echo number_format($rata_rata, 1); ?>
                        </div>
                        <div style="padding: 10px 20px; background: <?php echo $grade_color; ?>; color: white; 
                             border-radius: 20px; font-weight: bold; display: inline-block;">
                            <?php echo $grade; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Get detail criteria -->
                <?php
                if (isset($latest_penilaian['id'])) {
                    $latest_penilaian_id = $db->escapeString($latest_penilaian['id']);
                    $detail_query = "
                        SELECT k.nama_kriteria, d.nilai, k.bobot
                        FROM detail_penilaian d
                        JOIN kriteria k ON d.kriteria_id = k.id
                        WHERE d.penilaian_id = '$latest_penilaian_id'
                        ORDER BY k.bobot DESC
                    ";
                    $detail_result = $conn->query($detail_query);
                } else {
                    $detail_result = null;
                }
                ?>
                
                <?php if ($detail_result && $detail_result->num_rows > 0): ?>
                    <div style="margin-top: 20px;">
                        <h4 style="margin-bottom: 15px;">Detail Kriteria</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <?php while ($detail = $detail_result->fetch_assoc()): 
                                $percentage = ($detail['nilai'] / 100) * 100;
                                $color = '';
                                if ($detail['nilai'] >= 90) $color = '#27ae60';
                                elseif ($detail['nilai'] >= 80) $color = '#3498db';
                                elseif ($detail['nilai'] >= 70) $color = '#f39c12';
                                elseif ($detail['nilai'] >= 60) $color = '#e67e22';
                                else $color = '#e74c3c';
                            ?>
                                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                        <span style="font-weight: 500;"><?php echo $detail['nama_kriteria']; ?></span>
                                        <span style="font-weight: bold; color: <?php echo $color; ?>;">
                                            <?php echo number_format($detail['nilai'], 1); ?>
                                        </span>
                                    </div>
                                    <div style="height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden;">
                                        <div style="width: <?php echo $percentage; ?>%; height: 100%; background: <?php echo $color; ?>;"></div>
                                    </div>
                                    <div style="font-size: 12px; color: #7f8c8d; margin-top: 5px;">
                                        Bobot: <?php echo $detail['bobot']; ?>%
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($latest_penilaian['id'])): ?>
                <div style="margin-top: 25px; text-align: center;">
                    <a href="laporan.php?id=<?php echo $latest_penilaian['id']; ?>" 
                       target="_blank" class="btn btn-primary">
                        <i class="fas fa-print"></i> Cetak Laporan Detail
                    </a>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <i class="fas fa-clipboard-list" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                <h3 style="margin-bottom: 10px;">Belum Ada Penilaian</h3>
                <p style="color: #7f8c8d;">Anda belum memiliki riwayat penilaian kinerja.</p>
            </div>
        <?php endif; ?>
        
        <!-- History -->
        <div class="section-header" style="margin-top: 30px;">
            <h2>Riwayat Penilaian</h2>
        </div>
        
        <?php if ($history_result && $history_result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>Penilai</th>
                            <th>Tanggal</th>
                            <th>Nilai</th>
                            <th>Grade</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($history = $history_result->fetch_assoc()): 
                            $rata_rata = $history['rata_rata_nilai'] ?? 0;
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
                                <td><?php echo $history['nama_periode']; ?></td>
                                <td><?php echo $history['nama_penilai']; ?></td>
                                <td><?php echo date('d M Y', strtotime($history['tanggal_penilaian'])); ?></td>
                                <td>
                                    <div style="font-weight: bold; color: <?php echo $grade_color; ?>;">
                                        <?php echo number_format($rata_rata, 1); ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="display: inline-block; width: 30px; height: 30px; 
                                          background: <?php echo $grade_color; ?>; color: white; 
                                          border-radius: 50%; line-height: 30px; text-align: center; font-weight: bold;">
                                        <?php echo $grade; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="laporan.php?id=<?php echo $history['id']; ?>" 
                                       target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <p style="color: #7f8c8d;">Belum ada riwayat penilaian</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="quick-actions-section">
        <div class="section-header">
            <h2>Informasi Pegawai</h2>
        </div>
        
        <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                <div>
                    <small style="color: #7f8c8d;">Nama Lengkap</small>
                    <p style="font-weight: bold; margin: 5px 0;"><?php echo $pegawai['nama_lengkap']; ?></p>
                </div>
                
                <div>
                    <small style="color: #7f8c8d;">NIP</small>
                    <p style="font-weight: bold; margin: 5px 0;"><?php echo $pegawai['nip']; ?></p>
                </div>
                
                <div>
                    <small style="color: #7f8c8d;">Jabatan</small>
                    <p style="font-weight: bold; margin: 5px 0;"><?php echo $pegawai['jabatan']; ?></p>
                </div>
                
                <div>
                    <small style="color: #7f8c8d;">Unit Kerja</small>
                    <p style="font-weight: bold; margin: 5px 0;"><?php echo $pegawai['unit_kerja']; ?></p>
                </div>
                
                <?php if ($pegawai['email']): ?>
                    <div>
                        <small style="color: #7f8c8d;">Email</small>
                        <p style="font-weight: bold; margin: 5px 0;"><?php echo $pegawai['email']; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($pegawai['no_telp']): ?>
                    <div>
                        <small style="color: #7f8c8d;">No. Telepon</small>
                        <p style="font-weight: bold; margin: 5px 0;"><?php echo $pegawai['no_telp']; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Performance Trend -->
        <?php if ($stats['total_penilaian'] > 1): ?>
            <div style="margin-top: 30px; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h4 style="margin-bottom: 15px; color: #2c3e50;">Trend Kinerja</h4>
                
                <?php
                $trend_query = "
                    SELECT 
                        pr.nama_periode,
                        AVG(d.nilai) as rata_rata,
                        COUNT(d.id) as total_kriteria
                    FROM penilaian pn
                    JOIN periode_penilaian pr ON pn.periode_id = pr.id
                    JOIN detail_penilaian d ON pn.id = d.penilaian_id
                    WHERE pn.pegawai_id = '$pegawai_id'
                    AND pn.status = 'selesai'
                    GROUP BY pn.periode_id
                    ORDER BY pr.tanggal_mulai ASC
                    LIMIT 5
                ";
                $trend_result = $conn->query($trend_query);
                ?>
                
                <?php if ($trend_result && $trend_result->num_rows > 0): ?>
                    <div style="height: 200px; position: relative; margin-top: 20px;">
                        <?php 
                        $trend_data = [];
                        $max_value = 0;
                        $trend_result->data_seek(0);
                        while ($trend = $trend_result->fetch_assoc()) {
                            $trend_data[] = $trend;
                            if ($trend['rata_rata'] > $max_value) $max_value = $trend['rata_rata'];
                        }
                        $max_value = max($max_value, 100);
                        ?>
                        
                        <div style="position: absolute; top: 0; bottom: 0; left: 0; right: 0; display: flex; align-items: flex-end;">
                            <?php foreach ($trend_data as $index => $data): 
                                $height = ($data['rata_rata'] / $max_value) * 100;
                                $color = '';
                                if ($data['rata_rata'] >= 90) $color = '#27ae60';
                                elseif ($data['rata_rata'] >= 80) $color = '#3498db';
                                elseif ($data['rata_rata'] >= 70) $color = '#f39c12';
                                elseif ($data['rata_rata'] >= 60) $color = '#e67e22';
                                else $color = '#e74c3c';
                            ?>
                                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%; padding: 0 10px;">
                                    <div style="width: 30px; height: <?php echo $height; ?>%; background: <?php echo $color; ?>; 
                                         border-radius: 5px 5px 0 0; position: relative;">
                                        <div style="position: absolute; top: -25px; left: 50%; transform: translateX(-50%); 
                                             white-space: nowrap; font-size: 12px; font-weight: bold; color: <?php echo $color; ?>;">
                                            <?php echo number_format($data['rata_rata'], 1); ?>
                                        </div>
                                    </div>
                                    <div style="margin-top: 10px; font-size: 11px; text-align: center; color: #7f8c8d;">
                                        <?php echo substr($data['nama_periode'], -4); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                        <div style="font-size: 12px; color: #7f8c8d;">
                            <i class="fas fa-info-circle"></i> Menunjukkan perkembangan nilai rata-rata per periode
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>