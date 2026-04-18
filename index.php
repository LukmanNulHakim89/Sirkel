<?php
session_start();
require_once 'config/db.php';

// Ambil semua toko aktif beserta produknya
$toko_list = $conn->query("
    SELECT t.*, u.nama AS nama_penjual, u.telepon,
           COUNT(p.id) AS jumlah_produk
    FROM toko t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN produk p ON p.toko_id = t.id AND p.status='aktif'
    WHERE t.status = 'aktif'
    GROUP BY t.id
    ORDER BY t.nama_toko
");

$toko_data = [];
while ($t = $toko_list->fetch_assoc()) $toko_data[] = $t;

$kategori_list = ['makanan','fashion','elektronik','pertanian','kerajinan','jasa','lainnya'];
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SirKel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="index.php" class="brand">🌿 PasarLokal</a>
    <div class="d-flex align-items-center gap-2">
      <?php if (isLoggedIn()): ?>
        <?php if (isPenjual()): ?>
          <a href="toko/dashboard.php" class="nav-btn">🏪 Toko Saya</a>
          <?php endif; ?>
          <?php if (isAdmin()): ?>
            <a href="admin/dashboard.php" class="nav-btn">⚙️ Admin</a>
            <?php endif; ?>
            <span style="font-size:.85rem;color:#6b7280;">👋 <?= htmlspecialchars($_SESSION['nama']) ?></span>
            <a href="auth/logout.php" class="nav-btn" style="border-color:#ef4444;color:#ef4444;">Keluar</a>
            <?php else: ?>
              <a href="auth/login.php" class="nav-btn">Masuk</a>
              <a href="auth/register.php" class="nav-btn fill">Daftar</a>
              <?php endif; ?>
            </div>
          </div>
        </nav>

<!-- HERO -->
<section class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-7">
        <h1>Belanja dari<br>UMKM Terdekat 📍</h1>
        <p>Temukan produk lokal berkualitas di sekitar Anda.<br>Dukung penjual lokal, hemat ongkir!</p>
      </div>
      <div class="col-md-5 text-md-end mt-3 mt-md-0">
        <div style="background:rgba(255,255,255,.15);border-radius:14px;padding:16px 20px;display:inline-block;text-align:left;">
          <div style="font-size:.8rem;color:rgba(255,255,255,.7);margin-bottom:4px;">Toko aktif saat ini</div>
          <div style="font-size:2rem;font-weight:700;"><?= count($toko_data) ?> Toko</div>
          <div style="font-size:.8rem;color:rgba(255,255,255,.7);">Siap melayani Anda</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- GPS PANEL -->
<div class="container">
  <div class="gps-panel">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <div style="font-weight:700;font-size:1rem;margin-bottom:4px;">
          📍 Lokasi & Radius Pencarian
        </div>
        <div id="gpsStatus" style="font-size:.83rem;color:#6b7280;">
          <span class="status-dot dot-wait"></span>Mendeteksi lokasi Anda...
        </div>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="radius-btn active" onclick="setRadius(1)"  data-r="1">1 km</button>
        <button class="radius-btn"        onclick="setRadius(3)"  data-r="3">3 km</button>
        <button class="radius-btn"        onclick="setRadius(5)"  data-r="5">5 km</button>
        <button class="radius-btn"        onclick="setRadius(10)" data-r="10">10 km</button>
        <button class="radius-btn"        onclick="setRadius(999)" data-r="999">Semua</button>
      </div>
    </div>

    <!-- PETA -->
    <div id="map"></div>

    <!-- Filter kategori -->
    <div class="d-flex gap-2 flex-wrap mt-3">
      <button class="kat-btn active" data-k="semua" onclick="filterKat('semua',this)">Semua</button>
      <?php foreach ($kategori_list as $k): ?>
      <button class="kat-btn" data-k="<?= $k ?>" onclick="filterKat('<?= $k ?>',this)">
        <?= kategoriIcon($k) ?> <?= ucfirst($k) ?>
      </button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- HASIL TOKO -->
  <div id="hasilLabel" class="section-title">Semua Toko</div>
  <div class="row g-4" id="tokoGrid"></div>
  <div id="emptyState" class="empty-state" style="display:none;">
    <div class="ico">🔍</div>
    <h5>Tidak ada toko ditemukan</h5>
    <p>Coba perbesar radius atau ganti kategori</p>
  </div>
</div>

<!-- CTA PENJUAL -->
<div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);margin-top:60px;padding:60px 0;">
  <div class="container text-center">
    <h2 style="font-weight:700;color:#166534;">Punya Usaha Lokal? 🏪</h2>
    <p style="color:#4b7a57;margin:12px 0 24px;">Daftarkan toko Anda dan jangkau pembeli di sekitar Anda secara gratis!</p>
    <?php if (!isLoggedIn()): ?>
    <a href="auth/register.php" class="btn" style="background:#16a34a;color:#fff;border-radius:12px;padding:14px 36px;font-weight:700;font-size:1rem;">
      Daftar Gratis Sekarang
    </a>
    <?php elseif (isPenjual()): ?>
    <a href="toko/daftar-toko.php" class="btn" style="background:#16a34a;color:#fff;border-radius:12px;padding:14px 36px;font-weight:700;font-size:1rem;">
      Daftarkan Toko Saya
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- FOOTER -->
<footer>
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="brand-f mb-2">🌿 PasarLokal</div>
        <p style="font-size:.85rem;">Platform digital untuk UMKM lokal agar mudah ditemukan pembeli di sekitarnya.</p>
      </div>
      <div class="col-md-4">
        <h6 style="color:#fff;font-weight:600;">Untuk Penjual</h6>
        <ul style="list-style:none;padding:0;font-size:.85rem;">
          <li><a href="auth/register.php" style="color:rgba(255,255,255,.6);text-decoration:none;">Daftar Toko Gratis</a></li>
          <li><a href="auth/login.php" style="color:rgba(255,255,255,.6);text-decoration:none;">Kelola Produk</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6 style="color:#fff;font-weight:600;">Tentang</h6>
        <p style="font-size:.85rem;">Tugas UAS Pemrograman Berbasis Web — Marketplace Hyperlokal berbasis GPS.</p>
      </div>
    </div>
    <hr style="border-color:rgba(255,255,255,.1);margin-top:28px;">
    <p class="text-center mb-0" style="font-size:.8rem;">© 2024 PasarLokal · Mendukung UMKM Lokal Indonesia</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// ── Data toko dari PHP ──
