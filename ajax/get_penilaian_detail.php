<?php
require_once '../config/database.php';

checkRole(['admin', 'pimpinan']);

$db = new Database();
$conn = $db->getConnection();

$id = isset($_GET['id']) ? $db->escapeString($_GET['id']) : '';

if (!$id) {
    echo '<div style="text-align: center; padding: 20px; color: #e74c3c;">
            <i class="fas fa-exclamation-circle"></i> ID tidak valid
          </div>';
    exit();
}

// Get penilaian data
$query = "
    SELECT 
        pn.*,
        pg.nama_lengkap as nama_pegawai,
        pg.jabatan as jabatan_pegawai,
        pg.unit_kerja,
        pl.nama_penilai,
        pl.jabatan as jabatan_penilai,
        pr.nama_periode,
        pr.tanggal_mulai,
        pr.tanggal_selesai
    FROM penilaian pn
    JOIN pegawai pg ON pn.pegawai_id = pg.id
    JOIN penilai pl ON pn.penilai_id = pl.id
    JOIN periode_penilaian pr ON pn.periode_id = pr.id
    WHERE pn.id = '$id'
";

$result = $conn->query($query);
$penilaian = $result->fetch_assoc();

if (!$penilaian) {
    echo '<div style="text-align: center; padding: 20px; color: #e74c3c;">
            <i class="fas fa-exclamation-circle"></i> Data tidak ditemukan
          </div>';
    exit();
}

// Get detail criteria
$detail_query = "
    SELECT 
        d.*,
        k.kode_kriteria,
        k.nama_kriteria,
        k.bobot,
        k.deskripsi
    FROM detail_penilaian d
    JOIN kriteria k ON d.kriteria_id = k.id
    WHERE d.penilaian_id = '$id'
    ORDER BY k.bobot DESC
";

$detail_result = $conn->query($detail_query);

// Calculate average
$avg_query = "SELECT AVG(nilai) as rata_rata FROM detail_penilaian WHERE penilaian_id = '$id'";
$avg_result = $conn->query($avg_query);
$rata_rata = $avg_result->fetch_assoc()['rata_rata'] ?? 0;

// Determine grade
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

