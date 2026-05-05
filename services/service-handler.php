<?php
session_start();
require '../config.php';

// ── Auth guard ───────────────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please sign in.']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'download_file') {
    downloadFile();
    exit();
}

header('Content-Type: application/json');

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

    case 'upload_temp_file':
        uploadTempFile();
        break;

    case 'delete_temp_file':
        deleteTempFile();
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

function getPendingUploadDir() {
    $uploadBase = realpath(__DIR__ . '/../../files');
    if (!$uploadBase) {
        mkdir(__DIR__ . '/../../files', 0755, true);
        $uploadBase = realpath(__DIR__ . '/../../files');
    }

    $tempDir = $uploadBase . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . session_id();
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    return $tempDir;
}

function uploadTempFile() {
    if (empty($_FILES['files'])) {
        echo json_encode(['success' => false, 'message' => 'No files uploaded.']);
        return;
    }

    $files = $_FILES['files'];
    $tempDir = getPendingUploadDir();
    if (!isset($_SESSION['pending_uploads'])) {
        $_SESSION['pending_uploads'] = [];
    }

    $uploaded = [];
    $count = is_array($files['name']) ? count($files['name']) : 1;

    for ($i = 0; $i < $count; $i++) {
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        if ($error !== UPLOAD_ERR_OK) {
            continue;
        }

        $originalName = basename(is_array($files['name']) ? $files['name'][$i] : $files['name']);
        $size = is_array($files['size']) ? (int) $files['size'][$i] : (int) $files['size'];
        $mimeType = is_array($files['type']) ? $files['type'][$i] : $files['type'];
        $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
            continue;
        }

        if ($size > 5 * 1024 * 1024) {
            continue;
        }

        $token = bin2hex(random_bytes(16));
        $storedName = $token . '.' . $ext;
        $destPath = $tempDir . DIRECTORY_SEPARATOR . $storedName;

        if (move_uploaded_file($tmpName, $destPath)) {
            $_SESSION['pending_uploads'][$token] = [
                'path'          => $destPath,
                'original_name' => $originalName,
                'mime_type'     => $mimeType,
                'file_size'     => $size,
                'uploaded_at'   => date('Y-m-d H:i:s')
            ];

            $uploaded[] = [
                'token'         => $token,
                'original_name' => $originalName,
                'mime_type'     => $mimeType,
                'file_size'     => $size
            ];
        }
    }

    if (empty($uploaded)) {
        echo json_encode(['success' => false, 'message' => 'No valid files were uploaded.']);
        return;
    }

    echo json_encode(['success' => true, 'files' => $uploaded]);
}

function deleteTempFile() {
    $token = trim($_POST['token'] ?? '');
    if ($token === '' || empty($_SESSION['pending_uploads'][$token])) {
        echo json_encode(['success' => false, 'message' => 'No file token found.']);
        return;
    }

    $upload = $_SESSION['pending_uploads'][$token];
    if (!empty($upload['path']) && file_exists($upload['path'])) {
        @unlink($upload['path']);
    }

    unset($_SESSION['pending_uploads'][$token]);
    echo json_encode(['success' => true]);
}

function ensureServiceRequestFileBlobColumn() {
    global $pdo;
    $column = $pdo->query("SHOW COLUMNS FROM service_request_file LIKE 'file_data'")->fetch();
    if (!$column) {
        $pdo->exec("ALTER TABLE service_request_file ADD COLUMN file_data LONGBLOB NULL COMMENT 'Raw file content stored in database' AFTER mime_type");
    }
}

function isStaffUser() {
    return !empty($_SESSION['role']);
}

function downloadFile() {
    global $pdo;
    ensureServiceRequestFileBlobColumn();

    $fileId = intval($_GET['file_id'] ?? 0);
    if ($fileId <= 0) {
        http_response_code(400);
        echo 'Invalid file ID.';
        return;
    }

    $stmt = $pdo->prepare(
        "SELECT sf.file_id, sf.original_name, sf.mime_type, sf.file_size, sf.file_data, sf.stored_name, sr.resident_id
         FROM service_request_file sf
         JOIN service_request sr ON sr.request_id = sf.request_id
         WHERE sf.file_id = ?
         LIMIT 1"
    );
    $stmt->execute([$fileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        http_response_code(404);
        echo 'File not found.';
        return;
    }

    if (!isStaffUser() && (int) $file['resident_id'] !== (int) $_SESSION['user_id']) {
        http_response_code(403);
        echo 'Access denied.';
        return;
    }

    $filename = basename($file['original_name'] ?? 'attachment');
    $mimeType = $file['mime_type'] ?: 'application/octet-stream';
    $data = $file['file_data'];

    if (is_resource($data)) {
        $data = stream_get_contents($data);
    }

    if ($data === null && !empty($file['stored_name'])) {
        $uploadBase = realpath(__DIR__ . '/../../files');
        $path = $uploadBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['stored_name']);
        if (file_exists($path)) {
            $data = @file_get_contents($path);
        }
    }

    if ($data === null || $data === false) {
        http_response_code(404);
        echo 'File content unavailable.';
        return;
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . strlen($data));
    header('Content-Disposition: attachment; filename="' . str_replace('"', '\\"', $filename) . '"');
    echo $data;
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

    ensureServiceRequestFileBlobColumn();

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

            // ── Handle uploaded temp files referenced by cart tokens ────────────
            if (!empty($item['files']) && is_array($item['files'])) {
                $fileStmt = $pdo->prepare("
                    INSERT INTO service_request_file
                        (request_id, original_name, stored_name, file_size, mime_type, file_data)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                foreach ($item['files'] as $fileMeta) {
                    $token = trim($fileMeta['token'] ?? '');
                    if ($token === '' || empty($_SESSION['pending_uploads'][$token])) {
                        continue;
                    }

                    $upload = $_SESSION['pending_uploads'][$token];
                    if (empty($upload['path']) || !file_exists($upload['path'])) {
                        unset($_SESSION['pending_uploads'][$token]);
                        continue;
                    }

                    $originalName = basename($upload['original_name'] ?? ($fileMeta['original_name'] ?? 'document'));
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                        unset($_SESSION['pending_uploads'][$token]);
                        continue;
                    }

                    $folder = $uploadBase . DIRECTORY_SEPARATOR
                            . date('Y') . DIRECTORY_SEPARATOR
                            . $refNo;

                    if (!is_dir($folder)) mkdir($folder, 0755, true);

                    $storedName = uniqid('', true) . '.' . $ext;
                    $destPath   = $folder . DIRECTORY_SEPARATOR . $storedName;

                    $moved = @rename($upload['path'], $destPath);
                    if (!$moved && file_exists($upload['path'])) {
                        $moved = @copy($upload['path'], $destPath);
                    }

                    if ($moved && file_exists($destPath)) {
                        $fileData = @file_get_contents($destPath);
                        if ($fileData !== false) {
                            $fileStmt->execute([
                                $requestId,
                                $originalName,
                                date('Y') . '/' . $refNo . '/' . $storedName,
                                (int) ($upload['file_size'] ?? 0),
                                $upload['mime_type'] ?? 'application/octet-stream',
                                $fileData
                            ]);
                            if (file_exists($upload['path']) && $upload['path'] !== $destPath) {
                                @unlink($upload['path']);
                            }
                            unset($_SESSION['pending_uploads'][$token]);
                        }
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
