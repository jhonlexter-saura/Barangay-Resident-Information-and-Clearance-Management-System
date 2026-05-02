<?php
session_start();
require 'config.php';

// ── Auth guard ───────────────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    header('Location: resident-portal.php');
    exit();
}

// ── Handle AJAX: save profile ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    header('Content-Type: application/json');

    if ($_POST['action'] === 'save_profile') {

        $fields = [
            'first_name'         => trim($_POST['first_name']         ?? ''),
            'last_name'          => trim($_POST['last_name']          ?? ''),
            'middle_name'       => trim($_POST['middle_name']       ?? ''),
            'suffix'            => trim($_POST['suffix']            ?? ''),
            'birth_date'        => $_POST['birth_date']                    ?? null,
            'gender'               => $_POST['gender']                    ?? null,
            'civil_status'      => $_POST['civil_status']           ?? null,
            'nationality'       => trim($_POST['nationality']       ?? 'Filipino'),
            'mobile_number'     => trim($_POST['mobile_number']            ?? ''),
            'emergency_contact' => trim($_POST['emergency_contact'] ?? ''),
            'street'            => trim($_POST['street']            ?? ''),
            'barangay'          => trim($_POST['barangay']          ?? ''),
            'municipality'      => trim($_POST['municipality']      ?? ''),
            'province'          => trim($_POST['province']          ?? ''),
            'zip_code'          => trim($_POST['zip_code']          ?? ''),
        ];

        if (empty($fields['first_name']) || empty($fields['last_name'])) {
            echo json_encode(['success' => false, 'message' => 'First and last name are required.']);
            exit();
        }

          $stmt = $pdo->prepare("
              UPDATE resident SET
                  first_name    = ?,
                  last_name     = ?,
                  middle_name   = ?,
                  suffix        = ?,
                  birth_date    = ?,
                  gender        = ?,
                  civil_status  = ?,
                  citizenship   = ?,
                  mobile_number = ?
              WHERE resident_id = ?
          ");

          $ok = $stmt->execute([
              $fields['first_name'],
              $fields['last_name'],
              $fields['middle_name']   ?: null,
              $fields['suffix']        ?: null,
              $fields['birth_date']    ?: null,
              $fields['gender']        ?: null,
              $fields['civil_status']  ?: null,
              $fields['nationality']   ?: null,
              $fields['mobile_number'] ?: null,
              $_SESSION['user_id']                // ← exactly 10, matches query
          ]);

        if ($ok) {
            // Update session name in case firstname changed
            $_SESSION['firstname'] = $fields['first_name'];
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
        }
        exit();
    }

    if ($_POST['action'] === 'save_address') {

    $street       = trim($_POST['street']       ?? '');
    $barangay     = trim($_POST['barangay']     ?? '');
    $municipality = trim($_POST['municipality'] ?? '');
    $province     = trim($_POST['province']     ?? '');
    $zip_code     = trim($_POST['zip_code']     ?? '');

    // Check if resident already has a household
    $stmt = $pdo->prepare("
        SELECT household_id FROM household_member
        WHERE resident_id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE household SET
                street       = ?,
                barangay     = ?,
                municipality = ?,
                province     = ?,
                zip_code     = ?
            WHERE household_id = ?
        ");
        $ok = $stmt->execute([
            $street       ?: null,
            $barangay     ?: null,
            $municipality ?: null,
            $province     ?: null,
            $zip_code     ?: null,
            $existing['household_id']
        ]);

    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO household
                    (household_head_id, street, barangay, municipality, province, zip_code, tenure_status)
                VALUES (?, ?, ?, ?, ?, ?, 'Owner')
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $street       ?: null,
                $barangay     ?: null,
                $municipality ?: null,
                $province     ?: null,
                $zip_code     ?: null,
            ]);

            $newHouseholdId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("
                INSERT INTO household_member (household_id, resident_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$newHouseholdId, $_SESSION['user_id']]);

            $pdo->commit();
            $ok = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            $ok = false;
        }
    }

    echo json_encode([
        'success' => $ok,
        'message' => $ok ? 'Address updated successfully!' : 'Database error. Please try again.'
    ]);
    exit();
}

    if ($_POST['action'] === 'change_password') {

        $current = $_POST['current_password'] ?? '';
        $newPw   = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$current || !$newPw || !$confirm) {
            echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
            exit();
        }

        if (strlen($newPw) < 8) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
            exit();
        }

        if ($newPw !== $confirm) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
            exit();
        }

        // Fetch current hash
        $stmt = $pdo->prepare("SELECT password FROM resident WHERE resident_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit();
        }

        $newHash = password_hash($newPw, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE resident SET password = ? WHERE resident_id = ?");
        $ok = $stmt->execute([$newHash, $_SESSION['user_id']]);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Password updated successfully!' : 'Database error. Please try again.'
        ]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}

