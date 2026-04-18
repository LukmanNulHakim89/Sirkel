<?php
session_start();
require_once '../config/db.php';
if (isLoggedIn()) redirect('../index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telp  = trim($_POST['telepon'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $konf  = $_POST['konfirm'] ?? '';
    $role  = in_array($_POST['role'] ?? '', ['pembeli','penjual']) ? $_POST['role'] : 'pembeli';

    if (!$nama||!$email||!$pass) { $error = 'Semua field wajib diisi.'; }
    elseif (strlen($pass) < 6)   { $error = 'Password minimal 6 karakter.'; }
    elseif ($pass !== $konf)     { $error = 'Konfirmasi password tidak cocok.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Format email tidak valid.'; }
    else {
        $cek = $conn->prepare("SELECT id FROM users WHERE email=?");
        $cek->bind_param("s",$email); $cek->execute(); $cek->store_result();
        if ($cek->num_rows > 0) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $st = $conn->prepare("INSERT INTO users (nama,email,password,telepon,role) VALUES (?,?,?,?,?)");
            $st->bind_param("sssss",$nama,$email,$hash,$telp,$role);
            $st->execute(); $st->close();
            redirect('login.php', 'Registrasi berhasil! Silakan login.');
        }
        $cek->close();
    }
}
$role_val = $_POST['role'] ?? 'pembeli';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Daftar — PasarLokal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;}
body{background:#f0fdf4;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:32px 16px;}
.card{border:none;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.08);padding:40px;width:100%;max-width:460px;}
.brand{font-size:1.7rem;font-weight:700;color:#16a34a;text-align:center;margin-bottom:4px;}
.sub{color:#888;font-size:.85rem;text-align:center;margin-bottom:28px;}
.form-control,.form-select{border-radius:10px;border:1.5px solid #e2e8f0;padding:11px 14px;font-size:.9rem;}
.form-control:focus,.form-select:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.1);}
.form-label{font-weight:600;font-size:.83rem;color:#374151;}
.btn-primary{background:#16a34a;border:none;border-radius:10px;padding:12px;font-weight:600;width:100%;}
.btn-primary:hover{background:#15803d;}
.link{color:#16a34a;text-decoration:none;font-weight:600;}
.role-card{border:2px solid #e2e8f0;border-radius:12px;padding:14px 16px;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:12px;}
.role-card:has(input:checked){border-color:#16a34a;background:#f0fdf4;}
.role-card input{accent-color:#16a34a;}
.role-title{font-weight:600;font-size:.9rem;color:#1f2937;}
.role-desc{font-size:.78rem;color:#6b7280;}
</style>
</head>
<body>
<div class="card">
  <div class="brand">🌿 PasarLokal</div>
  <div class="sub">Buat akun gratis sekarang</div>

  <?php if ($error): ?>
  <div class="alert alert-danger border-0 rounded-3 py-2 px-3 mb-3" style="font-size:.875rem;background:#fef2f2;color:#991b1b;">
    <i class="fa fa-circle-exclamation me-1"></i><?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Daftar sebagai</label>
      <div class="d-flex gap-3">
        <label class="role-card flex-fill">
          <input type="radio" name="role" value="pembeli" <?= $role_val==='pembeli'?'checked':'' ?>>
          <div>
            <div class="role-title">🛍️ Pembeli</div>
            <div class="role-desc">Cari produk di sekitar saya</div>
          </div>
        </label>
        <label class="role-card flex-fill">
          <input type="radio" name="role" value="penjual" <?= $role_val==='penjual'?'checked':'' ?>>
          <div>
            <div class="role-title">🏪 Penjual</div>
            <div class="role-desc">Daftarkan toko saya</div>
          </div>
        </label>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Nama Lengkap</label>
      <input type="text" name="nama" class="form-control" placeholder="Nama lengkap"
             value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" placeholder="email@contoh.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">No. HP / WhatsApp</label>
      <input type="text" name="telepon" class="form-control" placeholder="08xxxxxxxxxx"
             value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>">
    </div>
    <div class="row g-3 mb-4">
      <div class="col">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
      </div>
      <div class="col">
        <label class="form-label">Konfirmasi</label>
        <input type="password" name="konfirm" class="form-control" placeholder="Ulangi password" required>
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa fa-user-plus me-2"></i>Buat Akun</button>
  </form>

  <p class="text-center mt-3 mb-0" style="font-size:.875rem;color:#888;">
    Sudah punya akun? <a href="login.php" class="link">Masuk di sini</a>
  </p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
