<?php
require 'config.php';
require_login();

$pid = (int)($_GET['patient_id'] ?? $_POST['patient_id'] ?? 0);
$rm = trim($_GET['medical_record_no'] ?? $_GET['rm'] ?? '');

if ($pid > 0) {
    $stmt = db()->prepare('SELECT * FROM patients WHERE id = ?');
    $stmt->execute([$pid]);
} elseif ($rm !== '') {
    $stmt = db()->prepare('SELECT * FROM patients WHERE medical_record_no = ?');
    $stmt->execute([$rm]);
} else {
    http_response_code(400);
    exit('Pilih pasien terlebih dahulu dari menu Pasien, atau buka halaman ini dengan nomor rekam medis.');
}

$p = $stmt->fetch();
if (!$p) {
    http_response_code(404);
    exit('Pasien tidak ditemukan. Cari pasien berdasarkan nomor rekam medis melalui menu Pasien.');
}

$pid = (int)$p['id'];
if (!is_center() && (int)$p['registered_branch_id'] !== (int)user()['branch_id']) {
    http_response_code(403);
    exit('Anda tidak memiliki akses ke data pasien ini.');
}
$error = null;
$doctors = db()->query('SELECT id, full_name, specialty FROM doctors WHERE is_active = 1 ORDER BY full_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $visitDate = trim($_POST['visit_date'] ?? '');
    $complaint = trim($_POST['complaint'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');

    $doctorStmt = db()->prepare('SELECT id, full_name FROM doctors WHERE id = ? AND is_active = 1');
    $doctorStmt->execute([$doctorId]);
    $doctor = $doctorStmt->fetch();

    if (!$doctor || $visitDate === '' || !validate_text($complaint, 10000, true) || !validate_text($diagnosis, 10000, true)
        || !validate_text(trim($_POST['treatment'] ?? ''), 10000) || !validate_text(trim($_POST['prescription'] ?? ''), 10000)
        || !validate_text(trim($_POST['notes'] ?? ''), 10000)) {
        $error = 'Dokter, tanggal kunjungan, keluhan, dan diagnosis wajib diisi. Pilih dokter dari daftar yang tersedia.';
    } else {
        try {
            $stmt = db()->prepare(
                'INSERT INTO medical_records
                (patient_id, branch_id, doctor_id, doctor_name, visit_date, complaint, diagnosis, treatment, prescription, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $pid,
                user()['branch_id'],
                $doctor['id'],
                $doctor['full_name'],
                $visitDate,
                $complaint,
                $diagnosis,
                trim($_POST['treatment'] ?? ''),
                trim($_POST['prescription'] ?? ''),
                trim($_POST['notes'] ?? ''),
                user()['id'],
            ]);
            flash('Rekam medis berhasil disimpan.');
            redirect('patient.php?id=' . $pid);
        } catch (Throwable $exception) {
            error_log('Gagal menyimpan rekam medis: ' . $exception->getMessage());
            $error = 'Rekam medis gagal disimpan. Periksa isian dan koneksi database.';
        }
    }
}

layout_start('Catat Rekam Medis');
?>
<h1>Catat Rekam Medis</h1>
<p>Pasien: <b><?=e($p['full_name'])?></b> (<?=e($p['medical_record_no'])?>)</p>
<?php if ($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<div class="card">
<form method="post">
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<input type="hidden" name="patient_id" value="<?=$pid?>">
<div class="form-grid">
<div>
<label for="doctor_id">Dokter *</label>
<select id="doctor_id" name="doctor_id" required>
<option value="">- Pilih dokter -</option>
<?php foreach ($doctors as $doctor): ?>
<option value="<?=$doctor['id']?>" <?=((int)($_POST['doctor_id'] ?? 0) === (int)$doctor['id']) ? 'selected' : ''?>><?=e($doctor['full_name'])?><?= $doctor['specialty'] ? ' — ' . e($doctor['specialty']) : '' ?></option>
<?php endforeach; ?>
</select>
<?php if (!$doctors): ?><small class="muted">Belum ada dokter aktif. Minta admin menambahkan referensi dokter.</small><?php endif; ?>
</div>
<div><label>Tanggal dan waktu kunjungan *</label><input type="datetime-local" name="visit_date" value="<?=e($_POST['visit_date'] ?? date('Y-m-d\TH:i'))?>" required></div>
<div class="full"><label>Keluhan *</label><textarea name="complaint" required><?=e($_POST['complaint'] ?? '')?></textarea></div>
<div class="full"><label>Diagnosis *</label><textarea name="diagnosis" required><?=e($_POST['diagnosis'] ?? '')?></textarea></div>
<div><label>Tindakan</label><textarea name="treatment"><?=e($_POST['treatment'] ?? '')?></textarea></div>
<div><label>Resep</label><textarea name="prescription"><?=e($_POST['prescription'] ?? '')?></textarea></div>
<div class="full"><label>Catatan</label><textarea name="notes"><?=e($_POST['notes'] ?? '')?></textarea></div>
</div>
<button class="btn">Simpan Rekam Medis</button>
<a class="btn secondary" href="patient.php?id=<?=$pid?>">Batal</a>
</form>
</div>
<?php layout_end(); ?>

<?php
?>