// ── Fetch user for page render ────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT r.*, h.house_number, h.street, h.sitio_purok,
           h.barangay, h.municipality, h.province, h.zip_code,
           h.household_id
    FROM resident r
    LEFT JOIN household_member hm ON hm.resident_id = r.resident_id
    LEFT JOIN household h         ON h.household_id  = hm.household_id
    WHERE r.resident_id = ?
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: resident-portal.php');
    exit();
}
// house_number + street combined for the "Street / House No." field
$streetDisplay = trim(($user['house_number'] ?? '') . ' ' . ($user['street'] ?? ''));

// ── Helpers ──────────────────────────────────────────────────────────────────
function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
function val($v) { return 'value="' . h($v) . '"'; }
function sel($field, $option) {
    return $field === $option ? 'selected' : '';
}

$initials   = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
$fullName   = h($user['first_name']) . ' ' . h($user['last_name']);
$firstname  = h($user['first_name']); 
$residentId = h($user['resident_id'] ?? 'RES-?????');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MySerbisyo — My Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,600;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/shared.css" rel="stylesheet">
  <link href="css/resident-home.css" rel="stylesheet">
  <link href="css/resident-profile.css" rel="stylesheet">
</head>

<body>

  <!-- ── Sidebar ── -->
  <aside class="r-sidebar" id="rSidebar">

    <div class="r-sidebar-brand">
      <div class="r-brand-logo"><i class="bi bi-buildings-fill"></i></div>
      <div class="r-brand-text">
        <span class="r-brand-name">MySerbisyo</span>
        <span class="r-brand-sub">Resident Portal</span>
      </div>
    </div>

    <nav class="r-sidebar-nav">

      <div class="r-nav-label">Menu</div>

      <a href="resident-home.php" class="r-nav-item" data-tooltip="Home">
        <i class="bi bi-house-fill r-nav-icon"></i>
        <span class="r-nav-text">Home</span>
      </a>

      <a href="resident-requests.html" class="r-nav-item" data-tooltip="My Requests">
        <i class="bi bi-file-earmark-text r-nav-icon"></i>
        <span class="r-nav-text">My Requests</span>
        <span class="r-nav-badge">2</span>
      </a>

      <a href="services/resident-payment.html" class="r-nav-item" data-tooltip="Payments">
        <i class="bi bi-cash-coin r-nav-icon"></i>
        <span class="r-nav-text">Payments</span>
      </a>

      <a href="services/appointments.html" class="r-nav-item" data-tooltip="Appointments">
        <i class="bi bi-calendar-check r-nav-icon"></i>
        <span class="r-nav-text">Appointments</span>
      </a>

      <a href="resident-notifications.html" class="r-nav-item" data-tooltip="Notifications">
        <i class="bi bi-bell r-nav-icon"></i>
        <span class="r-nav-text">Notifications</span>
        <span class="r-nav-badge">5</span>
      </a>

      <div class="r-nav-divider"></div>
      <div class="r-nav-label">Account</div>

      <a href="resident-profile.php" class="r-nav-item active" data-tooltip="My Profile">
        <i class="bi bi-person-circle r-nav-icon"></i>
        <span class="r-nav-text">My Profile</span>
      </a>

      <a href="resident-settings.php" class="r-nav-item" data-tooltip="Settings">
        <i class="bi bi-gear r-nav-icon"></i>
        <span class="r-nav-text">Settings</span>
      </a>

      <a href="resident-helpsupport.php" class="r-nav-item" data-tooltip="Help">
        <i class="bi bi-question-circle r-nav-icon"></i>
        <span class="r-nav-text">Help & Support</span>
      </a>

    </nav>

    <div class="r-sidebar-footer">
      <div class="r-user-row">
        <div class="r-user-avatar"><?= $initials ?></div>
        <div class="r-user-info">
          <span class="r-user-name"><?= $fullName ?></span>
          <span class="r-user-sub">Resident ID: <?= $residentId ?></span>
        </div>
        <a href="resident-logout.php" class="r-logout-btn" title="Sign out">
          <i class="bi bi-box-arrow-right"></i>
        </a>
      </div>
    </div>

  </aside>

  <!-- ── Main area ── -->
  <div class="r-main" id="rMain">

    <!-- ── Topbar ── -->
    <header class="r-topbar">
      <div class="r-topbar-left">
        <button class="r-menu-btn" id="rMenuBtn" aria-label="Open menu">
          <i class="bi bi-list"></i>
        </button>
        <div class="r-topbar-brand">
          <div class="r-tb-logo"><i class="bi bi-buildings-fill"></i></div>
          <a href="resident-home.php"><span class="r-tb-name">MySerbisyo</span></a>
        </div>
      </div>
      <div class="r-topbar-right">
        <button class="r-topbar-btn" aria-label="Notifications">
          <i class="bi bi-bell"></i>
          <span class="r-notif-dot"></span>
        </button>
        <a href="resident-profile.php" class="r-profile-chip">
          <div class="r-chip-avatar"><?= $initials ?></div>
          <span class="r-chip-name"><?= $firstname ?></span>
          <i class="bi bi-chevron-down"></i>
        </a>
      </div>
    </header>

    <!-- ── Profile content ── -->
    <main class="r-content">

      <!-- Page heading -->
      <div class="prof-page-header">
        <div>
          <h1 class="prof-page-title">My Profile</h1>
          <p class="prof-page-sub">Manage your personal information and account settings</p>
        </div>
        <button class="prof-save-btn" id="saveBtn" style="display:none;" onclick="saveProfile()">
          <i class="bi bi-check-lg"></i> Save Changes
        </button>
      </div>

      <div class="prof-layout">

        <!-- ── Left: profile card ── -->
        <div class="prof-left">

          <div class="avatar-card">
            <div class="avatar-wrap">
              <div class="avatar-circle" id="avatarCircle"><?= $initials ?></div>
              <button class="avatar-edit-btn" title="Change photo">
                <i class="bi bi-camera-fill"></i>
              </button>
            </div>
            <div class="avatar-name" id="avatarName"><?= $fullName ?></div>
            <div class="avatar-id">
              <i class="bi bi-person-badge"></i> <?= $residentId ?>
            </div>
            <div class="avatar-badge">
              <i class="bi bi-patch-check-fill"></i>
              <?= $user['is_verified'] ? 'Verified Resident' : 'Pending Verification' ?>
            </div>
          </div>

          <div class="prof-quick-links">
            <a href="#personal" class="pql-item active" data-section="personal">
              <i class="bi bi-person-fill"></i> Personal Info
            </a>
            <a href="#contact" class="pql-item" data-section="contact">
              <i class="bi bi-telephone-fill"></i> Contact Details
            </a>
            <a href="#address" class="pql-item" data-section="address">
              <i class="bi bi-house-fill"></i> Address
            </a>
            <a href="#security" class="pql-item" data-section="security">
              <i class="bi bi-shield-lock-fill"></i> Security
            </a>
            <a href="#documents" class="pql-item" data-section="documents">
              <i class="bi bi-folder-fill"></i> My Documents
            </a>
          </div>

        </div>

        <!-- ── Right: form sections ── -->
        <div class="prof-right">

          <!-- Personal Information -->
          <div class="prof-section" id="personal">
            <div class="prof-section-header">
              <div class="prof-section-title">
                <i class="bi bi-person-fill"></i> Personal Information
              </div>
              <button class="prof-edit-btn" onclick="toggleEdit('personal')">
                <i class="bi bi-pencil"></i> Edit
              </button>
            </div>
            <div class="prof-section-body">
              <div class="prof-field-row">
                <div class="prof-field">
                  <label class="prof-label">First Name</label>
                  <input type="text" class="prof-input" id="firstName" name="first_name"
                         <?= val($user['first_name']) ?> disabled>
                </div>
                <div class="prof-field">
                  <label class="prof-label">Last Name</label>
                  <input type="text" class="prof-input" id="lastName" name="last_name"
                         <?= val($user['last_name']) ?> disabled>
                </div>
              </div>
              <div class="prof-field-row">
                <div class="prof-field">
                  <label class="prof-label">Middle Name</label>
                  <input type="text" class="prof-input" id="middleName" name="middle_name"
                         <?= val($user['middle_name']) ?> disabled>
                </div>
                <div class="prof-field">
                  <label class="prof-label">Suffix</label>
                  <input type="text" class="prof-input" id="suffix" name="suffix"
                         <?= val($user['suffix']) ?> disabled>
                </div>
              </div>
              <div class="prof-field-row">
                <div class="prof-field">
                  <label class="prof-label">Date of Birth</label>
                  <input type="date" class="prof-input" id="dob" name="dob"
                         <?= val($user['birth_date']) ?> disabled>
                </div>
                <div class="prof-field">
                  <label class="prof-label">Sex</label>
                  <select class="prof-input" id="sex" name="gender" disabled>
                    <option value="">— select —</option>
                    <option value="male"   <?= sel($user['gender'], 'male')   ?>>Male</option>
                    <option value="female" <?= sel($user['gender'], 'female') ?>>Female</option>
                    <option value="other"  <?= sel($user['gender'], 'other')  ?>>Other</option>
                  </select>
                </div>
              </div>
              <div class="prof-field-row">
                <div class="prof-field">
                  <label class="prof-label">Civil Status</label>
                  <select class="prof-input" id="civil" name="civil_status" disabled>
                    <option value="">— select —</option>
                    <option value="single"    <?= sel($user['civil_status'], 'single')    ?>>Single</option>
                    <option value="married"   <?= sel($user['civil_status'], 'married')   ?>>Married</option>
                    <option value="widowed"   <?= sel($user['civil_status'], 'widowed')   ?>>Widowed</option>
                    <option value="separated" <?= sel($user['civil_status'], 'separated') ?>>Separated</option>
                  </select>
                </div>
                <div class="prof-field">
                  <label class="prof-label">Nationality</label>
                  <input type="text" class="prof-input" id="nationality" name="citizenship"
                         <?= val($user['citizenship'] ?? 'Filipino') ?> disabled>
                </div>
              </div>
            </div>
          </div>

          <!-- Contact Details -->
          <div class="prof-section" id="contact">
            <div class="prof-section-header">
              <div class="prof-section-title">
                <i class="bi bi-telephone-fill"></i> Contact Details
              </div>
              <button class="prof-edit-btn" onclick="toggleEdit('contact')">
                <i class="bi bi-pencil"></i> Edit
              </button>
            </div>
            <div class="prof-section-body">
              <div class="prof-field-row">
                <div class="prof-field">
                  <label class="prof-label">Email Address</label>
                  <div class="prof-input-wrap">
                    <input type="email" class="prof-input" id="email" name="email"
                           <?= val($user['email']) ?> disabled>
                    <span class="prof-verified"><i class="bi bi-patch-check-fill"></i> Verified</span>
                  </div>
                </div>
                <div class="prof-field">
                  <label class="prof-label">Mobile Number</label>
                  <input type="tel" class="prof-input" id="mobile" name="mobile"
                         <?= val($user['mobile_number']) ?> disabled>
                </div>
              </div>
              <div class="prof-field">
                <label class="prof-label">Emergency Contact</label>
                <input type="text" class="prof-input" id="emergency" name="emergency_contact"
                       <?= val($user['emergency_contact']) ?> disabled>
              </div>
            </div>
          </div>

          <!-- Address -->
          <div class="prof-section" id="address">
            <div class="prof-section-header">
              <div class="prof-section-title">
                <i class="bi bi-house-fill"></i> Address
              </div>
              <button class="prof-edit-btn" onclick="toggleEdit('address')">
                <i class="bi bi-pencil"></i> Edit
              </button>
            </div>
            <div class="prof-section-body">
              <div class="prof-field">
                <label class="prof-label">Street / House No.</label>
                <input type="text" class="prof-input" id="street" name="street"
                       <?= val($streetDisplay) ?> disabled>
              </div>
              <div class="prof-field-row">
                <div class="prof-field">
                  <label class="prof-label">Barangay</label>
                  <input type="text" class="prof-input" id="barangay" name="barangay"
                         <?= val($user['barangay']) ?> disabled>
                </div>
                <div class="prof-field">
                  <label class="prof-label">Municipality / City</label>
                  <input type="text" class="prof-input" id="municipality" name="municipality"
                         <?= val($user['municipality']) ?> disabled>
                </div>
              </div>
              <div class="prof-field-row">
                <div class="prof-field">
                  <label class="prof-label">Province</label>
                  <input type="text" class="prof-input" id="province" name="province"
                         <?= val($user['province']) ?> disabled>
                </div>
                <div class="prof-field">
                  <label class="prof-label">ZIP Code</label>
                  <input type="text" class="prof-input" id="zip" name="zip_code"
                         <?= val($user['zip_code']) ?> disabled>
                </div>
              </div>
            </div>
          </div>

          <!-- Security -->
          <div class="prof-section" id="security">
            <div class="prof-section-header">
              <div class="prof-section-title">
                <i class="bi bi-shield-lock-fill"></i> Security
              </div>
            </div>
            <div class="prof-section-body">
              <div class="security-item">
                <div class="sec-info">
                  <div class="sec-label">Password</div>
                  <div class="sec-sub">Update your account password</div>
                </div>
                <button class="sec-action-btn" onclick="showChangePassword()">Change Password</button>
              </div>

              <div class="change-pw-form" id="changePwForm" style="display:none;">
                <div class="prof-field">
                  <label class="prof-label">Current Password</label>
                  <input type="password" class="prof-input" id="currentPw" placeholder="••••••••">
                </div>
                <div class="prof-field">
                  <label class="prof-label">New Password</label>
                  <input type="password" class="prof-input" id="newPw" placeholder="••••••••">
                </div>
                <div class="prof-field">
                  <label class="prof-label">Confirm New Password</label>
                  <input type="password" class="prof-input" id="confirmPw" placeholder="••••••••">
                </div>
                <div class="change-pw-actions">
                  <button class="sec-action-btn" onclick="hideChangePassword()">Cancel</button>
                  <button class="sec-save-btn" onclick="updatePassword()">Update Password</button>
                </div>
              </div>

              <div class="security-item">
                <div class="sec-info">
                  <div class="sec-label">Two-Factor Authentication</div>
                  <div class="sec-sub">Add an extra layer of security</div>
                </div>
                <button class="sec-action-btn">Enable 2FA</button>
              </div>

              <div class="security-item">
                <div class="sec-info">
                  <div class="sec-label">Active Sessions</div>
                  <div class="sec-sub">1 device currently signed in</div>
                </div>
                <a href="resident-logout.php" class="sec-action-btn danger">Sign Out All</a>
              </div>
            </div>
          </div>

          <!-- My Documents -->
          <div class="prof-section" id="documents">
            <div class="prof-section-header">
              <div class="prof-section-title">
                <i class="bi bi-folder-fill"></i> My Documents
              </div>
              <span class="prof-section-sub">Recently issued</span>
            </div>
            <div class="prof-section-body">
              <div class="doc-list">

                <div class="doc-item">
                  <div class="doc-icon" style="background:#e8f3fc; color:#1a7fd4;">
                    <i class="bi bi-file-earmark-fill"></i>
                  </div>
                  <div class="doc-info">
                    <div class="doc-name">Barangay Clearance</div>
                    <div class="doc-meta">Issued: Mar 12, 2026 &nbsp;·&nbsp; Valid until: Mar 12, 2027</div>
                  </div>
                  <button class="doc-dl-btn"><i class="bi bi-download"></i> Download</button>
                </div>

                <div class="doc-item">
                  <div class="doc-icon" style="background:#e6f7ef; color:#1a9e5f;">
                    <i class="bi bi-file-earmark-fill"></i>
                  </div>
                  <div class="doc-info">
                    <div class="doc-name">Cedula / CTC 2026</div>
                    <div class="doc-meta">Issued: Jan 5, 2026 &nbsp;·&nbsp; Valid until: Dec 31, 2026</div>
                  </div>
                  <button class="doc-dl-btn"><i class="bi bi-download"></i> Download</button>
                </div>

                <div class="doc-item">
                  <div class="doc-icon" style="background:#fde8e8; color:#dc2626;">
                    <i class="bi bi-file-earmark-fill"></i>
                  </div>
                  <div class="doc-info">
                    <div class="doc-name">Health Certificate</div>
                    <div class="doc-meta">Issued: Feb 20, 2026 &nbsp;·&nbsp; Valid until: Feb 20, 2027</div>
                  </div>
                  <button class="doc-dl-btn"><i class="bi bi-download"></i> Download</button>
                </div>

              </div>
            </div>
          </div>

        </div>
      </div>

    </main>
  </div>

  <!-- Mobile overlay -->
  <div class="r-overlay" id="rOverlay"></div>

  <script src="js/resident-home.js"></script>
  <script src="js/resident-profile.js"></script>

</body>
</html>
