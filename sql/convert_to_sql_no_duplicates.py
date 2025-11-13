import json
import sys

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
    # Read recipe1.json
    try:
        with open('recipe1.json', 'r', encoding='utf-8') as f:
            data1 = json.load(f)
    except Exception as e:
        print(f"Error reading recipe1.json: {e}", file=sys.stderr)
        sys.exit(1)
    
    # Read recipe2.json
    try:
        with open('recipe2.json', 'r', encoding='utf-8') as f:
            data2 = json.load(f)
    except Exception as e:
        print(f"Error reading recipe2.json: {e}", file=sys.stderr)
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
    
    # Merge recipes and remove duplicates by RCP_SEQ
    seen_seqs = set()
    unique_recipes = []
    duplicates_removed = 0
    
    # First add all recipes from recipe1
    for recipe in recipes1:
        rcp_seq = recipe.get('RCP_SEQ', '')
        if rcp_seq and rcp_seq in seen_seqs:
            duplicates_removed += 1
            print(f"중복 제거: RCP_SEQ '{rcp_seq}' (recipe1.json)", file=sys.stderr)
            continue
        seen_seqs.add(rcp_seq)
        unique_recipes.append(recipe)
    
    # Then add recipes from recipe2, skipping duplicates
    for recipe in recipes2:
        rcp_seq = recipe.get('RCP_SEQ', '')
        if rcp_seq and rcp_seq in seen_seqs:
            duplicates_removed += 1
            print(f"중복 제거: RCP_SEQ '{rcp_seq}' (recipe2.json)", file=sys.stderr)
            continue
        seen_seqs.add(rcp_seq)
        unique_recipes.append(recipe)
    
    print(f"원본 레시피 수: {len(recipes1) + len(recipes2)}", file=sys.stderr)
    print(f"중복 제거 후: {len(unique_recipes)}", file=sys.stderr)
    print(f"제거된 중복: {duplicates_removed}개", file=sys.stderr)
    
    # Convert to SQL
    sql_output = convert_to_sql(unique_recipes)
    
    # Write to output file
    output_file = 'recipes_no_duplicates.sql'
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(sql_output)
    
    print(f"SQL 파일 생성 완료: {output_file}", file=sys.stderr)
    print(f"총 SQL 문 개수: {len(unique_recipes)}", file=sys.stderr)

if __name__ == '__main__':
    main()

