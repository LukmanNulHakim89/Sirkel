<?php
session_start();
require_once '../config/db.php';
if (!isLoggedIn() || !isPenjual()) redirect('../auth/login.php','Login sebagai penjual.','danger');

$uid = $_SESSION['user_id'];

// Toko milik penjual ini
$toko = $conn->query("SELECT * FROM toko WHERE user_id=$uid ORDER BY id DESC LIMIT 1")->fetch_assoc();

// Statistik
$jml_produk = $toko ? $conn->query("SELECT COUNT(*) AS c FROM produk WHERE toko_id={$toko['id']}")->fetch_assoc()['c'] : 0;
$jml_pesan  = $toko ? $conn->query("SELECT COUNT(*) AS c FROM pesan p JOIN produk pr ON p.produk_id=pr.id WHERE pr.toko_id={$toko['id']} AND p.status='baru'")->fetch_assoc()['c'] : 0;

// ── CRUD PRODUK ──
$action = $_GET['action'] ?? 'dashboard';
$pid    = intval($_GET['pid'] ?? 0);
$error  = '';

if ($toko && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $np   = trim($_POST['nama_produk'] ?? '');
    $desc = trim($_POST['deskripsi'] ?? '');
    $hrg  = floatval($_POST['harga'] ?? 0);
    $stk  = intval($_POST['stok'] ?? 0);
    $sat  = trim($_POST['satuan'] ?? 'pcs');
    $epid = intval($_POST['pid'] ?? 0);
    $gambar_lama = $_POST['gambar_lama'] ?? '';
    $gambar = $gambar_lama;

    if (!$np) { $error = 'Nama produk wajib diisi.'; }
    elseif ($hrg <= 0) { $error = 'Harga harus lebih dari 0.'; }
    else {
        if (!empty($_FILES['gambar']['name'])) {
            $ext = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext,['jpg','jpeg','png','webp'])) { $error='Format gambar tidak valid.'; }
            elseif ($_FILES['gambar']['size'] > 2*1024*1024) { $error='Gambar max 2MB.'; }
            else {
                $dir = '../uploads/produk/';
                if (!is_dir($dir)) mkdir($dir,0755,true);
                $fn = uniqid().'.'.$ext;
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $dir.$fn)) {
                    if ($gambar_lama && file_exists($dir.$gambar_lama)) @unlink($dir.$gambar_lama);
                    $gambar = $fn;
                }
            }
        }
        if (!$error) {
            if ($epid) {
                $st = $conn->prepare("UPDATE produk SET nama_produk=?,deskripsi=?,harga=?,stok=?,satuan=?,gambar=? WHERE id=? AND toko_id=?");
                $st->bind_param("ssdisiii",$np,$desc,$hrg,$stk,$sat,$gambar,$epid,$toko['id']); $st->execute(); $st->close();
                redirect('dashboard.php?action=produk','Produk diperbarui! ✅');
            } else {
                $st = $conn->prepare("INSERT INTO produk (toko_id,nama_produk,deskripsi,harga,stok,satuan,gambar) VALUES (?,?,?,?,?,?,?)");
                $st->bind_param("issdiss",$toko['id'],$np,$desc,$hrg,$stk,$sat,$gambar); $st->execute(); $st->close();
                redirect('dashboard.php?action=produk','Produk ditambahkan! 🎉');
            }
        } else { $action = $epid ? 'edit-produk' : 'tambah-produk'; if ($epid) $pid=$epid; }
    }
}

// Delete produk
if ($action === 'del-produk' && $pid && $toko) {
    $p = $conn->query("SELECT gambar FROM produk WHERE id=$pid AND toko_id={$toko['id']}")->fetch_assoc();
    if ($p) { if ($p['gambar']) @unlink("../uploads/produk/".$p['gambar']); $conn->query("DELETE FROM produk WHERE id=$pid"); }
    redirect('dashboard.php?action=produk','Produk dihapus.');
}

$produk_edit = null;
if ($action === 'edit-produk' && $pid && $toko)
    $produk_edit = $conn->query("SELECT * FROM produk WHERE id=$pid AND toko_id={$toko['id']}")->fetch_assoc();

$produk_list = $toko ? $conn->query("SELECT * FROM produk WHERE toko_id={$toko['id']} ORDER BY created_at DESC") : null;
$pesan_list  = $toko ? $conn->query("SELECT p.*,pr.nama_produk,u.nama AS nama_pembeli,u.telepon FROM pesan p JOIN produk pr ON p.produk_id=pr.id JOIN users u ON p.pembeli_id=u.id WHERE pr.toko_id={$toko['id']} ORDER BY p.created_at DESC LIMIT 20") : null;

