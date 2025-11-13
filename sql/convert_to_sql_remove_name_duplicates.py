import json
import sys
from collections import OrderedDict

def escape_sql_string(value):
    """Escape special characters for SQL"""
    if value is None:
        return 'NULL'
    if isinstance(value, (int, float)):
        return str(value)
    # Convert to string and escape quotes
    return "'" + str(value).replace("'", "''").replace("\\", "\\\\") + "'"

def convert_to_sql(recipes, table_name='recipes'):
    """Convert recipe objects to SQL INSERT statements"""
    if not recipes:
        return ""
    
    # Get all unique keys from all recipes
    all_keys = set()
    for recipe in recipes:
        all_keys.update(recipe.keys())
    
    # Sort keys for consistent column order
    columns = sorted(all_keys)
    
    sql_statements = []
    
    # Generate INSERT statements
    for recipe in recipes:
        values = []
        for col in columns:
            value = recipe.get(col, None)
            values.append(escape_sql_string(value))
        
        columns_str = ', '.join([f"`{col}`" for col in columns])
        values_str = ', '.join(values)
        
        sql_statements.append(f"INSERT INTO `{table_name}` ({columns_str}) VALUES ({values_str});")
    
    return '\n'.join(sql_statements)

def main():
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
    
    # Handle different JSON structures
    if isinstance(data1, dict):
        if 'COOKRCP01' in data1 and isinstance(data1['COOKRCP01'], dict):
            if 'row' in data1['COOKRCP01'] and isinstance(data1['COOKRCP01']['row'], list):
                recipes1 = data1['COOKRCP01']['row']
        elif 'row' in data1 and isinstance(data1['row'], list):
            recipes1 = data1['row']
    elif isinstance(data1, list):
        recipes1 = data1
    
    if isinstance(data2, dict):
        if 'COOKRCP01' in data2 and isinstance(data2['COOKRCP01'], dict):
            if 'row' in data2['COOKRCP01'] and isinstance(data2['COOKRCP01']['row'], list):
                recipes2 = data2['COOKRCP01']['row']
        elif 'row' in data2 and isinstance(data2['row'], list):
            recipes2 = data2['row']
    elif isinstance(data2, list):
        recipes2 = data2
    
    # Merge all recipes
    all_recipes = recipes1 + recipes2
    
    print(f"원본 레시피 수: {len(all_recipes)}", file=sys.stderr)
    print(f"  - recipe_new1.json: {len(recipes1)}개", file=sys.stderr)
    print(f"  - recipe_new2.json: {len(recipes2)}개", file=sys.stderr)
    print()
    
    # Remove duplicates by name (keep first occurrence)
    seen_names = OrderedDict()  # Keep insertion order
    unique_recipes = []
    removed_count = 0
    removed_recipes = []
    
    for recipe in all_recipes:
        recipe_name = recipe.get('RCP_NM', '')
        rcp_seq = recipe.get('RCP_SEQ', '')
        
        if recipe_name:
            if recipe_name not in seen_names:
                # First occurrence - keep it
                seen_names[recipe_name] = True
                unique_recipes.append(recipe)
            else:
                # Duplicate name - remove it
                removed_count += 1
                removed_recipes.append({
                    'name': recipe_name,
                    'seq': rcp_seq
                })
                print(f"제거: '{recipe_name}' (RCP_SEQ: {rcp_seq})", file=sys.stderr)
        else:
            # No name - keep it (shouldn't happen, but just in case)
            unique_recipes.append(recipe)
    
    print()
    print(f"이름 중복 제거 후: {len(unique_recipes)}개", file=sys.stderr)
    print(f"제거된 레시피: {removed_count}개", file=sys.stderr)
    
    if removed_recipes:
        print("\n제거된 레시피 목록:", file=sys.stderr)
        for removed in removed_recipes:
            print(f"  - {removed['name']} (RCP_SEQ: {removed['seq']})", file=sys.stderr)
    
    # Convert to SQL
    sql_output = convert_to_sql(unique_recipes)
    
    # Write to output file
    output_file = 'recipes_new_no_name_duplicates.sql'
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(sql_output)
    
    print(f"\nSQL 파일 생성 완료: {output_file}", file=sys.stderr)
    print(f"총 SQL 문 개수: {len(unique_recipes)}", file=sys.stderr)

if __name__ == '__main__':
    main()

