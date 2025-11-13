import json
import sys
from collections import Counter

def check_duplicates():
    """Check for duplicate recipes in the JSON files"""
    
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
    
    # Extract from recipe1
    if isinstance(data1, dict) and 'COOKRCP01' in data1:
        if 'row' in data1['COOKRCP01'] and isinstance(data1['COOKRCP01']['row'], list):
            recipes1 = data1['COOKRCP01']['row']
    elif isinstance(data1, list):
        recipes1 = data1
    
    # Extract from recipe2
    if isinstance(data2, dict) and 'COOKRCP01' in data2:
        if 'row' in data2['COOKRCP01'] and isinstance(data2['COOKRCP01']['row'], list):
            recipes2 = data2['COOKRCP01']['row']
    elif isinstance(data2, list):
        recipes2 = data2
    
    # Merge recipes
    all_recipes = recipes1 + recipes2
    
    print(f"총 레시피 수: {len(all_recipes)}")
    print(f"  - recipe_new1.json: {len(recipes1)}개")
    print(f"  - recipe_new2.json: {len(recipes2)}개")
    print()
    
    # Check for duplicates by RCP_SEQ (recipe sequence number)
    if all_recipes and 'RCP_SEQ' in all_recipes[0]:
        rcp_seqs = [recipe.get('RCP_SEQ', '') for recipe in all_recipes]
        seq_counter = Counter(rcp_seqs)
        duplicates_by_seq = {seq: count for seq, count in seq_counter.items() if count > 1}
        
        if duplicates_by_seq:
            print(f"⚠️  RCP_SEQ 기준 중복 발견: {len(duplicates_by_seq)}개")
            print("\n중복된 RCP_SEQ:")
            for seq, count in sorted(duplicates_by_seq.items(), key=lambda x: x[1], reverse=True)[:10]:
                print(f"  - RCP_SEQ '{seq}': {count}회")
            if len(duplicates_by_seq) > 10:
                print(f"  ... 외 {len(duplicates_by_seq) - 10}개")
        else:
            print("✅ RCP_SEQ 기준 중복 없음")
        print()
    
    # Check for duplicates by RCP_NM (recipe name)
    if all_recipes and 'RCP_NM' in all_recipes[0]:
        rcp_nms = [recipe.get('RCP_NM', '') for recipe in all_recipes]
        nm_counter = Counter(rcp_nms)
        duplicates_by_name = {nm: count for nm, count in nm_counter.items() if count > 1}
        
        if duplicates_by_name:
            print(f"⚠️  레시피 이름(RCP_NM) 기준 중복 발견: {len(duplicates_by_name)}개")
            print("\n중복된 레시피 이름 (상위 10개):")
            for name, count in sorted(duplicates_by_name.items(), key=lambda x: x[1], reverse=True)[:10]:
                print(f"  - '{name}': {count}회")
            if len(duplicates_by_name) > 10:
                print(f"  ... 외 {len(duplicates_by_name) - 10}개")
        else:
            print("✅ 레시피 이름 기준 중복 없음")
        print()
    
    # Check for exact duplicates (all fields match)
    # Convert recipes to tuples for hashing
    recipe_strings = []
    for recipe in all_recipes:
        # Sort keys for consistent comparison
        sorted_items = sorted(recipe.items())
        recipe_str = str(sorted_items)
        recipe_strings.append(recipe_str)
    
    string_counter = Counter(recipe_strings)
    exact_duplicates = {recipe_str: count for recipe_str, count in string_counter.items() if count > 1}
    
    if exact_duplicates:
        print(f"⚠️  완전히 동일한 레시피 발견: {len(exact_duplicates)}개")
        print(f"   (모든 필드가 동일한 레시피)")
        total_duplicate_count = sum(count - 1 for count in exact_duplicates.values())
        print(f"   중복 제거 시 {total_duplicate_count}개 레시피 제거 가능")
    else:
        print("✅ 완전히 동일한 레시피 없음")
    print()
    
    # Check overlap between recipe1 and recipe2
    if recipes1 and recipes2:
        if 'RCP_SEQ' in recipes1[0] and 'RCP_SEQ' in recipes2[0]:
            seqs1 = set(recipe.get('RCP_SEQ', '') for recipe in recipes1)
            seqs2 = set(recipe.get('RCP_SEQ', '') for recipe in recipes2)
            overlap = seqs1.intersection(seqs2)
            
            if overlap:
                print(f"⚠️  recipe_new1.json과 recipe_new2.json 간 중복: {len(overlap)}개")
                print(f"   공통 RCP_SEQ: {sorted(list(overlap))[:10]}")
                if len(overlap) > 10:
                    print(f"   ... 외 {len(overlap) - 10}개")
            else:
                print("✅ recipe_new1.json과 recipe_new2.json 간 중복 없음")
        print()
    
    # Summary
    unique_count = len(set(recipe_strings))
    print("=" * 50)
    print("요약:")
    print(f"  전체 레시피 수: {len(all_recipes)}")
    print(f"  고유 레시피 수: {unique_count}")
    if len(all_recipes) > unique_count:
        print(f"  중복 레시피 수: {len(all_recipes) - unique_count}")
    print("=" * 50)

if __name__ == '__main__':
    check_duplicates()

