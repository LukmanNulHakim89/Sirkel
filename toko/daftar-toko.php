<?php
session_start();
require_once '../config/db.php';
if (!isLoggedIn() || !isPenjual()) redirect('../auth/login.php','Login sebagai penjual.','danger');

$uid  = $_SESSION['user_id'];
$toko = $conn->query("SELECT * FROM toko WHERE user_id=$uid LIMIT 1")->fetch_assoc();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_toko'] ?? '');
    $desk = trim($_POST['deskripsi'] ?? '');
    $almt = trim($_POST['alamat'] ?? '');
    $kota = trim($_POST['kota'] ?? '');
    $kec  = trim($_POST['kecamatan'] ?? '');
    $lat  = floatval($_POST['latitude'] ?? 0);
    $lon  = floatval($_POST['longitude'] ?? 0);
    $kat  = $_POST['kategori'] ?? 'lainnya';

    if (!$nama||!$almt||!$kota) { $error='Nama toko, alamat, dan kota wajib diisi.'; }
    elseif (!$lat||!$lon)       { $error='Koordinat GPS wajib diisi. Klik tombol "Deteksi Lokasi".'; }
    else {
        if ($toko) {
            $st = $conn->prepare("UPDATE toko SET nama_toko=?,deskripsi=?,alamat=?,kota=?,kecamatan=?,latitude=?,longitude=?,kategori=? WHERE id=?");
            $st->bind_param("sssssddsi",$nama,$desk,$almt,$kota,$kec,$lat,$lon,$kat,$toko['id']); $st->execute(); $st->close();
            redirect('dashboard.php','Informasi toko diperbarui! ✅');
        } else {
            $st = $conn->prepare("INSERT INTO toko (user_id,nama_toko,deskripsi,alamat,kota,kecamatan,latitude,longitude,kategori) VALUES (?,?,?,?,?,?,?,?,?)");
            $st->bind_param("issssssdds",$uid,$nama,$desk,$almt,$kota,$kec,$lat,$lon,$kat); 
            // fix: d for decimal
            $st2 = $conn->prepare("INSERT INTO toko (user_id,nama_toko,deskripsi,alamat,kota,kecamatan,latitude,longitude,kategori,status) VALUES (?,?,?,?,?,?,?,?,'lainnya','pending')");
            $st3 = $conn->prepare("INSERT INTO toko (user_id,nama_toko,deskripsi,alamat,kota,kecamatan,latitude,longitude,kategori,status) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $st3->bind_param("isssssddss",$uid,$nama,$desk,$almt,$kota,$kec,$lat,$lon,$kat,'pending');
            $st3->execute(); $st3->close();
            redirect('dashboard.php','Toko didaftarkan! Menunggu persetujuan admin. ⏳');
        }
    }
}
$kategori_list = ['makanan','fashion','elektronik','pertanian','kerajinan','jasa','lainnya'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $toko ? 'Edit Toko' : 'Daftar Toko' ?> — PasarLokal</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
*{font-family:'Plus Jakarta Sans',sans-serif;}
body{background:#f8fafc;min-height:100vh;}
.navbar{background:#fff;border-bottom:1px solid #e5e7eb;padding:14px 0;}
.brand{font-size:1.4rem;font-weight:700;color:#16a34a;text-decoration:none;}
.card{border:none;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.06);}
.form-control,.form-select{border-radius:10px;border:1.5px solid #e2e8f0;padding:11px 14px;font-size:.9rem;}
.form-control:focus,.form-select:focus{border-color:#16a34a;box-shadow:0 0 0 3px rgba(22,163,74,.1);}
.form-label{font-weight:600;font-size:.83rem;color:#374151;}
.btn-green{background:#16a34a;color:#fff;border:none;border-radius:10px;}
.btn-green:hover{background:#15803d;color:#fff;}
#map-pick{height:280px;border-radius:12px;border:1.5px solid #e2e8f0;margin-top:10px;}
.gps-box{background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:10px;padding:14px 16px;margin-bottom:12px;}
.info-box{background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:12px 16px;font-size:.83rem;color:#1e40af;margin-bottom:20px;}
</style>
</head>
<body>
<nav class="navbar">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="dashboard.php" class="brand">🌿 PasarLokal</a>
    <a href="dashboard.php" style="color:#16a34a;font-weight:600;font-size:.875rem;text-decoration:none;">← Dashboard</a>
  </div>
</nav>

<div class="container py-4" style="max-width:720px;">
  <h4 style="font-weight:700;margin-bottom:4px;"><?= $toko ? '✏️ Edit Informasi Toko' : '🏪 Daftarkan Toko Baru' ?></h4>
  <p style="color:#6b7280;font-size:.88rem;margin-bottom:24px;">
    <?= $toko ? 'Perbarui informasi toko Anda.' : 'Lengkapi form berikut. Toko akan aktif setelah disetujui admin.' ?>
  </p>

  <?php if ($error): ?>
  <div class="alert border-0 rounded-3 mb-4" style="background:#fef2f2;color:#991b1b;font-size:.88rem;">
    <i class="fa fa-circle-exclamation me-1"></i><?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <?php if (!$toko): ?>
  <div class="info-box">
    ℹ️ Setelah mendaftar, admin akan mereview toko Anda dalam 1x24 jam. Anda akan bisa menambah produk setelah disetujui.
  </div>
  <?php endif; ?>

  <div class="card p-4">
    <form method="POST">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nama Toko *</label>
          <input type="text" name="nama_toko" class="form-control" required
                 value="<?= htmlspecialchars($_POST['nama_toko']??$toko['nama_toko']??'') ?>"
                 placeholder="Contoh: Warung Bu Sari">
        </div>
        <div class="col-md-6">
          <label class="form-label">Kategori *</label>
          <select name="kategori" class="form-select">
            <?php foreach ($kategori_list as $k): ?>
            <option value="<?= $k ?>" <?= (($_POST['kategori']??$toko['kategori']??'')===$k)?'selected':'' ?>>
              <?= kategoriIcon($k) ?> <?= ucfirst($k) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Deskripsi Toko</label>
          <textarea name="deskripsi" class="form-control" rows="2"
                    placeholder="Ceritakan produk/layanan Anda secara singkat..."><?= htmlspecialchars($_POST['deskripsi']??$toko['deskripsi']??'') ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Alamat Lengkap *</label>
          <input type="text" name="alamat" class="form-control" required
                 value="<?= htmlspecialchars($_POST['alamat']??$toko['alamat']??'') ?>"
                 placeholder="Jl. Contoh No. 12, RT/RW...">
        </div>
        <div class="col-md-6">
          <label class="form-label">Kecamatan</label>
          <input type="text" name="kecamatan" class="form-control"
                 value="<?= htmlspecialchars($_POST['kecamatan']??$toko['kecamatan']??'') ?>"
                 placeholder="Pancoran Mas">
        </div>
        <div class="col-md-6">
          <label class="form-label">Kota / Kabupaten *</label>
          <input type="text" name="kota" class="form-control" required
                 value="<?= htmlspecialchars($_POST['kota']??$toko['kota']??'') ?>"
                 placeholder="Depok">
        </div>

        <!-- GPS -->
        <div class="col-12">
          <label class="form-label">📍 Koordinat GPS (untuk muncul di peta) *</label>
          <div class="gps-box">
            <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
              <button type="button" onclick="deteksiGPS()" class="btn btn-green btn-sm px-3" style="border-radius:8px;">
                <i class="fa fa-location-crosshairs me-1"></i>Deteksi Lokasi Otomatis
              </button>
              <span id="gpsInfo" style="font-size:.82rem;color:#6b7280;">Atau klik langsung di peta</span>
            </div>
            <div class="row g-2">
              <div class="col">
                <input type="number" name="latitude" id="lat" class="form-control" step="any"
                       value="<?= htmlspecialchars($_POST['latitude']??$toko['latitude']??'') ?>"
                       placeholder="Latitude (-6.xxxxx)" required>
              </div>
              <div class="col">
                <input type="number" name="longitude" id="lon" class="form-control" step="any"
                       value="<?= htmlspecialchars($_POST['longitude']??$toko['longitude']??'') ?>"
                       placeholder="Longitude (106.xxxxx)" required>
              </div>
            </div>
          </div>
          <div id="map-pick"></div>
          <small class="text-muted">Klik peta untuk menentukan lokasi toko secara manual.</small>
        </div>
      </div>

      <hr class="my-4">
      <div class="d-flex gap-3">
        <button type="submit" class="btn btn-green px-5" style="padding:12px 32px;">
          <i class="fa fa-save me-2"></i><?= $toko ? 'Simpan Perubahan' : 'Daftarkan Toko' ?>
        </button>
        <a href="dashboard.php" class="btn btn-outline-secondary px-4" style="border-radius:10px;">Batal</a>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const initLat = parseFloat(document.getElementById('lat').value) || -6.3728;
const initLon = parseFloat(document.getElementById('lon').value) || 106.8317;

const map  = L.map('map-pick').setView([initLat, initLon], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

let marker = L.marker([initLat, initLon], {draggable:true}).addTo(map);
marker.on('dragend', e => {
    const p = e.target.getLatLng();
    document.getElementById('lat').value = p.lat.toFixed(7);
    document.getElementById('lon').value = p.lng.toFixed(7);
});

map.on('click', e => {
    marker.setLatLng(e.latlng);
    document.getElementById('lat').value = e.latlng.lat.toFixed(7);
    document.getElementById('lon').value = e.latlng.lng.toFixed(7);
    document.getElementById('gpsInfo').textContent = '📍 Lokasi dipilih dari peta';
});

function deteksiGPS() {
    document.getElementById('gpsInfo').textContent = '⏳ Mendeteksi...';
    if (!navigator.geolocation) { document.getElementById('gpsInfo').textContent='GPS tidak didukung browser ini.'; return; }
    navigator.geolocation.getCurrentPosition(p => {
        const {latitude:la, longitude:lo} = p.coords;
        document.getElementById('lat').value = la.toFixed(7);
        document.getElementById('lon').value = lo.toFixed(7);
        marker.setLatLng([la,lo]);
        map.setView([la,lo],17);
        document.getElementById('gpsInfo').textContent = `✅ ${la.toFixed(5)}, ${lo.toFixed(5)}`;
    }, () => { document.getElementById('gpsInfo').textContent='❌ Izin lokasi ditolak. Klik peta manual.'; });
}
</script>
</body>
</html>
