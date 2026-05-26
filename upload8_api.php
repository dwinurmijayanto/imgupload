<?php
/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  ImgShare Upload API — powered by private storage backend       ║
 * ╠══════════════════════════════════════════════════════════════════╣
 * ║  Usage:                                                          ║
 * ║    GET  upload8_api.php?url=https://example.com/image.jpg       ║
 * ║    POST upload8_api.php  JSON: {"url":"https://..."}            ║
 * ║    POST upload8_api.php  form: url=https://...                  ║
 * ║    GET  upload8_api.php?action=discover                         ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Konfigurasi domain publik ─────────────────────────────────────────────
$_protocol  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$_all_hdrs  = getallheaders();
$_cf_worker = $_all_hdrs['Cf-Worker'] ?? null;
$_host      = $_cf_worker ? 'img.' . $_cf_worker : ($_SERVER['HTTP_HOST'] ?? 'img.vidshare.my.id');
define('PUBLIC_CDN', $_protocol . '://' . $_host . '/images');

if (($_GET['debug'] ?? '') === '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'HTTP_HOST'   => $_SERVER['HTTP_HOST'] ?? 'x',
        'Cf-Worker'   => $_all_hdrs['Cf-Worker'] ?? 'x',
        'PUBLIC_CDN'  => PUBLIC_CDN,
        'ALL_HEADERS' => $_all_hdrs,
    ]);
    exit;
}

define('DISCOVERY_CACHE', sys_get_temp_dir() . '/8upload_discovery.json');
define('CACHE_TTL', 3600);

// ── Mode: discover ────────────────────────────────────────────────────────
if (($_GET['action'] ?? '') === 'discover') {
    $force = !empty($_GET['refresh']);
    $disc  = autoDiscover($force);
    // Hapus semua info sensitif sebelum output
    unset($disc['upload_endpoint'], $disc['js_files'], $disc['js_endpoints'],
          $disc['page_patterns'], $disc['page_url']);
    $disc['storage'] = 'private';
    echo json_encode($disc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// 1. Ambil URL gambar
// ══════════════════════════════════════════════════════════════════════════
$imageUrl = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ct = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $body     = json_decode(file_get_contents('php://input'), true);
        $imageUrl = $body['url'] ?? null;
    } else {
        $imageUrl = $_POST['url'] ?? null;
    }
} else {
    $imageUrl = $_GET['url'] ?? null;
}

if (empty($imageUrl)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Parameter "url" wajib diisi.',
        'examples' => [
            'GET'       => '?url=https://example.com/image.jpg',
            'POST JSON' => '{"url":"https://example.com/image.jpg"}',
            'POST form' => 'url=https://example.com/image.jpg',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'URL tidak valid.']);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// 2. Auto-Discovery
// ══════════════════════════════════════════════════════════════════════════
$disc = autoDiscover();

if (!$disc['success']) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error'   => 'Storage backend tidak tersedia. Coba lagi nanti.',
    ]);
    exit;
}

$uploadEndpoint = $disc['upload_endpoint'];
$fieldName      = $disc['field_name'];

// ══════════════════════════════════════════════════════════════════════════
// 3. POST ke storage backend (tersembunyi)
// ══════════════════════════════════════════════════════════════════════════
$postBody = rawurlencode(rtrim($fieldName, '[]')) . '%5B%5D=' . rawurlencode($imageUrl);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $uploadEndpoint,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $postBody,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 8,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36',
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Referer: ' . $disc['page_url'],
        'Origin: https://8upload.com',
        'Cache-Control: no-cache',
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_COOKIEFILE     => '',
    CURLOPT_COOKIEJAR      => '',
]);

$response     = curl_exec($ch);
$httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize   = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$curlError    = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Upload gagal, coba lagi nanti.']);
    exit;
}

$respHeader = substr($response, 0, $headerSize);
$respBody   = substr($response, $headerSize);

// ══════════════════════════════════════════════════════════════════════════
// 4. Ekstrak URL preview
// ══════════════════════════════════════════════════════════════════════════
$uploadPageUrl = extractUploadPage($effectiveUrl, $respHeader, $respBody, $disc);

