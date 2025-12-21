<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/* =====================
   AUTH
===================== */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../unauthorized.php");
    exit();
}

/* =====================
   VALIDASI ID
===================== */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: penilaian.php");
    exit();
}

$db   = new Database();
$conn = $db->getConnection();
$id   = $db->escapeString($_GET['id']);

/* =====================
   DATA PENILAIAN
===================== */
$query = "
SELECT 
    pn.*,
    pg.nip,
    pg.nama_lengkap AS nama_pegawai,
    pg.jabatan AS jabatan_pegawai,
    pg.unit_kerja,
    pl.nama_penilai,
    pl.jabatan AS jabatan_penilai,
    pr.nama_periode
FROM penilaian pn
JOIN pegawai pg ON pn.pegawai_id = pg.id
JOIN penilai pl ON pn.penilai_id = pl.id
JOIN periode_penilaian pr ON pn.periode_id = pr.id
WHERE pn.id = '$id'
";

$result = $conn->query($query);
if ($result->num_rows === 0) {
    die("Data penilaian tidak ditemukan");
}
$penilaian = $result->fetch_assoc();

/* =====================
   DETAIL PENILAIAN
===================== */
$detail_query = "
SELECT 
    dp.nilai,
    dp.catatan,
    k.nama_kriteria,
    k.deskripsi,
    k.bobot
FROM detail_penilaian dp
JOIN kriteria k ON dp.kriteria_id = k.id
WHERE dp.penilaian_id = '$id'
ORDER BY k.id
";

$detail_result = $conn->query($detail_query);

$detail       = [];
$total_nilai  = 0;
$total_bobot  = 0;

while ($row = $detail_result->fetch_assoc()) {
    $nilai = floatval($row['nilai']);
    $bobot = floatval($row['bobot']);

    $row['nilai_akhir'] = $nilai * $bobot;
    $detail[] = $row;

    $total_nilai += $nilai * $bobot;
    $total_bobot += $bobot;
}

$rata_rata = $total_bobot > 0 ? $total_nilai / $total_bobot : 0;

/* =====================
   FUNGSI GRADE
===================== */
function getGrade($score) {
    if ($score >= 85) return ['grade'=>'A','label'=>'Sangat Baik','color'=>'#27ae60'];
    if ($score >= 70) return ['grade'=>'B','label'=>'Baik','color'=>'#2ecc71'];
    if ($score >= 55) return ['grade'=>'C','label'=>'Cukup','color'=>'#f39c12'];
    if ($score >= 40) return ['grade'=>'D','label'=>'Kurang','color'=>'#e67e22'];
    return ['grade'=>'E','label'=>'Sangat Kurang','color'=>'#e74c3c'];
}

$grade = getGrade($rata_rata);

$title = "Detail Penilaian - " . $penilaian['nama_pegawai'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="penilaian.php" class="btn btn-primary shadow-sm px-4 py-2">
        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar Penilaian
    </a>
</div>

<div class="container-fluid">

    <div class="text-center mb-4">
        <h4 class="font-weight-bold">LAPORAN HASIL PENILAIAN KINERJA</h4>
        <h5><?= strtoupper($penilaian['nama_periode']) ?></h5>
    </div>

    <!-- DATA PEGAWAI -->
    <div class="row mb-4">
        <div class="col-md-6">
            <table class="table table-bordered">
                <tr><th>Nama</th><td><?= htmlspecialchars($penilaian['nama_pegawai']) ?></td></tr>
                <tr><th>NIP</th><td><?= htmlspecialchars($penilaian['nip']) ?></td></tr>
                <tr><th>Jabatan</th><td><?= htmlspecialchars($penilaian['jabatan_pegawai']) ?></td></tr>
                <tr><th>Unit Kerja</th><td><?= htmlspecialchars($penilaian['unit_kerja']) ?></td></tr>
            </table>
        </div>
        <div class="col-md-6">
            <table class="table table-bordered">
                <tr><th>Penilai</th><td><?= htmlspecialchars($penilaian['nama_penilai']) ?></td></tr>
                <tr><th>Jabatan Penilai</th><td><?= htmlspecialchars($penilaian['jabatan_penilai']) ?></td></tr>
                <tr><th>Tanggal</th><td><?= date('d F Y', strtotime($penilaian['tanggal_penilaian'])) ?></td></tr>
                <tr><th>Status</th><td><?= strtoupper($penilaian['status']) ?></td></tr>
            </table>
        </div>
    </div>

    <!-- REKAP NILAI -->
    <div class="card mb-4">
        <div class="card-header font-weight-bold">REKAP NILAI AKHIR</div>
        <div class="card-body">
            <table class="table table-bordered text-center">
                <tr>
                    <th>Nilai Akhir</th>
                    <th>Grade</th>
                    <th>Keterangan</th>
                </tr>
                <tr>
                    <td><?= number_format($rata_rata, 2) ?></td>
                    <td><?= $grade['grade'] ?></td>
                    <td><?= $grade['label'] ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- DETAIL NILAI -->
    <div class="card mb-4">
        <div class="card-header font-weight-bold">DETAIL PENILAIAN</div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="text-center">
                    <tr>
                        <th>No</th>
                        <th>Kriteria</th>
                        <th>Bobot (%)</th>
                        <th>Nilai</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($detail as $i => $d): 
                    $g = getGrade($d['nilai']);
                ?>
                    <tr>
                        <td class="text-center"><?= $i+1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($d['nama_kriteria']) ?></strong><br>
                            <small><?= htmlspecialchars($d['deskripsi']) ?></small>
                        </td>
                        <td class="text-center"><?= $d['bobot'] ?></td>
                        <td class="text-center"><?= number_format($d['nilai'],2) ?></td>
                        <td class="text-center">
                            <span class="badge" style="background:<?= $g['color'] ?>;color:white">
                                <?= $g['grade'] ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- CATATAN PENILAI -->
<div class="card mt-4">
    <div class="card-header font-weight-bold">
        CATATAN PENILAI
    </div>
    <div class="card-body">
        <?php if (!empty(trim($penilaian['catatan']))): ?>
            <?= nl2br(htmlspecialchars($penilaian['catatan'])) ?>
        <?php else: ?>
            <em class="text-muted">Belum ada catatan dari penilai.</em>
        <?php endif; ?>
    </div>
</div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
