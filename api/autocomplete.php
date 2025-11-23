<?php
/**
 * 자동완성 API 엔드포인트
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (empty($query)) {
        sendJsonResponse(['suggestions' => []]);
    }
    
    $suggestions = searchAutocomplete($query, 10);
    sendJsonResponse(['suggestions' => $suggestions]);
    
} catch (Exception $e) {
    error_log("Autocomplete error: " . $e->getMessage());
    sendJsonResponse(['error' => '자동완성 검색 중 오류가 발생했습니다.'], 500);
}
?>

