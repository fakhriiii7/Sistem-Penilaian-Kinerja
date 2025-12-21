<?php
require_once '../config/database.php';

// Check if user is a leader
checkRole(['pimpinan']);

$db = new Database();
$conn = $db->getConnection();

$action = isset($_GET['action']) ? $db->escapeString($_GET['action']) : '';
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
    
    if ($periode) {
        // Get statistics for the period
        $stats_query = "
            SELECT 
                COUNT(DISTINCT pn.id) as total_penilaian,
                COUNT(DISTINCT pn.pegawai_id) as total_pegawai,
                AVG(d.nilai) as avg_nilai_global,
                MIN(d.nilai) as min_nilai,
                MAX(d.nilai) as max_nilai
            FROM penilaian pn
            JOIN detail_penilaian d ON pn.id = d.penilaian_id
            WHERE pn.periode_id = '$periode_id'
            AND pn.status = 'selesai'
        ";
        $stats_result = $conn->query($stats_query);
        $stats = $stats_result->fetch_assoc();

        // Get grade distribution
        $grade_query = "
            SELECT 
                CASE 
                    WHEN d.nilai >= 90 THEN 'A (Sangat Baik)'
                    WHEN d.nilai >= 80 THEN 'B (Baik)'
                    WHEN d.nilai >= 70 THEN 'C (Cukup)'
                    WHEN d.nilai >= 60 THEN 'D (Kurang)'
                    ELSE 'E (Sangat Kurang)'
                END as grade,
                COUNT(*) as jumlah,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM detail_penilaian dn JOIN penilaian pn ON dn.penilaian_id = pn.id WHERE pn.periode_id = '$periode_id'), 2) as persentase
            FROM detail_penilaian d
            JOIN penilaian pn ON d.penilaian_id = pn.id
            WHERE pn.periode_id = '$periode_id'
            AND pn.status = 'selesai'
            GROUP BY grade
            ORDER BY grade
        ";
        $grade_result = $conn->query($grade_query);

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
    }
}

