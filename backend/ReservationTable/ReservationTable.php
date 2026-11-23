<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../database/session.php';
require_once __DIR__ . '/../database/conn.php';

// =========================================================================
// 1. AUTHENTICATION
// =========================================================================

$currentUser = auth();

if ($currentUser === null) {
    header('Location: ' . url('/'));
    exit;
}

// =========================================================================
// 2. DATABASE CONNECTION
// =========================================================================

$pdo = db();

if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database connection is not available.');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// =========================================================================
// 3. HELPER FUNCTION
// =========================================================================

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

$pageTitle = 'Table Management';

// =========================================================================
// 3B. HANDLE POST INSERT (`restaurant_tables`)
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'create_table');

    if ($action === 'create_table') {
        $tableNumber = trim((string) ($_POST['table_number'] ?? ''));
        $tableName   = trim((string) ($_POST['table_name'] ?? ''));
        $capacityRaw = $_POST['capacity'] ?? 4;
        $shape       = strtolower(trim((string) ($_POST['shape'] ?? 'rectangle')));
        $isActiveRaw = $_POST['is_active'] ?? 1;
        $notes       = trim((string) ($_POST['notes'] ?? ''));

        $areaId    = isset($_POST['area_id']) && $_POST['area_id'] !== '' ? (int) $_POST['area_id'] : null;
        $xPosition = isset($_POST['x_position']) && $_POST['x_position'] !== '' ? (float) $_POST['x_position'] : 0.0;
        $yPosition = isset($_POST['y_position']) && $_POST['y_position'] !== '' ? (float) $_POST['y_position'] : 0.0;
        $width     = isset($_POST['width']) && $_POST['width'] !== '' ? (float) $_POST['width'] : 100.0;
        $height    = isset($_POST['height']) && $_POST['height'] !== '' ? (float) $_POST['height'] : 100.0;
        $rotation  = isset($_POST['rotation']) && $_POST['rotation'] !== '' ? (float) $_POST['rotation'] : 0.0;

        $allowedShapes = ['rectangle', 'square', 'round', 'oval'];
        if (!in_array($shape, $allowedShapes, true)) {
            $shape = 'rectangle';
        }

        $capacity = filter_var($capacityRaw, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 100],
        ]);

        $isActive = in_array((string) $isActiveRaw, ['1', 'true', 'on'], true) ? 1 : 0;

        $errors = [];

        if ($tableNumber === '') {
            $errors[] = 'Table number is required.';
        } elseif (mb_strlen($tableNumber) > 50) {
            $errors[] = 'Table number must not exceed 50 characters.';
        }

        if (mb_strlen($tableName) > 100) {
            $errors[] = 'Table name must not exceed 100 characters.';
        }

        if ($capacity === false) {
            $errors[] = 'Capacity must be a whole number between 1 and 100.';
        }

        if ($errors !== []) {
            $_SESSION['flash_error'] = implode(' ', $errors);
        } else {
            try {
                $insertSql = "
                    INSERT INTO restaurant_tables (
                        area_id,
                        table_number,
                        table_name,
                        capacity,
                        shape,
                        x_position,
                        y_position,
                        width,
                        height,
                        rotation,
                        is_active,
                        notes,
                        created_at,
                        updated_at
                    ) VALUES (
                        :area_id,
                        :table_number,
                        :table_name,
                        :capacity,
                        :shape,
                        :x_position,
                        :y_position,
                        :width,
                        :height,
                        :rotation,
                        :is_active,
                        :notes,
                        NOW(),
                        NOW()
                    )
                ";

                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    ':area_id'      => $areaId,
                    ':table_number' => $tableNumber,
                    ':table_name'   => $tableName !== '' ? $tableName : null,
                    ':capacity'     => $capacity,
                    ':shape'        => $shape,
                    ':x_position'   => $xPosition,
                    ':y_position'   => $yPosition,
                    ':width'        => $width,
                    ':height'       => $height,
                    ':rotation'     => $rotation,
                    ':is_active'    => $isActive,
                    ':notes'        => $notes !== '' ? $notes : null,
                ]);

                $_SESSION['flash_success'] = "Table \"{$tableNumber}\" has been added successfully.";
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $_SESSION['flash_error'] = "A table with number \"{$tableNumber}\" already exists.";
                } else {
                    error_log('Failed to insert restaurant table: ' . $e->getMessage());
                    $_SESSION['flash_error'] = 'Failed to add table to the database.';
                }
            }
        }
    }

    header('Location: ' . url('/frontend/ReservationTable/ReservationTable.php'), true, 303);
    exit;
}

// =========================================================================
// 4. FILTERS & SEARCH
// =========================================================================

$search = trim((string) ($_GET['search'] ?? ''));
$areaFilter = trim((string) ($_GET['area_id'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? ''));

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "
        (
            CAST(rt.table_id AS CHAR) LIKE :search
            OR rt.table_number LIKE :search
            OR COALESCE(rt.table_name, '') LIKE :search
            OR COALESCE(rt.shape, '') LIKE :search
            OR COALESCE(rt.notes, '') LIKE :search
        )
    ";
    $params[':search'] = '%' . $search . '%';
}

if ($areaFilter !== '' && ctype_digit($areaFilter)) {
    $whereClauses[] = "rt.area_id = :area_id";
    $params[':area_id'] = (int) $areaFilter;
}

if ($statusFilter === 'active') {
    $whereClauses[] = "rt.is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $whereClauses[] = "rt.is_active = 0";
}

$whereSql = $whereClauses !== []
    ? 'WHERE ' . implode(' AND ', $whereClauses)
    : '';

// =========================================================================
// 5. FETCH RESTAURANT TABLES
// =========================================================================

$sql = "
    SELECT
        rt.table_id,
        rt.area_id,
        rt.table_number,
        rt.table_name,
        rt.capacity,
        rt.shape,
        rt.x_position,
        rt.y_position,
        rt.width,
        rt.height,
        rt.rotation,
        rt.is_active,
        rt.notes,
        rt.created_at,
        rt.updated_at
    FROM restaurant_tables rt
    {$whereSql}
    ORDER BY rt.area_id ASC, rt.table_number ASC, rt.table_id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tables = $stmt->fetchAll();

// =========================================================================
// 6. DISTINCT AREAS FOR FILTER DROPDOWN
// =========================================================================

$areasStmt = $pdo->query("
    SELECT DISTINCT area_id
    FROM restaurant_tables
    WHERE area_id IS NOT NULL
    ORDER BY area_id ASC
");
$areas = $areasStmt->fetchAll();

$availableAreasStmt = $pdo->query("
    SELECT area_id, area_name
    FROM floor_areas
    WHERE is_active = 1
    ORDER BY sort_order ASC, area_name ASC
");
$availableAreas = $availableAreasStmt->fetchAll();

// =========================================================================
// 7. STATISTICS
// =========================================================================

$totalTables = (int) $pdo->query("SELECT COUNT(*) FROM restaurant_tables")->fetchColumn();
$activeTables = (int) $pdo->query("SELECT COUNT(*) FROM restaurant_tables WHERE is_active = 1")->fetchColumn();
$inactiveTables = (int) $pdo->query("SELECT COUNT(*) FROM restaurant_tables WHERE is_active = 0")->fetchColumn();
$totalCapacity = (int) $pdo->query("SELECT COALESCE(SUM(capacity), 0) FROM restaurant_tables WHERE is_active = 1")->fetchColumn();

// =========================================================================
// 8. JSON PAYLOAD FOR FLOOR PLAN / JS VIEW
// =========================================================================

$tablesJson = json_encode(
    $tables,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT |
    JSON_THROW_ON_ERROR
);