if (!$uploadPageUrl) {
    if (str_contains($fieldName, '[]')) {
        $altField = str_replace('[]', '', $fieldName);
        $altBody  = rawurlencode($altField) . '=' . rawurlencode($imageUrl);

        $ch2 = curl_init();
        curl_setopt_array($ch2, [
            CURLOPT_URL            => $uploadEndpoint,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $altBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 8,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: ' . $disc['page_url'],
                'Origin: https://8upload.com',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_COOKIEFILE     => '',
        ]);
        $r2  = curl_exec($ch2);
        $hs2 = curl_getinfo($ch2, CURLINFO_HEADER_SIZE);
        $eu2 = curl_getinfo($ch2, CURLINFO_EFFECTIVE_URL);
        curl_close($ch2);

        $uploadPageUrl = extractUploadPage($eu2, substr($r2, 0, $hs2), substr($r2, $hs2), $disc);
        if ($uploadPageUrl) {
            $disc['field_name'] = $altField;
            file_put_contents(DISCOVERY_CACHE, json_encode(array_merge($disc, ['expires' => time() + CACHE_TTL])));
        }
    }
}

if (!$uploadPageUrl) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error'   => "Upload gagal – silakan coba lagi. (HTTP $httpCode)",
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// 5. Ambil direct link lalu MASKING ke domain sendiri
// ══════════════════════════════════════════════════════════════════════════
$rawDirectLink    = fetchDirectLink($uploadPageUrl, $disc);
$maskedDirectLink = maskUrl($rawDirectLink);