$page_title = "Laporan Penilaian";
require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Laporan Penilaian Kinerja</h2>
    </div>
    <div class="card-body">
        <!-- Filter Section -->
        <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h4 style="margin-bottom: 15px;">Pilih Periode Laporan</h4>
            <form method="GET" action="" style="display: flex; gap: 15px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label>Periode Penilaian</label>
                    <select name="periode_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Pilih Periode --</option>
                        <?php while ($row = $periods->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>" <?php echo ($periode_id == $row['id']) ? 'selected' : ''; ?>>
                                <?php echo $row['nama_periode'] . ' (' . date('d M Y', strtotime($row['tanggal_mulai'])) . ' - ' . date('d M Y', strtotime($row['tanggal_selesai'])) . ')' ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Tampilkan</button>
                <?php if ($periode_id): ?>
                    <button onclick="window.print()" class="btn btn-secondary" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($periode && isset($stats)): ?>
            <!-- Report Header -->
        <div id="print-area">
            <div class="print-title" style="text-align: center; margin-bottom: 30px; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2>LAPORAN PENILAIAN KINERJA PEGAWAI</h2>
                <h3><?php echo $periode['nama_periode']; ?></h3>
                <p>Periode: <?php echo date('d F Y', strtotime($periode['tanggal_mulai'])) . ' - ' . date('d F Y', strtotime($periode['tanggal_selesai'])); ?></p>
                <p>Dicetak pada: <?php echo date('d F Y H:i:s'); ?></p>
            </div>

            <!-- Summary Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3 style="margin: 0 0 10px 0; color: #1976d2;"><?php echo $stats['total_penilaian']; ?></h3>
                    <p style="margin: 0; color: #555;">Total Penilaian</p>
                </div>
                <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3 style="margin: 0 0 10px 0; color: #2e7d32;"><?php echo $stats['total_pegawai']; ?></h3>
                    <p style="margin: 0; color: #555;">Total Pegawai Dinilai</p>
                </div>
                <div style="background: #fff3e0; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3 style="margin: 0 0 10px 0; color: #ef6c00;"><?php echo number_format($stats['avg_nilai_global'], 2); ?></h3>
                    <p style="margin: 0; color: #555;">Rata-rata Nilai</p>
                </div>
                <div style="background: #f3e5f5; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3 style="margin: 0 0 10px 0; color: #7b1fa2;"><?php echo $stats['min_nilai']; ?> - <?php echo $stats['max_nilai']; ?></h3>
                    <p style="margin: 0; color: #555;">Rentang Nilai</p>
                </div>
            </div>

            <!-- Grade Distribution -->
            <?php if ($grade_result && $grade_result->num_rows > 0): ?>
                <div style="margin-bottom: 30px;">
                    <h3 style="margin-bottom: 20px;">Distribusi Nilai</h3>
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background-color: #f5f5f5;">
                                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #ddd;">Grade</th>
                                    <th style="padding: 12px; text-align: right; border-bottom: 1px solid #ddd;">Jumlah</th>
                                    <th style="padding: 12px; text-align: right; border-bottom: 1px solid #ddd;">Persentase</th>
                                    <th style="padding: 12px; width: 50%; border-bottom: 1px solid #ddd;">Visual</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $grade_result->fetch_assoc()): ?>
                                    <tr>
                                        <td style="padding: 12px; border-bottom: 1px solid #eee;"><?php echo $row['grade']; ?></td>
                                        <td style="padding: 12px; text-align: right; border-bottom: 1px solid #eee;"><?php echo $row['jumlah']; ?></td>
                                        <td style="padding: 12px; text-align: right; border-bottom: 1px solid #eee;"><?php echo $row['persentase']; ?>%</td>
                                        <td style="padding: 12px; border-bottom: 1px solid #eee;">
                                            <div style="background-color: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden;">
                                                <div style="background-color: #4caf50; width: <?php echo $row['persentase']; ?>%; height: 100%;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Detail Penilaian -->
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
                                    <th>No</th>
                                    <th>Nama Pegawai</th>
                                    <th>Jabatan</th>
                                    <th>Unit Kerja</th>
                                    <th>Penilai</th>
                                    <th>Rata-rata Nilai</th>
                                    <th>Grade</th>
                                    <th>Tanggal Penilaian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while ($row = $penilaian_result->fetch_assoc()): 
                                    $grade = '';
                                    $grade_class = '';
                                    if ($row['rata_rata_nilai'] >= 90) {
                                        $grade = 'A (Sangat Baik)';
                                        $grade_class = 'grade-a';
                                    } elseif ($row['rata_rata_nilai'] >= 80) {
                                        $grade = 'B (Baik)';
                                        $grade_class = 'grade-b';
                                    } elseif ($row['rata_rata_nilai'] >= 70) {
                                        $grade = 'C (Cukup)';
                                        $grade_class = 'grade-c';
                                    } elseif ($row['rata_rata_nilai'] >= 60) {
                                        $grade = 'D (Kurang)';
                                        $grade_class = 'grade-d';
                                    } else {
                                        $grade = 'E (Sangat Kurang)';
                                        $grade_class = 'grade-e';
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_pegawai']); ?></td>
                                        <td><?php echo htmlspecialchars($row['jabatan_pegawai']); ?></td>
                                        <td><?php echo htmlspecialchars($row['unit_kerja']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_penilai']); ?></td>
                                        <td style="text-align: center;"><?php echo number_format($row['rata_rata_nilai'], 2); ?></td>
                                        <td style="text-align: center;" class="<?php echo $grade_class; ?>">
                                            <?php echo $grade; ?>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($row['tanggal_penilaian'])); ?></td>
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
                
                <div style="margin-bottom: 20px;">
                    <h4>Ringkasan Kinerja</h4>
                    <p>Berdasarkan hasil penilaian kinerja periode <?php echo $periode['nama_periode']; ?>:</p>
                    <ul>
                        <li>Rata-rata nilai keseluruhan adalah <strong><?php echo number_format($stats['avg_nilai_global'], 2); ?></strong> dari skala 100.</li>
                        <li>Nilai tertinggi yang dicapai adalah <strong><?php echo $stats['max_nilai']; ?></strong> dan nilai terendah adalah <strong><?php echo $stats['min_nilai']; ?></strong>.</li>
                        <li>Total pegawai yang dinilai sebanyak <strong><?php echo $stats['total_pegawai']; ?> orang</strong>.</li>
                    </ul>
                </div>

                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                    <h4 style="margin-bottom: 15px;">Rekomendasi</h4>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                        <?php if ($stats['avg_nilai_global'] >= 80): ?>
                            <p style="color: #27ae60;">
                                <i class="fas fa-check-circle"></i> Secara keseluruhan, kinerja pegawai pada periode ini sangat baik. 
                                Pertahankan dan tingkatkan terus kualitas kerja tim.
                            </p>
                        <?php elseif ($stats['avg_nilai_global'] >= 70): ?>
                            <p style="color: #f39c12;">
                                <i class="fas fa-info-circle"></i> Kinerja pegawai pada periode ini cukup baik. 
                                Perlu peningkatan pada beberapa aspek untuk mencapai hasil yang lebih optimal.
                            </p>
                        <?php else: ?>
                            <p style="color: #e74c3c;">
                                <i class="fas fa-exclamation-circle"></i> Kinerja pegawai pada periode ini perlu perhatian khusus. 
                                Disarankan untuk melakukan evaluasi menyeluruh dan memberikan pembinaan lebih intensif.
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
                <i class="fas fa-chart-pie" style="font-size: 48px; color: #7f8c8d; margin-bottom: 20px;"></i>
                <p>Silakan pilih periode penilaian untuk melihat laporan</p>
            </div>
        <?php endif; ?>
    </div>
    </div>
</div>

<style>
    .grade-a { color: #27ae60; font-weight: bold; }
    .grade-b { color: #2980b9; font-weight: bold; }
    .grade-c { color: #f39c12; font-weight: bold; }
    .grade-d { color: #e67e22; font-weight: bold; }
    .grade-e { color: #e74c3c; font-weight: bold; }
</style>

<style>
@media print {

    /* SEMBUNYIKAN SEMUA */
    body * {
        visibility: hidden;
    }

    /* TAMPILKAN LAPORAN SAJA */
    #print-area,
    #print-area * {
        visibility: visible;
    }

    /* POSISI LAPORAN (HILANGKAN SPACE KIRI) */
    #print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    /* BODY TANPA MENGUBAH STYLE */
    body {
        zoom: 0.7;
        margin: 0;
        padding: 0;
    }

    /* CARD TETAP */
    .card,
    .card-body {
        box-shadow: none; /* hanya shadow, warna tetap */
    }

    /* HILANGKAN TOMBOL SAAT PRINT */
    .btn,
    form {
        display: none !important;
    }

    /* PAGE */
    @page {
        size: A4;
        margin: 15mm;
    }
}
</style>


<?php require_once '../includes/footer.php'; ?>
