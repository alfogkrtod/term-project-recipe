/**
 * 레시피 검색 애플리케이션 JavaScript
 */

// 전역 변수
let selectedIngredients = [];
let currentPage = 1;
let totalPages = 1;
let autocompleteTimer = null;

// DOM 요소
const ingredientSearch = document.getElementById('ingredient-search');
const autocompleteResults = document.getElementById('autocomplete-results');
const selectedIngredientsContainer = document.getElementById('selected-ingredients');
const recipeListSection = document.getElementById('recipe-list-section');
const recipeGrid = document.getElementById('recipe-grid');
const pagination = document.getElementById('pagination');
const recipeDetailSection = document.getElementById('recipe-detail-section');
const recipeDetail = document.getElementById('recipe-detail');
const backToListButton = document.getElementById('back-to-list');

// 이벤트 리스너
if (ingredientSearch) {
    ingredientSearch.addEventListener('input', handleSearchInput);
    ingredientSearch.addEventListener('keydown', handleSearchKeydown);
    ingredientSearch.addEventListener('focus', handleSearchFocus);
}

if (backToListButton) {
    backToListButton.addEventListener('click', showRecipeList);
}

// 검색 입력 처리
function handleSearchInput(e) {
    const query = e.target.value.trim();
    
    // 타이머 초기화
    if (autocompleteTimer) {
        clearTimeout(autocompleteTimer);
    }
    
    if (query.length === 0) {
        hideAutocomplete();
        return;
    }
    
    // 1초 후 자동완성 검색
    autocompleteTimer = setTimeout(() => {
        searchAutocomplete(query);
    }, 1000);
}

// 검색 키보드 이벤트 처리
function handleSearchKeydown(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const query = ingredientSearch.value.trim();
        if (query && !selectedIngredients.includes(query)) {
            addIngredient(query);
            ingredientSearch.value = '';
            hideAutocomplete();
        }
    } else if (e.key === 'Escape') {
        hideAutocomplete();
    }
}

// 검색 포커스 처리
function handleSearchFocus() {
    const query = ingredientSearch.value.trim();
    if (query.length > 0) {
        searchAutocomplete(query);
    }
}

