<?php
// api.php — Nutrition Facts Label Practice
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$config_file = __DIR__ . '/db_config.php';
if (!file_exists($config_file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration file not found.']);
    exit;
}
require $config_file;

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// ── Pick a random fdc_id efficiently ─────────────────────────────────────────
// ORDER BY RAND() on large tables is very slow.
// Instead: pick a random offset within the total row count.
$fdc_id = isset($_GET['fdc_id']) ? (int) $_GET['fdc_id'] : null;

if ($fdc_id === null) {
    $total = (int) $pdo->query('
        SELECT COUNT(*)
        FROM food_nutrient_flat n
        INNER JOIN branded_food b ON b.fdc_id = n.fdc_id
        INNER JOIN off_verified ov ON ov.gtin_upc = b.gtin_upc
        WHERE ov.image_front_url IS NOT NULL
    ')->fetchColumn();
    if ($total === 0) {
        http_response_code(500);
        echo json_encode(['error' => 'No products with photos found in database.']);
        exit;
    }
    $offset = random_int(0, $total - 1);
    $row = $pdo->query("
        SELECT n.fdc_id
        FROM food_nutrient_flat n
        INNER JOIN branded_food b ON b.fdc_id = n.fdc_id
        INNER JOIN off_verified ov ON ov.gtin_upc = b.gtin_upc
        WHERE ov.image_front_url IS NOT NULL
        LIMIT 1 OFFSET $offset
    ")->fetch();
    if (!$row) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not select a random product.']);
        exit;
    }
    $fdc_id = (int) $row['fdc_id'];
}

// ── Fetch full product data ───────────────────────────────────────────────────
$stmt = $pdo->prepare('
    SELECT
        f.fdc_id,
        f.description,
        f.publication_date,
        b.brand_owner,
        b.brand_name,
        b.gtin_upc,
        b.ingredients,
        b.not_a_significant_source_of,
        b.serving_size,
        b.serving_size_unit,
        b.household_serving_fulltext,
        b.branded_food_category,
        n.energy_kcal,
        n.fat_g,
        n.saturated_fat_g,
        n.trans_fat_g,
        n.polyunsat_fat_g,
        n.monounsat_fat_g,
        n.cholesterol_mg,
        n.sodium_mg,
        n.carbohydrate_g,
        n.fiber_g,
        n.sugars_g,
        n.protein_g,
        n.calcium_mg,
        n.iron_mg,
        n.potassium_mg,
        n.vitamin_d_mcg,
        ov.image_front_url
    FROM food f
    JOIN branded_food        b  USING (fdc_id)
    JOIN food_nutrient_flat  n  USING (fdc_id)
    LEFT JOIN off_verified   ov ON ov.gtin_upc = b.gtin_upc
    WHERE f.fdc_id = ?
');
$stmt->execute([$fdc_id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found.']);
    exit;
}

// ── Daily Values (FDA 2020) ───────────────────────────────────────────────────
$dv = [
    'fat_g'           => 78,
    'saturated_fat_g' => 20,
    'cholesterol_mg'  => 300,
    'sodium_mg'       => 2300,
    'carbohydrate_g'  => 275,
    'fiber_g'         => 28,
    'protein_g'       => 50,
    'calcium_mg'      => 1300,
    'iron_mg'         => 18,
    'potassium_mg'    => 4700,
    'vitamin_d_mcg'   => 20,
];
$dv_pct = [];
foreach ($dv as $key => $ref) {
    $val = $product[$key];
    $dv_pct[$key] = ($val !== null && $ref > 0)
        ? (int) round(((float)$val / $ref) * 100)
        : null;
}

// ── FDA rounding rules ────────────────────────────────────────────────────────
function round_nutrient(?string $value, string $unit): ?string {
    if ($value === null) return null;
    $f = (float) $value;
    return match ($unit) {
        'g'    => $f < 0.5 ? number_format($f, 1) : (string)(int) round($f),
        'mg'   => (string)(int) round($f),
        'mcg'  => (string)(int) round($f),
        'kcal' => (string)(int) round($f),
        default => (string) round($f, 1),
    };
}

$nutrients_display = [
    'energy_kcal'     => round_nutrient($product['energy_kcal'],     'kcal'),
    'fat_g'           => round_nutrient($product['fat_g'],           'g'),
    'saturated_fat_g' => round_nutrient($product['saturated_fat_g'], 'g'),
    'trans_fat_g'     => round_nutrient($product['trans_fat_g'],     'g'),
    'polyunsat_fat_g' => round_nutrient($product['polyunsat_fat_g'], 'g'),
    'monounsat_fat_g' => round_nutrient($product['monounsat_fat_g'], 'g'),
    'cholesterol_mg'  => round_nutrient($product['cholesterol_mg'],  'mg'),
    'sodium_mg'       => round_nutrient($product['sodium_mg'],       'mg'),
    'carbohydrate_g'  => round_nutrient($product['carbohydrate_g'],  'g'),
    'fiber_g'         => round_nutrient($product['fiber_g'],         'g'),
    'sugars_g'        => round_nutrient($product['sugars_g'],        'g'),
    'protein_g'       => round_nutrient($product['protein_g'],       'g'),
    'calcium_mg'      => round_nutrient($product['calcium_mg'],      'mg'),
    'iron_mg'         => round_nutrient($product['iron_mg'],         'mg'),
    'potassium_mg'    => round_nutrient($product['potassium_mg'],    'mg'),
    'vitamin_d_mcg'   => round_nutrient($product['vitamin_d_mcg'],  'mcg'),
];

echo json_encode([
    'fdc_id'                      => (int) $product['fdc_id'],
    'gtin_upc'                    => $product['gtin_upc'],
    'description'                 => $product['description'],
    'brand_owner'                 => $product['brand_owner'],
    'brand_name'                  => $product['brand_name'],
    'branded_food_category'       => $product['branded_food_category'],
    'ingredients'                 => $product['ingredients'],
    'not_a_significant_source_of' => $product['not_a_significant_source_of'],
    'serving_size'                => (float) $product['serving_size'],
    'serving_size_unit'           => $product['serving_size_unit'],
    'household_serving_fulltext'  => $product['household_serving_fulltext'],
    'image_front_url'             => $product['image_front_url'],
    'nutrients'                   => $nutrients_display,
    'daily_value_pct'             => $dv_pct,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
