<?php
session_start();
require '../config.php';

// ── Auth guard ───────────────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please sign in.']);
    exit();
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ============================================================
// ROUTE
// ============================================================
switch ($action) {

    case 'get_profile':
        getProfile();
        break;

    case 'submit_requests':
        submitRequests();
        break;

    case 'get_my_requests':
        getMyRequests();
        break;

    case 'get_request_detail':
        getRequestDetail();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        break;
}

// ============================================================
// ACTION: get_profile
// Returns resident data for auto-filling service forms
// ============================================================
function getProfile() {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT r.resident_id, r.first_name, r.middle_name, r.last_name,
               r.suffix, r.birth_date, r.gender, r.civil_status,
               r.citizenship, r.occupation, r.mobile_number, r.email,
               h.house_number, h.street, h.barangay, h.municipality,
               h.province, h.zip_code
        FROM resident r
        LEFT JOIN household_member hm ON hm.resident_id = r.resident_id
        LEFT JOIN household h         ON h.household_id  = hm.household_id
        WHERE r.resident_id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Resident not found.']);
        return;
    }

    // Build display values
    $nameParts = array_filter([
        $user['first_name'],
        $user['middle_name'],
        $user['last_name'],
        $user['suffix']
    ]);
    $fullName = implode(' ', $nameParts);

    $addressParts = array_filter([
        trim(($user['house_number'] ?? '') . ' ' . ($user['street'] ?? '')),
        $user['barangay'],
        $user['municipality'],
        $user['province']
    ]);
    $address = implode(', ', $addressParts);

    $dob = $user['birth_date']
        ? date('F j, Y', strtotime($user['birth_date']))
        : '';

    echo json_encode([
        'success'     => true,
        'resident_id' => $user['resident_id'],
        'full_name'   => $fullName,
        'dob'         => $dob,
        'address'     => $address,
        'email'       => $user['email'],
        'mobile'      => $user['mobile_number'],
        'initials'    => strtoupper(
                            substr($user['first_name'], 0, 1) .
                            substr($user['last_name'],  0, 1)
                         ),
        'first_name'  => $user['first_name'],
    ]);
}

