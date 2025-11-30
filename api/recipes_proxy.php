<?php
/**
 * Server-side proxy for external recipe API (JSON only)
 * Receives: GET `ingredients` (comma-separated), `page` (optional, default 1)
 * Calls external API (FoodSafetyKorea COOKRCP01) with dataType=json and returns
 * normalized JSON to the client.
 */

require_once __DIR__ . '/../config/external_api.php';

header('Content-Type: application/json; charset=utf-8');

// Simple helper to return error responses
function send_error($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

$ingredients = isset($_GET['ingredients']) ? trim($_GET['ingredients']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = defined('EXTERNAL_API_PER_PAGE') ? EXTERNAL_API_PER_PAGE : 50;

if ($ingredients === '') {
    send_error('ingredients parameter is required', 400);
}

// Build startIdx/endIdx according to spec (1-based)
$startIdx = ($page - 1) * $perPage + 1;
$endIdx = $page * $perPage;

// Build external API URL according to spec: base/{key}/{serviceId}/json/{start}/{end}/RCP_PARTS_DTLS=...
$encodedIngredients = rawurlencode($ingredients);
$externalUrl = rtrim(EXTERNAL_API_BASE_PATH, '/') . '/' . rawurlencode(EXTERNAL_API_KEY) . '/' . rawurlencode(EXTERNAL_API_SERVICE_ID) . '/json/' . $startIdx . '/' . $endIdx . '/RCP_PARTS_DTLS=' . $encodedIngredients;

// Optional additional query params could be appended here if needed

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $externalUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$resp = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false) {
    send_error('Failed to fetch external API: ' . $curlErr, 502);
}

// Try to decode JSON
$decoded = json_decode($resp, true);

// If JSON decoding failed, return raw response for debug
if ($decoded === null) {
    // External API may have returned non-JSON or an error
    if (isset($_GET['debug']) && $_GET['debug']) {
        echo json_encode(['recipes' => [], 'raw' => $resp, 'externalUrl' => $externalUrl]);
    } else {
        send_error('External API did not return valid JSON', 502);
    }
    exit;
}

// Try to locate the array of items in the decoded JSON. Many APIs wrap in a root key.
function find_first_array($data) {
    if (is_array($data)) {
        // If numeric-indexed array, assume it's the items array
        $allKeys = array_keys($data);
        $isNumeric = count($allKeys) > 0 && is_int($allKeys[0]);
        if ($isNumeric) return $data;

        // Otherwise search deeper for the first numeric array
        foreach ($data as $v) {
            if (is_array($v)) {
                $found = find_first_array($v);
                if ($found !== null) return $found;
            }
        }
    }
    return null;
}

$items = find_first_array($decoded);
if ($items === null) {
    // No array found; try to wrap single object
    $items = is_array($decoded) ? [$decoded] : [];
}

$total = null;
// Try to detect total count from common keys
$possibleTotals = ['total_count','totalCount','total','count','list_total_count','RESULT_TOTAL_COUNT'];
foreach ($possibleTotals as $k) {
    if (isset($decoded[$k]) && is_numeric($decoded[$k])) { $total = intval($decoded[$k]); break; }
    // also search one level deep
    foreach ($decoded as $sub) {
        if (is_array($sub) && isset($sub[$k]) && is_numeric($sub[$k])) { $total = intval($sub[$k]); break 2; }
    }
}

if ($total === null) {
    $total = count($items);
}
// Prefer the known FoodSafetyKorea shape: { "COOKRCP01": { "total_count": "...", "row": [ ... ] } }
$items = null;
$total = null;
if (isset($decoded[EXTERNAL_API_SERVICE_ID]) && is_array($decoded[EXTERNAL_API_SERVICE_ID])) {
    $svc = $decoded[EXTERNAL_API_SERVICE_ID];
    if (isset($svc['row']) && is_array($svc['row'])) {
        $items = $svc['row'];
    }
    if (isset($svc['total_count']) && is_numeric($svc['total_count'])) {
        $total = intval($svc['total_count']);
    }
}

// Fallback: search first numeric array anywhere in the response
if ($items === null) {
    $items = find_first_array($decoded);
    if ($items === null) {
        $items = is_array($decoded) ? [$decoded] : [];
    }
}

// Fallback: try to detect total count from common keys (one level deep)
if ($total === null) {
    $possibleTotals = ['total_count','totalCount','total','count','list_total_count','RESULT_TOTAL_COUNT'];
    foreach ($possibleTotals as $k) {
        if (isset($decoded[$k]) && is_numeric($decoded[$k])) { $total = intval($decoded[$k]); break; }
        foreach ($decoded as $sub) {
            if (is_array($sub) && isset($sub[$k]) && is_numeric($sub[$k])) { $total = intval($sub[$k]); break 2; }
        }
    }
}

if ($total === null) {
    $total = count($items);
}

$totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;

$out = [
    'recipes' => $items,
    'total' => $total,
    'page' => $page,
    'perPage' => $perPage,
    'totalPages' => $totalPages
];

if (isset($_GET['debug']) && $_GET['debug']) {
    $out['debug'] = [ 'externalUrl' => $externalUrl, 'externalHttpCode' => $httpCode, 'externalRaw' => $decoded ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);

?>
