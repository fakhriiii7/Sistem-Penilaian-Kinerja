<?php
require_once '../config/database.php';

// Check if user is an employee
checkRole(['pegawai']);

$db = new Database();
$conn = $db->getConnection();

// Get current user ID from session
$user_id = $_SESSION['user_id'];

// Get employee data
$pegawai_query = "SELECT * FROM pegawai WHERE user_id = '$user_id' LIMIT 1";
$pegawai_result = $conn->query($pegawai_query);

if ($pegawai_result->num_rows === 0) {
    $_SESSION['error'] = "Data pegawai tidak ditemukan";
    header("Location: index.php");
    exit();
}

$pegawai = $pegawai_result->fetch_assoc();
$pegawai_id = $pegawai['id'];

// Get all periods where the employee has been evaluated
$periods_query = "
    SELECT DISTINCT pp.* 
    FROM periode_penilaian pp
    JOIN penilaian p ON pp.id = p.periode_id
    WHERE p.pegawai_id = '$pegawai_id'
    AND p.status = 'selesai'
    ORDER BY pp.tanggal_mulai DESC
";
$periods = $conn->query($periods_query);

// Get selected period or default to the most recent one
$periode_id = isset($_GET['periode_id']) ? $db->escapeString($_GET['periode_id']) : '';

if (!$periode_id && $periods->num_rows > 0) {
    $periods->data_seek(0);
    $periode_id = $periods->fetch_assoc()['id'];
    $periods->data_seek(0); // Reset pointer
}

$penilaian_data = [];
$periode = null;

if ($periode_id) {
    // Get periode data
    $periode_query = "SELECT * FROM periode_penilaian WHERE id = '$periode_id'";
    $periode_result = $conn->query($periode_query);
    $periode = $periode_result->fetch_assoc();
    
    if ($periode) {
        // Get penilaian data for this employee and period
        $penilaian_query = "
            SELECT 
                p.*,
                pl.nama_penilai,
                pl.jabatan as jabatan_penilai,
                (SELECT AVG(nilai) FROM detail_penilaian WHERE penilaian_id = p.id) as rata_rata_nilai
            FROM penilaian p
            JOIN penilai pl ON p.penilai_id = pl.id
            WHERE p.pegawai_id = '$pegawai_id'
            AND p.periode_id = '$periode_id'
            AND p.status = 'selesai'
            ORDER BY p.tanggal_penilaian DESC
            LIMIT 1
        ";
        $penilaian_result = $conn->query($penilaian_query);
        
        if ($penilaian_result->num_rows > 0) {
            $penilaian = $penilaian_result->fetch_assoc();
            $penilaian_id = $penilaian['id'];
            
            // Get detail penilaian (kriteria)
            $detail_query = "
                SELECT 
                    d.*,
                    k.nama_kriteria,
                    k.bobot,
                    (d.nilai * k.bobot / 100) as nilai_terbobot
                FROM detail_penilaian d
                JOIN kriteria k ON d.kriteria_id = k.id
                WHERE d.penilaian_id = '$penilaian_id'
                ORDER BY k.id
            ";
            $detail_result = $conn->query($detail_query);
            
            // Calculate total nilai terbobot
            $total_nilai_terbobot = 0;
            $penilaian_data = [];
            
            while ($row = $detail_result->fetch_assoc()) {
                $penilaian_data[] = $row;
                $total_nilai_terbobot += $row['nilai_terbobot'];
            }
            
            // Add total to penilaian data
            $penilaian['total_nilai_terbobot'] = $total_nilai_terbobot;
            
            // Determine grade
            if ($total_nilai_terbobot >= 90) {
                $penilaian['grade'] = 'A (Sangat Baik)';
                $penilaian['grade_class'] = 'grade-a';
            } elseif ($total_nilai_terbobot >= 80) {
                $penilaian['grade'] = 'B (Baik)';
                $penilaian['grade_class'] = 'grade-b';
            } elseif ($total_nilai_terbobot >= 70) {
                $penilaian['grade'] = 'C (Cukup)';
                $penilaian['grade_class'] = 'grade-c';
            } elseif ($total_nilai_terbobot >= 60) {
                $penilaian['grade'] = 'D (Kurang)';
                $penilaian['grade_class'] = 'grade-d';
            } else {
                $penilaian['grade'] = 'E (Sangat Kurang)';
                $penilaian['grade_class'] = 'grade-e';
            }
        }
    }
}

