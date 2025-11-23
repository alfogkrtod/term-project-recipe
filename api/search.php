<?php
/**
 * 레시피 검색 API 엔드포인트
 */
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // 재료 배열 받기 (JSON 또는 쿼리 파라미터)
    $ingredients = [];
    
    if (isset($_GET['ingredients'])) {
        if (is_string($_GET['ingredients'])) {
            $ingredients = json_decode($_GET['ingredients'], true);
            if (!is_array($ingredients)) {
                $ingredients = explode(',', $_GET['ingredients']);
            }
        } elseif (is_array($_GET['ingredients'])) {
            $ingredients = $_GET['ingredients'];
        }
    } elseif (isset($_POST['ingredients'])) {
        if (is_string($_POST['ingredients'])) {
            $ingredients = json_decode($_POST['ingredients'], true);
            if (!is_array($ingredients)) {
                $ingredients = explode(',', $_POST['ingredients']);
            }
        } elseif (is_array($_POST['ingredients'])) {
            $ingredients = $_POST['ingredients'];
        }
    }
    
    // 빈 값 제거 및 trim
    $ingredients = array_filter(array_map('trim', $ingredients), function($item) {
        return !empty($item);
    });
    $ingredients = array_values($ingredients);
    
    if (empty($ingredients)) {
        sendJsonResponse(['recipes' => [], 'total' => 0, 'page' => 1, 'perPage' => 50, 'totalPages' => 0]);
    }
    
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 50;
    
    $result = searchRecipesByIngredients($ingredients, $page, $perPage);
    sendJsonResponse($result);
    
} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    sendJsonResponse(['error' => '레시피 검색 중 오류가 발생했습니다.'], 500);
}
?>

