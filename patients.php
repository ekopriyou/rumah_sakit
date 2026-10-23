<?php
require 'config.php';
require_login();

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT p.*, b.name branch_name
        FROM patients p
        JOIN branches b ON b.id = p.registered_branch_id
        WHERE 1=1';
$params = [];

if (!is_center()) {
    if ($q !== '') {
        // Petugas cabang dapat menemukan pasien cabang lain dengan nomor RM.
        $sql .= ' AND (p.registered_branch_id = ? OR p.medical_record_no = ?)';
        $params[] = (int)user()['branch_id'];
        $params[] = $q;
    } else {
        $sql .= ' AND p.registered_branch_id = ?';
        $params[] = (int)user()['branch_id'];
    }
}

if ($q !== '') {
    $sql .= ' AND (p.full_name LIKE ? OR p.medical_record_no LIKE ? OR p.nik LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$sql .= ' ORDER BY p.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

layout_start('Data Pasien');
?>
<div class="card">
<h1>Data Pasien</h1>
<form>
<input name="q" value="<?=e($q)?>" placeholder="Cari nama, nomor rekam medis, atau NIK">
<button class="btn">Cari</button>
<a class="btn" href="patient_form.php">+ Pasien Baru</a>
</form>
<?php if (!is_center()): ?><p class="muted">Untuk pasien dari cabang lain, cari menggunakan nomor rekam medis lengkap.</p><?php endif; ?>
</div>
<div class="card table-wrap">
<table>
<tr><th>No. RM</th><th>Nama</th><th>Cabang pendaftaran</th><th>Tanggal lahir</th><th>Aksi</th></tr>
<?php foreach ($rows as $p): ?>
<tr>
<td><?=e($p['medical_record_no'])?></td>
<td><?=e($p['full_name'])?></td>
<td><?=e($p['branch_name'])?></td>
<td><?=e($p['birth_date'])?></td>
<td><a class="btn" href="patient.php?id=<?=$p['id']?>">Lihat rekam medis</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5">Data tidak ditemukan.</td></tr><?php endif; ?>
</table>
</div>
<?php layout_end(); ?>

<?php
