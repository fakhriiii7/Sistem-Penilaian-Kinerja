<?php
require_once '../config/database.php';

// Cek role pimpinan
checkRole(['pimpinan']);

$db   = new Database();
$conn = $db->getConnection();

$action = $_GET['action'] ?? '';
$id     = $_GET['id'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Ambil data penilai
$user_id = $_SESSION['user_id'];
$penilai = $conn->query(
    "SELECT * FROM penilai WHERE user_id = '$user_id'"
)->fetch_assoc();

// Ambil periode aktif
$active_period = $conn->query("
    SELECT * FROM periode_penilaian
    WHERE status = 'aktif'
    ORDER BY id DESC LIMIT 1
")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_penilaian'])) {

    $pegawai_id = $_POST['pegawai_id'];
    $periode_id = $_POST['periode_id'];
    $tanggal    = $_POST['tanggal_penilaian'];
    $status     = $_POST['status'];

    $cek = $conn->query("
        SELECT id FROM penilaian
        WHERE pegawai_id='$pegawai_id'
        AND periode_id='$periode_id'
        AND penilai_id='{$penilai['id']}'
    ");

    if ($cek->num_rows > 0) {
        flashMessage('error', 'Penilaian sudah ada!');
    } else {
        $conn->query("
            INSERT INTO penilaian
            (periode_id, pegawai_id, penilai_id, tanggal_penilaian, status)
            VALUES
            ('$periode_id','$pegawai_id','{$penilai['id']}','$tanggal','$status')
        ");

        $penilaian_id = $conn->insert_id;

        if ($status === 'selesai') {
            redirect("penilaian.php?action=isi_nilai&id=$penilaian_id");
        } else {
            redirect("penilaian.php?action=edit&id=$penilaian_id");
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_nilai'])) {

    $penilaian_id = $_POST['penilaian_id'];

    // Hapus nilai lama
    $conn->query("DELETE FROM detail_penilaian WHERE penilaian_id='$penilaian_id'");

    foreach ($_POST['nilai'] as $kriteria_id => $nilai) {
        $catatan = $_POST['catatan'][$kriteria_id] ?? '';

        $conn->query("
            INSERT INTO detail_penilaian
            (penilaian_id, kriteria_id, nilai, catatan)
            VALUES
            ('$penilaian_id','$kriteria_id','$nilai','$catatan')
        ");
    }

    // HANYA update status
    $conn->query("
        UPDATE penilaian
        SET status='selesai'
        WHERE id='$penilaian_id'
    ");

    flashMessage('success', 'Penilaian berhasil disimpan');
    redirect('penilaian.php');

    // Update status penilaian saja (nilai dihitung dinamis)
    if ($success) {

        $update_query = "
        UPDATE penilaian 
        SET status = 'selesai'
        WHERE id = '$penilaian_id'
    ";
        $conn->query($update_query);

        flashMessage('success', 'Penilaian berhasil disimpan!');
        redirect('penilaian.php');
    } else {
        flashMessage('error', 'Gagal menyimpan nilai: ' . $conn->error);
    }
}

if ($action === 'delete' && $id) {

    $cek = $conn->query("
        SELECT id FROM penilaian
        WHERE id='$id' AND penilai_id='{$penilai['id']}'
    ");

    if ($cek->num_rows === 0) {
        flashMessage('error', 'Tidak punya akses');
    } else {
        $conn->query("DELETE FROM detail_penilaian WHERE penilaian_id='$id'");
        $conn->query("DELETE FROM penilaian WHERE id='$id'");
        flashMessage('success', 'Penilaian dihapus');
    }

    redirect('penilaian.php');
}

if ($action === 'delete' && $id) {

    $cek = $conn->query("
        SELECT id FROM penilaian
        WHERE id='$id' AND penilai_id='{$penilai['id']}'
    ");

    if ($cek->num_rows === 0) {
        flashMessage('error', 'Tidak punya akses');
    } else {
        $conn->query("DELETE FROM detail_penilaian WHERE penilaian_id='$id'");
        $conn->query("DELETE FROM penilaian WHERE id='$id'");
        flashMessage('success', 'Penilaian dihapus');
    }

    redirect('penilaian.php');
}

// Get data for edit or view
$penilaian_data = null;
$detail_data = [];
if (($action == 'edit' || $action == 'view' || $action == 'isi_nilai') && $id) {
    // Check ownership
    $check_query = "SELECT pn.*, pg.nama_lengkap as nama_pegawai, pg.jabatan as jabatan_pegawai, 
                           pr.nama_periode, pr.tanggal_mulai, pr.tanggal_selesai
                    FROM penilaian pn
                    JOIN pegawai pg ON pn.pegawai_id = pg.id
                    JOIN periode_penilaian pr ON pn.periode_id = pr.id
                    WHERE pn.id = '$id' AND pn.penilai_id = '{$penilai['id']}'";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows > 0) {
        $penilaian_data = $check_result->fetch_assoc();

        // Get detail penilaian if exists
        if ($action == 'edit' || $action == 'isi_nilai') {
            $detail_query = "SELECT d.*, k.nama_kriteria, k.bobot 
                            FROM detail_penilaian d
                            JOIN kriteria k ON d.kriteria_id = k.id
                            WHERE d.penilaian_id = '$id'
                            ORDER BY k.bobot DESC";
            $detail_result = $conn->query($detail_query);
            while ($row = $detail_result->fetch_assoc()) {
                $detail_data[$row['kriteria_id']] = $row;
            }
        }
    } else {
        flashMessage('error', 'Penilaian tidak ditemukan atau tidak memiliki akses!');
        redirect('penilaian.php');
    }
}

// Get all pegawai
$pegawai_list = $conn->query("SELECT * FROM pegawai ORDER BY nama_lengkap ASC");

// Get all criteria
$kriteria_list = $conn->query("SELECT * FROM kriteria ORDER BY bobot DESC");

// Get penilaian list for this penilai
$where_conditions = ["pn.penilai_id = '{$penilai['id']}'"];
if ($status_filter) {
    $where_conditions[] = "pn.status = '$status_filter'";
}
if ($active_period) {
    $where_conditions[] = "pn.periode_id = '{$active_period['id']}'";
}

$where_clause = implode(' AND ', $where_conditions);

$penilaian_query = "
    SELECT 
        pn.*,
        pg.nama_lengkap as nama_pegawai,
        pg.jabatan as jabatan_pegawai,
        pr.nama_periode,
        (SELECT AVG(nilai) FROM detail_penilaian WHERE penilaian_id = pn.id) as rata_rata_nilai
    FROM penilaian pn
    JOIN pegawai pg ON pn.pegawai_id = pg.id
    JOIN periode_penilaian pr ON pn.periode_id = pr.id
    WHERE $where_clause
    ORDER BY pn.tanggal_penilaian DESC
";

$penilaian_result = $conn->query($penilaian_query);

$page_title = $action == 'tambah' ? 'Tambah Penilaian' : ($action == 'edit' ? 'Edit Penilaian' : ($action == 'isi_nilai' ? 'Isi Nilai Penilaian' : 'Penilaian'));
require_once '../includes/header.php';
?>

<div class="dashboard-header">
    <div class="header-title">
        <h1><?php echo $page_title; ?></h1>
        <div class="user-info">
            <span class="user-name"><?php echo $_SESSION['nama_lengkap']; ?></span>
            <span class="user-role">Pimpinan</span>
        </div>
    </div>
    <p>Sistem Informasi Penilaian Kinerja Pegawai</p>
</div>

<?php if ($action == 'tambah'): ?>
    <!-- Form Tambah Penilaian Baru -->
    <div class="card">
        <div class="card-header">
            <h2>Buat Penilaian Baru</h2>
            <a href="penilaian.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="tambah_penilaian" value="1">

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
                    <div class="form-group">
                        <label for="pegawai_id">Pegawai *</label>
                        <select id="pegawai_id" name="pegawai_id" class="form-control" required>
                            <option value="">-- Pilih Pegawai --</option>
                            <?php while ($pegawai = $pegawai_list->fetch_assoc()): ?>
                                <option value="<?php echo $pegawai['id']; ?>">
                                    <?php echo $pegawai['nama_lengkap']; ?> - <?php echo $pegawai['jabatan']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="periode_id">Periode Penilaian *</label>
                        <select id="periode_id" name="periode_id" class="form-control" required>
                            <?php if ($active_period): ?>
                                <option value="<?php echo $active_period['id']; ?>" selected>
                                    <?php echo $active_period['nama_periode']; ?> (Aktif)
                                </option>
                            <?php else: ?>
                                <option value="">-- Tidak ada periode aktif --</option>
                            <?php endif; ?>
                        </select>
                        <?php if (!$active_period): ?>
                            <small style="color: #e74c3c;">
                                <i class="fas fa-exclamation-circle"></i> Tidak ada periode penilaian yang aktif
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="tanggal_penilaian">Tanggal Penilaian *</label>
                        <input type="date" id="tanggal_penilaian" name="tanggal_penilaian" class="form-control"
                            value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="draft">Draft (Simpan sementara)</option>
                            <option value="selesai">Selesai (Lanjut ke pengisian nilai)</option>
                        </select>
                        <small style="color: #7f8c8d;">
                            Pilih "Selesai" jika ingin langsung mengisi nilai
                        </small>
                    </div>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Buat Penilaian
                    </button>
                    <a href="penilaian.php" class="btn btn-warning">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

<?php elseif ($action == 'edit' || $action == 'isi_nilai'): ?>
    <!-- Form Isi Nilai / Edit Penilaian -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2>
                <?php echo $action == 'isi_nilai' ? 'Isi Nilai Penilaian' : 'Edit Penilaian'; ?>
                <?php if ($penilaian_data): ?>
                    <small style="font-size: 14px; color: #7f8c8d; margin-left: 10px;">
                        - <?php echo $penilaian_data['nama_pegawai']; ?>
                    </small>
                <?php endif; ?>
            </h2>
            <a href="penilaian.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if ($penilaian_data): ?>
            <div class="card-body">
                <!-- Info Penilaian -->
                <div style="padding: 20px; background: #f8f9fa; border-radius: 10px; margin-bottom: 30px;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <div>
                            <small style="color: #7f8c8d;">Pegawai</small>
                            <p style="font-weight: bold; margin: 5px 0;"><?php echo $penilaian_data['nama_pegawai']; ?></p>
                            <p style="color: #7f8c8d; margin: 0;"><?php echo $penilaian_data['jabatan_pegawai']; ?></p>
                        </div>

                        <div>
                            <small style="color: #7f8c8d;">Periode</small>
                            <p style="font-weight: bold; margin: 5px 0;"><?php echo $penilaian_data['nama_periode']; ?></p>
                            <p style="color: #7f8c8d; margin: 0;">
                                <?php echo date('d M Y', strtotime($penilaian_data['tanggal_mulai'])); ?> -
                                <?php echo date('d M Y', strtotime($penilaian_data['tanggal_selesai'])); ?>
                            </p>
                        </div>

                        <div>
                            <small style="color: #7f8c8d;">Tanggal Penilaian</small>
                            <p style="font-weight: bold; margin: 5px 0;">
                                <?php echo date('d F Y', strtotime($penilaian_data['tanggal_penilaian'])); ?>
                            </p>
                            <span class="status-badge <?php echo $penilaian_data['status'] == 'selesai' ? 'status-selesai' : 'status-draft'; ?>">
                                <?php echo $penilaian_data['status'] == 'selesai' ? 'Selesai' : 'Draft'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Form Nilai -->
                <form method="POST" action="">
                    <input type="hidden" name="penilaian_id" value="<?php echo $penilaian_data['id']; ?>">

                    <?php if ($action == 'isi_nilai'): ?>
                        <input type="hidden" name="save_nilai" value="1">

                        <div style="margin-bottom: 30px; padding: 20px; background: #e8f4fc; border-radius: 10px;">
                            <h4 style="margin-bottom: 10px; color: #3498db;">
                                <i class="fas fa-info-circle"></i> Panduan Penilaian
                            </h4>
                            <p style="margin: 0; color: #2c3e50;">
                                Berikan nilai 0-100 untuk setiap kriteria berdasarkan performa pegawai.
                                Nilai 0-59 = Kurang, 60-69 = Cukup, 70-79 = Baik, 80-89 = Sangat Baik, 90-100 = Luar Biasa.
                            </p>
                        </div>

                        <!-- Kriteria Penilaian -->
                        <h3 style="margin-bottom: 20px;">Kriteria Penilaian</h3>

                        <?php if ($kriteria_list->num_rows > 0): ?>
                            <?php $total_bobot = 0; ?>
                            <?php while ($kriteria = $kriteria_list->fetch_assoc()):
                                $total_bobot += $kriteria['bobot'];
                                $existing_nilai = isset($detail_data[$kriteria['id']]) ? $detail_data[$kriteria['id']]['nilai'] : '';
                                $existing_catatan = isset($detail_data[$kriteria['id']]) ? $detail_data[$kriteria['id']]['catatan'] : '';
                            ?>
                                <div style="margin-bottom: 25px; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <div>
                                            <h4 style="margin: 0; color: #2c3e50;"><?php echo $kriteria['nama_kriteria']; ?></h4>
                                            <?php if ($kriteria['deskripsi']): ?>
                                                <p style="color: #7f8c8d; margin: 5px 0 0 0; font-size: 14px;">
                                                    <?php echo $kriteria['deskripsi']; ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div style="text-align: right;">
                                            <div style="font-size: 24px; font-weight: bold; color: #3498db;">
                                                <?php echo $kriteria['bobot']; ?>%
                                            </div>
                                            <small style="color: #7f8c8d;">Bobot</small>
                                        </div>
                                    </div>

                                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                                        <div>
                                            <label for="nilai_<?php echo $kriteria['id']; ?>" style="font-weight: 500; margin-bottom: 5px; display: block;">
                                                Nilai (0-100) *
                                            </label>
                                            <div style="position: relative;">
                                                <input type="number" id="nilai_<?php echo $kriteria['id']; ?>"
                                                    name="nilai[<?php echo $kriteria['id']; ?>]"
                                                    class="form-control" min="0" max="100" step="0.1"
                                                    value="<?php echo $existing_nilai; ?>" required
                                                    oninput="updateNilaiPreview(<?php echo $kriteria['id']; ?>, this.value)">
                                                <div style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); 
                                                 color: #7f8c8d; font-weight: bold;">/100</div>
                                            </div>

                                            <!-- Nilai Preview -->
                                            <div id="nilai_preview_<?php echo $kriteria['id']; ?>"
                                                style="margin-top: 10px; text-align: center; padding: 5px; 
                                                    border-radius: 5px; font-weight: bold; display: none;">
                                            </div>
                                        </div>

                                        <div>
                                            <label for="catatan_<?php echo $kriteria['id']; ?>" style="font-weight: 500; margin-bottom: 5px; display: block;">
                                                Catatan / Umpan Balik
                                            </label>
                                            <textarea id="catatan_<?php echo $kriteria['id']; ?>"
                                                name="catatan[<?php echo $kriteria['id']; ?>]"
                                                class="form-control" rows="3"
                                                placeholder="Berikan catatan atau umpan balik untuk kriteria ini..."><?php echo $existing_catatan; ?></textarea>
                                        </div>
                                    </div>

                                    <!-- Skala Nilai -->
                                    <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                        <div style="display: flex; justify-content: space-between; font-size: 12px; color: #7f8c8d;">
                                            <span>0-59: Kurang</span>
                                            <span>60-69: Cukup</span>
                                            <span>70-79: Baik</span>
                                            <span>80-89: Sangat Baik</span>
                                            <span>90-100: Luar Biasa</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>

                            <div style="padding: 20px; background: #2c3e50; color: white; border-radius: 10px; text-align: center; margin-top: 30px;">
                                <h4 style="margin: 0;">Total Bobot: <?php echo $total_bobot; ?>%</h4>
                                <p style="margin: 5px 0 0 0; opacity: 0.8;">
                                    <?php echo $total_bobot == 100 ? '✓ Bobot optimal' : '⚠ Perlu penyesuaian bobot'; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px;">
                                <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #e74c3c; margin-bottom: 20px;"></i>
                                <p>Belum ada kriteria penilaian yang ditetapkan</p>
                                <p style="color: #7f8c8d;">Silakan hubungi admin untuk menambahkan kriteria penilaian</p>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 40px; display: flex; gap: 15px; justify-content: center;">
                            <button type="submit" class="btn btn-success" style="padding: 12px 30px;">
                                <i class="fas fa-check-circle"></i> Simpan & Selesaikan Penilaian
                            </button>
                            <button type="button" onclick="saveAsDraft()" class="btn btn-warning" style="padding: 12px 30px;">
                                <i class="fas fa-save"></i> Simpan sebagai Draft
                            </button>
                            <a href="penilaian.php" class="btn btn-danger" style="padding: 12px 30px;">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>

                    <?php else: // Edit only status 
                    ?>
                        <input type="hidden" name="save_draft" value="1">

                        <div style="text-align: center; padding: 40px;">
                            <i class="fas fa-edit" style="font-size: 48px; color: #3498db; margin-bottom: 20px;"></i>
                            <h3>Edit Status Penilaian</h3>
                            <p style="color: #7f8c8d; margin-bottom: 30px;">
                                Penilaian ini masih dalam status draft. Anda dapat melanjutkan pengisian nilai atau menghapusnya.
                            </p>

                            <div style="display: flex; gap: 15px; justify-content: center;">
                                <a href="penilaian.php?action=isi_nilai&id=<?php echo $penilaian_data['id']; ?>"
                                    class="btn btn-success" style="padding: 12px 30px;">
                                    <i class="fas fa-edit"></i> Lanjutkan Isi Nilai
                                </a>
                                <button type="submit" class="btn btn-warning" style="padding: 12px 30px;">
                                    <i class="fas fa-save"></i> Simpan sebagai Draft
                                </button>
                                <a href="penilaian.php?action=delete&id=<?php echo $penilaian_data['id']; ?>"
                                    class="btn btn-danger" style="padding: 12px 30px;"
                                    onclick="return confirm('Hapus penilaian ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- List Penilaian -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h2>Daftar Penilaian</h2>
            <div style="display: flex; gap: 10px;">
                <!-- Status Filter -->
                <div style="display: flex; gap: 5px; background: #f8f9fa; padding: 5px; border-radius: 5px;">
                    <a href="penilaian.php"
                        class="btn btn-sm <?php echo !$status_filter ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Semua
                    </a>
                    <a href="penilaian.php?status=selesai"
                        class="btn btn-sm <?php echo $status_filter == 'selesai' ? 'btn-success' : 'btn-outline-success'; ?>">
                        Selesai
                    </a>
                    <a href="penilaian.php?status=draft"
                        class="btn btn-sm <?php echo $status_filter == 'draft' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                        Draft
                    </a>
                </div>

                <?php if ($active_period): ?>
                    <a href="?action=tambah" class="btn btn-success">
                        <i class="fas fa-plus"></i> Buat Penilaian
                    </a>
                <?php else: ?>
                    <button class="btn btn-success" disabled title="Tidak ada periode aktif">
                        <i class="fas fa-plus"></i> Buat Penilaian
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body">
            <?php if ($active_period): ?>
                <div style="margin-bottom: 20px; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                 color: white; border-radius: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h4 style="margin: 0;">Periode Aktif: <?php echo $active_period['nama_periode']; ?></h4>
                            <p style="margin: 5px 0 0 0; opacity: 0.9;">
                                <?php echo date('d M Y', strtotime($active_period['tanggal_mulai'])); ?> -
                                <?php echo date('d M Y', strtotime($active_period['tanggal_selesai'])); ?>
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <?php
                            $end_date = new DateTime($active_period['tanggal_selesai']);
                            $today = new DateTime();
                            $days_left = $today->diff($end_date)->days;
                            ?>
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $days_left; ?></div>
                            <div>Hari Tersisa</div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 20px; padding: 15px; background: #f8d7da; color: #721c24; border-radius: 10px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <strong>Tidak ada periode penilaian yang aktif.</strong>
                    Silakan hubungi administrator untuk mengaktifkan periode penilaian.
                </div>
            <?php endif; ?>

            <?php if ($penilaian_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Pegawai</th>
                                <th>Periode</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Nilai</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php while ($row = $penilaian_result->fetch_assoc()):
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
                                        <?php if ($row['status'] == 'selesai'): ?>
                                            <div style="text-align: center;">
                                                <div style="font-size: 16px; font-weight: bold; color: <?php echo $grade_color; ?>;">
                                                    <?php echo number_format($rata_rata, 1); ?>
                                                </div>
                                                <div style="font-size: 12px; color: <?php echo $grade_color; ?>; font-weight: bold;">
                                                    <?php echo $grade; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #7f8c8d;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <?php if ($row['status'] == 'selesai'): ?>
                                                <a href="laporan.php?id=<?php echo $row['id']; ?>"
                                                    target="_blank"
                                                    class="btn btn-sm btn-primary" title="Lihat Laporan">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="penilaian.php?action=view&id=<?php echo $row['id']; ?>"
                                                    class="btn btn-sm btn-info" title="Detail">
                                                    <i class="fas fa-info-circle"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="penilaian.php?action=edit&id=<?php echo $row['id']; ?>"
                                                    class="btn btn-sm btn-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="penilaian.php?action=delete&id=<?php echo $row['id']; ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Hapus penilaian ini?')"
                                                    title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Statistics -->
                <?php
                $stats = [
                    'total' => 0,
                    'selesai' => 0,
                    'draft' => 0,
                    'avg_nilai' => 0
                ];

                $stats_query = "
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN p.status = 'selesai' THEN 1 ELSE 0 END) as selesai,
                        SUM(CASE WHEN p.status = 'draft' THEN 1 ELSE 0 END) as draft,
                        AVG(
                            CASE 
                                WHEN p.status = 'selesai' THEN (
                                    SELECT AVG(dp.nilai)
                                    FROM detail_penilaian dp
                                    WHERE dp.penilaian_id = p.id
                                )
                                ELSE NULL
                            END
                        ) as avg_nilai
                    FROM penilaian p
                    WHERE p.penilai_id = '{$penilai['id']}'" . ($active_period ? " AND p.periode_id = '{$active_period['id']}'" : "");

                $stats_result = $conn->query($stats_query);
                if ($stats_result) {
                    $stats = $stats_result->fetch_assoc();
                }
                ?>

                <div style="margin-top: 30px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
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
                <div style="text-align: center; padding: 60px;">
                    <i class="fas fa-clipboard-list" style="font-size: 72px; color: #ddd; margin-bottom: 20px;"></i>
                    <h3 style="margin-bottom: 10px;">Belum Ada Penilaian</h3>
                    <p style="color: #7f8c8d; margin-bottom: 30px; max-width: 500px; margin-left: auto; margin-right: auto;">
                        Anda belum membuat penilaian untuk periode ini.
                        <?php if ($active_period): ?>
                            Silakan buat penilaian pertama Anda dengan menekan tombol "Buat Penilaian".
                        <?php else: ?>
                            Tidak ada periode penilaian yang aktif saat ini.
                        <?php endif; ?>
                    </p>

                    <?php if ($active_period): ?>
                        <a href="?action=tambah" class="btn btn-success btn-lg">
                            <i class="fas fa-plus"></i> Buat Penilaian Pertama
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<script>
    function updateNilaiPreview(kriteriaId, nilai) {
        const preview = document.getElementById('nilai_preview_' + kriteriaId);
        const numericValue = parseFloat(nilai) || 0;

        let category = '';
        let color = '';

        if (numericValue >= 90) {
            category = 'LUAR BIASA';
            color = '#27ae60';
        } else if (numericValue >= 80) {
            category = 'SANGAT BAIK';
            color = '#3498db';
        } else if (numericValue >= 70) {
            category = 'BAIK';
            color = '#f39c12';
        } else if (numericValue >= 60) {
            category = 'CUKUP';
            color = '#e67e22';
        } else if (numericValue > 0) {
            category = 'KURANG';
            color = '#e74c3c';
        } else {
            category = 'BELUM DINILAI';
            color = '#7f8c8d';
        }

        preview.innerHTML = `
        <span style="color: ${color};">
            ${numericValue > 0 ? numericValue.toFixed(1) : '0'} - ${category}
        </span>
    `;
        preview.style.display = 'block';
        preview.style.backgroundColor = color + '20'; // 20 = 12% opacity
        preview.style.border = '1px solid ' + color;
    }

    // Initialize nilai preview for existing values
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($detail_data)): ?>
            <?php foreach ($detail_data as $kriteria_id => $detail): ?>
                if (document.getElementById('nilai_<?php echo $kriteria_id; ?>')) {
                    updateNilaiPreview(<?php echo $kriteria_id; ?>, '<?php echo $detail["nilai"]; ?>');
                }
            <?php endforeach; ?>
        <?php endif; ?>
    });

    function saveAsDraft() {
        if (confirm('Simpan sebagai draft? Anda dapat melanjutkan pengisian nilai nanti.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'penilaian_id';
            inputId.value = '<?php echo $penilaian_data["id"] ?? ""; ?>';

            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'save_draft';
            inputAction.value = '1';

            form.appendChild(inputId);
            form.appendChild(inputAction);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Auto-calculate total
    function calculateTotal() {
        let total = 0;
        const inputs = document.querySelectorAll('input[name^="nilai["]');

        inputs.forEach(input => {
            const value = parseFloat(input.value) || 0;
            total += value;
        });

        document.getElementById('total_nilai').textContent = total.toFixed(1);
    }

    // Validate nilai range
    function validateNilai(input) {
        let value = parseFloat(input.value);

        if (isNaN(value)) {
            input.value = '';
            return;
        }

        if (value < 0) {
            input.value = 0;
        } else if (value > 100) {
            input.value = 100;
        }

        // Update preview
        const kriteriaId = input.name.match(/\[(\d+)\]/)[1];
        updateNilaiPreview(kriteriaId, input.value);
        calculateTotal();
    }
</script>

<style>
    /* Additional styles for penilaian form */
    .nilai-input-container {
        position: relative;
    }

    .nilai-input-container input {
        padding-right: 50px;
    }

    .nilai-suffix {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #7f8c8d;
        font-weight: bold;
    }

    .criteria-card {
        transition: all 0.3s ease;
        border-left: 4px solid #3498db;
    }

    .criteria-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Progress bar for nilai */
    .nilai-progress {
        height: 6px;
        background: #e0e0e0;
        border-radius: 3px;
        margin-top: 10px;
        overflow: hidden;
    }

    .nilai-progress-bar {
        height: 100%;
        background: #3498db;
        transition: width 0.3s ease;
    }

    /* Rating stars (optional) */
    .rating-stars {
        display: flex;
        gap: 5px;
        margin-top: 10px;
    }

    .rating-star {
        font-size: 20px;
        color: #ddd;
        cursor: pointer;
        transition: color 0.2s;
    }

    .rating-star.active {
        color: #f39c12;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .criteria-card {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<script>
    function showPenilaianDetail(penilaianId) {
        const modal = document.getElementById('detailPenilaianModal');
        const content = document.getElementById('detailPenilaianContent');

        modal.style.display = 'flex';

        // Load data via AJAX
        fetch(`../ajax/get_penilaian_detail.php?id=${penilaianId}`)
            .then(response => response.text())
            .then(data => {
                content.innerHTML = data;
            })
            .catch(error => {
                console.error('Error:', error);
                content.innerHTML = '<p>Error loading data</p>';
            });
    }

    function closeDetailPenilaianModal() {
        document.getElementById('detailPenilaianModal').style.display = 'none';
    }
</script>

<?php require_once '../includes/footer.php'; ?>