// ══════════════════════════════════════════════════════════════════════════
// 6. Response — HANYA domain sendiri yang terlihat
// ══════════════════════════════════════════════════════════════════════════
echo json_encode([
    'success'     => true,
    'source_url'  => $imageUrl,
    'direct_link' => $maskedDirectLink,
    'hotlink'     => $maskedDirectLink,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);


// ══════════════════════════════════════════════════════════════════════════
// FUNGSI: Masking URL backend → URL publik
// ══════════════════════════════════════════════════════════════════════════
function maskUrl(?string $url): ?string
{
    if (!$url) return null;
    return preg_replace(
        '#^https://i\.8upload\.com/image/#',
        PUBLIC_CDN . '/',
        $url
    );
}


// ══════════════════════════════════════════════════════════════════════════
// FUNGSI: AUTO-DISCOVERY
// ══════════════════════════════════════════════════════════════════════════
function autoDiscover(bool $forceRefresh = false): array
{
    if (!$forceRefresh && file_exists(DISCOVERY_CACHE)) {
        $cached = json_decode(file_get_contents(DISCOVERY_CACHE), true);
        if ($cached && isset($cached['expires']) && time() < $cached['expires'] && $cached['success']) {
            $cached['from_cache'] = true;
            return $cached;
        }
    }

    $result = [
        'success'         => false,
        'from_cache'      => false,
        'page_url'        => 'https://8upload.com/url.php',
        'upload_endpoint' => null,
        'field_name'      => null,
        'js_files'        => [],
        'js_endpoints'    => [],
        'page_patterns'   => [
            'upload_page' => ['#https://8upload\.com/uploads/[a-zA-Z0-9]+#'],
            'direct_link' => [
                '#https://i\.8upload\.com/image/[a-zA-Z0-9]+/[^"\'<>\s\r\n]+#',
                '#https://i\.8upload\.com/[^"\'<>\s\r\n]+\.(jpg|jpeg|png|gif|webp|avif)#i',
            ],
        ],
        'error'   => null,
        'expires' => time() + CACHE_TTL,
    ];

    $html = httpGet('https://8upload.com/url.php', ['Referer: https://8upload.com/']);
    if (!$html) {
        $result['error'] = 'Tidak dapat terhubung ke storage backend.';
        return $result;
    }

    $formAction = '';
    if (preg_match('/<form[^>]*>/i', $html, $formTag)) {
        if (preg_match('/action=["\']([^"\']*)["\']/', $formTag[0], $am)) {
            $formAction = trim($am[1]);
        }
    }

    if ($formAction === '' || $formAction === null) {
        $result['upload_endpoint'] = 'https://8upload.com/url.php';
    } elseif (preg_match('/^https?:\/\//', $formAction)) {
        $result['upload_endpoint'] = $formAction;
    } else {
        $result['upload_endpoint'] = 'https://8upload.com/' . ltrim($formAction, '/');
    }

    $fieldName = null;
    if (preg_match_all('/<input([^>]*)>/i', $html, $inputs)) {
        foreach ($inputs[1] as $attrs) {
            if (preg_match('/type=["\'](?:hidden|submit|button|file|checkbox|radio|image)["\']/', $attrs)) continue;
            if (preg_match('/name=["\']([^"\']+)["\']/', $attrs, $nm)) {
                $fieldName = $nm[1];
                break;
            }
        }
    }
    if (!$fieldName && preg_match('/<textarea[^>]+name=["\']([^"\']+)["\']/', $html, $ta)) {
        $fieldName = $ta[1];
    }
    $result['field_name'] = $fieldName ?? 'image_url[]';

    preg_match_all('/<script[^>]+src=["\']([^"\']+\.js[^"\']*)["\'][^>]*>/i', $html, $scripts);
    foreach ($scripts[1] as $jsSrc) {
        if (str_starts_with($jsSrc, '//'))            $jsUrl = 'https:' . $jsSrc;
        elseif (preg_match('/^https?:\/\//', $jsSrc)) $jsUrl = $jsSrc;
        else                                           $jsUrl = 'https://8upload.com/' . ltrim($jsSrc, '/');

        if (!str_contains($jsUrl, '8upload.com')) {
            $result['js_files'][] = ['url' => $jsUrl, 'scanned' => false];
            continue;
        }
        $result['js_files'][] = ['url' => $jsUrl, 'scanned' => true];
        $js = httpGet($jsUrl);
        if (!$js) continue;

        if (preg_match_all('/["\`\'](\/?\w[\w\-\/]*\.php[^"\'`\s]*)["\`\']/i', $js, $phpRefs)) {
            foreach ($phpRefs[1] as $ref) {
                if (!preg_match('/upload|url|post|img|image/i', $ref)) continue;
                $ep = preg_match('/^https?:\/\//', $ref) ? $ref : 'https://8upload.com/' . ltrim($ref, '/');
                $result['js_endpoints'][] = $ep;
            }
        }
        if (preg_match('/\.append\s*\(\s*["\']([^"\']+)["\']/', $js, $fd)) {
            $result['field_name'] = $fd[1];
        }
    }

    $result['page_patterns']['upload_page'] = array_values(array_unique($result['page_patterns']['upload_page']));
    $result['page_patterns']['direct_link'] = array_values(array_unique($result['page_patterns']['direct_link']));
    $result['js_endpoints']                 = array_values(array_unique($result['js_endpoints']));
    $result['success'] = !empty($result['upload_endpoint']) && !empty($result['field_name']);
    $result['error']   = $result['success'] ? null : 'Konfigurasi storage tidak dapat ditemukan.';

    if ($result['success']) {
        @file_put_contents(DISCOVERY_CACHE, json_encode($result));
    }
    return $result;
}

function extractUploadPage(string $effectiveUrl, string $respHeader, string $respBody, array $disc): ?string
{
    $patterns = $disc['page_patterns']['upload_page'];
    foreach ($patterns as $pat) {
        if (preg_match($pat, $effectiveUrl, $m)) return $m[0];
    }
    preg_match_all('/^Location:\s*(.+)$/im', $respHeader, $locs);
    foreach (array_reverse($locs[1] ?? []) as $loc) {
        $loc = trim($loc);
        foreach ($patterns as $pat) {
            if (preg_match($pat, $loc, $m)) return $m[0];
        }
    }
    foreach ([$respBody, html_entity_decode($respBody)] as $src) {
        foreach ($patterns as $pat) {
            if (preg_match($pat, $src, $m)) return $m[0];
        }
    }
    return null;
}

function fetchDirectLink(string $pageUrl, array $disc): ?string
{
    $html = httpGet($pageUrl, ['Referer: https://8upload.com/', 'Accept: text/html,*/*;q=0.8']);
    if (!$html) return null;

    $strictPat = '#https://i\.8upload\.com/image/[a-zA-Z0-9]+/[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|gif|webp|avif)#i';
    if (preg_match_all($strictPat, $html, $all)) {
        foreach ($all[0] as $u) {
            if (!str_contains($u, '/preview/')) return $u;
        }
    }
    foreach ($disc['page_patterns']['direct_link'] as $pat) {
        if (preg_match($pat, $html, $m)) {
            $u = cleanUrl($m[0]);
            if ($u && !str_contains($u, '/preview/')) return $u;
        }
    }
    if (preg_match_all('#https://i\.8upload\.com/[^\s"\'<>\[\]]+#', $html, $all)) {
        foreach ($all[0] as $u) {
            $u = cleanUrl($u);
            if ($u && !str_contains($u, '/preview/')) return $u;
        }
    }
    return null;
}

function cleanUrl(string $url): ?string
{
    if (preg_match('#^(https://[^\s"\'<>\[\]]+?\.(jpg|jpeg|png|gif|webp|avif))#i', $url, $m)) return $m[1];
    $url = preg_replace('/[\[\(].*$/i', '', $url);
    $url = rtrim($url, ".,;:)\"'\r\n\t ]\\");
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
}

function httpGet(string $url, array $extraHeaders = []): ?string
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => array_merge(['Accept: text/html,*/*;q=0.8', 'Accept-Language: en-US,en;q=0.5'], $extraHeaders),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_ENCODING       => '',
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    return ($err || $body === false) ? null : $body;
}