$page_title = "Laporan Penilaian Saya";
require_once '../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Laporan Penilaian Kinerja Saya</h2>
    </div>
    <div class="card-body">
        <!-- Filter Section -->
        <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h4 style="margin-bottom: 15px;">Pilih Periode Penilaian</h4>
            <form method="GET" action="" style="display: flex; gap: 15px; align-items: flex-end;">
                <div style="flex: 1;">
                    <label>Periode Penilaian</label>
                    <select name="periode_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Pilih Periode --</option>
                        <?php if ($periods->num_rows > 0): ?>
                            <?php while ($row = $periods->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>" <?php echo ($periode_id == $row['id']) ? 'selected' : ''; ?>>
                                    <?php echo $row['nama_periode'] . ' (' . date('d M Y', strtotime($row['tanggal_mulai'])) . ' - ' . date('d M Y', strtotime($row['tanggal_selesai'])) . ')' ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Tampilkan</button>
                <?php if ($periode_id && !empty($penilaian)): ?>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Cetak Laporan
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($periode && !empty($penilaian)): ?>
            <!-- Report Header -->
        <div id="print-area">
            <div style="text-align: center; margin-bottom: 30px; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2>LAPORAN HASIL PENILAIAN KINERJA</h2>
                <h3><?php echo $periode['nama_periode']; ?></h3>
                <p>Periode: <?php echo date('d F Y', strtotime($periode['tanggal_mulai'])) . ' - ' . date('d F Y', strtotime($periode['tanggal_selesai'])); ?></p>
                <p>Dicetak pada: <?php echo date('d F Y H:i:s'); ?></p>
            </div>

            <!-- Employee Info -->
            <div style="margin-bottom: 30px; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;">Identitas Pegawai</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <p style="margin: 5px 0;"><strong>Nama Lengkap:</strong> <?php echo htmlspecialchars($pegawai['nama_lengkap']); ?></p>
                        <p style="margin: 5px 0;"><strong>NIP:</strong> <?php echo isset($pegawai['nip']) ? htmlspecialchars($pegawai['nip']) : '-'; ?></p>
                        <p style="margin: 5px 0;"><strong>Jabatan:</strong> <?php echo isset($pegawai['jabatan']) ? htmlspecialchars($pegawai['jabatan']) : '-'; ?></p>
                    </div>
                    <div>
                        <p style="margin: 5px 0;"><strong>Unit Kerja:</strong> <?php echo isset($pegawai['unit_kerja']) ? htmlspecialchars($pegawai['unit_kerja']) : '-'; ?></p>
                        <p style="margin: 5px 0;"><strong>Pangkat/Golongan:</strong> <?php echo isset($pegawai['pangkat_golongan']) ? htmlspecialchars($pegawai['pangkat_golongan']) : '-'; ?></p>
                    </div>
                </div>
            </div>

            <!-- Penilai Info -->
            <div style="margin-bottom: 30px; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;">Penilai</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <p style="margin: 5px 0;"><strong>Nama Penilai:</strong> <?php echo htmlspecialchars($penilaian['nama_penilai']); ?></p>
                        <p style="margin: 5px 0;"><strong>Jabatan:</strong> <?php echo htmlspecialchars($penilaian['jabatan_penilai']); ?></p>
                    </div>
                    <div>
                        <p style="margin: 5px 0;"><strong>Tanggal Penilaian:</strong> <?php echo date('d F Y', strtotime($penilaian['tanggal_penilaian'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div style="background: #e3f2fd; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3 style="margin: 0 0 10px 0; color: #1976d2;"><?php echo number_format($penilaian['total_nilai_terbobot'], 2); ?></h3>
                    <p style="margin: 0; color: #555;">Total Nilai Akhir</p>
                </div>
                <div style="background: #e8f5e9; padding: 20px; border-radius: 10px; text-align: center;">
                    <h3 style="margin: 0 0 10px 0; color: #2e7d32;" class="<?php echo $penilaian['grade_class']; ?>">
                        <?php echo $penilaian['grade']; ?>
                    </h3>
                    <p style="margin: 0; color: #555;">Predikat</p>
                </div>
            </div>

            <!-- Detail Penilaian -->
            <div style="margin-bottom: 30px;">
                <h3 style="margin-bottom: 20px;">Detail Penilaian</h3>
                <div class="table-responsive" style="width:100%">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kriteria Penilaian</th>
                                <th>Bobot</th>
                                <th>Nilai</th>
                                <th>Nilai Terbobot</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($penilaian_data as $row): 
                            ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_kriteria']); ?></td>
                                    <td style="text-align: center;"><?php echo $row['bobot']; ?>%</td>
                                    <td style="text-align: center;"><?php echo number_format($row['nilai'], 2); ?></td>
                                    <td style="text-align: center;"><?php echo number_format($row['nilai_terbobot'], 2); ?></td>
                                    <td><?php echo !empty($row['keterangan']) ? nl2br(htmlspecialchars($row['keterangan'])) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <!-- Total Row -->
                            <tr style="font-weight: bold; background-color: #f5f5f5;">
                                <td colspan="3"></td>
                                <td style="text-align: center;">Total</td>
                                <td style="text-align: center;"><?php echo number_format($penilaian['total_nilai_terbobot'], 2); ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Feedback and Notes -->
            <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;">Catatan dan Saran</h3>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; min-height: 100px;">
                    <?php if (!empty($penilaian['catatan'])): ?>
                        <?php echo nl2br(htmlspecialchars($penilaian['catatan'])); ?>
                    <?php else: ?>
                        <p style="color: #7f8c8d; font-style: italic;">Tidak ada catatan tambahan</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($periode_id && empty($penilaian)): ?>
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #e74c3c; margin-bottom: 20px;"></i>
                <p>Data penilaian untuk periode ini tidak ditemukan</p>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-chart-pie" style="font-size: 48px; color: #7f8c8d; margin-bottom: 20px;"></i>
                <p>Silakan pilih periode penilaian untuk melihat laporan</p>
                <?php if ($periods->num_rows === 0): ?>
                    <p class="mt-3">Anda belum memiliki riwayat penilaian</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    </div>
</div>

<style>
    .grade-a { color: #27ae60; }
    .grade-b { color: #2980b9; }
    .grade-c { color: #f39c12; }
    .grade-d { color: #e67e22; }
    .grade-e { color: #e74c3c; }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
        background-color: transparent;
    }
    
    .data-table th,
    .data-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }
    
    .data-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    
    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    @media (max-width: 768px) {
        .data-table {
            display: block;
            overflow-x: auto;
        }
    }
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

@media print {

    /* Paksa tabel full halaman */
    .table-responsive {
        overflow: visible !important;
    }

    .data-table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed;
    }

    .data-table th,
    .data-table td {
        word-wrap: break-word;
        white-space: normal;
    }

}
</style>

<?php require_once '../includes/footer.php'; ?>
