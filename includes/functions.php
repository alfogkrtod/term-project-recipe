<?php
/**
 * 공통 함수 파일
 */

// 개발 중에 에러를 화면에 표시하도록 설정
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/krayhangeul.php';

/**
 * 재료명 파싱 함수
 * RCP_PARTS_DTLS에서 재료명을 추출
 * @param string $partsDetails 재료 정보 텍스트
 * @return array 재료명 배열
 */
function parseIngredients($partsDetails) {
    $ingredients = [];
    
    if (empty($partsDetails)) {
        return $ingredients;
    }
    
    // 줄바꿈으로 분리
    $lines = explode("\n", $partsDetails);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // 빈 줄이나 특수문자로 시작하는 줄 제외
        if (empty($line) || preg_match('/^[●·\-\*]/', $line)) {
            continue;
        }
        
        // 숫자, 괄호, g, ml 등이 포함된 패턴에서 재료명 추출
        // 예: "양파 10g(2×1cm)" -> "양파"
        // 예: "당근 5g(3×1×1cm)" -> "당근"
        if (preg_match('/^([가-힣]+(?:\s+[가-힣]+)*)\s*[\d\(]/', $line, $matches)) {
            $ingredient = trim($matches[1]);
            if (!empty($ingredient) && !in_array($ingredient, $ingredients)) {
                $ingredients[] = $ingredient;
            }
        }
        // 간단한 재료명 패턴 (숫자 없이)
        elseif (preg_match('/^([가-힣]+(?:\s+[가-힣]+)*)$/', $line, $matches)) {
            $ingredient = trim($matches[1]);
            if (!empty($ingredient) && strlen($ingredient) > 1 && !in_array($ingredient, $ingredients)) {
                $ingredients[] = $ingredient;
            }
        }
    }
    
    return $ingredients;
}

/**
 * 모든 레시피에서 재료명 목록 추출
 * @return array 재료명 배열 (중복 제거)
 */
function getAllIngredients() {
    $pdo = getDBConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->query("SELECT DISTINCT RCP_PARTS_DTLS FROM recipes WHERE RCP_PARTS_DTLS IS NOT NULL AND RCP_PARTS_DTLS != ''");
        $allIngredients = [];
        
        while ($row = $stmt->fetch()) {
            $ingredients = parseIngredients($row['RCP_PARTS_DTLS']);
            $allIngredients = array_merge($allIngredients, $ingredients);
        }
        
        // 중복 제거 및 정렬
        $allIngredients = array_unique($allIngredients);
        sort($allIngredients);
        
        return array_values($allIngredients);
    } catch (PDOException $e) {
        error_log("Error getting ingredients: " . $e->getMessage());
        return [];
    }
}

/**
 * 한글 자소 분리를 이용한 검색어 매칭
 * @param string $searchTerm 검색어
 * @param string $target 비교 대상
 * @return bool 매칭 여부
 */
function matchHangul($searchTerm, $target) {
    $cray = new CrayHangulClass();
    
    // 검색어를 자소로 분리
    $searchPuli = $cray->hangulPuli($searchTerm, true);
    $targetPuli = $cray->hangulPuli($target, true);
    
    // 검색어의 자소가 대상 문자열에 포함되는지 확인
    return mb_strpos($targetPuli, $searchPuli) !== false;
}

/**
 * 자동완성 후보 검색
 * @param string $query 검색어
 * @param int $limit 최대 결과 수
 * @return array 자동완성 후보 배열
 */
function searchAutocomplete($query, $limit = 10) {
    if (empty($query)) {
        return [];
    }
    
    $allIngredients = getAllIngredients();
    $cray = new CrayHangulClass();
    $queryPuli = $cray->hangulPuli($query, true);
    
    $matches = [];
    
    foreach ($allIngredients as $ingredient) {
        $ingredientPuli = $cray->hangulPuli($ingredient, true);
        
        // 검색어의 자소가 재료명의 자소에 포함되는지 확인
        if (mb_strpos($ingredientPuli, $queryPuli) !== false) {
            $matches[] = $ingredient;
            
            if (count($matches) >= $limit) {
                break;
            }
        }
    }
    
    return $matches;
}

/**
 * 재료로 레시피 검색 (AND 조건)
 * @param array $ingredients 재료 배열
 * @param int $page 페이지 번호 (1부터 시작)
 * @param int $perPage 페이지당 레시피 수
 * @return array ['recipes' => 레시피 배열, 'total' => 전체 개수]
 */
function searchRecipesByIngredients($ingredients, $page = 1, $perPage = 50) {
    $pdo = getDBConnection();
    if (!$pdo || empty($ingredients)) {
        return ['recipes' => [], 'total' => 0];
    }
    
    try {
        // 각 재료가 RCP_PARTS_DTLS에 포함되는지 확인하는 조건 생성
        $conditions = [];
        $params = [];
        
        foreach ($ingredients as $index => $ingredient) {
            $paramName = ":ingredient{$index}";
            $conditions[] = "RCP_PARTS_DTLS LIKE {$paramName}";
            $params[$paramName] = "%{$ingredient}%";
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        // 전체 개수 조회
        $countSql = "SELECT COUNT(*) as total FROM recipes WHERE {$whereClause}";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // 페이지네이션
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM recipes WHERE {$whereClause} ORDER BY RCP_SEQ ASC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $recipes = $stmt->fetchAll();
        
        return [
            'recipes' => $recipes,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    } catch (PDOException $e) {
        error_log("Error searching recipes: " . $e->getMessage());
        return ['recipes' => [], 'total' => 0];
    }
}

/**
 * 레시피 상세 정보 조회
 * @param int $rcpSeq 레시피 순번
 * @return array|null 레시피 정보
 */
function getRecipeDetail($rcpSeq) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM recipes WHERE RCP_SEQ = :rcp_seq");
        $stmt->bindValue(':rcp_seq', $rcpSeq, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error getting recipe detail: " . $e->getMessage());
        return null;
    }
}

/**
 * JSON 응답 출력
 * @param array $data 응답 데이터
 * @param int $statusCode HTTP 상태 코드
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
?>

