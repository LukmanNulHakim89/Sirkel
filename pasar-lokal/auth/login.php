<?php
session_start();
require_once '../config/db.php';
if (isLoggedIn()) redirect('../index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!$email || !$pass) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $st = $conn->prepare("SELECT * FROM users WHERE email=?");
        $st->bind_param("s", $email); $st->execute();
        $u = $st->get_result()->fetch_assoc();
        if ($u && password_verify($pass, $u['password'])) {
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['nama']    = $u['nama'];
            $_SESSION['email']   = $u['email'];
            $_SESSION['role']    = $u['role'];
            if ($u['role'] === 'admin')   redirect('../admin/dashboard.php', 'Selamat datang Admin!');
            if ($u['role'] === 'penjual') redirect('../toko/dashboard.php', 'Selamat datang, ' . $u['nama'] . '!');
            redirect('../index.php', 'Selamat datang, ' . $u['nama'] . '!');
        } else {
            $error = 'Email atau password salah.';
        }
        $st->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — PasarLokal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;}
body{background:#f0fdf4;min-height:100vh;display:flex;align-items:center;justify-content:center;}
.card{border:none;border-radius:20px;box-shadow:0 8px 40px rgba(0,0,0,.08);padding:40px;width:100%;max-width:420px;}
.brand{font-size:1.7rem;font-weight:700;color:#16a34a;text-align:center;margin-bottom:4px;}
.sub{color:#888;font-size:.85rem;text-align:center;margin-bottom:28px;}
.form-control{border-radius:10px;border:1.5px solid #e2e8f0;padding:11px 14px;font-size:.9rem;}
.form-control:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.1);}
.form-label{font-weight:600;font-size:.83rem;color:#374151;}
.btn-primary{background:#16a34a;border:none;border-radius:10px;padding:12px;font-weight:600;width:100%;}
.btn-primary:hover{background:#15803d;}
.link{color:#16a34a;text-decoration:none;font-weight:600;}
.demo{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;font-size:.8rem;color:#166534;margin-top:16px;}
</style>
</head>
<body>
<div class="card">
  <div class="brand">🌿 PasarLokal</div>
  <div class="sub">Marketplace UMKM di sekitar Anda</div>

  <?php if ($error): ?>
  <div class="alert alert-danger border-0 rounded-3 py-2 px-3 mb-3" style="font-size:.875rem;background:#fef2f2;color:#991b1b;">
    <i class="fa fa-circle-exclamation me-1"></i><?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <form method="POST">
    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" placeholder="email@contoh.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    </div>
    <div class="mb-4">
      <label class="form-label">Password</label>
      <div class="input-group">
        <input type="password" name="password" id="pwd" class="form-control" placeholder="••••••••" required style="border-right:0;">
        <button type="button" onclick="t=document.getElementById('pwd');t.type=t.type==='password'?'text':'password'"
                class="btn btn-outline-secondary" style="border-radius:0 10px 10px 0;border-left:0;border-color:#e2e8f0;">
          <i class="fa fa-eye" style="font-size:13px;color:#888;"></i>
        </button>
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa fa-right-to-bracket me-2"></i>Masuk</button>
  </form>

  <div class="demo">
    <strong>Demo:</strong> admin@pasarlokal.id | sari@gmail.com | dedi@gmail.com<br>
    Password semua: <strong>password</strong>
  </div>

  <p class="text-center mt-3 mb-0" style="font-size:.875rem;color:#888;">
    Belum punya akun? <a href="register.php" class="link">Daftar di sini</a>
  </p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
