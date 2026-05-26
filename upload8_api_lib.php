<?php
/**
 * upload8_api_lib.php
 * Library function untuk upload gambar via URL ke 8upload.com
 * dan mengambil direct/hotlink-nya.
 *
 * @param  string $imageUrl  URL gambar yang ingin di-upload
 * @return array  [
 *   'success'     => bool,
 *   'source_url'  => string,
 *   'upload_page' => string|null,
 *   'direct_link' => string|null,
 *   'hotlink'     => string|null,
 *   'error'       => string|null,
 * ]
 */
function upload8_from_url(string $imageUrl): array
{
    // ── Validasi ──────────────────────────────────────────────────────────
    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        return ['success' => false, 'error' => 'URL tidak valid.'];
    }

    // ── Step 1 : POST ke form upload URL ─────────────────────────────────
    $uploadEndpoint = 'https://8upload.com/upload_url.php';
    $postFields     = http_build_query(['url' => $imageUrl]);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $uploadEndpoint,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/x-www-form-urlencoded',
            'Referer: https://8upload.com/url.php',
            'Origin: https://8upload.com',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_HEADER         => true,
    ]);

    $response     = curl_exec($ch);
    $headerSize   = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError    = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'error' => 'cURL error: ' . $curlError];
    }

    $body = substr($response, $headerSize);

    // ── Step 2 : Cari upload page URL ────────────────────────────────────
    $uploadPageUrl = null;

    // Dari effective URL setelah redirect
    if (preg_match('#https://8upload\.com/uploads/[a-zA-Z0-9]+#', $effectiveUrl, $m)) {
        $uploadPageUrl = $m[0];
    }

    // Dari body HTML
    if (!$uploadPageUrl && preg_match('#https://8upload\.com/uploads/[a-zA-Z0-9]+#', $body, $m)) {
        $uploadPageUrl = $m[0];
    }

    // Dari header Location (jika tidak follow redirect)
    if (!$uploadPageUrl) {
        preg_match_all('/^Location:\s*(.+)$/im', substr($response, 0, $headerSize), $locs);
        foreach (array_reverse($locs[1] ?? []) as $loc) {
            $loc = trim($loc);
            if (str_contains($loc, '8upload.com/uploads/')) {
                $uploadPageUrl = $loc;
                break;
            }
        }
    }

    if (!$uploadPageUrl) {
        return [
            'success'       => false,
            'error'         => 'Upload gagal – tidak ditemukan halaman hasil. HTTP ' . $httpCode,
            'source_url'    => $imageUrl,
            'debug_snippet' => substr(strip_tags($body), 0, 400),
        ];
    }

    // ── Step 3 : Buka halaman preview – ekstrak direct link ──────────────
    $directLink = _8upload_fetch_direct_link($uploadPageUrl);

    return [
        'success'     => true,
        'source_url'  => $imageUrl,
        'upload_page' => $uploadPageUrl,
        'direct_link' => $directLink,
        'hotlink'     => $directLink,
    ];
}


/**
 * Buka halaman preview 8upload dan ambil direct/hotlink URL gambar.
 * Contoh direct link: https://i.8upload.com/image/<hash>/<filename>
 */
function _8upload_fetch_direct_link(string $pageUrl): ?string
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $pageUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        CURLOPT_HTTPHEADER     => ['Referer: https://8upload.com/'],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return null;

    // Pola utama: https://i.8upload.com/image/<hash>/<filename>
    if (preg_match('#https://i\.8upload\.com/image/[a-zA-Z0-9]+/[^"\'<>\s]+#', $html, $m)) {
        return rtrim($m[0], ".,;");
    }

    // Fallback: semua URL di subdomain i.8upload.com
    if (preg_match('#https://i\.8upload\.com/[^"\'<>\s]+\.(jpg|jpeg|png|gif|webp|avif)#i', $html, $m)) {
        return rtrim($m[0], ".,;");
    }

    return null;
}