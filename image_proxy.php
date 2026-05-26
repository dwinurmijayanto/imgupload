<?php
/**
 * Image Proxy — img.vidshare.my.id/images/{id}/{filename}
 *
 * Letakkan file ini di root server, lalu tambahkan rule .htaccess:
 *
 *   RewriteEngine On
 *   RewriteRule ^images/(.+)$ image_proxy.php?path=$1 [QSA,L]
 *
 * Atau jika pakai Nginx, tambahkan di server block:
 *
 *   location ~ ^/images/(.+)$ {
 *       fastcgi_param QUERY_STRING path=$1;
 *       include fastcgi_params;
 *       fastcgi_pass unix:/run/php/php8.x-fpm.sock;
 *       fastcgi_param SCRIPT_FILENAME /path/to/image_proxy.php;
 *   }
 *
 * Request masuk : https://img.vidshare.my.id/images/babc5820ecc53fbf/photo.jpg
 * Diproxy ke   : https://i.8upload.com/image/babc5820ecc53fbf/photo.jpg
 */

// ── Keamanan: Tidak boleh ada header yang bocorkan origin ────────────────
header_remove('X-Powered-By');
header_remove('Server');

// ── Ambil path dari query string (diisi oleh RewriteRule) ───────────────
$rawPath = $_GET['path'] ?? '';

// Sanitasi: hanya boleh huruf, angka, /, -, _, dan titik
$cleanPath = preg_replace('#[^a-zA-Z0-9/_\-\.]#', '', $rawPath);

if (empty($cleanPath)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Bad Request';
    exit;
}

// ── Validasi ekstensi ────────────────────────────────────────────────────
$ext = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp'];

if (!in_array($ext, $allowedExts, true)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo 'Forbidden';
    exit;
}

// ── Cache sederhana di filesystem ────────────────────────────────────────
$cacheDir  = sys_get_temp_dir() . '/imgproxy_cache/';
$cacheKey  = md5($cleanPath);
$cachePath = $cacheDir . $cacheKey . '.' . $ext;
$cacheTTL  = 86400 * 7; // 7 hari

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}

// ── Mime types ───────────────────────────────────────────────────────────
$mimeMap = [
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'avif' => 'image/avif',
    'bmp'  => 'image/bmp',
];
$mime = $mimeMap[$ext] ?? 'image/jpeg';

// ── Cek cache ─────────────────────────────────────────────────────────────
if (file_exists($cachePath) && (time() - filemtime($cachePath)) < $cacheTTL) {
    sendImage($cachePath, $mime);
    exit;
}

// ── Fetch dari 8upload (tersembunyi dari client) ──────────────────────────
$originUrl = 'https://i.8upload.com/image/' . $cleanPath;

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $originUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; ImgProxy/1.0)',
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER     => [
        'Accept: image/*,*/*;q=0.8',
        'Accept-Encoding: identity',
    ],
]);

$imageData = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr   = curl_error($ch);
curl_close($ch);

if ($curlErr || $httpCode !== 200 || empty($imageData)) {
    http_response_code($httpCode ?: 502);
    header('Content-Type: text/plain');
    // Pesan error TIDAK menyebutkan 8upload
    echo 'Image not available';
    exit;
}

// ── Simpan ke cache ───────────────────────────────────────────────────────
@file_put_contents($cachePath, $imageData);

// ── Kirim gambar ──────────────────────────────────────────────────────────
sendImage($cachePath, $mime, $imageData);


// ─────────────────────────────────────────────────────────────────────────
function sendImage(string $path, string $mime, string $data = null): void
{
    $data    = $data ?? file_get_contents($path);
    $size    = strlen($data);
    $etag    = '"' . md5($data) . '"';
    $lastMod = gmdate('D, d M Y H:i:s', filemtime($path)) . ' GMT';

    // Conditional request support
    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    if ($ifNoneMatch === $etag) {
        http_response_code(304);
        exit;
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $size);
    header('Cache-Control: public, max-age=604800, immutable');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . $lastMod);
    // Sembunyikan referrer saat gambar di-embed di halaman lain
    header('Referrer-Policy: no-referrer');
    // Tidak ada header yang bocorkan origin
    echo $data;
}