flashMsg();
$flash = ob_get_clean();
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard Penjual — PasarLokal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;}
body{background:#f8fafc;margin:0;display:flex;}
.sidebar{width:240px;min-height:100vh;background:#fff;border-right:1px solid #e5e7eb;padding:0;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto;}
.sb-brand{padding:24px 20px;font-size:1.2rem;font-weight:700;color:#16a34a;border-bottom:1px solid #f0f0f0;}
.sb-nav{padding:16px 12px;}
.sb-link{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;color:#374151;font-size:.88rem;font-weight:500;text-decoration:none;transition:.15s;margin-bottom:3px;}
.sb-link:hover,.sb-link.act{background:#f0fdf4;color:#16a34a;}
.sb-link.act{font-weight:700;}
.sb-link i{width:18px;text-align:center;font-size:.9rem;}
.main{flex:1;overflow:hidden;}
.topbar{background:#fff;border-bottom:1px solid #e5e7eb;padding:16px 28px;display:flex;align-items:center;justify-content:space-between;}
.topbar h1{font-size:1.1rem;font-weight:700;color:#111827;margin:0;}
.content{padding:28px;}

.stat-card{background:#fff;border-radius:14px;border:1.5px solid #e5e7eb;padding:20px;transition:.2s;}
.stat-card:hover{border-color:#16a34a;}
.stat-val{font-size:1.8rem;font-weight:700;color:#111827;}
.stat-label{font-size:.8rem;color:#9ca3af;margin-top:4px;}

.table th{font-size:.78rem;text-transform:uppercase;color:#9ca3af;font-weight:600;border:none;}
.table td{font-size:.88rem;vertical-align:middle;border-color:#f5f5f5;}
.card{border:none;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.05);}
.card-header{background:#fff;border-bottom:1px solid #f0f0f0;border-radius:14px 14px 0 0!important;padding:16px 20px;font-weight:700;font-size:.9rem;color:#111827;}

.form-control,.form-select{border-radius:10px;border:1.5px solid #e2e8f0;padding:10px 14px;font-size:.88rem;}
.form-control:focus,.form-select:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.1);}
.form-label{font-weight:600;font-size:.82rem;color:#374151;}
.btn-green{background:#16a34a;color:#fff;border:none;border-radius:10px;}
.btn-green:hover{background:#15803d;color:#fff;}

.status-badge{padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:700;}
.s-pending{background:#fef3c7;color:#92400e;} .s-aktif{background:#d1fae5;color:#065f46;} .s-ditolak{background:#fee2e2;color:#991b1b;}
</style>
</head>
<body>
<div class="sidebar">
  <div class="sb-brand">🌿 PasarLokal</div>
  <nav class="sb-nav">
    <a href="dashboard.php" class="sb-link <?= $action==='dashboard'?'act':'' ?>"><i class="fa fa-gauge-high"></i>Dashboard</a>
    <a href="dashboard.php?action=produk" class="sb-link <?= in_array($action,['produk','tambah-produk','edit-produk'])?'act':'' ?>"><i class="fa fa-box"></i>Produk Saya</a>
    <a href="dashboard.php?action=pesan" class="sb-link <?= $action==='pesan'?'act':'' ?>"><i class="fa fa-message"></i>Pesan Masuk <?= $jml_pesan>0?"<span style='background:#ef4444;color:#fff;border-radius:20px;padding:1px 7px;font-size:.7rem;'>$jml_pesan</span>":'' ?></a>
    <a href="daftar-toko.php" class="sb-link"><i class="fa fa-store"></i><?= $toko ? 'Edit Toko' : 'Daftarkan Toko' ?></a>
    <hr style="border-color:#f0f0f0;margin:8px 0;">
    <a href="../index.php" class="sb-link"><i class="fa fa-arrow-left"></i>Lihat PasarLokal</a>
    <a href="../auth/logout.php" class="sb-link" style="color:#ef4444;"><i class="fa fa-right-from-bracket"></i>Logout</a>
  </nav>
</div>

<div class="main">
  <div class="topbar">
    <h1><?= match($action) {
      'produk'       => '📦 Produk Saya',
      'tambah-produk'=> '➕ Tambah Produk',
      'edit-produk'  => '✏️ Edit Produk',
      'pesan'        => '💬 Pesan Masuk',
      default        => '📊 Dashboard'
    } ?></h1>
    <div style="font-size:.83rem;color:#9ca3af;">👋 <?= htmlspecialchars($_SESSION['nama']) ?></div>
  </div>

  <div class="content">
    <?= $flash ?>

    <?php if ($error): ?>
    <div class="alert border-0 rounded-3 mb-4" style="background:#fef2f2;color:#991b1b;font-size:.88rem;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$toko && $action === 'dashboard'): ?>
    <!-- Belum punya toko -->
    <div style="text-align:center;padding:60px 20px;background:#fff;border-radius:16px;border:2px dashed #d1fae5;">
      <div style="font-size:3rem;margin-bottom:16px;">🏪</div>
      <h4 style="font-weight:700;color:#111827;">Anda belum punya toko</h4>
      <p style="color:#6b7280;">Daftarkan toko Anda agar pembeli di sekitar bisa menemukan produk Anda!</p>
      <a href="daftar-toko.php" class="btn btn-green px-5 mt-2" style="border-radius:10px;padding:12px 32px;">Daftarkan Toko Sekarang</a>
    </div>

    <?php elseif ($action === 'dashboard'): ?>
    <!-- Status toko -->
    <?php if ($toko['status'] === 'pending'): ?>
    <div class="alert border-0 rounded-3 mb-4" style="background:#fef3c7;color:#92400e;font-size:.88rem;">
      ⏳ <strong>Toko Anda sedang menunggu persetujuan admin.</strong> Biasanya 1x24 jam.
    </div>
    <?php elseif ($toko['status'] === 'ditolak'): ?>
    <div class="alert border-0 rounded-3 mb-4" style="background:#fee2e2;color:#991b1b;font-size:.88rem;">
      ❌ Toko ditolak: <?= htmlspecialchars($toko['catatan_admin'] ?? '-') ?>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card"><div style="font-size:1.5rem;margin-bottom:8px;">🏪</div>
          <div class="stat-val"><?= ucfirst($toko['status']) ?></div><div class="stat-label">Status Toko</div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card"><div style="font-size:1.5rem;margin-bottom:8px;">📦</div>
          <div class="stat-val"><?= $jml_produk ?></div><div class="stat-label">Produk Aktif</div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card"><div style="font-size:1.5rem;margin-bottom:8px;">💬</div>
          <div class="stat-val"><?= $jml_pesan ?></div><div class="stat-label">Pesan Baru</div></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card"><div style="font-size:1.5rem;margin-bottom:8px;">📍</div>
          <div class="stat-val" style="font-size:1rem;"><?= htmlspecialchars($toko['kota']) ?></div>
          <div class="stat-label"><?= htmlspecialchars($toko['kecamatan']??'') ?></div></div>
      </div>
    </div>
    <div class="d-flex gap-3">
      <a href="dashboard.php?action=tambah-produk" class="btn btn-green px-4" style="border-radius:10px;">+ Tambah Produk</a>
      <a href="daftar-toko.php" class="btn btn-outline-secondary px-4" style="border-radius:10px;">Edit Info Toko</a>
    </div>

    <?php elseif (in_array($action, ['tambah-produk','edit-produk'])): ?>
    <!-- FORM PRODUK -->
    <div class="card"><div class="card-body p-4">
      <form method="POST" enctype="multipart/form-data">
        <?php if ($produk_edit): ?>
          <input type="hidden" name="pid" value="<?= $produk_edit['id'] ?>">
          <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($produk_edit['gambar']??'') ?>">
        <?php endif; ?>
        <div class="row g-3">
          <div class="col-md-8">
            <div class="mb-3"><label class="form-label">Nama Produk *</label>
              <input type="text" name="nama_produk" class="form-control" required
                     value="<?= htmlspecialchars($_POST['nama_produk']??$produk_edit['nama_produk']??'') ?>"></div>
            <div class="mb-3"><label class="form-label">Deskripsi</label>
              <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($_POST['deskripsi']??$produk_edit['deskripsi']??'') ?></textarea></div>
            <div class="row g-3">
              <div class="col"><label class="form-label">Harga (Rp) *</label>
                <input type="number" name="harga" class="form-control" min="0" required
                       value="<?= htmlspecialchars($_POST['harga']??$produk_edit['harga']??'') ?>"></div>
              <div class="col"><label class="form-label">Stok</label>
                <input type="number" name="stok" class="form-control" min="0"
                       value="<?= htmlspecialchars($_POST['stok']??$produk_edit['stok']??'0') ?>"></div>
              <div class="col"><label class="form-label">Satuan</label>
                <input type="text" name="satuan" class="form-control"
                       value="<?= htmlspecialchars($_POST['satuan']??$produk_edit['satuan']??'pcs') ?>"></div>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Foto Produk</label>
            <?php if (!empty($produk_edit['gambar'])): ?>
            <img src="../uploads/produk/<?= htmlspecialchars($produk_edit['gambar']) ?>"
                 id="imgPrev" style="width:100%;border-radius:10px;margin-bottom:8px;display:block;">
            <?php else: ?>
            <img id="imgPrev" style="width:100%;border-radius:10px;margin-bottom:8px;display:none;">
            <?php endif; ?>
            <input type="file" name="gambar" class="form-control" accept="image/*"
                   onchange="p=document.getElementById('imgPrev');r=new FileReader();r.onload=e=>{p.src=e.target.result;p.style.display='block'};r.readAsDataURL(this.files[0])">
            <small class="text-muted">JPG/PNG/WEBP, maks 2MB</small>
          </div>
        </div>
        <hr class="my-4">
        <div class="d-flex gap-3">
          <button type="submit" class="btn btn-green px-5" style="border-radius:10px;padding:11px 32px;">
            <i class="fa fa-save me-2"></i><?= $produk_edit ? 'Simpan' : 'Tambah Produk' ?>
          </button>
          <a href="dashboard.php?action=produk" class="btn btn-outline-secondary px-4" style="border-radius:10px;">Batal</a>
        </div>
      </form>
    </div></div>

    <?php elseif ($action === 'produk'): ?>
    <!-- LIST PRODUK -->
    <div class="mb-3 d-flex justify-content-between align-items-center">
      <span style="font-size:.88rem;color:#6b7280;"><?= $jml_produk ?> produk</span>
      <a href="dashboard.php?action=tambah-produk" class="btn btn-green btn-sm px-3" style="border-radius:8px;">+ Tambah</a>
    </div>
    <div class="card"><div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead><tr>
          <th class="ps-4">Produk</th><th>Harga</th><th>Stok</th><th>Aksi</th>
        </tr></thead>
        <tbody>
        <?php while ($p = $produk_list->fetch_assoc()): ?>
        <tr>
          <td class="ps-4"><strong style="font-size:.88rem;"><?= htmlspecialchars($p['nama_produk']) ?></strong><br>
            <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($p['deskripsi']??'',0,50,'...')) ?></small></td>
          <td><strong style="color:#16a34a;"><?= formatRupiah($p['harga']) ?></strong></td>
          <td><span style="color:<?= $p['stok']>5?'#16a34a':($p['stok']>0?'#f59e0b':'#ef4444') ?>;font-weight:600;">
            <?= $p['stok'] ?> <?= htmlspecialchars($p['satuan']) ?></span></td>
          <td>
            <a href="dashboard.php?action=edit-produk&pid=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary me-1" style="border-radius:7px;">Edit</a>
            <a href="dashboard.php?action=del-produk&pid=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" style="border-radius:7px;"
               onclick="return confirm('Hapus produk ini?')">Hapus</a>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if ($jml_produk == 0): ?>
        <tr><td colspan="4" class="text-center text-muted py-5">Belum ada produk. <a href="dashboard.php?action=tambah-produk" style="color:#16a34a;">Tambah sekarang</a></td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div></div>

    <?php elseif ($action === 'pesan' && $toko): ?>
    <!-- PESAN MASUK -->
    <div class="card"><div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead><tr><th class="ps-4">Pembeli</th><th>Produk</th><th>Pesan</th><th>Jml</th><th>Waktu</th></tr></thead>
        <tbody>
        <?php $ada=false; while ($m = $pesan_list->fetch_assoc()): $ada=true; ?>
        <tr>
          <td class="ps-4"><strong style="font-size:.88rem;"><?= htmlspecialchars($m['nama_pembeli']) ?></strong><br>
            <?php if ($m['telepon']): ?>
            <a href="https://wa.me/<?= preg_replace('/^0/','62',preg_replace('/\D/','',$m['telepon'])) ?>?text=Halo+saya+dari+PasarLokal"
               target="_blank" style="font-size:.75rem;color:#25d366;"><i class="fa-brands fa-whatsapp"></i> Balas WA</a>
            <?php endif; ?>
          </td>
          <td style="font-size:.85rem;"><?= htmlspecialchars($m['nama_produk']) ?></td>
          <td style="font-size:.85rem;max-width:200px;"><?= htmlspecialchars($m['pesan']) ?></td>
          <td style="font-weight:700;"><?= $m['jumlah'] ?></td>
          <td style="font-size:.78rem;color:#9ca3af;"><?= date('d/m H:i', strtotime($m['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$ada): ?>
        <tr><td colspan="5" class="text-center text-muted py-5">Belum ada pesan masuk</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div></div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$content = ob_get_clean();
echo $content;
?>