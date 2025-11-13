import json
import sys
from collections import defaultdict

def compare_duplicate_names():
    """Compare recipes with duplicate names to see what's different"""
    
    # Read recipe_new1.json
    try:
        with open('recipe_new1.json', 'r', encoding='utf-8') as f:
            data1 = json.load(f)
    except Exception as e:
        print(f"Error reading recipe_new1.json: {e}", file=sys.stderr)
        sys.exit(1)
    
    # Read recipe_new2.json
    try:
        with open('recipe_new2.json', 'r', encoding='utf-8') as f:
            data2 = json.load(f)
    except Exception as e:
        print(f"Error reading recipe_new2.json: {e}", file=sys.stderr)
        sys.exit(1)
    
    # Extract recipes from both files
    recipes1 = []
    recipes2 = []
    
    # Extract from recipe_new1
    if isinstance(data1, dict) and 'COOKRCP01' in data1:
        if 'row' in data1['COOKRCP01'] and isinstance(data1['COOKRCP01']['row'], list):
            recipes1 = data1['COOKRCP01']['row']
    elif isinstance(data1, list):
        recipes1 = data1
    
    # Extract from recipe_new2
    if isinstance(data2, dict) and 'COOKRCP01' in data2:
        if 'row' in data2['COOKRCP01'] and isinstance(data2['COOKRCP01']['row'], list):
            recipes2 = data2['COOKRCP01']['row']
    elif isinstance(data2, list):
        recipes2 = data2
    
    # Merge recipes
    all_recipes = recipes1 + recipes2
    
    # Group recipes by name
    recipes_by_name = defaultdict(list)
    for recipe in all_recipes:
        name = recipe.get('RCP_NM', '')
        if name:
            recipes_by_name[name].append(recipe)
    
    # Find duplicates
    duplicates = {name: recipes for name, recipes in recipes_by_name.items() if len(recipes) > 1}
    
    if not duplicates:
        print("중복된 이름의 레시피가 없습니다.")
        return
    
    print(f"이름이 중복된 레시피: {len(duplicates)}개\n")
    print("=" * 80)
    
    for name, recipes in sorted(duplicates.items()):
        print(f"\n📝 레시피 이름: '{name}' ({len(recipes)}개)")
        print("-" * 80)
        
        for idx, recipe in enumerate(recipes, 1):
            print(f"\n[레시피 {idx}]")
            print(f"  RCP_SEQ: {recipe.get('RCP_SEQ', 'N/A')}")
            print(f"  RCP_WAY2: {recipe.get('RCP_WAY2', 'N/A')}")
            print(f"  RCP_PAT2: {recipe.get('RCP_PAT2', 'N/A')}")
            
            # Compare key fields
            parts = recipe.get('RCP_PARTS_DTLS', '')
            if parts:
                parts_preview = parts[:100] + "..." if len(parts) > 100 else parts
                print(f"  재료: {parts_preview}")
            
            # Show first manual step
            manual01 = recipe.get('MANUAL01', '')
            if manual01:
                manual_preview = manual01[:100] + "..." if len(manual01) > 100 else manual01
                print(f"  첫 번째 단계: {manual_preview}")
            
            # Show nutritional info
            info_eng = recipe.get('INFO_ENG', '')
            info_car = recipe.get('INFO_CAR', '')
            info_pro = recipe.get('INFO_PRO', '')
            if info_eng or info_car or info_pro:
                print(f"  영양정보: 열량={info_eng}, 탄수화물={info_car}, 단백질={info_pro}")
        
        # Compare differences
        print(f"\n🔍 차이점 분석:")
        if len(recipes) == 2:
            r1, r2 = recipes[0], recipes[1]
            
            # Compare all fields
            all_keys = set(r1.keys()) | set(r2.keys())
            different_fields = []
            same_fields = []
            
            for key in sorted(all_keys):
                val1 = r1.get(key, None)
                val2 = r2.get(key, None)
                
                if val1 != val2:
                    different_fields.append(key)
                else:
                    same_fields.append(key)
            
            print(f"  동일한 필드: {len(same_fields)}개")
            print(f"  다른 필드: {len(different_fields)}개")
            
            if different_fields:
                print(f"\n  주요 차이점:")
                for field in different_fields[:10]:  # Show first 10 differences
                    val1 = r1.get(field, None)
                    val2 = r2.get(field, None)
                    
                    # Truncate long values
                    if val1 and isinstance(val1, str) and len(val1) > 50:
                        val1 = val1[:50] + "..."
                    if val2 and isinstance(val2, str) and len(val2) > 50:
                        val2 = val2[:50] + "..."
                    
                    print(f"    - {field}:")
                    print(f"      레시피1: {val1}")
                    print(f"      레시피2: {val2}")
                
                if len(different_fields) > 10:
                    print(f"    ... 외 {len(different_fields) - 10}개 필드가 다름")
        
        print("=" * 80)

if __name__ == '__main__':
    compare_duplicate_names()