// 자동완성 검색
async function searchAutocomplete(query) {
    try {
        const response = await fetch(`api/autocomplete.php?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        if (data.suggestions && data.suggestions.length > 0) {
            displayAutocomplete(data.suggestions);
        } else {
            hideAutocomplete();
        }
    } catch (error) {
        console.error('Autocomplete error:', error);
        hideAutocomplete();
    }
}

// 자동완성 결과 표시
function displayAutocomplete(suggestions) {
    autocompleteResults.innerHTML = '';
    
    suggestions.forEach(suggestion => {
        // 이미 선택된 재료는 제외
        if (selectedIngredients.includes(suggestion)) {
            return;
        }
        
        const item = document.createElement('div');
        item.className = 'autocomplete-item';
        item.textContent = suggestion;
        item.addEventListener('click', () => {
            addIngredient(suggestion);
            ingredientSearch.value = '';
            hideAutocomplete();
        });
        autocompleteResults.appendChild(item);
    });
    
    if (autocompleteResults.children.length > 0) {
        autocompleteResults.classList.add('show');
    } else {
        hideAutocomplete();
    }
}

// 자동완성 숨기기
function hideAutocomplete() {
    autocompleteResults.classList.remove('show');
}

// 재료 추가
function addIngredient(ingredient) {
    if (selectedIngredients.includes(ingredient)) {
        return;
    }
    
    selectedIngredients.push(ingredient);
    updateSelectedIngredientsDisplay();
    searchRecipes();
}

// 재료 제거
function removeIngredient(ingredient) {
    selectedIngredients = selectedIngredients.filter(item => item !== ingredient);
    updateSelectedIngredientsDisplay();
    searchRecipes();
}

// 선택된 재료 표시 업데이트
function updateSelectedIngredientsDisplay() {
    selectedIngredientsContainer.innerHTML = '';
    
    selectedIngredients.forEach(ingredient => {
        const tag = document.createElement('div');
        tag.className = 'ingredient-tag';
        tag.innerHTML = `
            <span>${escapeHtml(ingredient)}</span>
            <span class="remove" data-ingredient="${escapeHtml(ingredient)}">×</span>
        `;
        
        const removeButton = tag.querySelector('.remove');
        removeButton.addEventListener('click', () => {
            removeIngredient(ingredient);
        });
        
        selectedIngredientsContainer.appendChild(tag);
    });
}

// 레시피 검색
async function searchRecipes(page = 1) {
    if (selectedIngredients.length === 0) {
        recipeListSection.style.display = 'none';
        return;
    }
    
    try {
        const ingredientsParam = selectedIngredients.map(ing => encodeURIComponent(ing)).join(',');
        const response = await fetch(`api/search.php?ingredients=${ingredientsParam}&page=${page}`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Search error:', data.error);
            return;
        }
        
        currentPage = data.page || 1;
        totalPages = data.totalPages || 1;
        
        displayRecipes(data.recipes || []);
        displayPagination(data.totalPages || 0, data.page || 1);
        
        recipeListSection.style.display = 'block';
        recipeDetailSection.style.display = 'none';
    } catch (error) {
        console.error('Search error:', error);
    }
}

// 레시피 목록 표시
function displayRecipes(recipes) {
    recipeGrid.innerHTML = '';
    
    if (recipes.length === 0) {
        recipeGrid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; padding: 40px;">검색 결과가 없습니다.</p>';
        return;
    }
    
    recipes.forEach(recipe => {
        const card = document.createElement('div');
        card.className = 'recipe-card';
        card.addEventListener('click', () => {
            showRecipeDetail(recipe.RCP_SEQ);
        });
        
        const img = document.createElement('img');
        img.src = recipe.ATT_FILE_NO_MAIN || 'public/assets/img/no-image.png';
        img.alt = recipe.RCP_NM || '레시피 이미지';
        img.onerror = function() {
            this.src = 'public/assets/img/no-image.png';
        };
        
        const name = document.createElement('div');
        name.className = 'recipe-name';
        name.textContent = recipe.RCP_NM || '레시피명 없음';
        
        card.appendChild(img);
        card.appendChild(name);
        recipeGrid.appendChild(card);
    });
}

// 페이지네이션 표시
function displayPagination(totalPages, currentPage) {
    pagination.innerHTML = '';
    
    if (totalPages <= 1) {
        return;
    }
    
    // 이전 버튼
    const prevButton = document.createElement('button');
    prevButton.textContent = '‹';
    prevButton.disabled = currentPage === 1;
    prevButton.addEventListener('click', () => {
        if (currentPage > 1) {
            searchRecipes(currentPage - 1);
        }
    });
    pagination.appendChild(prevButton);
    
    // 페이지 번호 버튼
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        const firstButton = document.createElement('button');
        firstButton.textContent = '1';
        firstButton.addEventListener('click', () => searchRecipes(1));
        pagination.appendChild(firstButton);
        
        if (startPage > 2) {
            const ellipsis = document.createElement('span');
            ellipsis.textContent = '...';
            ellipsis.style.padding = '8px';
            pagination.appendChild(ellipsis);
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const button = document.createElement('button');
        button.textContent = i;
        if (i === currentPage) {
            button.classList.add('active');
        }
        button.addEventListener('click', () => searchRecipes(i));
        pagination.appendChild(button);
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsis = document.createElement('span');
            ellipsis.textContent = '...';
            ellipsis.style.padding = '8px';
            pagination.appendChild(ellipsis);
        }
        
        const lastButton = document.createElement('button');
        lastButton.textContent = totalPages;
        lastButton.addEventListener('click', () => searchRecipes(totalPages));
        pagination.appendChild(lastButton);
    }
    
    // 다음 버튼
    const nextButton = document.createElement('button');
    nextButton.textContent = '›';
    nextButton.disabled = currentPage === totalPages;
    nextButton.addEventListener('click', () => {
        if (currentPage < totalPages) {
            searchRecipes(currentPage + 1);
        }
    });
    pagination.appendChild(nextButton);
}

// 레시피 상세 표시
async function showRecipeDetail(rcpSeq) {
    try {
        const response = await fetch(`api/recipe.php?id=${rcpSeq}`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Recipe detail error:', data.error);
            alert('레시피 정보를 불러올 수 없습니다.');
            return;
        }
        
        const recipe = data.recipe;
        displayRecipeDetail(recipe);
        
        recipeListSection.style.display = 'none';
        recipeDetailSection.style.display = 'block';
        
        // 상단으로 스크롤
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
        console.error('Recipe detail error:', error);
        alert('레시피 정보를 불러올 수 없습니다.');
    }
}

// 레시피 상세 정보 표시
function displayRecipeDetail(recipe) {
    let html = `
        <div class="recipe-header">
            <h2>${escapeHtml(recipe.RCP_NM || '레시피명 없음')}</h2>
            ${recipe.ATT_FILE_NO_MK ? `<img src="${escapeHtml(recipe.ATT_FILE_NO_MK)}" alt="${escapeHtml(recipe.RCP_NM)}">` : ''}
        </div>
    `;
    
    // 영양 정보
    if (recipe.INFO_ENG || recipe.INFO_CAR || recipe.INFO_PRO || recipe.INFO_FAT || recipe.INFO_NA) {
        html += `
            <div class="recipe-info">
                ${recipe.INFO_ENG ? `
                    <div class="info-item">
                        <div class="label">열량</div>
                        <div class="value">${escapeHtml(recipe.INFO_ENG)}kcal</div>
                    </div>
                ` : ''}
                ${recipe.INFO_CAR ? `
                    <div class="info-item">
                        <div class="label">탄수화물</div>
                        <div class="value">${escapeHtml(recipe.INFO_CAR)}g</div>
                    </div>
                ` : ''}
                ${recipe.INFO_PRO ? `
                    <div class="info-item">
                        <div class="label">단백질</div>
                        <div class="value">${escapeHtml(recipe.INFO_PRO)}g</div>
                    </div>
                ` : ''}
                ${recipe.INFO_FAT ? `
                    <div class="info-item">
                        <div class="label">지방</div>
                        <div class="value">${escapeHtml(recipe.INFO_FAT)}g</div>
                    </div>
                ` : ''}
                ${recipe.INFO_NA ? `
                    <div class="info-item">
                        <div class="label">나트륨</div>
                        <div class="value">${escapeHtml(recipe.INFO_NA)}mg</div>
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    // 요리 팁
    if (recipe.RCP_NA_TIP) {
        html += `
            <div class="recipe-tip">
                <strong>요리 팁</strong>
                <p>${escapeHtml(recipe.RCP_NA_TIP)}</p>
            </div>
        `;
    }
    
    // 재료 정보
    if (recipe.RCP_PARTS_DTLS) {
        html += `
            <div class="recipe-ingredients">
                <h3>재료</h3>
                <pre style="white-space: pre-wrap; font-family: inherit; background: #f8f9fa; padding: 15px; border-radius: 8px;">${escapeHtml(recipe.RCP_PARTS_DTLS)}</pre>
            </div>
        `;
    }
    
    // 만드는 방법
    html += '<div class="recipe-manuals"><h3>만드는 방법</h3>';
    
    for (let i = 1; i <= 20; i++) {
        const manual = recipe[`MANUAL${String(i).padStart(2, '0')}`];
        const manualImg = recipe[`MANUAL_IMG${String(i).padStart(2, '0')}`];
        
        if (manual && manual.trim()) {
            html += `
                <div class="manual-step">
                    <div class="manual-step-number">${i}</div>
                    <div class="manual-step-content">
                        <p>${escapeHtml(manual)}</p>
                        ${manualImg ? `<img src="${escapeHtml(manualImg)}" alt="단계 ${i}">` : ''}
                    </div>
                </div>
            `;
        }
    }
    
    html += '</div>';
    
    recipeDetail.innerHTML = html;
}

// 목록으로 돌아가기
function showRecipeList() {
    recipeDetailSection.style.display = 'none';
    recipeListSection.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// HTML 이스케이프
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 외부 클릭 시 자동완성 숨기기
document.addEventListener('click', (e) => {
    if (!ingredientSearch.contains(e.target) && !autocompleteResults.contains(e.target)) {
        hideAutocomplete();
    }
});

