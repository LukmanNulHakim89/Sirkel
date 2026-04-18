<?php
session_start();
require_once '../config/db.php';
if (!isLoggedIn() || !isAdmin()) redirect('../auth/login.php','Akses ditolak.','danger');

// Approve / Tolak toko
if (isset($_GET['approve'])) {
    $tid = intval($_GET['approve']);
    $conn->query("UPDATE toko SET status='aktif' WHERE id=$tid");
    redirect('dashboard.php','Toko disetujui! ✅');
}
if (isset($_GET['tolak'])) {
    $tid    = intval($_GET['tolak']);
    $catatan = trim($_GET['catatan'] ?? 'Tidak memenuhi syarat.');
    $st = $conn->prepare("UPDATE toko SET status='ditolak', catatan_admin=? WHERE id=?");
    $st->bind_param("si",$catatan,$tid); $st->execute(); $st->close();
    redirect('dashboard.php','Toko ditolak.');
}
if (isset($_GET['nonaktif'])) {
    $tid = intval($_GET['nonaktif']);
    $conn->query("UPDATE toko SET status='nonaktif' WHERE id=$tid");
    redirect('dashboard.php','Toko dinonaktifkan.');
}

// Statistik
$stat = [
    'toko_pending' => $conn->query("SELECT COUNT(*) AS c FROM toko WHERE status='pending'")->fetch_assoc()['c'],
    'toko_aktif'   => $conn->query("SELECT COUNT(*) AS c FROM toko WHERE status='aktif'")->fetch_assoc()['c'],
    'total_user'   => $conn->query("SELECT COUNT(*) AS c FROM users WHERE role!='admin'")->fetch_assoc()['c'],
    'total_produk' => $conn->query("SELECT COUNT(*) AS c FROM produk")->fetch_assoc()['c'],
];

$toko_pending = $conn->query("SELECT t.*,u.nama AS nama_penjual,u.email,u.telepon FROM toko t JOIN users u ON t.user_id=u.id WHERE t.status='pending' ORDER BY t.created_at DESC");
$toko_semua   = $conn->query("SELECT t.*,u.nama AS nama_penjual,COUNT(p.id) AS jml_produk FROM toko t JOIN users u ON t.user_id=u.id LEFT JOIN produk p ON p.toko_id=t.id GROUP BY t.id ORDER BY t.created_at DESC");
$users_list   = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 20");

