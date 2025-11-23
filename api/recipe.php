<?php
/**
 * 레시피 상세 정보 API 엔드포인트
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $rcpSeq = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($rcpSeq <= 0) {
        sendJsonResponse(['error' => '잘못된 레시피 ID입니다.'], 400);
    }
    
    $recipe = getRecipeDetail($rcpSeq);
    
    if (!$recipe) {
        sendJsonResponse(['error' => '레시피를 찾을 수 없습니다.'], 404);
    }
    
    sendJsonResponse(['recipe' => $recipe]);
    
} catch (Exception $e) {
    error_log("Recipe detail error: " . $e->getMessage());
    sendJsonResponse(['error' => '레시피 정보를 가져오는 중 오류가 발생했습니다.'], 500);
}
?>