<div style="max-width: 800px; margin: 0 auto;">
    <!-- Header Info -->
    <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f1f1f1;">
        <h2 style="color: #2c3e50; margin-bottom: 10px;">Detail Penilaian</h2>
        <div style="display: flex; justify-content: center; gap: 20px; margin-top: 15px;">
            <div>
                <small style="color: #7f8c8d;">Periode</small>
                <p style="font-weight: bold; margin: 5px 0;"><?php echo $penilaian['nama_periode']; ?></p>
            </div>
            <div>
                <small style="color: #7f8c8d;">Tanggal</small>
                <p style="font-weight: bold; margin: 5px 0;"><?php echo date('d F Y', strtotime($penilaian['tanggal_penilaian'])); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Pegawai dan Penilai Info -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
        <div style="padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h4 style="color: #3498db; margin-bottom: 15px;">
                <i class="fas fa-user"></i> Pegawai
            </h4>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 8px 0; color: #7f8c8d;">Nama</td>
                    <td style="padding: 8px 0; font-weight: bold;"><?php echo $penilaian['nama_pegawai']; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #7f8c8d;">Jabatan</td>
                    <td style="padding: 8px 0;"><?php echo $penilaian['jabatan_pegawai']; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #7f8c8d;">Unit Kerja</td>
                    <td style="padding: 8px 0;"><?php echo $penilaian['unit_kerja']; ?></td>
                </tr>
            </table>
        </div>
        
        <div style="padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h4 style="color: #3498db; margin-bottom: 15px;">
                <i class="fas fa-user-tie"></i> Penilai
            </h4>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 8px 0; color: #7f8c8d;">Nama</td>
                    <td style="padding: 8px 0; font-weight: bold;"><?php echo $penilaian['nama_penilai']; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #7f8c8d;">Jabatan</td>
                    <td style="padding: 8px 0;"><?php echo $penilaian['jabatan_penilai']; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; color: #7f8c8d;">Status</td>
                    <td style="padding: 8px 0;">
                        <span style="padding: 5px 15px; background: <?php echo $penilaian['status'] == 'selesai' ? '#d4edda' : '#fff3cd'; ?>; 
                              color: <?php echo $penilaian['status'] == 'selesai' ? '#155724' : '#856404'; ?>; 
                              border-radius: 20px; font-size: 12px;">
                            <?php echo $penilaian['status'] == 'selesai' ? 'Selesai' : 'Draft'; ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <!-- Summary Score -->
    <div style="text-align: center; margin-bottom: 30px; padding: 25px; background: linear-gradient(135deg, <?php echo $grade_color; ?>20 0%, <?php echo $grade_color; ?>10 100%); border-radius: 15px;">
        <h3 style="color: #2c3e50; margin-bottom: 15px;">Ringkasan Nilai</h3>
        <div style="display: flex; justify-content: center; align-items: center; gap: 40px;">
            <div style="text-align: center;">
                <div style="font-size: 48px; font-weight: bold; color: <?php echo $grade_color; ?>;">
                    <?php echo number_format($rata_rata, 1); ?>
                </div>
                <div style="color: #7f8c8d;">Rata-rata</div>
            </div>
            <div style="width: 2px; height: 80px; background: #ddd;"></div>
            <div style="text-align: center;">
                <div style="font-size: 48px; font-weight: bold; color: <?php echo $grade_color; ?>;">
                    <?php echo $grade; ?>
                </div>
                <div style="color: #7f8c8d;">Grade</div>
            </div>
        </div>
    </div>
    
    <!-- Detail Kriteria -->
    <?php if ($detail_result->num_rows > 0): ?>
        <div style="margin-bottom: 30px;">
            <h3 style="color: #2c3e50; margin-bottom: 20px;">
                <i class="fas fa-list-alt"></i> Detail Kriteria Penilaian
            </h3>
            
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="padding: 12px; text-align: left;">Kriteria</th>
                            <th style="padding: 12px; text-align: center;">Bobot</th>
                            <th style="padding: 12px; text-align: center;">Nilai</th>
                            <th style="padding: 12px; text-align: center;">Grade</th>
                            <th style="padding: 12px; text-align: left;">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($detail = $detail_result->fetch_assoc()): 
                            $detail_color = '';
                            if ($detail['nilai'] >= 90) $detail_color = '#27ae60';
                            elseif ($detail['nilai'] >= 80) $detail_color = '#3498db';
                            elseif ($detail['nilai'] >= 70) $detail_color = '#f39c12';
                            elseif ($detail['nilai'] >= 60) $detail_color = '#e67e22';
                            else $detail_color = '#e74c3c';
                            
                            $detail_grade = '';
                            if ($detail['nilai'] >= 90) $detail_grade = 'A';
                            elseif ($detail['nilai'] >= 80) $detail_grade = 'B';
                            elseif ($detail['nilai'] >= 70) $detail_grade = 'C';
                            elseif ($detail['nilai'] >= 60) $detail_grade = 'D';
                            else $detail_grade = 'E';
                        ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 12px;">
                                    <div style="font-weight: 500; margin-bottom: 5px;"><?php echo $detail['nama_kriteria']; ?></div>
                                    <?php if ($detail['deskripsi']): ?>
                                        <div style="font-size: 12px; color: #7f8c8d;"><?php echo $detail['deskripsi']; ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span style="padding: 5px 10px; background: #3498db; color: white; border-radius: 3px; font-weight: bold;">
                                        <?php echo $detail['bobot']; ?>%
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span style="font-weight: bold; color: <?php echo $detail_color; ?>;">
                                        <?php echo number_format($detail['nilai'], 1); ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <span style="display: inline-block; width: 30px; height: 30px; background: <?php echo $detail_color; ?>; 
                                          color: white; border-radius: 50%; line-height: 30px; font-weight: bold;">
                                        <?php echo $detail_grade; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px;">
                                    <?php echo $detail['catatan'] ? nl2br(htmlspecialchars($detail['catatan'])) : '<span style="color: #7f8c8d;">-</span>'; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 10px; margin-bottom: 30px;">
            <i class="fas fa-info-circle" style="font-size: 36px; color: #7f8c8d; margin-bottom: 15px;"></i>
            <p style="color: #7f8c8d;">Belum ada detail penilaian</p>
        </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <div style="text-align: center; padding-top: 20px; border-top: 1px solid #eee;">
        <small style="color: #7f8c8d;">
            <i class="fas fa-clock"></i> Dicetak pada: <?php echo date('d F Y H:i:s'); ?>
        </small>
    </div>
</div>