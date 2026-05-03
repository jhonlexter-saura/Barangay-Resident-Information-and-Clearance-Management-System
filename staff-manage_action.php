<?php
/**
 * staff-manage_action.php
 * Handles all POST actions from staff-manage.php:
 *   add            → create a new barangay_official
 *   edit           → update username / role_position / access_level
 *   reset_password → set a new hashed password
 *   toggle_status  → activate or deactivate an account
 */

session_start();
require 'config.php';

// ── Auth & admin guard ───────────────────────────────────────────────────────
if (empty($_SESSION['loggedin'])) {
    header('Location: staff-portal.php');
    exit();
}
if ($_SESSION['role'] !== 'Admin') {
    $_SESSION['error'] = 'Unauthorized action.';
    header('Location: staff-dashboard.php');
    exit();
}

// ── Only accept POST ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: staff-manage.php');
    exit();
}

$action = $_POST['action'] ?? '';

// ════════════════════════════════════════════════════════════════════════════
// ACTION: add
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'add') {

    $resident_id   = intval($_POST['resident_id']   ?? 0);
    $username      = trim($_POST['username']         ?? '');
    $password      =      $_POST['password']         ?? '';
    $confirm       =      $_POST['confirm_password'] ?? '';
    $role_position = trim($_POST['role_position']    ?? '');
    $access_level  = trim($_POST['access_level']     ?? '');

    // ── Validate ─────────────────────────────────────────────────────────────
    if ($resident_id <= 0) {
        $_SESSION['error'] = 'Please select a linked resident record.';
        header('Location: staff-manage.php'); exit();
    }
    if (!$username || !$password || !$confirm || !$role_position || !$access_level) {
        $_SESSION['error'] = 'All fields are required.';
        header('Location: staff-manage.php'); exit();
    }
    if (!preg_match('/^[a-zA-Z0-9._\-]{3,60}$/', $username)) {
        $_SESSION['error'] = 'Invalid username format (3–60 chars, letters/numbers/dots/underscores/hyphens).';
        header('Location: staff-manage.php'); exit();
    }
    if (strlen($password) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters.';
        header('Location: staff-manage.php'); exit();
    }
    if ($password !== $confirm) {
        $_SESSION['error'] = 'Passwords do not match.';
        header('Location: staff-manage.php'); exit();
    }

    $validPositions = ['Chairman','Secretary','Treasurer','Councilor','Tanod','Clerk','Admin'];
    $validAccess    = ['Admin','Editor','Viewer'];
    if (!in_array($role_position, $validPositions, true)) {
        $_SESSION['error'] = 'Invalid position selected.';
        header('Location: staff-manage.php'); exit();
    }
    if (!in_array($access_level, $validAccess, true)) {
        $_SESSION['error'] = 'Invalid access level selected.';
        header('Location: staff-manage.php'); exit();
    }

    // ── Check resident exists ─────────────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT resident_id FROM resident WHERE resident_id = ?");
    $stmt->execute([$resident_id]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'Selected resident does not exist.';
        header('Location: staff-manage.php'); exit();
    }

    // ── Check resident not already an official ────────────────────────────────
    $stmt = $pdo->prepare("SELECT user_id FROM barangay_official WHERE resident_id = ?");
    $stmt->execute([$resident_id]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'This resident already has a staff account.';
        header('Location: staff-manage.php'); exit();
    }

    // ── Check username unique ─────────────────────────────────────────────────
    $stmt = $pdo->prepare("SELECT user_id FROM barangay_official WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'That username is already taken.';
        header('Location: staff-manage.php'); exit();
    }

    // ── Insert ────────────────────────────────────────────────────────────────
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO barangay_official
            (resident_id, role_position, access_level, username, hashed_password, is_active)
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    if ($stmt->execute([$resident_id, $role_position, $access_level, $username, $hash])) {
        $_SESSION['success'] = "Official account '{$username}' created successfully.";
    } else {
        $_SESSION['error'] = 'Something went wrong. Please try again.';
    }

    header('Location: staff-manage.php'); exit();
}

