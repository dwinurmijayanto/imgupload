<?php
/**
 * imgshare_diag.php
 * Letakkan di root img.vidshare.my.id/imgshare_diag.php
 * Akses via browser: https://img.vidshare.my.id/imgshare_diag.php
 * HAPUS file ini setelah selesai debug!
 */

// Matikan output PHP error ke browser agar tidak merusak JSON
// (ini yang menyebabkan "Unexpected token '<'" di frontend)
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start(); // Buffer output — tangkap semua output sebelum header dikirim

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$report = [
    'timestamp'   => date('Y-m-d H:i:s T'),
    'php_version' => PHP_VERSION,
    'server_soft' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'checks'      => [],
    'curl_test'   => null,
    'proxy_syntax'=> null,
];

// ── 1. Ekstensi wajib ─────────────────────────────────────────────────────
$required_ext = ['curl', 'fileinfo', 'json', 'mbstring'];
foreach ($required_ext as $ext) {
    $report['checks'][$ext] = [
        'loaded' => extension_loaded($ext),
        'status' => extension_loaded($ext) ? 'OK' : 'MISSING',
    ];
}

// ── 2. php.ini penting ────────────────────────────────────────────────────
$report['checks']['php_ini'] = [
    'display_errors'     => ini_get('display_errors'),
    'upload_max_filesize'=> ini_get('upload_max_filesize'),
    'post_max_size'      => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'file_uploads'       => ini_get('file_uploads'),
    'upload_tmp_dir'     => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
    'tmp_writable'       => is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()),
];

// ── 3. Cek file proxy ada dan bisa dibaca ─────────────────────────────────
$proxyFile = __DIR__ . '/upload_proxy.php';
$report['checks']['proxy_file'] = [
    'path'     => $proxyFile,
    'exists'   => file_exists($proxyFile),
    'readable' => is_readable($proxyFile),
    'size'     => file_exists($proxyFile) ? filesize($proxyFile) . ' bytes' : null,
    'modified' => file_exists($proxyFile) ? date('Y-m-d H:i:s', filemtime($proxyFile)) : null,
];

// ── 4. Cek PHP syntax upload_proxy.php ───────────────────────────────────
if (file_exists($proxyFile)) {
    $lintOutput = shell_exec('php -l ' . escapeshellarg($proxyFile) . ' 2>&1');
    $report['proxy_syntax'] = [
        'lint_output' => trim($lintOutput),
        'valid'       => $lintOutput && str_contains($lintOutput, 'No syntax errors'),
    ];
    
    // Baca 30 baris pertama untuk cek ada output sebelum <?php
    $lines = file($proxyFile, FILE_IGNORE_NEW_LINES);
    $firstLine = $lines[0] ?? '';
    $report['checks']['proxy_first_line'] = [
        'content'       => $firstLine,
        'starts_with_php' => str_starts_with(trim($firstLine), '<?php'),
        'has_bom'       => str_starts_with($firstLine, "\xEF\xBB\xBF"), // UTF-8 BOM
        'warning'       => !str_starts_with(trim($firstLine), '<?php') 
                           ? 'PERINGATAN: File tidak dimulai dengan <?php — ini menyebabkan output HTML sebelum JSON!'
                           : null,
    ];
    
    // Cari whitespace/output sebelum <?php
    $raw = file_get_contents($proxyFile);
    $phpPos = strpos($raw, '<?php');
    $report['checks']['proxy_output_before_php'] = [
        'php_tag_position'    => $phpPos,
        'chars_before_php_tag'=> $phpPos,
        'content_before_tag'  => $phpPos > 0 ? bin2hex(substr($raw, 0, $phpPos)) : null,
        'warning'             => $phpPos > 0 
                                 ? "ADA $phpPos karakter sebelum <?php tag — ini penyebab HTML output!"
                                 : null,
    ];
}

