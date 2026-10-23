<?php
// Cookie sesi lebih aman: hanya HTTP(S), tidak dapat diakses JavaScript, dan SameSite.
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

// Tambahkan dukungan relasi dokter pada instalasi lama tanpa menghapus riwayat.
function ensure_doctor_schema(): void {
    static $done = false;
    if ($done) return;
    try {
        $pdo = db();
        $exists = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'doctors'")->fetchColumn();
        if (!(int)$exists) {
            $pdo->exec("CREATE TABLE doctors (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(150) NOT NULL, specialty VARCHAR(100) NULL, license_no VARCHAR(50) NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uq_doctors_license (license_no), INDEX idx_doctors_active_name (is_active, full_name)) ENGINE=InnoDB");
        }
        $column = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'medical_records' AND column_name = 'doctor_id'")->fetchColumn();
        if (!(int)$column) $pdo->exec("ALTER TABLE medical_records ADD doctor_id INT UNSIGNED NULL AFTER branch_id, ADD INDEX idx_records_doctor (doctor_id)");
        $constraint = $pdo->query("SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND constraint_name = 'fk_records_doctor'")->fetchColumn();
        if (!(int)$constraint) $pdo->exec("ALTER TABLE medical_records ADD CONSTRAINT fk_records_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL");
        $done = true;
    } catch (Throwable $exception) {
        error_log('Gagal menyiapkan tabel dokter: ' . $exception->getMessage());
    }
}

function security_headers(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}
security_headers();

function client_ip_key(): string { return hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown')); }
function login_throttled(): bool {
    $now = time();
    $_SESSION['login_attempts'] = array_values(array_filter($_SESSION['login_attempts'] ?? [], fn($t) => $t > $now - 900));
    return count($_SESSION['login_attempts']) >= 10;
}
function record_login_failure(): void { $_SESSION['login_attempts'][] = time(); }
function clear_login_failures(): void { unset($_SESSION['login_attempts']); }
function validate_text(string $value, int $max, bool $required = false): bool {
    return (!$required || $value !== '') && mb_strlen($value, 'UTF-8') <= $max;
}
function audit_event(string $event, ?int $targetId = null): void {
    error_log(json_encode(['time'=>date(DATE_ATOM),'event'=>$event,'target_id'=>$targetId,'user_id'=>user()['id'] ?? null,'branch_id'=>user()['branch_id'] ?? null], JSON_UNESCAPED_UNICODE));
}

// Jalankan migrasi setelah fungsi koneksi didefinisikan.
function bootstrap_schema(): void { ensure_doctor_schema(); }

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'rumah_sakit';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            error_log('Koneksi database gagal: ' . $exception->getMessage());
            http_response_code(503);
            exit('Database belum tersedia. Jalankan MySQL dari XAMPP, lalu muat ulang halaman.');
        }
    }
    return $pdo;
}

bootstrap_schema();

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header('Location: ' . $url); exit; }
function csrf_token(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function check_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Token keamanan tidak valid.'); } }
function user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): void { if (!user()) redirect('login.php'); }
function is_center(): bool { return user() && user()['branch_code'] === 'PUSAT'; }
function can_access_branch(int $branchId): bool { return is_center() || (int) user()['branch_id'] === $branchId; }
function require_admin(): void {
    require_login();
    if ((user()['role'] ?? '') !== 'admin') { http_response_code(403); exit('Akses ditolak.'); }
}
function flash(?string $message = null): ?string { if ($message !== null) { $_SESSION['flash'] = $message; return null; } $m = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $m; }
function layout_start(string $title): void { $u = user(); ?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($title) ?> - Rumah Sakit</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="topbar"><div class="brand">RS Sehat Bersama</div><?php if ($u): ?><div class="userbar"><?= e($u['full_name']) ?> · <?= e($u['branch_name']) ?> <a href="logout.php">Keluar</a></div><?php endif; ?></header>
<?php if ($u): ?><nav><a href="index.php">Dashboard</a><a href="patients.php">Pasien</a><a href="record_form.php">Catat Rekam Medis</a><?php if ($u['role'] === 'admin'): ?><a href="doctors.php">Referensi Dokter</a><?php endif; ?></nav><?php endif; ?><main class="container">
<?php if ($m = flash()): ?><div class="alert success"><?= e($m) ?></div><?php endif; ?>
<?php }
function layout_end(): void { ?></main><footer>Prototype tahap awal · Lindungi kerahasiaan data pasien.</footer></body></html><?php }
?>

