<?php
// config/db.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pasar_lokal');
define('SITE_NAME', 'PasarLokal');
define('SITE_URL', 'http://localhost/pasar-lokal');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("<div style='font-family:sans-serif;padding:40px;color:#c0392b;'>
        <h2>❌ Koneksi database gagal</h2>
        <p>{$conn->connect_error}</p>
        <p>Pastikan XAMPP berjalan dan database <b>pasar_lokal</b> sudah diimport.</p>
    </div>");
}
$conn->set_charset("utf8mb4");

function isLoggedIn()  { return isset($_SESSION['user_id']); }
function isAdmin()     { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function isPenjual()   { return isset($_SESSION['role']) && $_SESSION['role'] === 'penjual'; }

function redirect($url, $msg = '', $type = 'success') {
    if ($msg) { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; }
    header("Location: $url"); exit;
}

function formatRupiah($n) { return 'Rp ' . number_format($n, 0, ',', '.'); }

// Haversine formula: jarak dua koordinat dalam km
function hitungJarak($lat1, $lon1, $lat2, $lon2) {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2)*sin($dLat/2) +
         cos(deg2rad($lat1))*cos(deg2rad($lat2))*
         sin($dLon/2)*sin($dLon/2);
    return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
}

function flashMsg() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $colors = ['success'=>'#d4edda|#155724','danger'=>'#f8d7da|#721c24','warning'=>'#fff3cd|#856404','info'=>'#cce5ff|#004085'];
        [$bg, $color] = explode('|', $colors[$f['type']] ?? $colors['info']);
        echo "<div style='background:$bg;color:$color;padding:12px 20px;border-radius:10px;margin-bottom:16px;font-size:.9rem;'>
                {$f['msg']}
              </div>";
    }
}

function kategoriIcon($k) {
    return match($k) {
        'makanan'    => '🍜',
        'fashion'    => '👗',
        'elektronik' => '💻',
        'pertanian'  => '🌾',
        'kerajinan'  => '🎨',
        'jasa'       => '🔧',
        default      => '🏪',
    };
}
?>