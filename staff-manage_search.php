<?php
/**
 * staff-manage_search.php
 * AJAX endpoint — returns JSON list of residents matching the search query.
 * Only residents who do NOT already have a barangay_official account are returned.
 */

session_start();
require 'config.php';

header('Content-Type: application/json');

// ── Auth guard ───────────────────────────────────────────────────────────────
if (empty($_SESSION['loggedin']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode([]);
    exit();
}

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit();
}

$like = '%' . $q . '%';

$stmt = $pdo->prepare("
    SELECT
        r.resident_id,
        r.first_name,
        r.last_name,
        r.email,
        r.mobile_number
    FROM resident r
    WHERE
        -- exclude residents who already have a staff account
        r.resident_id NOT IN (SELECT resident_id FROM barangay_official)
        AND (
            r.first_name  LIKE ?
            OR r.last_name  LIKE ?
            OR CONCAT(r.first_name, ' ', r.last_name) LIKE ?
            OR r.email      LIKE ?
        )
    ORDER BY r.last_name, r.first_name
    LIMIT 10
");

$stmt->execute([$like, $like, $like, $like]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($results);
exit();