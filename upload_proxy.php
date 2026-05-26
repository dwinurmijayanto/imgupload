<?php
/**
 * upload_proxy.php — Proxy upload FILE ke 8upload.com
 * Letakkan di: img.beritatkp.com/upload_proxy.php
 */

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// ── CORS: izinkan request dari browser (XHR langsung dari WP admin) ────────
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: false');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept');
header('Access-Control-Max-Age: 86400');
header_remove('X-Powered-By');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// ── Konfigurasi ────────────────────────────────────────────────────────────
$_protocol  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$_all_hdrs  = getallheaders();
$_cf_worker = $_all_hdrs['Cf-Worker'] ?? null;
$_host      = $_cf_worker ? 'img.' . $_cf_worker : ($_SERVER['HTTP_HOST'] ?? 'img.beritatkp.com');
define('PUBLIC_CDN',     $_protocol . '://' . $_host . '/images');
define('BACKEND_UPLOAD', 'https://8upload.com/upload/mt/');
define('BACKEND_BASE',   'https://8upload.com');
define('MAX_FILE_SIZE',  12 * 1024 * 1024);
define('ALLOWED_EXTS',   ['jpg','jpeg','png','gif','webp','avif','bmp','tif','tiff']);
define('DEBUG_MODE',     false);

// ── Validasi ada file ──────────────────────────────────────────────────────
if (empty($_FILES['images'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Tidak ada file. Field name harus "images[]".',
    ]);
    exit;
}

// ── Normalisasi $_FILES ────────────────────────────────────────────────────
$files = normalizeFiles($_FILES['images']);

if (empty($files)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File tidak valid.']);
    exit;
}

$file = $files[0];

