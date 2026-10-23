<?php
require 'config.php';
require_admin();

$error = null;
$form = ['id' => '', 'full_name' => '', 'specialty' => '', 'license_no' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
    try {
        if ($action === 'save') {
            $form = ['id' => $id ?: '', 'full_name' => trim($_POST['full_name'] ?? ''), 'specialty' => trim($_POST['specialty'] ?? ''), 'license_no' => trim($_POST['license_no'] ?? '')];
            if (!validate_text($form['full_name'], 150, true) || !validate_text($form['specialty'], 100) || !validate_text($form['license_no'], 50)) {
                throw new RuntimeException('Periksa kembali isian. Nama wajib dan panjang tiap kolom harus sesuai batas.');
            }
            $specialty = $form['specialty'] !== '' ? $form['specialty'] : null;
            $license = $form['license_no'] !== '' ? $form['license_no'] : null;
            if ($id) {
                $stmt = db()->prepare('UPDATE doctors SET full_name = ?, specialty = ?, license_no = ? WHERE id = ?');
                $stmt->execute([$form['full_name'], $specialty, $license, $id]);
                if (!$stmt->rowCount()) {
                    $exists = db()->prepare('SELECT 1 FROM doctors WHERE id = ?'); $exists->execute([$id]);
                    if (!$exists->fetchColumn()) throw new RuntimeException('Dokter tidak ditemukan.');
                }
                audit_event('doctor_updated', $id);
                flash('Data dokter berhasil diperbarui. Riwayat kunjungan tetap tersimpan.');
            } else {
                $stmt = db()->prepare('INSERT INTO doctors (full_name, specialty, license_no) VALUES (?, ?, ?)');
                $stmt->execute([$form['full_name'], $specialty, $license]);
                audit_event('doctor_created', (int)db()->lastInsertId());
                flash('Dokter berhasil ditambahkan.');
            }
            redirect('doctors.php');
        } elseif ($action === 'toggle' && $id) {
            $stmt = db()->prepare('UPDATE doctors SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?');
            $stmt->execute([$id]);
            if (!$stmt->rowCount()) throw new RuntimeException('Dokter tidak ditemukan atau status tidak berubah.');
            audit_event('doctor_status_changed', $id);
            flash('Status dokter berhasil diubah. Riwayat pemeriksaan tidak dihapus.');
            redirect('doctors.php');
        } else {
            throw new RuntimeException('Permintaan tidak valid.');
        }
    } catch (RuntimeException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log('Pengelolaan dokter gagal: ' . $exception->getMessage());
        $error = 'Data gagal disimpan. Periksa kemungkinan nomor STR/SIP duplikat.';
    }
}

$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
if ($editId && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = db()->prepare('SELECT id, full_name, specialty, license_no FROM doctors WHERE id = ?');
    $stmt->execute([$editId]);
    $found = $stmt->fetch();
    if ($found) $form = $found;
    else $error = 'Dokter tidak ditemukan.';
}
$doctors = db()->query('SELECT d.*, (SELECT COUNT(*) FROM medical_records r WHERE r.doctor_id = d.id) AS visit_count FROM doctors d ORDER BY d.is_active DESC, d.full_name')->fetchAll();
layout_start('Referensi Dokter');
?>
<h1>Referensi Dokter</h1>
<p class="muted">Dokter yang keluar sebaiknya dinonaktifkan, bukan dihapus. Rekam medis lama tetap utuh dan dokter nonaktif tidak muncul pada pilihan kunjungan baru.</p>
<?php if ($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?>
<div class="card">
<h2><?= $form['id'] ? 'Edit Dokter' : 'Tambah Dokter' ?></h2>
<form method="post">
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=e((string)$form['id'])?>">
<div class="form-grid">
<div class="full"><label>Nama dokter *</label><input name="full_name" maxlength="150" required value="<?=e($form['full_name'])?>" placeholder="Contoh: dr. Andi Wijaya, Sp.PD"></div>
<div><label>Spesialisasi</label><input name="specialty" maxlength="100" value="<?=e($form['specialty'])?>" placeholder="Contoh: Penyakit Dalam"></div>
<div><label>Nomor STR/SIP</label><input name="license_no" maxlength="50" value="<?=e($form['license_no'])?>"></div>
</div>
<button class="btn"><?= $form['id'] ? 'Simpan Perubahan' : 'Tambah Dokter' ?></button>
<?php if ($form['id']): ?> <a class="btn secondary" href="doctors.php">Batal</a><?php endif; ?>
</form>
</div>
<div class="card table-wrap"><h2>Daftar Dokter</h2>
<table><tr><th>Nama</th><th>Spesialisasi</th><th>STR/SIP</th><th>Status</th><th>Riwayat</th><th>Aksi</th></tr>
<?php foreach ($doctors as $doctor): ?>
<tr><td><?=e($doctor['full_name'])?></td><td><?=e($doctor['specialty'])?></td><td><?=e($doctor['license_no'])?></td><td><?= $doctor['is_active'] ? 'Aktif' : 'Nonaktif' ?></td><td><?= (int)$doctor['visit_count'] ?> kunjungan</td><td><a class="btn secondary" href="doctors.php?edit=<?=(int)$doctor['id']?>">Edit</a> <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=(int)$doctor['id']?>"><button class="btn secondary"><?= $doctor['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form></td></tr>
<?php endforeach; ?>
<?php if (!$doctors): ?><tr><td colspan="6">Belum ada dokter.</td></tr><?php endif; ?>
</table></div>
<?php layout_end(); ?>