$flash_msg = $_SESSION['flash']['msg'] ?? ''; $flash_type = $_SESSION['flash']['type'] ?? 'success';
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — PasarLokal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;}
body{background:#f8fafc;margin:0;display:flex;}
.sidebar{width:240px;min-height:100vh;background:#111827;padding:0;flex-shrink:0;position:sticky;top:0;height:100vh;}
.sb-brand{padding:24px 20px;font-size:1.2rem;font-weight:700;color:#4ade80;border-bottom:1px solid rgba(255,255,255,.1);}
.sb-nav{padding:16px 12px;}
.sb-link{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;color:rgba(255,255,255,.6);font-size:.88rem;font-weight:500;text-decoration:none;transition:.15s;margin-bottom:3px;}
.sb-link:hover,.sb-link.act{background:rgba(255,255,255,.08);color:#fff;}
.sb-link i{width:18px;text-align:center;}
.main{flex:1;}
.topbar{background:#fff;border-bottom:1px solid #e5e7eb;padding:16px 28px;display:flex;align-items:center;justify-content:space-between;}
.topbar h1{font-size:1.1rem;font-weight:700;margin:0;}
.content{padding:28px;}
.stat-card{background:#fff;border-radius:14px;border:1.5px solid #e5e7eb;padding:20px;}
.stat-val{font-size:1.8rem;font-weight:700;color:#111827;}
.stat-label{font-size:.8rem;color:#9ca3af;margin-top:4px;}
.card{border:none;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.05);}
.card-header{background:#fff;border-bottom:1px solid #f0f0f0;border-radius:14px 14px 0 0!important;padding:16px 20px;font-weight:700;font-size:.9rem;}
.table th{font-size:.75rem;text-transform:uppercase;color:#9ca3af;font-weight:600;border:none;}
.table td{font-size:.85rem;vertical-align:middle;border-color:#f5f5f5;}
.s-badge{padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:700;}
.s-pending{background:#fef3c7;color:#92400e;} .s-aktif{background:#d1fae5;color:#065f46;}
.s-ditolak{background:#fee2e2;color:#991b1b;} .s-nonaktif{background:#f3f4f6;color:#6b7280;}
.tab-btn{border:none;background:none;padding:10px 20px;font-size:.88rem;font-weight:600;color:#9ca3af;cursor:pointer;border-bottom:2px solid transparent;transition:.15s;}
.tab-btn.active{color:#16a34a;border-color:#16a34a;}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sb-brand">🌿 PasarLokal Admin</div>
  <nav class="sb-nav">
    <a href="dashboard.php" class="sb-link act"><i class="fa fa-gauge-high"></i>Dashboard</a>
    <a href="../index.php" class="sb-link"><i class="fa fa-store"></i>Lihat PasarLokal</a>
    <hr style="border-color:rgba(255,255,255,.1);margin:8px 0;">
    <a href="../auth/logout.php" class="sb-link" style="color:rgba(239,68,68,.7);"><i class="fa fa-right-from-bracket"></i>Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar">
    <h1>⚙️ Admin Panel</h1>
    <span style="font-size:.83rem;color:#9ca3af;">Admin: <?= htmlspecialchars($_SESSION['nama']) ?></span>
  </div>
  <div class="content">

    <?php if ($flash_msg): ?>
    <div class="alert border-0 rounded-3 mb-4" style="background:<?= $flash_type==='success'?'#d1fae5':'#fee2e2' ?>;color:<?= $flash_type==='success'?'#065f46':'#991b1b' ?>;font-size:.88rem;">
      <?= htmlspecialchars($flash_msg) ?>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3"><div class="stat-card">
        <div style="font-size:1.4rem;margin-bottom:6px;">⏳</div>
        <div class="stat-val" style="color:<?= $stat['toko_pending']>0?'#f59e0b':'#111' ?>;"><?= $stat['toko_pending'] ?></div>
        <div class="stat-label">Toko Pending</div>
      </div></div>
      <div class="col-6 col-md-3"><div class="stat-card">
        <div style="font-size:1.4rem;margin-bottom:6px;">✅</div>
        <div class="stat-val"><?= $stat['toko_aktif'] ?></div>
        <div class="stat-label">Toko Aktif</div>
      </div></div>
      <div class="col-6 col-md-3"><div class="stat-card">
        <div style="font-size:1.4rem;margin-bottom:6px;">👥</div>
        <div class="stat-val"><?= $stat['total_user'] ?></div>
        <div class="stat-label">Total User</div>
      </div></div>
      <div class="col-6 col-md-3"><div class="stat-card">
        <div style="font-size:1.4rem;margin-bottom:6px;">📦</div>
        <div class="stat-val"><?= $stat['total_produk'] ?></div>
        <div class="stat-label">Total Produk</div>
      </div></div>
    </div>

    <!-- Toko Pending -->
    <?php if ($stat['toko_pending'] > 0): ?>
    <div class="card mb-4">
      <div class="card-header" style="background:#fef3c7;border-color:#fde68a;">
        ⏳ Toko Menunggu Persetujuan (<?= $stat['toko_pending'] ?>)
      </div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0">
          <thead><tr>
            <th class="ps-4">Nama Toko</th><th>Penjual</th><th>Kategori</th><th>Lokasi</th><th>Aksi</th>
          </tr></thead>
          <tbody>
          <?php while ($t = $toko_pending->fetch_assoc()): ?>
          <tr>
            <td class="ps-4"><strong><?= htmlspecialchars($t['nama_toko']) ?></strong><br>
              <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($t['deskripsi']??'',0,50,'...')) ?></small></td>
            <td><?= htmlspecialchars($t['nama_penjual']) ?><br><small class="text-muted"><?= htmlspecialchars($t['email']) ?></small></td>
            <td><span style="font-size:1.2rem;"><?= kategoriIcon($t['kategori']) ?></span> <?= ucfirst($t['kategori']) ?></td>
            <td style="font-size:.82rem;"><?= htmlspecialchars($t['kecamatan']?$t['kecamatan'].', ':'') ?><?= htmlspecialchars($t['kota']) ?><br>
              <small style="color:#9ca3af;"><?= $t['latitude'] ?>, <?= $t['longitude'] ?></small></td>
            <td>
              <a href="?approve=<?= $t['id'] ?>" class="btn btn-sm btn-success me-1" style="border-radius:7px;"
                 onclick="return confirm('Setujui toko <?= addslashes($t['nama_toko']) ?>?')">✅ Setujui</a>
              <a href="?tolak=<?= $t['id'] ?>&catatan=Tidak+memenuhi+syarat" class="btn btn-sm btn-danger" style="border-radius:7px;"
                 onclick="return confirm('Tolak toko ini?')">❌ Tolak</a>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Tab: Semua Toko / Users -->
    <div style="border-bottom:1px solid #e5e7eb;margin-bottom:20px;">
      <button class="tab-btn active" onclick="showTab('toko',this)">🏪 Semua Toko</button>
      <button class="tab-btn" onclick="showTab('users',this)">👥 Pengguna</button>
    </div>

    <div id="tab-toko">
      <div class="card">
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead><tr><th class="ps-4">Toko</th><th>Penjual</th><th>Lokasi</th><th>Produk</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php while ($t = $toko_semua->fetch_assoc()): ?>
            <tr>
              <td class="ps-4"><?= htmlspecialchars($t['nama_toko']) ?></td>
              <td style="font-size:.83rem;"><?= htmlspecialchars($t['nama_penjual']) ?></td>
              <td style="font-size:.82rem;"><?= htmlspecialchars($t['kota']) ?></td>
              <td><?= $t['jml_produk'] ?></td>
              <td><span class="s-badge s-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
              <td>
                <?php if ($t['status']==='aktif'): ?>
                <a href="?nonaktif=<?= $t['id'] ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:7px;font-size:.75rem;"
                   onclick="return confirm('Nonaktifkan toko ini?')">Nonaktifkan</a>
                <?php elseif ($t['status']!=='aktif'): ?>
                <a href="?approve=<?= $t['id'] ?>" class="btn btn-sm btn-outline-success" style="border-radius:7px;font-size:.75rem;">Aktifkan</a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="tab-users" style="display:none;">
      <div class="card">
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead><tr><th class="ps-4">Nama</th><th>Email</th><th>Role</th><th>Bergabung</th></tr></thead>
            <tbody>
            <?php while ($u = $users_list->fetch_assoc()): ?>
            <tr>
              <td class="ps-4"><?= htmlspecialchars($u['nama']) ?></td>
              <td style="font-size:.83rem;"><?= htmlspecialchars($u['email']) ?></td>
              <td><span class="s-badge <?= $u['role']==='admin'?'s-ditolak':($u['role']==='penjual'?'s-pending':'s-aktif') ?>">
                <?= ucfirst($u['role']) ?></span></td>
              <td style="font-size:.78rem;color:#9ca3af;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showTab(id, btn) {
    ['toko','users'].forEach(t => document.getElementById('tab-'+t).style.display = t===id?'block':'none');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
</script>
</body>
</html>
