<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../database/session.php';
require_once __DIR__ . '/../database/conn.php';

// =========================================================================
// 1. AUTHENTICATION & METHOD CHECK
// =========================================================================

$currentUser = auth();

if ($currentUser === null) {
    header('Location: ' . url('/'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('/frontend/ReservationTable/ReservationTable.php'));
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
// 3. COLLECT & VALIDATE INPUTS
// =========================================================================

$tableIdRaw  = $_POST['table_id'] ?? ($_GET['id'] ?? '');
$tableId     = filter_var($tableIdRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

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

if ($tableId === false) {
    $errors[] = 'Invalid table ID.';
}

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

$fallbackRedirect = url('/frontend/ReservationTable/ReservationTable.php');
$redirectUrl = $tableId !== false
    ? $fallbackRedirect
    : $fallbackRedirect;

if ($errors !== []) {
    $_SESSION['flash_error'] = implode(' ', $errors);
    header('Location: ' . $redirectUrl);
    exit;
}

// =========================================================================
// 4. UPDATE `restaurant_tables`
// =========================================================================

try {
    $checkStmt = $pdo->prepare("SELECT table_id FROM restaurant_tables WHERE table_id = :table_id LIMIT 1");
    $checkStmt->execute([':table_id' => $tableId]);

    if (!$checkStmt->fetch()) {
        $_SESSION['flash_error'] = 'Table record was not found.';
        header('Location: ' . $fallbackRedirect);
        exit;
    }

    $sql = "
        UPDATE restaurant_tables
        SET
            area_id      = :area_id,
            table_number = :table_number,
            table_name   = :table_name,
            capacity     = :capacity,
            shape        = :shape,
            x_position   = :x_position,
            y_position   = :y_position,
            width        = :width,
            height       = :height,
            rotation     = :rotation,
            is_active    = :is_active,
            notes        = :notes,
            updated_at   = NOW()
        WHERE table_id = :table_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':table_id'     => $tableId,
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

    $_SESSION['flash_success'] = "Table \"{$tableNumber}\" has been updated successfully.";
    header('Location: ' . $fallbackRedirect);
    exit;
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['flash_error'] = "A table with number \"{$tableNumber}\" already exists.";
    } else {
        $_SESSION['flash_error'] = 'Failed to update table in the database.';
    }

    header('Location: ' . $redirectUrl);
    exit;
}