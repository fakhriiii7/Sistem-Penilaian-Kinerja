<?php
require_once '../config/database.php';

// Check jika user adalah admin
checkRole(['admin']);

$db = new Database();
$conn = $db->getConnection();

$action = isset($_GET['action']) ? $db->escapeString($_GET['action']) : '';
$id = isset($_GET['id']) ? $db->escapeString($_GET['id']) : '';
$periode_id = isset($_GET['periode_id']) ? $db->escapeString($_GET['periode_id']) : '';

// Get active period
$active_period = $conn->query("
    SELECT * FROM periode_penilaian 
    WHERE status = 'aktif' 
    ORDER BY id DESC LIMIT 1
")->fetch_assoc();

// Get all periods for filter
$periods = $conn->query("SELECT * FROM periode_penilaian ORDER BY tanggal_mulai DESC");

// Default to active period if not specified
if (!$periode_id && $active_period) {
    $periode_id = $active_period['id'];
}

// Generate report
$report_data = [];
if ($periode_id) {
    // Get periode data
    $periode_query = "SELECT * FROM periode_penilaian WHERE id = '$periode_id'";
    $periode_result = $conn->query($periode_query);
    $periode = $periode_result->fetch_assoc();
    
    // Get all penilaian for this period
    $penilaian_query = "
        SELECT 
            pn.*,
            pg.nama_lengkap as nama_pegawai,
            pg.jabatan as jabatan_pegawai,
            pg.unit_kerja,
            pl.nama_penilai,
            (SELECT AVG(nilai) FROM detail_penilaian WHERE penilaian_id = pn.id) as rata_rata_nilai
        FROM penilaian pn
        JOIN pegawai pg ON pn.pegawai_id = pg.id
        JOIN penilai pl ON pn.penilai_id = pl.id
        WHERE pn.periode_id = '$periode_id'
        AND pn.status = 'selesai'
        ORDER BY rata_rata_nilai DESC
    ";
    
    $penilaian_result = $conn->query($penilaian_query);
    
    // Get statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total_penilaian,
            COUNT(DISTINCT pn.pegawai_id) as total_pegawai,
            COUNT(DISTINCT pn.penilai_id) as total_penilai,
            AVG(d.nilai) as avg_nilai_global
        FROM penilaian pn
        LEFT JOIN detail_penilaian d ON pn.id = d.penilaian_id
        WHERE pn.periode_id = '$periode_id'
        AND pn.status = 'selesai'
    ";
    
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result->fetch_assoc();
    
    // PERBAIKAN QUERY GRADE DISTRIBUTION:
    // Menggunakan subquery untuk menghindari reference ke alias dalam GROUP BY
    $grade_query = "
        SELECT 
            grade_range,
            COUNT(*) as jumlah,
            COUNT(*) * 100.0 / (SELECT COUNT(*) FROM penilaian WHERE periode_id = '$periode_id' AND status = 'selesai') as persentase
        FROM (
            SELECT 
                CASE
                    WHEN (SELECT AVG(nilai) FROM detail_penilaian WHERE penilaian_id = pn.id) >= 90 THEN 'A (90-100)'
                    WHEN (SELECT AVG(nilai) FROM detail_penilaian WHERE penilaian_id = pn.id) >= 80 THEN 'B (80-89)'
                    WHEN (SELECT AVG(nilai) FROM detail_penilaian WHERE penilaian_id = pn.id) >= 70 THEN 'C (70-79)'
                    WHEN (SELECT AVG(nilai) FROM detail_penilaian WHERE penilaian_id = pn.id) >= 60 THEN 'D (60-69)'
                    ELSE 'E (< 60)'
                END as grade_range
            FROM penilaian pn
            WHERE pn.periode_id = '$periode_id'
            AND pn.status = 'selesai'
        ) as grade_data
        WHERE grade_range IS NOT NULL
        GROUP BY grade_range
        ORDER BY 
            CASE grade_range
                WHEN 'A (90-100)' THEN 1
                WHEN 'B (80-89)' THEN 2
                WHEN 'C (70-79)' THEN 3
                WHEN 'D (60-69)' THEN 4
                ELSE 5
            END
    ";
    
    $grade_result = $conn->query($grade_query);
}

$page_title = 'Laporan Penilaian';
require_once '../includes/header.php';

// If action is detail, show printable report
if ($action == 'detail' && $id) {
    // Buat file laporan_detail.php terpisah
    echo '<script>window.location.href = "laporan_detail.php?id=' . $id . '";</script>';
    exit();
}
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Laporan dan Analisis</h2>
        <div style="display: flex; gap: 10px;">
            <?php if ($periode && isset($stats)): ?>
                <a href="javascript:window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Cetak Laporan
                </a>
                <a href="laporan.php?action=export&periode_id=<?php echo $periode_id; ?>" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Filter Section -->
        <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h4 style="margin-bottom: 15px;">Pilih Periode Laporan</h4>
            <form method="GET" action="" style="display: flex; gap: 15px; align-items: flex-end;">
                <div class="form-group" style="flex: 1;">
                    <select id="periode_id" name="periode_id" class="form-control" required>
                        <option value="">-- Pilih Periode --</option>
                        <?php 
                        $periods_all = $conn->query("SELECT * FROM periode_penilaian ORDER BY tanggal_mulai DESC");
                        while ($period = $periods_all->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $period['id']; ?>" 
                                <?php echo ($periode_id == $period['id']) ? 'selected' : ''; ?>>
                                <?php echo $period['nama_periode']; ?>
                                (<?php echo date('d M Y', strtotime($period['tanggal_mulai'])); ?> - 
                                 <?php echo date('d M Y', strtotime($period['tanggal_selesai'])); ?>)
                                <?php if ($period['status'] == 'aktif'): ?> - Aktif<?php endif; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="height: 42px;">
                        <i class="fas fa-chart-bar"></i> Tampilkan Laporan
                    </button>
                    <?php if ($periode_id): ?>
                        <a href="laporan.php" class="btn btn-warning" style="height: 42px;">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if ($periode && isset($stats)): ?>
            <!-- Report Header -->
            <div style="text-align: center; margin-bottom: 30px; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2>LAPORAN PENILAIAN KINERJA PEGAWAI</h2>
                <h3><?php echo $periode['nama_periode']; ?></h3>
                <p>
                    Periode: <?php echo date('d F Y', strtotime($periode['tanggal_mulai'])); ?> - 
                    <?php echo date('d F Y', strtotime($periode['tanggal_selesai'])); ?>
                </p>
                <p style="color: #7f8c8d;">
                    Generated on: <?php echo date('d F Y H:i:s'); ?>
                </p>
            </div>

            <!-- Summary Statistics -->
            <div style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 20px;">Statistik Utama</h3>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                    <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                        <div style="font-size: 36px; color: #3498db; font-weight: bold;">
                            <?php echo $stats['total_penilaian']; ?>
                        </div>
                        <div style="color: #7f8c8d;">Total Penilaian</div>
                    </div>
                    
                    <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                        <div style="font-size: 36px; color: #2ecc71; font-weight: bold;">
                            <?php echo $stats['total_pegawai']; ?>
                        </div>
                        <div style="color: #7f8c8d;">Pegawai Dinilai</div>
                    </div>
                    
                    <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                        <div style="font-size: 36px; color: #9b59b6; font-weight: bold;">
                            <?php echo $stats['total_penilai']; ?>
                        </div>
                        <div style="color: #7f8c8d;">Jumlah Penilai</div>
                    </div>
                    
                    <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
                        <div style="font-size: 36px; color: #e67e22; font-weight: bold;">
                            <?php echo number_format($stats['avg_nilai_global'] ?? 0, 1); ?>
                        </div>
                        <div style="color: #7f8c8d;">Rata-rata Nilai</div>
                    </div>
                </div>
            </div>

            <!-- Grade Distribution -->
            <?php if ($grade_result && $grade_result->num_rows > 0): ?>
                <div style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 20px;">Distribusi Nilai</h3>
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 12px; text-align: left;">Grade</th>
                                    <th style="padding: 12px; text-align: center;">Jumlah</th>
                                    <th style="padding: 12px; text-align: center;">Persentase</th>
                                    <th style="padding: 12px; text-align: center;">Visualisasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($grade = $grade_result->fetch_assoc()): 
                                    $grade_color = '';
                                    switch(substr($grade['grade_range'], 0, 1)) {
                                        case 'A': $grade_color = '#27ae60'; break;
                                        case 'B': $grade_color = '#3498db'; break;
                                        case 'C': $grade_color = '#f39c12'; break;
                                        case 'D': $grade_color = '#e67e22'; break;
                                        default: $grade_color = '#e74c3c';
                                    }
                                ?>
                                    <tr>
                                        <td style="padding: 12px;">
                                            <span style="display: inline-block; width: 20px; height: 20px; background: <?php echo $grade_color; ?>; border-radius: 3px; margin-right: 10px;"></span>
                                            <strong><?php echo $grade['grade_range']; ?></strong>
                                        </td>
                                        <td style="padding: 12px; text-align: center; font-weight: bold;">
                                            <?php echo $grade['jumlah']; ?>
                                        </td>
                                        <td style="padding: 12px; text-align: center;">
                                            <?php echo number_format($grade['persentase'], 1); ?>%
                                        </td>
                                        <td style="padding: 12px;">
                                            <div style="width: 100%; height: 20px; background: #f1f1f1; border-radius: 10px; overflow: hidden;">
                                                <div style="width: <?php echo $grade['persentase']; ?>%; height: 100%; background: <?php echo $grade_color; ?>;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Detailed Report -->
            <div style="margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;">Detail Penilaian</h3>
                    <span style="color: #7f8c8d;">Total: <?php echo $penilaian_result->num_rows; ?> data</span>
                </div>
                
                <?php if ($penilaian_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Pegawai</th>
                                    <th>Jabatan</th>
                                    <th>Unit Kerja</th>
                                    <th>Penilai</th>
                                    <th>Nilai</th>
                                    <th>Grade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                $penilaian_result->data_seek(0); // Reset pointer
                                while ($row = $penilaian_result->fetch_assoc()): 
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
                                        <td>
                                            <span style="display: inline-block; width: 30px; height: 30px; 
                                                  background: <?php echo $grade_color; ?>; color: white; 
                                                  border-radius: 50%; line-height: 30px; text-align: center; font-weight: bold;">
                                                <?php echo $rank++; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo $row['nama_pegawai']; ?></strong>
                                        </td>
                                        <td><?php echo $row['jabatan_pegawai']; ?></td>
                                        <td><?php echo $row['unit_kerja']; ?></td>
                                        <td><?php echo $row['nama_penilai']; ?></td>
                                        <td>
                                            <div style="text-align: center;">
                                                <div style="font-size: 18px; font-weight: bold; color: <?php echo $grade_color; ?>;">
                                                    <?php echo number_format($rata_rata, 1); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="display: inline-block; padding: 5px 15px; 
                                                  background: <?php echo $grade_color; ?>; color: white; 
                                                  border-radius: 20px; font-weight: bold;">
                                                <?php echo $grade; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-active">
                                                Selesai
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: white; border-radius: 10px;">
                        <i class="fas fa-chart-line" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                        <p>Belum ada data penilaian yang selesai untuk periode ini</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Analysis -->
            <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 30px;">
                <h3 style="margin-bottom: 20px;">Analisis dan Rekomendasi</h3>
                <?php
                // Get top performers
                $top_query = "SELECT pg.nama_lengkap, AVG(d.nilai) as rata_rata
                             FROM penilaian pn
                             JOIN pegawai pg ON pn.pegawai_id = pg.id
                             JOIN detail_penilaian d ON pn.id = d.penilaian_id
                             WHERE pn.periode_id = '$periode_id'
                             AND pn.status = 'selesai'
                             GROUP BY pn.pegawai_id
                             ORDER BY rata_rata DESC
                             LIMIT 3";
                
                $top_result = $conn->query($top_query);
                $top_performers = [];
                while ($row = $top_result->fetch_assoc()) {
                    $top_performers[] = $row;
                }
                
                // Get bottom performers
                $bottom_query = "SELECT pg.nama_lengkap, AVG(d.nilai) as rata_rata
                                FROM penilaian pn
                                JOIN pegawai pg ON pn.pegawai_id = pg.id
                                JOIN detail_penilaian d ON pn.id = d.penilaian_id
                                WHERE pn.periode_id = '$periode_id'
                                AND pn.status = 'selesai'
                                GROUP BY pn.pegawai_id
                                ORDER BY rata_rata ASC
                                LIMIT 3";
                
                $bottom_result = $conn->query($bottom_query);
                $bottom_performers = [];
                while ($row = $bottom_result->fetch_assoc()) {
                    $bottom_performers[] = $row;
                }
                ?>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div>
                        <h4 style="color: #27ae60; margin-bottom: 15px;">
                            <i class="fas fa-trophy"></i> Top Performers
                        </h4>
                        <?php if (!empty($top_performers)): ?>
                            <ul style="list-style: none; padding: 0;">
                                <?php foreach ($top_performers as $index => $performer): ?>
                                    <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                                        <strong><?php echo $index + 1; ?>. <?php echo $performer['nama_lengkap']; ?></strong>
                                        <div style="color: #7f8c8d; font-size: 14px;">
                                            Nilai rata-rata: <strong style="color: #27ae60;"><?php echo number_format($performer['rata_rata'], 1); ?></strong>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="color: #7f8c8d;">Tidak ada data</p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <h4 style="color: #e74c3c; margin-bottom: 15px;">
                            <i class="fas fa-chart-line"></i> Perlu Perhatian
                        </h4>
                        <?php if (!empty($bottom_performers)): ?>
                            <ul style="list-style: none; padding: 0;">
                                <?php foreach ($bottom_performers as $index => $performer): ?>
                                    <li style="padding: 10px 0; border-bottom: 1px solid #eee;">
                                        <strong><?php echo $index + 1; ?>. <?php echo $performer['nama_lengkap']; ?></strong>
                                        <div style="color: #7f8c8d; font-size: 14px;">
                                            Nilai rata-rata: <strong style="color: #e74c3c;"><?php echo number_format($performer['rata_rata'], 1); ?></strong>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="color: #7f8c8d;">Tidak ada data</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                    <h4 style="margin-bottom: 15px;">Rekomendasi</h4>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                        <?php if ($stats['avg_nilai_global'] >= 80): ?>
                            <p style="color: #27ae60;">
                                <i class="fas fa-check-circle"></i> 
                                <strong>Kinerja Baik:</strong> Rata-rata nilai keseluruhan menunjukkan performa yang baik. 
                                Pertahankan dan berikan apresiasi kepada pegawai berprestasi.
                            </p>
                        <?php elseif ($stats['avg_nilai_global'] >= 70): ?>
                            <p style="color: #f39c12;">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>Kinerja Cukup:</strong> Perlu peningkatan di beberapa aspek. 
                                Berikan pelatihan dan bimbingan tambahan.
                            </p>
                        <?php else: ?>
                            <p style="color: #e74c3c;">
                                <i class="fas fa-exclamation-circle"></i> 
                                <strong>Perlu Perhatian Khusus:</strong> Kinerja perlu ditingkatkan secara signifikan. 
                                Evaluasi sistem kerja dan berikan program pengembangan khusus.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($periode_id && !$periode): ?>
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #e74c3c; margin-bottom: 20px;"></i>
                <p>Periode tidak ditemukan</p>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-chart-bar" style="font-size: 48px; color: #ddd; margin-bottom: 20px;"></i>
                <p>Pilih periode untuk melihat laporan</p>
                <?php if ($active_period): ?>
                    <p style="color: #7f8c8d;">
                        Periode aktif saat ini: <strong><?php echo $active_period['nama_periode']; ?></strong>
                    </p>
                    <a href="laporan.php?periode_id=<?php echo $active_period['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-chart-line"></i> Lihat Laporan Periode Aktif
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>