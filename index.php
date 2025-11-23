<?php
/**
 * 메인 페이지
 */
require_once __DIR__ . '/templates/header.php';
?>

<div class="container">
    <div class="search-section">
        <h2>재료로 레시피 검색</h2>
        <div class="search-box">
            <input type="text" id="ingredient-search" placeholder="재료명을 입력하세요..." autocomplete="off">
            <div id="autocomplete-results" class="autocomplete-results"></div>
        </div>
        <div id="selected-ingredients" class="selected-ingredients"></div>
    </div>
    
    <div id="recipe-list-section" class="recipe-list-section" style="display: none;">
        <div id="recipe-grid" class="recipe-grid"></div>
        <div id="pagination" class="pagination"></div>
    </div>
    
    <div id="recipe-detail-section" class="recipe-detail-section" style="display: none;">
        <button id="back-to-list" class="back-button">← 목록으로 돌아가기</button>
        <div id="recipe-detail" class="recipe-detail"></div>
    </div>
</div>

<?php
require_once __DIR__ . '/templates/footer.php';
?>