// ── Validasi file ──────────────────────────────────────────────────────────
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => uploadErrMsg($file['error'])]);
    exit;
}
if ($file['size'] > MAX_FILE_SIZE) {
    echo json_encode(['success' => false, 'error' => 'Ukuran file maksimal 12 MB.']);
    exit;
}
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ALLOWED_EXTS, true)) {
    echo json_encode(['success' => false, 'error' => "Format .$ext tidak didukung."]);
    exit;
}
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
if (!str_starts_with($mime, 'image/')) {
    echo json_encode(['success' => false, 'error' => 'Bukan file gambar valid.']);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// STEP 1: POST file ke 8upload.com via cURL (server-side, bebas CORS)
// ══════════════════════════════════════════════════════════════════════════
$curlFile = new CURLFile($file['tmp_name'], $mime, $file['name']);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => BACKEND_UPLOAD,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => ['images[]' => $curlFile],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT        => 90,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36',
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json, text/javascript, */*; q=0.01',
        'X-Requested-With: XMLHttpRequest',
        'Referer: https://8upload.com/',
        'Origin: https://8upload.com',
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_COOKIEFILE     => '',
    CURLOPT_COOKIEJAR      => '',
]);

$rawResponse = curl_exec($ch);
$httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$effectiveUrl= curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$curlErr     = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo json_encode([
        'success' => false,
        'error'   => 'Koneksi ke storage gagal: ' . $curlErr,
    ]);
    exit;
}

$respHeaders = substr($rawResponse, 0, $headerSize);
$respBody    = substr($rawResponse, $headerSize);

// ── Parse result path ──────────────────────────────────────────────────────
$resultPath = null;

$json = json_decode($respBody, true);
if ($json !== null) {
    if (isset($json['response']))                              $resultPath = $json['response'];
    elseif (is_string($json) && str_starts_with($json, '/')) $resultPath = $json;
    elseif (isset($json[0]) && is_string($json[0]))           $resultPath = $json[0];
}

if (!$resultPath) {
    $clean = trim($respBody, " \t\n\r\0\x0B\"'");
    if (str_starts_with($clean, '/')) $resultPath = $clean;
}

if (!$resultPath && preg_match('#(/uploads/[a-zA-Z0-9/]+)#', $respBody, $m)) {
    $resultPath = $m[1];
}

// Cek Location header
if (!$resultPath) {
    preg_match_all('/^Location:\s*(.+)$/im', $respHeaders, $locs);
    foreach (array_reverse($locs[1] ?? []) as $loc) {
        $loc = trim($loc);
        if (str_contains($loc, '8upload.com/uploads/')) {
            $resultPath = parse_url($loc, PHP_URL_PATH);
            break;
        }
        if (preg_match('#i\.8upload\.com/image/#', $loc)) {
            $masked = maskUrl(trim($loc));
            outputSuccess($masked);
            exit;
        }
    }
}

if (!$resultPath) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error'   => 'Response dari storage tidak dikenali (HTTP ' . $httpCode . ').',
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// STEP 2: Fetch halaman preview → ambil direct link
// ══════════════════════════════════════════════════════════════════════════
$previewUrl = BACKEND_BASE . '/' . ltrim($resultPath, '/');

$pageHtml = httpGet($previewUrl, ['Referer: https://8upload.com/']);

$directLink = null;

if ($pageHtml) {
    // Pola 1: strict dengan ekstensi
    $strictPat = '#https://i\.8upload\.com/image/[a-zA-Z0-9]+/[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|gif|webp|avif|bmp|tif|tiff)#i';
    if (preg_match_all($strictPat, $pageHtml, $matches)) {
        foreach (array_unique($matches[0]) as $url) {
            if (!str_contains($url, '/preview/')) {
                $directLink = $url;
                break;
            }
        }
    }

    // Pola 2: semua URL i.8upload.com
    if (!$directLink && preg_match_all('#https://i\.8upload\.com/[^\s"\'<>\[\]]+#', $pageHtml, $all)) {
        foreach (array_unique($all[0]) as $url) {
            $url = cleanUrl($url);
            if ($url && !str_contains($url, '/preview/')) {
                $directLink = $url;
                break;
            }
        }
    }

    // Pola 3: img src
    if (!$directLink && preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $pageHtml, $imgs)) {
        foreach ($imgs[1] as $src) {
            if (str_contains($src, '8upload')) {
                $directLink = $src;
                break;
            }
        }
    }
}

if (!$directLink) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error'   => 'Direct link tidak ditemukan di halaman preview.',
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// STEP 3: Masking & output
// ══════════════════════════════════════════════════════════════════════════
$maskedLink = maskUrl($directLink);
outputSuccess($maskedLink);


// ══════════════════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════════════════

function outputSuccess(string $link): void
{
    echo json_encode(
        ['success' => true, 'direct_link' => $link, 'hotlink' => $link],
        JSON_UNESCAPED_SLASHES
    );
}

function maskUrl(?string $url): ?string
{
    if (!$url) return null;
    return preg_replace('#^https://i\.8upload\.com/image/#', PUBLIC_CDN . '/', $url);
}

function httpGet(string $url, array $extra = []): ?string
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => array_merge(['Accept: text/html,*/*;q=0.8'], $extra),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_ENCODING       => '',
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    return ($err || $body === false) ? null : $body;
}

function cleanUrl(string $url): ?string
{
    if (preg_match('#^(https://[^\s"\'<>\[\]]+?\.(jpg|jpeg|png|gif|webp|avif|bmp|tif|tiff))#i', $url, $m)) return $m[1];
    $url = preg_replace('/[\[\(].*$/i', '', $url);
    $url = rtrim($url, ".,;:)\"'\r\n\t ]\\");
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
}

function normalizeFiles(array $f): array
{
    $out = [];
    if (is_array($f['name'])) {
        for ($i = 0; $i < count($f['name']); $i++) {
            if ($f['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
            $out[] = ['name'=>$f['name'][$i],'type'=>$f['type'][$i],'tmp_name'=>$f['tmp_name'][$i],'error'=>$f['error'][$i],'size'=>$f['size'][$i]];
        }
    } else {
        if ($f['error'] !== UPLOAD_ERR_NO_FILE) $out[] = $f;
    }
    return $out;
}

function uploadErrMsg(int $code): string
{
    return match($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File terlalu besar.',
        UPLOAD_ERR_PARTIAL   => 'Upload tidak lengkap.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Kesalahan server.',
        default => 'Upload error (kode ' . $code . ').',
    };
}