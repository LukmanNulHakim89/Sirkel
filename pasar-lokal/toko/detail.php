<?php
session_start();
require_once '../config/db.php';

$id = intval($_GET['id'] ?? 0);
$toko = $conn->query("
    SELECT t.*, u.nama AS nama_penjual, u.telepon, u.email AS email_penjual
    FROM toko t JOIN users u ON t.user_id = u.id
    WHERE t.id = $id AND t.status = 'aktif'
")->fetch_assoc();

if (!$toko) redirect('../index.php', 'Toko tidak ditemukan.', 'danger');

$produk_list = $conn->query("
    SELECT * FROM produk WHERE toko_id=$id AND status='aktif' ORDER BY created_at DESC
");

// Kirim pesan/minat
$sukses = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $pid   = intval($_POST['produk_id'] ?? 0);
    $pesan = trim($_POST['pesan'] ?? '');
    $jml   = intval($_POST['jumlah'] ?? 1);
    $uid   = $_SESSION['user_id'];
    if ($pid && $pesan) {
        $st = $conn->prepare("INSERT INTO pesan (produk_id,pembeli_id,pesan,jumlah) VALUES (?,?,?,?)");
        $st->bind_param("issi", $pid, $pesan, $jml); $st->execute(); $st->close();
        $sukses = 'Pesan terkirim! Penjual akan segera merespons.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($toko['nama_toko']) ?> — PasarLokal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;}
body{background:#f8fafc;margin:0;}
.navbar{background:#fff;border-bottom:1px solid #e5e7eb;padding:14px 0;position:sticky;top:0;z-index:999;}
.brand{font-size:1.4rem;font-weight:700;color:#16a34a;text-decoration:none;}

.toko-hero{background:linear-gradient(135deg,#f0fdf4,#dcfce7);padding:40px 0;border-bottom:1px solid #d1fae5;}
.toko-avatar{width:80px;height:80px;background:#16a34a;border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:2.5rem;}
.toko-nama{font-size:1.6rem;font-weight:700;color:#111827;}
.badge-status{background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:.78rem;font-weight:700;}

.produk-card{background:#fff;border-radius:14px;border:1.5px solid #e5e7eb;overflow:hidden;transition:.2s;height:100%;}
.produk-card:hover{border-color:#16a34a;box-shadow:0 6px 24px rgba(22,163,74,.1);}
.produk-img{width:100%;height:170px;object-fit:cover;background:linear-gradient(135deg,#f0fdf4,#dcfce7);display:flex;align-items:center;justify-content:center;font-size:3rem;}
.produk-body{padding:14px;}
.produk-nama{font-weight:700;font-size:.95rem;color:#111827;}
.produk-harga{font-size:1.1rem;font-weight:700;color:#16a34a;margin:6px 0;}
.btn-minat{background:#16a34a;color:#fff;border:none;border-radius:8px;padding:9px;font-size:.83rem;font-weight:600;width:100%;transition:.2s;}
.btn-minat:hover{background:#15803d;}
.btn-wa{background:#25d366;color:#fff;border:none;border-radius:8px;padding:9px;font-size:.83rem;font-weight:600;width:100%;margin-top:6px;transition:.2s;}
.btn-wa:hover{background:#128c7e;}

#mini-map{height:220px;border-radius:12px;border:1.5px solid #e5e7eb;}

.sidebar-card{background:#fff;border-radius:14px;border:1.5px solid #e5e7eb;padding:20px;margin-bottom:16px;}
.sidebar-card h6{font-weight:700;font-size:.85rem;text-transform:uppercase;color:#9ca3af;letter-spacing:.5px;margin-bottom:12px;}
</style>
</head>
<body>

<nav class="navbar">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="../index.php" class="brand">🌿 PasarLokal</a>
    <a href="../index.php" style="color:#16a34a;font-weight:600;font-size:.9rem;text-decoration:none;">
      ← Kembali ke Peta
    </a>
  </div>
</nav>

<!-- TOKO HERO -->
<div class="toko-hero">
  <div class="container">
    <div class="d-flex align-items-center gap-4 flex-wrap">
      <div class="toko-avatar"><?= kategoriIcon($toko['kategori']) ?></div>
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="toko-nama"><?= htmlspecialchars($toko['nama_toko']) ?></span>
          <span class="badge-status">✅ Aktif</span>
        </div>
        <div style="color:#6b7280;font-size:.9rem;">
          📍 <?= htmlspecialchars($toko['alamat'].', '.($toko['kecamatan']?$toko['kecamatan'].', ':'').$toko['kota']) ?>
        </div>
        <div style="color:#6b7280;font-size:.85rem;margin-top:4px;">
          👤 Penjual: <?= htmlspecialchars($toko['nama_penjual']) ?>
          <?php if ($toko['telepon']): ?>
          · 📱 <?= htmlspecialchars($toko['telepon']) ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php if ($toko['deskripsi']): ?>
    <p style="margin-top:16px;color:#374151;font-size:.9rem;max-width:600px;"><?= htmlspecialchars($toko['deskripsi']) ?></p>
    <?php endif; ?>
  </div>
</div>

<div class="container py-4">
  <?php if ($sukses): ?>
  <div class="alert border-0 rounded-3 mb-4" style="background:#d1fae5;color:#065f46;font-size:.9rem;">
    ✅ <?= $sukses ?>
  </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Produk -->
    <div class="col-lg-8">
      <h5 style="font-weight:700;margin-bottom:20px;">🛒 Produk Tersedia</h5>
      <div class="row g-3">
        <?php
        $produk_arr = [];
        while ($p = $produk_list->fetch_assoc()) $produk_arr[] = $p;
        foreach ($produk_arr as $p):
            $waMsg = urlencode("Halo {$toko['nama_toko']}, saya tertarik dengan *{$p['nama_produk']}* (Rp " . number_format($p['harga'],0,',','.') . "). Apakah masih tersedia?");
            $waLink = $toko['telepon'] ? "https://wa.me/".preg_replace('/^0/','62',preg_replace('/\D/','',$toko['telepon']))."?text=$waMsg" : '#';
        ?>
        <div class="col-sm-6">
          <div class="produk-card">
            <div class="produk-img">
              <?php if ($p['gambar'] && file_exists("../uploads/produk/{$p['gambar']}")): ?>
                <img src="../uploads/produk/<?= htmlspecialchars($p['gambar']) ?>" style="width:100%;height:170px;object-fit:cover;">
              <?php else: ?>
                <?= kategoriIcon($toko['kategori']) ?>
              <?php endif; ?>
            </div>
            <div class="produk-body">
              <div class="produk-nama"><?= htmlspecialchars($p['nama_produk']) ?></div>
              <div style="font-size:.8rem;color:#9ca3af;margin-top:2px;"><?= htmlspecialchars($p['deskripsi'] ?? '') ?></div>
              <div class="produk-harga"><?= formatRupiah($p['harga']) ?> <span style="font-size:.75rem;color:#9ca3af;font-weight:400;">/ <?= htmlspecialchars($p['satuan']) ?></span></div>
              <div style="font-size:.78rem;color:<?= $p['stok']>0?'#16a34a':'#ef4444' ?>;font-weight:600;margin-bottom:8px;">
                <?= $p['stok'] > 0 ? "Stok: {$p['stok']} {$p['satuan']}" : 'Habis' ?>
              </div>
              <?php if ($p['stok'] > 0): ?>
                <?php if (isLoggedIn()): ?>
                <button class="btn-minat" data-bs-toggle="modal" data-bs-target="#pesanModal"
                        data-id="<?= $p['id'] ?>" data-nama="<?= htmlspecialchars($p['nama_produk']) ?>"
                        data-harga="<?= formatRupiah($p['harga']) ?>">
                  💬 Tanyakan ke Penjual
                </button>
                <?php else: ?>
                <a href="../auth/login.php" class="btn-minat" style="text-align:center;display:block;text-decoration:none;">
                  Login untuk Bertanya
                </a>
                <?php endif; ?>
                <?php if ($toko['telepon']): ?>
                <button onclick="window.open('<?= $waLink ?>','_blank')" class="btn-wa">
                  <i class="fa-brands fa-whatsapp me-1"></i>WhatsApp Langsung
                </button>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$produk_arr): ?>
        <div class="col-12 text-center py-5 text-muted">Belum ada produk di toko ini.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
      <div class="sidebar-card">
        <h6>📍 Lokasi Toko</h6>
        <div id="mini-map"></div>
        <p style="font-size:.82rem;color:#6b7280;margin-top:10px;margin-bottom:0;">
          <?= htmlspecialchars($toko['alamat']) ?>, <?= htmlspecialchars($toko['kecamatan']??'') ?>, <?= htmlspecialchars($toko['kota']) ?>
        </p>
      </div>
      <?php if ($toko['telepon']): ?>
      <div class="sidebar-card">
        <h6>📞 Kontak Penjual</h6>
        <p style="font-size:.9rem;margin-bottom:10px;color:#374151;">
          👤 <?= htmlspecialchars($toko['nama_penjual']) ?><br>
          📱 <?= htmlspecialchars($toko['telepon']) ?>
        </p>
        <a href="https://wa.me/<?= preg_replace('/^0/','62',preg_replace('/\D/','',$toko['telepon'])) ?>?text=Halo+<?= urlencode($toko['nama_toko']) ?>"
           target="_blank" class="btn-wa" style="display:block;text-align:center;text-decoration:none;padding:10px;">
          <i class="fa-brands fa-whatsapp me-1"></i>Chat WhatsApp
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal Pesan -->
<div class="modal fade" id="pesanModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-bold">💬 Tanyakan Produk</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-0">
        <form method="POST">
          <input type="hidden" name="produk_id" id="modalProdukId">
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Produk</label>
            <input type="text" id="modalProdukNama" class="form-control form-control-sm" readonly
                   style="border-radius:8px;background:#f9fafb;">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Jumlah</label>
            <input type="number" name="jumlah" class="form-control form-control-sm" value="1" min="1"
                   style="border-radius:8px;width:100px;">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:.85rem;">Pesan ke Penjual</label>
            <textarea name="pesan" class="form-control form-control-sm" rows="3"
                      placeholder="Contoh: Apakah masih tersedia? Bisa antar ke...?"
                      style="border-radius:8px;" required></textarea>
          </div>
          <button type="submit" class="btn w-100 fw-bold" style="background:#16a34a;color:#fff;border-radius:10px;padding:11px;">
            Kirim Pesan
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const lat = <?= $toko['latitude'] ?>, lon = <?= $toko['longitude'] ?>;
const miniMap = L.map('mini-map').setView([lat,lon],15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);
L.marker([lat,lon]).addTo(miniMap)
  .bindPopup('<?= addslashes($toko['nama_toko']) ?>').openPopup();

document.getElementById('pesanModal').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('modalProdukId').value  = btn.dataset.id;
    document.getElementById('modalProdukNama').value = btn.dataset.nama + ' — ' + btn.dataset.harga;
});
</script>
</body>
</html>
