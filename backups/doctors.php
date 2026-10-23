<?php
require 'config.php';
require_login();
if (user()['role'] !== 'admin') {
    http_response_code(403);
    exit('Hanya admin yang dapat mengelola referensi dokter.');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $name = trim($_POST['full_name'] ?? '');
            $specialty = trim($_POST['specialty'] ?? '') ?: null;
            $license = trim($_POST['license_no'] ?? '') ?: null;
            if ($name === '') throw new RuntimeException('Nama dokter wajib diisi.');
            $stmt = db()->prepare('INSERT INTO doctors (full_name, specialty, license_no) VALUES (?, ?, ?)');
            $stmt->execute([$name, $specialty, $license]);
            flash('Dokter berhasil ditambahkan.');
        } elseif ($action === 'toggle') {
            $stmt = db()->prepare('UPDATE doctors SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?');
            $stmt->execute([(int)($_POST['id'] ?? 0)]);
            flash('Status dokter berhasil diubah.');
        }
        redirect('doctors.php');
    } catch (Throwable $exception) {
        $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Dokter gagal disimpan. Nomor STR/SIP mungkin sudah digunakan.';
    }
}
$doctors = db()->query('SELECT * FROM doctors ORDER BY is_active DESC, full_name')->fetchAll();
layout_start('Referensi Dokter');
?>
<h1>Referensi Dokter</h1>
<p class="muted">Dokter aktif akan muncul pada pilihan dokter saat admin atau petugas mencatat kunjungan.</p>
<?php if ($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<div class="card">
<h2>Tambah Dokter</h2>
<form method="post">
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<input type="hidden" name="action" value="add">
<div class="form-grid">
<div class="full"><label>Nama dokter *</label><input name="full_name" required placeholder="Contoh: dr. Andi Wijaya, Sp.PD"></div>
<div><label>Spesialisasi</label><input name="specialty" placeholder="Contoh: Penyakit Dalam"></div>
<div><label>Nomor STR/SIP</label><input name="license_no"></div>
</div>
<button class="btn">Tambah Dokter</button>
</form>
</div>
<div class="card table-wrap">
<h2>Daftar Dokter</h2>
<table><tr><th>Nama</th><th>Spesialisasi</th><th>STR/SIP</th><th>Status</th><th>Aksi</th></tr>
<?php foreach ($doctors as $doctor): ?>
<tr><td><?=e($doctor['full_name'])?></td><td><?=e($doctor['specialty'])?></td><td><?=e($doctor['license_no'])?></td><td><?= $doctor['is_active'] ? 'Aktif' : 'Nonaktif' ?></td><td><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$doctor['id']?>"><button class="btn secondary"><?= $doctor['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></td></tr>
<?php endforeach; ?>
<?php if (!$doctors): ?><tr><td colspan="5">Belum ada dokter.</td></tr><?php endif; ?>
</table>
</div>
<?php layout_end(); ?>
