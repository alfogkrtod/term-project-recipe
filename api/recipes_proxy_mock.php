<?php
/**
 * Mock proxy that returns sample external API response stored in out.txt
 * Useful for frontend testing without making real external requests.
 * Usage: /api/recipes_proxy_mock.php?ingredients=양파&page=1&debug=1
 */

header('Content-Type: application/json; charset=utf-8');

function send_error($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$file = __DIR__ . '/../out.txt';
if (!is_readable($file)) {
    send_error('Mock data file not found', 500);
}

$raw = file_get_contents($file);
if ($raw === false) {
    send_error('Failed to read mock data', 500);
}

$decoded = json_decode($raw, true);
if ($decoded === null) {
    send_error('Mock data is not valid JSON', 500);
}

// Try to extract items and total using the known shape
$serviceKey = 'COOKRCP01';
$items = [];
$total = 0;
if (isset($decoded[$serviceKey]) && is_array($decoded[$serviceKey])) {
    $svc = $decoded[$serviceKey];
    if (isset($svc['row']) && is_array($svc['row'])) {
        $items = $svc['row'];
    }
    if (isset($svc['total_count'])) {
        $total = intval($svc['total_count']);
    }
}

$decoded = json_decode($raw, true);
    $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($decoded));
if ($decoded === null) {
    // If decoding fails, attempt to extract the first balanced JSON object from the file.
    $start = strpos($raw, '{');
    $end = null;
    if ($start !== false) {
        $depth = 0;
        $len = strlen($raw);
        for ($i = $start; $i < $len; $i++) {
            $ch = $raw[$i];
            if ($ch === '{') { $depth++; }
            elseif ($ch === '}') { $depth--; if ($depth === 0) { $end = $i; break; } }
        }
    }

    if ($start === false || $end === null) {
        send_error('Mock data is not valid JSON', 500);
    }

    $jsonSub = substr($raw, $start, $end - $start + 1);
    $decoded = json_decode($jsonSub, true);
    if ($decoded === null) {
        send_error('Mock data is not valid JSON after extraction', 500);
    }
}
    // simplistic fallback: gather rows if present at top
    foreach ($decoded as $k => $v) {
        if (is_array($v) && count($v) > 0) { $found = $v; break; }
    }
    if ($found !== null) $items = $found;
}

// Pagination from query (mock will slice)
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 50;
$start = ($page - 1) * $perPage;
$paged = array_slice($items, $start, $perPage);

$out = [
    'recipes' => $paged,
    'total' => $total > 0 ? $total : count($items),
    'page' => $page,
    'perPage' => $perPage,
    'totalPages' => $perPage > 0 ? (int)ceil(($total > 0 ? $total : count($items)) / $perPage) : 1
];

if (isset($_GET['debug']) && $_GET['debug']) {
    $out['debug'] = [ 'source' => 'mock', 'mockFile' => basename($file) ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);

?>
