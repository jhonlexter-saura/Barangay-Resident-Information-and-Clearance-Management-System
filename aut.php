<?php
// ── auth.php — include at the top of every protected page ──────────────────

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Cache headers — prevent back-button and URL bypass ────────────────────
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// ── Auth guard ────────────────────────────────────────────────────────────
if (empty($_SESSION['loggedin']) || empty($_SESSION['user_id'])) {
    header('Location: resident-portal.php');
    exit();
}