// ── 5. Test cURL ke 8upload ───────────────────────────────────────────────
if (extension_loaded('curl')) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://8upload.com/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 ImgShareDiag/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_NOBODY         => true, // HEAD request saja
    ]);
    curl_exec($ch);
    $report['curl_test'] = [
        'url'        => 'https://8upload.com/',
        'http_code'  => curl_getinfo($ch, CURLINFO_HTTP_CODE),
        'total_time' => round(curl_getinfo($ch, CURLINFO_TOTAL_TIME), 3) . 's',
        'curl_error' => curl_error($ch) ?: null,
        'ssl_ok'     => curl_getinfo($ch, CURLINFO_HTTP_CODE) > 0,
        'status'     => curl_getinfo($ch, CURLINFO_HTTP_CODE) > 0 ? 'REACHABLE' : 'UNREACHABLE',
    ];
    curl_close($ch);
    
    // Test endpoint upload spesifik
    $ch2 = curl_init();
    curl_setopt_array($ch2, [
        CURLOPT_URL            => 'https://8upload.com/upload/mt/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 ImgShareDiag/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_NOBODY         => true,
        CURLOPT_POST           => true,
    ]);
    curl_exec($ch2);
    $report['curl_test']['upload_endpoint'] = [
        'url'       => 'https://8upload.com/upload/mt/',
        'http_code' => curl_getinfo($ch2, CURLINFO_HTTP_CODE),
        'error'     => curl_error($ch2) ?: null,
        'note'      => curl_getinfo($ch2, CURLINFO_HTTP_CODE) > 0 ? 'Endpoint reachable' : 'Cannot connect',
    ];
    curl_close($ch2);
}

// ── 6. Tangkap PHP error/warning yang mungkin ter-output sebelumnya ───────
$spuriousOutput = ob_get_clean();
$report['spurious_output_before_json'] = [
    'length'  => strlen($spuriousOutput),
    'content' => $spuriousOutput ? base64_encode($spuriousOutput) : null,
    'decoded' => $spuriousOutput ?: null,
    'warning' => $spuriousOutput 
                 ? 'ADA OUTPUT SEBELUM JSON: ' . substr(strip_tags($spuriousOutput), 0, 200)
                 : null,
];

// ── 7. Summary ───────────────────────────────────────────────────────────
$allOk = true;
$issues = [];

foreach ($required_ext as $ext) {
    if (!$report['checks'][$ext]['loaded']) {
        $allOk = false;
        $issues[] = "Ekstensi PHP tidak ada: $ext";
    }
}
if (!$report['checks']['php_ini']['file_uploads']) {
    $allOk = false; $issues[] = 'file_uploads dimatikan di php.ini';
}
if (!$report['checks']['php_ini']['tmp_writable']) {
    $allOk = false; $issues[] = 'Direktori tmp tidak writable';
}
if (isset($report['checks']['proxy_output_before_php']['chars_before_php_tag']) 
    && $report['checks']['proxy_output_before_php']['chars_before_php_tag'] > 0) {
    $allOk = false; $issues[] = 'Ada karakter sebelum <?php tag di upload_proxy.php';
}
if (isset($report['proxy_syntax']['valid']) && !$report['proxy_syntax']['valid']) {
    $allOk = false; $issues[] = 'Syntax error di upload_proxy.php: ' . $report['proxy_syntax']['lint_output'];
}
if (isset($report['checks']['proxy_first_line']['has_bom']) && $report['checks']['proxy_first_line']['has_bom']) {
    $allOk = false; $issues[] = 'File upload_proxy.php punya UTF-8 BOM — hapus BOM!';
}

$report['summary'] = [
    'all_ok' => $allOk,
    'issues' => $issues,
    'recommendation' => $allOk 
        ? 'Environment OK. Coba upload lagi dan lihat debug panel.'
        : 'Perbaiki issues di atas terlebih dahulu.',
];

ob_start(); // Buffer baru untuk output final
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$finalOutput = ob_get_clean();

// Pastikan tidak ada output lain yang terselip
echo $finalOutput;