// ============================================================
// ACTION: submit_requests
// Called when user clicks "Submit Request to LGU" on payment page
// Expects JSON body: { payment_method, items: [ { service_type, fields, files[] } ] }
// ============================================================
function submitRequests() {
    global $pdo;

    // ── Parse POST data
    $cartJson      = $_POST['cart_json']      ?? '[]';
    $paymentMethod = $_POST['payment_method'] ?? 'counter';
    $items         = json_decode($cartJson, true) ?? [];
    $residentId    = $_SESSION['user_id'];

    // ── Validate after decoding
    if (empty($items) || !is_array($items)) {
        echo json_encode(['success' => false, 'message' => 'No items in cart.']);
        return;
    }

    // Map JS payment method values to DB ENUM
    $methodMap = [
        'counter'  => 'Cash',
        'gcash'    => 'GCash',
        'bank'     => 'Bank Transfer',
    ];
    $paymentMethod = $methodMap[$paymentMethod] ?? 'Cash';

    // Map service names to DB ENUM values
    $typeMap = [
        'Barangay Clearance'               => 'Barangay Clearance',
        'Cedula / Community Tax Certificate' => 'Cedula / Community Tax Certificate',
        'Business Permit'                  => 'Business Permit',
        'Health Certificate'               => 'Health Certificate',
        'Indigency Certificate'            => 'Certificate of Indigency',
        'Real Property Tax Payment'        => 'Real Property Tax',
        'Scholarship Application'          => 'Scholarship Application',
        'Book an Appointment'              => 'Book an Appointment',
    ];

    // ── Upload directory ─────────────────────────────────────────────────────
    $uploadBase = realpath(__DIR__ . '/../../files');
    if (!$uploadBase) {
        // Create if it doesn't exist
        mkdir(__DIR__ . '/../../files', 0755, true);
        $uploadBase = realpath(__DIR__ . '/../../files');
    }

    $referenceNumbers = [];
    $errors           = [];

    $pdo->beginTransaction();

    try {
        foreach ($items as $index => $item) {

            $serviceType = $typeMap[$item['name']] ?? 'Other';
            $fields      = $item['fields']  ?? [];
            $fee         = $item['fee']     ?? 'Free';
            $purpose     = $fields['purpose'] ?? $fields['visit_reason'] ?? null;

            // ── Determine payment status ─────────────────────────────────────
            $paymentStatus = ($fee === 'Free') ? 'Exempted' : 'Pending';

            // ── Insert service_request ────────────────────────────────────────
            $stmt = $pdo->prepare("
                INSERT INTO service_request
                    (resident_id, document_type, purpose, status, payment_status, date_requested)
                VALUES
                    (?, ?, ?, 'Pending', ?, CURDATE())
            ");
            $stmt->execute([
                $residentId,
                $serviceType,
                $purpose,
                $paymentStatus
            ]);

            $requestId = $pdo->lastInsertId();

            // ── Generate reference number ─────────────────────────────────────
            $refNo = 'REQ-' . date('Y') . '-' . str_pad($requestId, 6, '0', STR_PAD_LEFT);
            $referenceNumbers[] = $refNo;

            // ── Insert extra fields into service_request_detail ──────────────
            if (!empty($fields)) {
                $detailStmt = $pdo->prepare("
                    INSERT INTO service_request_detail (request_id, field_key, field_value)
                    VALUES (?, ?, ?)
                ");
                foreach ($fields as $key => $value) {
                    if ($key === 'purpose' || $key === 'visit_reason') continue;
                    $detailStmt->execute([$requestId, $key, $value]);
                }
            }

            // ── Insert payment record (if not free) ───────────────────────────
            if ($paymentStatus !== 'Exempted') {
                // Parse numeric fee or leave as 0 for "Varies"/"Computed"
                $amount = 0.00;
                if (is_numeric(str_replace(['₱', ','], '', $fee))) {
                    $amount = floatval(str_replace(['₱', ','], '', $fee));
                }

                $payStmt = $pdo->prepare("
                    INSERT INTO payment
                        (request_id, resident_id, amount, payment_method, payment_status)
                    VALUES
                        (?, ?, ?, ?, 'Pending')
                ");
                $payStmt->execute([
                    $requestId,
                    $residentId,
                    $amount,
                    $paymentMethod
                ]);
            }

            // ── Handle uploaded files ─────────────────────────────────────────
            $fileKey = "files_{$index}";
            if (!empty($_FILES[$fileKey])) {
                $files = $_FILES[$fileKey];

                // Normalize to array structure
                if (!is_array($files['name'])) {
                    $files = array_map(fn($v) => [$v], $files);
                }

                $fileStmt = $pdo->prepare("
                    INSERT INTO service_request_file
                        (request_id, original_name, stored_name, file_size, mime_type)
                    VALUES (?, ?, ?, ?, ?)
                ");

                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

                    $originalName = basename($files['name'][$i]);
                    $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                    // Validate extension
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) continue;

                    // Validate size (5MB max)
                    if ($files['size'][$i] > 5 * 1024 * 1024) continue;

                    // Build folder: files/2026/REQ-2026-000001/
                    $folder = $uploadBase . DIRECTORY_SEPARATOR
                            . date('Y') . DIRECTORY_SEPARATOR
                            . $refNo;

                    if (!is_dir($folder)) mkdir($folder, 0755, true);

                    $storedName = uniqid('', true) . '.' . $ext;
                    $destPath   = $folder . DIRECTORY_SEPARATOR . $storedName;

                    if (move_uploaded_file($files['tmp_name'][$i], $destPath)) {
                        $fileStmt->execute([
                            $requestId,
                            $originalName,
                            date('Y') . '/' . $refNo . '/' . $storedName,
                            $files['size'][$i],
                            $files['type'][$i]
                        ]);
                    }
                }
            }
        }

        $pdo->commit();

        echo json_encode([
            'success'    => true,
            'message'    => 'Requests submitted successfully.',
            'references' => $referenceNumbers,
            'ref_display' => implode(', ', $referenceNumbers)
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Submission failed. Please try again.',
            'debug'   => $e->getMessage() // remove in production
        ]);
    }
}

// ============================================================
// ACTION: get_my_requests
// Returns all requests for the logged-in resident
// ============================================================
function getMyRequests() {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT sr.request_id, sr.document_type, sr.purpose,
               sr.status, sr.payment_status,
               sr.date_requested, sr.date_issued, sr.remarks,
               p.amount, p.payment_method, p.payment_status AS pay_status
        FROM service_request sr
        LEFT JOIN payment p ON p.request_id = sr.request_id
        WHERE sr.resident_id = ?
        ORDER BY sr.date_requested DESC, sr.request_id DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Attach reference numbers
    foreach ($requests as &$req) {
        $req['reference_no'] = 'REQ-' . date('Y', strtotime($req['date_requested']))
                             . '-' . str_pad($req['request_id'], 6, '0', STR_PAD_LEFT);
    }

    echo json_encode([
        'success'  => true,
        'requests' => $requests
    ]);
}

// ============================================================
// ACTION: get_request_detail
// Returns full detail + files for a single request
// ============================================================
function getRequestDetail() {
    global $pdo;

    $requestId = intval($_GET['request_id'] ?? 0);
    if (!$requestId) {
        echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
        return;
    }

    // Verify ownership
    $stmt = $pdo->prepare("
        SELECT * FROM service_request
        WHERE request_id = ? AND resident_id = ?
    ");
    $stmt->execute([$requestId, $_SESSION['user_id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Request not found.']);
        return;
    }

    // Get extra fields
    $stmt = $pdo->prepare("
        SELECT field_key, field_value
        FROM service_request_detail
        WHERE request_id = ?
    ");
    $stmt->execute([$requestId]);
    $details = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Get files
    $stmt = $pdo->prepare("
        SELECT file_id, original_name, stored_name, file_size, uploaded_at
        FROM service_request_file
        WHERE request_id = ?
    ");
    $stmt->execute([$requestId]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get payment
    $stmt = $pdo->prepare("
        SELECT * FROM payment WHERE request_id = ? LIMIT 1
    ");
    $stmt->execute([$requestId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    $request['reference_no'] = 'REQ-' . date('Y', strtotime($request['date_requested']))
                             . '-' . str_pad($request['request_id'], 6, '0', STR_PAD_LEFT);

    echo json_encode([
        'success' => true,
        'request' => $request,
        'details' => $details,
        'files'   => $files,
        'payment' => $payment
    ]);
}