// ════════════════════════════════════════════════════════════════════════════
// ACTION: edit
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'edit') {

    $user_id       = intval($_POST['user_id']        ?? 0);
    $username      = trim($_POST['username']          ?? '');
    $role_position = trim($_POST['role_position']     ?? '');
    $access_level  = trim($_POST['access_level']      ?? '');

    if ($user_id <= 0 || !$username || !$role_position || !$access_level) {
        $_SESSION['error'] = 'All fields are required.';
        header('Location: staff-manage.php'); exit();
    }
    if (!preg_match('/^[a-zA-Z0-9._\-]{3,60}$/', $username)) {
        $_SESSION['error'] = 'Invalid username format.';
        header('Location: staff-manage.php'); exit();
    }

    $validPositions = ['Chairman','Secretary','Treasurer','Councilor','Tanod','Clerk','Admin'];
    $validAccess    = ['Admin','Editor','Viewer'];
    if (!in_array($role_position, $validPositions, true) || !in_array($access_level, $validAccess, true)) {
        $_SESSION['error'] = 'Invalid position or access level.';
        header('Location: staff-manage.php'); exit();
    }

    // Ensure username not taken by another user
    $stmt = $pdo->prepare("SELECT user_id FROM barangay_official WHERE username = ? AND user_id != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'That username is already taken by another official.';
        header('Location: staff-manage.php'); exit();
    }

    $stmt = $pdo->prepare("
        UPDATE barangay_official
        SET username = ?, role_position = ?, access_level = ?
        WHERE user_id = ?
    ");
    if ($stmt->execute([$username, $role_position, $access_level, $user_id])) {
        $_SESSION['success'] = "Official '{$username}' updated successfully.";
    } else {
        $_SESSION['error'] = 'Update failed. Please try again.';
    }

    header('Location: staff-manage.php'); exit();
}

// ════════════════════════════════════════════════════════════════════════════
// ACTION: reset_password
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'reset_password') {

    $user_id  = intval($_POST['user_id']           ?? 0);
    $password =        $_POST['new_password']       ?? '';
    $confirm  =        $_POST['confirm_new_password'] ?? '';

    if ($user_id <= 0 || !$password || !$confirm) {
        $_SESSION['error'] = 'All fields are required.';
        header('Location: staff-manage.php'); exit();
    }
    if (strlen($password) < 8) {
        $_SESSION['error'] = 'New password must be at least 8 characters.';
        header('Location: staff-manage.php'); exit();
    }
    if ($password !== $confirm) {
        $_SESSION['error'] = 'Passwords do not match.';
        header('Location: staff-manage.php'); exit();
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE barangay_official SET hashed_password = ? WHERE user_id = ?");
    if ($stmt->execute([$hash, $user_id])) {
        $_SESSION['success'] = 'Password reset successfully.';
    } else {
        $_SESSION['error'] = 'Password reset failed. Please try again.';
    }

    header('Location: staff-manage.php'); exit();
}

// ════════════════════════════════════════════════════════════════════════════
// ACTION: toggle_status
// ════════════════════════════════════════════════════════════════════════════
if ($action === 'toggle_status') {

    $user_id    = intval($_POST['user_id']    ?? 0);
    $new_status = intval($_POST['new_status'] ?? -1);

    if ($user_id <= 0 || !in_array($new_status, [0, 1], true)) {
        $_SESSION['error'] = 'Invalid request.';
        header('Location: staff-manage.php'); exit();
    }

    // Prevent admin from deactivating their own account
    if ($user_id === intval($_SESSION['user_id']) && $new_status === 0) {
        $_SESSION['error'] = 'You cannot deactivate your own account.';
        header('Location: staff-manage.php'); exit();
    }

    $stmt = $pdo->prepare("UPDATE barangay_official SET is_active = ? WHERE user_id = ?");
    if ($stmt->execute([$new_status, $user_id])) {
        $_SESSION['success'] = $new_status
            ? 'Account activated successfully.'
            : 'Account deactivated successfully.';
    } else {
        $_SESSION['error'] = 'Status update failed. Please try again.';
    }

    header('Location: staff-manage.php'); exit();
}

// ── Unknown action ───────────────────────────────────────────────────────────
$_SESSION['error'] = 'Unknown action.';
header('Location: staff-manage.php');
exit();