const semuaToko = <?= json_encode($toko_data) ?>;
const ikonKat = {makanan:'🍜',fashion:'👗',elektronik:'💻',pertanian:'🌾',kerajinan:'🎨',jasa:'🔧',lainnya:'🏪'};

let userLat = null, userLon = null;
let radiusKm = 1;
let katFilter = 'semua';
let map, userMarker, radiusCircle, tokoMarkers = [];

// ── Init Leaflet ──
map = L.map('map').setView([-6.2, 106.816], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// ── GPS ──
function setRadius(r) {
    radiusKm = r;
    document.querySelectorAll('.radius-btn').forEach(b => {
        b.classList.toggle('active', parseInt(b.dataset.r) === r || (r===999 && b.dataset.r==='999'));
    });
    if (radiusCircle) { map.removeLayer(radiusCircle); radiusCircle = null; }
    if (userLat && r !== 999) {
        radiusCircle = L.circle([userLat, userLon], {
            radius: r * 1000,
            color: '#16a34a', fillColor: '#16a34a', fillOpacity: 0.07, weight: 2
        }).addTo(map);
    }
    tampilkanToko();
}

function filterKat(k, el) {
    katFilter = k;
    document.querySelectorAll('.kat-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    tampilkanToko();
}

// ── Haversine JS ──
function jarak(lat1, lon1, lat2, lon2) {
    const R = 6371, dL = (lat2-lat1)*Math.PI/180, dO = (lon2-lon1)*Math.PI/180;
    const a = Math.sin(dL/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dO/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function tampilkanToko() {
    // Hapus marker lama
    tokoMarkers.forEach(m => map.removeLayer(m));
    tokoMarkers = [];

    const grid  = document.getElementById('tokoGrid');
    const empty = document.getElementById('emptyState');
    const label = document.getElementById('hasilLabel');
    grid.innerHTML = '';

    let filtered = semuaToko.filter(t => {
        if (katFilter !== 'semua' && t.kategori !== katFilter) return false;
        if (userLat && radiusKm !== 999) {
            t._jarak = jarak(userLat, userLon, parseFloat(t.latitude), parseFloat(t.longitude));
            return t._jarak <= radiusKm;
        }
        t._jarak = userLat ? jarak(userLat, userLon, parseFloat(t.latitude), parseFloat(t.longitude)) : null;
        return true;
    });

    if (userLat) filtered.sort((a,b) => (a._jarak||999) - (b._jarak||999));

    label.textContent = userLat
        ? `🏪 ${filtered.length} Toko dalam ${radiusKm === 999 ? 'semua' : radiusKm + ' km'}`
        : `🏪 Semua Toko (${filtered.length})`;

    if (!filtered.length) {
        empty.style.display = 'block';
    } else {
        empty.style.display = 'none';
        filtered.forEach(t => {
            // Map marker
            const ikon = L.divIcon({
                html: `<div style="background:#16a34a;color:#fff;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3);">${ikonKat[t.kategori]||'🏪'}</div>`,
                iconSize: [36,36], iconAnchor: [18,18], className:''
            });
            const m = L.marker([parseFloat(t.latitude), parseFloat(t.longitude)], {icon: ikon})
                .addTo(map)
                .bindPopup(`<strong>${t.nama_toko}</strong><br>${t.alamat}<br><a href="toko/detail.php?id=${t.id}" style="color:#16a34a;font-weight:600;">Lihat Toko →</a>`);
            tokoMarkers.push(m);

            // Card
            const jarakHtml = t._jarak !== null
                ? `<span class="jarak-label">${t._jarak < 1 ? Math.round(t._jarak*1000)+'m' : t._jarak.toFixed(1)+'km'}</span>`
                : '';
            const waLink = t.telepon
                ? `https://wa.me/${t.telepon.replace(/^0/,'62').replace(/\D/,'')}?text=Halo+${encodeURIComponent(t.nama_toko)}%2C+saya+tertarik+dengan+produk+Anda`
                : '#';

            grid.innerHTML += `
            <div class="col-md-6 col-lg-4">
              <div class="toko-card">
                <div class="toko-header">
                  <div class="toko-icon">${ikonKat[t.kategori]||'🏪'}</div>
                  <div class="flex-grow-1">
                    <div class="toko-nama">${t.nama_toko}</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                      <span class="badge-kat">${t.kategori}</span>
                      ${jarakHtml}
                    </div>
                  </div>
                </div>
                <div class="toko-body">
                  <div style="font-size:.82rem;color:#6b7280;margin-bottom:10px;">
                    📍 ${t.alamat}${t.kecamatan ? ', '+t.kecamatan : ''}, ${t.kota}
                  </div>
                  <div style="font-size:.82rem;color:#374151;margin-bottom:12px;">${t.deskripsi||'—'}</div>
                  <div style="font-size:.78rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">${t.jumlah_produk} Produk</div>
                  ${t.telepon ? `<button onclick="window.open('${waLink}','_blank')" class="btn-wa"><i class="fa-brands fa-whatsapp me-1"></i>Chat via WhatsApp</button>` : ''}
                  <a href="toko/detail.php?id=${t.id}" class="btn-detail">Lihat Produk Lengkap →</a>
                </div>
              </div>
            </div>`;
        });
    }
}

// ── Minta GPS ──
document.getElementById('gpsStatus').innerHTML = '<span class="status-dot dot-wait"></span>Meminta izin lokasi...';
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        pos => {
            userLat = pos.coords.latitude;
            userLon = pos.coords.longitude;
            document.getElementById('gpsStatus').innerHTML =
                `<span class="status-dot dot-ok"></span>Lokasi terdeteksi: ${userLat.toFixed(5)}, ${userLon.toFixed(5)}`;

            userMarker = L.marker([userLat, userLon], {
                icon: L.divIcon({
                    html:'<div style="background:#3b82f6;border:3px solid #fff;border-radius:50%;width:16px;height:16px;box-shadow:0 0 0 4px rgba(59,130,246,.3);"></div>',
                    iconSize:[16,16], iconAnchor:[8,8], className:''
                })
            }).addTo(map).bindPopup('📍 Lokasi Anda').openPopup();

            map.setView([userLat, userLon], 14);
            setRadius(radiusKm);
        },
        err => {
            document.getElementById('gpsStatus').innerHTML =
                '<span class="status-dot dot-err"></span>Lokasi tidak bisa diakses — menampilkan semua toko';
            tampilkanToko();
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
} else {
    document.getElementById('gpsStatus').innerHTML =
        '<span class="status-dot dot-err"></span>Browser tidak mendukung GPS';
    tampilkanToko();
}
</script>
</body>
</html>