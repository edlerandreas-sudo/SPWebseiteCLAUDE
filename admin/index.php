<?php
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
session_start();
require_once __DIR__ . '/data-store.php';

$adminUser = '';
$adminPassHash = '';
$adminPassPlain = '';

$configPath = __DIR__ . '/config.php';
if (is_file($configPath)) {
    require $configPath;
}

if ($adminUser === '') {
    $adminUser = getenv('SP_ADMIN_USER') ?: '';
}
if ($adminPassHash === '') {
    $adminPassHash = getenv('SP_ADMIN_PASS_HASH') ?: '';
}
if ($adminPassPlain === '') {
    $adminPassPlain = getenv('SP_ADMIN_PASS') ?: '';
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

$isConfigured = $adminUser !== '' && ($adminPassHash !== '' || $adminPassPlain !== '');
$loginError = false;

if ($isConfigured && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $user = trim((string)($_POST['username'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $isValidPassword = false;

    if ($adminPassHash !== '') {
        $isValidPassword = password_verify($pass, $adminPassHash);
    } elseif ($adminPassPlain !== '') {
        // Klartext-Fallback: nur für initiale Einrichtung, bcrypt-Hash empfohlen!
        $isValidPassword = hash_equals($adminPassPlain, $pass);
        // Bei erfolgreichem Login: Hash-Hinweis loggen
        if ($isValidPassword) {
            error_log('SP-Admin: Klartext-Passwort aktiv – bitte auf password_hash() umstellen!');
        }
    }

    if (hash_equals($adminUser, $user) && $isValidPassword) {
        session_regenerate_id(true);
        $_SESSION['sp_admin_auth'] = true;
        header('Location: index.php');
        exit;
    }

    $loginError = true;
}

if (!$isConfigured) {
    http_response_code(503);
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Setup – Steirer Pellets</title>
  <style>
    body { font-family: sans-serif; margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0f4d18; color: #1f2937; }
    .card { background: white; max-width: 620px; padding: 32px; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.2); }
    code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
    h1 { margin-top: 0; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Admin noch nicht konfiguriert</h1>
    <p>Der Admin-Bereich benötigt Zugangsdaten in <code>admin/config.php</code> oder alternativ als Server-Umgebungsvariablen.</p>
    <p>Bitte setzen Sie <code>$adminUser</code> sowie entweder <code>$adminPassHash</code> oder <code>$adminPassPlain</code> in der Konfigurationsdatei.</p>
  </div>
</body>
</html>
    <?php
    exit;
}

if (empty($_SESSION['sp_admin_auth'])) {
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login – Steirer Pellets</title>
  <style>
    body { font-family: sans-serif; margin: 0; min-height: 100vh; display: grid; place-items: center; background: linear-gradient(135deg, #0f4d18, #1d6b28); }
    .card { background: white; width: min(92vw, 380px); padding: 32px; border-radius: 18px; box-shadow: 0 24px 60px rgba(0,0,0,0.3); }
    h1 { margin: 0 0 8px; font-size: 1.4rem; }
    p { margin: 0 0 20px; color: #6b7280; }
    label { display: block; font-size: 0.85rem; margin-bottom: 6px; color: #374151; }
    input { width: 100%; box-sizing: border-box; padding: 12px 14px; margin-bottom: 14px; border: 1px solid #d1d5db; border-radius: 10px; }
    button { width: 100%; padding: 12px 14px; border: 0; border-radius: 10px; background: #1d6b28; color: white; font-weight: 700; cursor: pointer; }
    .error { margin-bottom: 14px; color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 10px 12px; }
  </style>
</head>
<body>
  <form class="card" method="post" action="index.php">
    <h1>Admin Login</h1>
    <p>Bitte mit den serverseitig konfigurierten Zugangsdaten anmelden.</p>
    <?php if ($loginError) { ?>
      <div class="error">Benutzername oder Passwort falsch.</div>
    <?php } ?>
    <input type="hidden" name="action" value="login" />
    <label for="username">Benutzername</label>
    <input id="username" name="username" autocomplete="username" required />
    <label for="password">Passwort</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required />
    <button type="submit">Anmelden</button>
  </form>
</body>
</html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin – Steirer Pellets</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --green:      #1d6b28;
      --green-dark: #0f4d18;
      --green-pale: #e6f4e8;
      --green-lite: #f2faf3;
      --amber:      #e8922a;
      --red:        #dc2626;
      --gray-50:    #f9fafb;
      --gray-100:   #f3f4f6;
      --gray-200:   #e5e7eb;
      --gray-300:   #d1d5db;
      --gray-400:   #9ca3af;
      --gray-500:   #6b7280;
      --gray-600:   #4b5563;
      --gray-700:   #374151;
      --gray-800:   #1f2937;
      --gray-900:   #111827;
      --radius:     10px;
      --shadow:     0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
      --shadow-md:  0 4px 6px rgba(0,0,0,0.07), 0 2px 4px rgba(0,0,0,0.06);
      --shadow-lg:  0 10px 25px rgba(0,0,0,0.1);
    }
    body { font-family: 'Inter', sans-serif; background: var(--gray-50); color: var(--gray-800); min-height: 100vh; }

    /* ── LOGIN ── */
    #loginScreen {
      position: fixed; inset: 0; background: linear-gradient(135deg, var(--green-dark), var(--green));
      display: flex; align-items: center; justify-content: center; z-index: 1000;
    }
    .login-box {
      background: white; border-radius: 18px; padding: 48px 40px;
      width: 100%; max-width: 380px; box-shadow: 0 24px 60px rgba(0,0,0,0.3);
      text-align: center;
    }
    .login-logo { font-size: 2.5rem; margin-bottom: 8px; }
    .login-box h1 { font-size: 1.4rem; font-weight: 800; color: var(--gray-900); margin-bottom: 6px; }
    .login-box p  { font-size: 0.85rem; color: var(--gray-500); margin-bottom: 32px; }
    .login-box input {
      width: 100%; padding: 12px 16px; border: 1.5px solid var(--gray-300); border-radius: var(--radius);
      font-size: 0.95rem; font-family: inherit; margin-bottom: 14px; outline: none; transition: border-color .2s;
    }
    .login-box input:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,107,40,0.1); }
    .login-btn {
      width: 100%; padding: 13px; background: linear-gradient(135deg, var(--green), var(--green-dark));
      color: white; border: none; border-radius: var(--radius); font-size: 1rem; font-weight: 700;
      cursor: pointer; transition: opacity .2s, transform .15s;
    }
    .login-btn:hover { opacity: 0.92; transform: translateY(-1px); }
    .login-error { color: var(--red); font-size: 0.83rem; margin-top: 10px; display: none; }

    /* ── LAYOUT ── */
    #adminApp { display: block; }
    .admin-sidebar {
      position: fixed; left: 0; top: 0; bottom: 0; width: 240px;
      background: linear-gradient(180deg, var(--green-dark) 0%, #0a3510 100%);
      display: flex; flex-direction: column; z-index: 100;
      box-shadow: 4px 0 20px rgba(0,0,0,0.15);
    }
    .sidebar-brand {
      padding: 24px 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1);
      display: flex; align-items: center; gap: 12px;
    }
    .sidebar-brand-icon {
      width: 38px; height: 38px; background: rgba(255,255,255,0.12);
      border-radius: 9px; display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; color: #5abf6a; flex-shrink: 0;
    }
    .sidebar-brand-text { color: white; font-weight: 700; font-size: 0.92rem; line-height: 1.3; }
    .sidebar-brand-text small { display: block; font-size: 0.72rem; color: rgba(255,255,255,0.5); font-weight: 400; }

    .sidebar-nav { padding: 16px 12px; flex: 1; display: flex; flex-direction: column; gap: 4px; }
    .nav-item {
      display: flex; align-items: center; gap: 11px; padding: 11px 14px;
      border-radius: 9px; color: rgba(255,255,255,0.7); font-size: 0.875rem; font-weight: 500;
      cursor: pointer; transition: all .2s; border: none; background: none; width: 100%; text-align: left;
    }
    .nav-item i { width: 18px; text-align: center; flex-shrink: 0; font-size: 0.9rem; }
    .nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
    .nav-item.active { background: rgba(255,255,255,0.15); color: white; font-weight: 600; }
    .nav-item.active i { color: #5abf6a; }

    .sidebar-footer {
      padding: 16px 12px; border-top: 1px solid rgba(255,255,255,0.1);
    }
    .logout-btn {
      display: flex; align-items: center; gap: 10px; padding: 10px 14px;
      border-radius: 9px; color: rgba(255,255,255,0.55); font-size: 0.82rem;
      cursor: pointer; transition: all .2s; border: none; background: none; width: 100%;
    }
    .logout-btn:hover { color: #ff8080; background: rgba(255,100,100,0.1); }

    .admin-main { margin-left: 240px; min-height: 100vh; padding: 36px 40px; }

    /* ── HEADER ── */
    .page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 32px; flex-wrap: wrap; gap: 16px; }
    .page-header h1 { font-size: 1.55rem; font-weight: 800; color: var(--gray-900); }
    .page-header p  { font-size: 0.85rem; color: var(--gray-500); margin-top: 4px; }

    /* ── CARDS ── */
    .card { background: white; border-radius: var(--radius); border: 1px solid var(--gray-200); box-shadow: var(--shadow); }
    .card-header {
      padding: 18px 24px 16px; border-bottom: 1px solid var(--gray-200);
      display: flex; align-items: center; justify-content: space-between; gap: 12px;
    }
    .card-header h2 { font-size: 1rem; font-weight: 700; color: var(--gray-800); display: flex; align-items: center; gap: 8px; }
    .card-header h2 i { color: var(--green); }
    .card-body { padding: 24px; }

    /* ── PREISE ── */
    .price-form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 0.78rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; letter-spacing: 0.05em; }
    .form-group input, .form-group select, .form-group textarea {
      padding: 10px 14px; border: 1.5px solid var(--gray-300); border-radius: 8px;
      font-size: 0.95rem; font-family: inherit; outline: none; transition: border-color .2s, box-shadow .2s;
      background: white; color: var(--gray-900);
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
      border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,107,40,0.1);
    }
    .form-group textarea { resize: vertical; min-height: 80px; }
    .form-group .hint { font-size: 0.75rem; color: var(--gray-400); }
    .input-prefix { position: relative; }
    .input-prefix span {
      position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
      font-size: 0.9rem; color: var(--gray-400); pointer-events: none;
    }
    .input-prefix input { padding-left: 28px; }

    /* ── BUTTONS ── */
    .btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; border: none; transition: all .2s; font-family: inherit; }
    .btn-primary { background: linear-gradient(135deg, var(--green), var(--green-dark)); color: white; }
    .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(29,107,40,0.3); }
    .btn-outline { background: white; color: var(--green); border: 1.5px solid var(--green); }
    .btn-outline:hover { background: var(--green-lite); }
    .btn-danger { background: white; color: var(--red); border: 1.5px solid #fca5a5; }
    .btn-danger:hover { background: #fef2f2; }
    .btn-sm { padding: 6px 12px; font-size: 0.78rem; }
    .btn-amber { background: var(--amber); color: white; }
    .btn-amber:hover { opacity: 0.9; }

    /* ── TOAST ── */
    #toast {
      position: fixed; bottom: 28px; right: 28px; z-index: 9999;
      background: var(--gray-900); color: white; padding: 14px 20px 14px 16px;
      border-radius: 10px; font-size: 0.875rem; font-weight: 500; display: flex;
      align-items: center; gap: 10px; box-shadow: var(--shadow-lg);
      transform: translateY(80px); opacity: 0; transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
      max-width: 340px;
    }
    #toast.show { transform: translateY(0); opacity: 1; }
    #toast.success i { color: #4ade80; }
    #toast.error   i { color: #f87171; }

    /* ── PRICE PREVIEW ── */
    .price-preview {
      display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 8px;
    }
    .preview-badge {
      background: var(--green-lite); border: 1px solid #c3e6c8; border-radius: 10px;
      padding: 14px 16px; text-align: center;
    }
    .preview-badge .val { font-size: 1.5rem; font-weight: 800; color: var(--green); display: block; line-height: 1; margin-bottom: 4px; }
    .preview-badge .lbl { font-size: 0.72rem; color: var(--gray-500); font-weight: 600; }

    /* ── ARTIKEL TABELLE ── */
    .articles-table { width: 100%; border-collapse: collapse; }
    .articles-table th {
      background: var(--gray-50); padding: 10px 14px; text-align: left;
      font-size: 0.75rem; font-weight: 700; color: var(--gray-500); text-transform: uppercase;
      letter-spacing: 0.05em; border-bottom: 1px solid var(--gray-200);
    }
    .articles-table td {
      padding: 13px 14px; border-bottom: 1px solid var(--gray-100);
      font-size: 0.875rem; color: var(--gray-700); vertical-align: middle;
    }
    .articles-table tr:last-child td { border-bottom: none; }
    .articles-table tr:hover td { background: var(--gray-50); }
    .article-title-cell { font-weight: 600; color: var(--gray-900); max-width: 280px; }
    .article-title-cell small { display: block; font-size: 0.72rem; color: var(--gray-400); font-weight: 400; margin-top: 2px; }
    .cat-badge {
      display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 0.7rem; font-weight: 700;
      background: var(--green-pale); color: var(--green);
    }
    .cat-badge.cat-aktuell  { background: #fef3c7; color: #92400e; }
    .cat-badge.cat-nach     { background: #dcfce7; color: #166534; }
    .cat-badge.cat-tipps    { background: #ede9fe; color: #5b21b6; }
    .cat-badge.cat-produkt  { background: #dbeafe; color: #1e40af; }
    .status-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; }
    .status-dot.published { background: #22c55e; }
    .status-dot.draft     { background: var(--gray-400); }
    .table-actions { display: flex; gap: 6px; align-items: center; }

    /* ── ARTIKEL-EDITOR ── */
    .editor-grid { display: grid; grid-template-columns: 1fr 300px; gap: 24px; align-items: start; }
    .editor-main { display: flex; flex-direction: column; gap: 18px; }
    .editor-side { display: flex; flex-direction: column; gap: 16px; }
    .side-card { background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: var(--radius); padding: 18px; }
    .side-card h3 { font-size: 0.82rem; font-weight: 700; color: var(--gray-600); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 14px; display: flex; align-items: center; gap: 6px; }
    .side-card h3 i { color: var(--green); }

    #contentEditor {
      width: 100%; min-height: 360px; padding: 16px; border: 1.5px solid var(--gray-300); border-radius: 8px;
      font-family: 'Inter', sans-serif; font-size: 0.9rem; line-height: 1.7; outline: none;
      transition: border-color .2s; resize: vertical;
    }
    #contentEditor:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,107,40,0.1); }
    .editor-toolbar {
      display: flex; gap: 6px; flex-wrap: wrap; padding: 10px 14px;
      background: var(--gray-50); border: 1.5px solid var(--gray-300); border-bottom: none;
      border-radius: 8px 8px 0 0;
    }
    .editor-toolbar button {
      padding: 5px 10px; border: 1px solid var(--gray-300); background: white; border-radius: 5px;
      font-size: 0.78rem; cursor: pointer; font-family: inherit; transition: all .15s; color: var(--gray-700);
    }
    .editor-toolbar button:hover { background: var(--green-pale); border-color: var(--green); color: var(--green); }
    #contentEditor { border-radius: 0 0 8px 8px; }

    /* ── BILD-UPLOAD ── */
    .img-drop-zone {
      border: 2px dashed var(--gray-300); border-radius: 10px;
      padding: 24px 16px; text-align: center; cursor: pointer;
      transition: all .2s; background: var(--gray-50);
      position: relative;
    }
    .img-drop-zone:hover, .img-drop-zone.drag-over {
      border-color: var(--green); background: var(--green-lite);
    }
    .img-drop-zone.has-image {
      border-color: var(--green); background: var(--green-lite);
      border-style: solid;
    }
    .img-drop-zone i { font-size: 1.8rem; color: var(--gray-300); margin-bottom: 8px; display: block; }
    .img-drop-zone.drag-over i { color: var(--green); }
    .img-drop-zone.has-image i { color: var(--green); }
    .img-drop-zone p  { font-size: 0.8rem; color: var(--gray-400); line-height: 1.5; }
    .img-drop-zone strong { color: var(--green); }
    .img-drop-zone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
    .cover-img-preview {
      position: relative; border-radius: 10px; overflow: hidden;
      margin-top: 10px; display: none;
    }
    .cover-img-preview img { width: 100%; max-height: 160px; object-fit: cover; display: block; border-radius: 8px; box-shadow: var(--shadow-md); }
    .cover-img-actions {
      display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap;
    }
    .img-tab-bar { display: flex; gap: 0; margin-bottom: 12px; border: 1px solid var(--gray-200); border-radius: 8px; overflow: hidden; }
    .img-tab {
      flex: 1; padding: 8px 10px; background: white; border: none; font-family: inherit;
      font-size: 0.78rem; font-weight: 600; color: var(--gray-500); cursor: pointer; transition: all .15s;
      border-right: 1px solid var(--gray-200);
    }
    .img-tab:last-child { border-right: none; }
    .img-tab.active { background: var(--green); color: white; }
    .img-tab-panel { display: none; }
    .img-tab-panel.active { display: block; }

    /* Bild-Bibliothek */
    .img-library { display: grid; grid-template-columns: repeat(3,1fr); gap: 6px; max-height: 220px; overflow-y: auto; margin-top: 8px; }
    .img-lib-item {
      aspect-ratio: 1; border-radius: 6px; overflow: hidden; cursor: pointer;
      border: 2px solid transparent; transition: border-color .15s; position: relative;
    }
    .img-lib-item:hover { border-color: var(--green); }
    .img-lib-item img { width: 100%; height: 100%; object-fit: cover; }
    .img-lib-item .lib-del {
      position: absolute; top: 3px; right: 3px; background: rgba(0,0,0,0.6); color: white;
      border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 0.65rem;
      cursor: pointer; display: none; align-items: center; justify-content: center;
    }
    .img-lib-item:hover .lib-del { display: flex; }
    .img-lib-empty { grid-column: 1/-1; text-align: center; padding: 20px; color: var(--gray-400); font-size: 0.78rem; }

    /* Inline-Bild-Dialog */
    #imgDialog {
      display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5);
      z-index: 3000; align-items: center; justify-content: center;
    }
    #imgDialog.open { display: flex; }
    .img-dialog-box {
      background: white; border-radius: 16px; padding: 28px 24px; width: 90%; max-width: 480px;
      box-shadow: 0 24px 60px rgba(0,0,0,0.25);
    }
    .img-dialog-box h3 { font-size: 1rem; font-weight: 700; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
    .img-dialog-box h3 i { color: var(--green); }
    .img-dialog-tabs { display: flex; gap: 0; margin-bottom: 16px; border: 1px solid var(--gray-200); border-radius: 8px; overflow: hidden; }
    .img-dialog-tab {
      flex: 1; padding: 8px; background: white; border: none; font-family: inherit;
      font-size: 0.82rem; font-weight: 600; color: var(--gray-500); cursor: pointer;
      border-right: 1px solid var(--gray-200); transition: all .15s;
    }
    .img-dialog-tab:last-child { border-right: none; }
    .img-dialog-tab.active { background: var(--green); color: white; }
    .img-dialog-panel { display: none; }
    .img-dialog-panel.active { display: block; }
    .img-size-row { display: flex; gap: 8px; align-items: center; margin-top: 12px; flex-wrap: wrap; }
    .img-size-row label { font-size: 0.75rem; font-weight: 600; color: var(--gray-600); }
    .img-size-row select { padding: 5px 8px; border: 1px solid var(--gray-300); border-radius: 6px; font-size: 0.82rem; font-family: inherit; }
    .img-upload-area {
      border: 2px dashed var(--gray-300); border-radius: 8px; padding: 20px;
      text-align: center; cursor: pointer; transition: all .2s; position: relative;
    }
    .img-upload-area:hover { border-color: var(--green); background: var(--green-lite); }
    .img-upload-area input { position: absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
    .img-upload-area i { font-size: 1.4rem; color: var(--gray-300); margin-bottom: 6px; display: block; }
    .img-upload-area p { font-size: 0.78rem; color: var(--gray-400); }
    #inlineImgPreview { margin-top: 10px; display: none; }
    #inlineImgPreview img { width: 100%; max-height: 140px; object-fit: cover; border-radius: 6px; }
    .img-dialog-btns { display: flex; gap: 10px; margin-top: 18px; justify-content: flex-end; }
    .img-lib-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 6px; max-height: 200px; overflow-y: auto; }
    .img-lib-grid-item { aspect-ratio: 1; border-radius: 6px; overflow: hidden; cursor: pointer; border: 2px solid transparent; transition: border-color .15s; }
    .img-lib-grid-item:hover { border-color: var(--green); }
    .img-lib-grid-item img { width: 100%; height: 100%; object-fit: cover; }
    .img-lib-grid-empty { grid-column: 1/-1; text-align: center; padding: 16px; color: var(--gray-400); font-size: 0.78rem; }

    /* Tag-Input */
    .tag-input-wrap { display: flex; flex-wrap: wrap; gap: 6px; padding: 8px 10px; border: 1.5px solid var(--gray-300); border-radius: 8px; cursor: text; min-height: 44px; align-items: center; }
    .tag-input-wrap:focus-within { border-color: var(--green); box-shadow: 0 0 0 3px rgba(29,107,40,0.1); }
    .tag-chip { display: inline-flex; align-items: center; gap: 4px; background: var(--green-pale); border: 1px solid #c3e6c8; color: var(--green); padding: 2px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 600; }
    .tag-chip button { background: none; border: none; cursor: pointer; color: var(--green); font-size: 0.8rem; padding: 0; line-height: 1; }
    #tagInput { border: none; outline: none; font-family: inherit; font-size: 0.82rem; min-width: 80px; flex: 1; }

    /* Sections */
    .section-panel { display: none; }
    .section-panel.active { display: block; }

    /* Loading */
    .loading-row { text-align: center; padding: 48px; color: var(--gray-400); }
    .loading-row i { font-size: 1.5rem; margin-bottom: 12px; display: block; }

    /* Empty state */
    .empty-state { text-align: center; padding: 56px 20px; }
    .empty-state i { font-size: 2.5rem; color: var(--gray-300); margin-bottom: 16px; display: block; }
    .empty-state h3 { font-size: 1rem; color: var(--gray-500); margin-bottom: 8px; }
    .empty-state p { font-size: 0.83rem; color: var(--gray-400); margin-bottom: 20px; }

    /* Confirm dialog */
    #confirmModal {
      display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45);
      z-index: 2000; align-items: center; justify-content: center;
    }
    #confirmModal.open { display: flex; }
    .confirm-box {
      background: white; border-radius: 14px; padding: 32px 28px; max-width: 360px; width: 90%;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2); text-align: center;
    }
    .confirm-box i { font-size: 2rem; color: var(--red); margin-bottom: 14px; display: block; }
    .confirm-box h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: 8px; }
    .confirm-box p { font-size: 0.85rem; color: var(--gray-500); margin-bottom: 24px; }
    .confirm-btns { display: flex; gap: 10px; justify-content: center; }

    @media (max-width: 900px) {
      .admin-sidebar { display: none; }
      .admin-main { margin-left: 0; padding: 20px 16px; }
      .price-form-grid, .price-preview { grid-template-columns: 1fr 1fr; }
      .editor-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 600px) {
      .price-form-grid, .price-preview { grid-template-columns: 1fr; }
    }

    /* ── HILFE ── */
    .hilfe-quick-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 28px; }
    .hilfe-quick-card .card-body h3 { font-size: 0.95rem; font-weight: 700; margin-bottom: 6px; }
    .hilfe-quick-card .card-body p  { font-size: 0.82rem; color: var(--gray-500); }
    .hilfe-quick-icon { font-size: 2rem; margin-bottom: 8px; }

    .hilfe-badge {
      font-size: 0.75rem; padding: 4px 10px; border-radius: 999px; font-weight: 600;
    }
    .hilfe-badge-green  { background: #dcfce7; color: #166534; }
    .hilfe-badge-blue   { background: #dbeafe; color: #1e40af; }
    .hilfe-badge-purple { background: #ede9fe; color: #5b21b6; }

    .hilfe-steps {
      padding-left: 22px; display: flex; flex-direction: column; gap: 16px; margin-bottom: 16px;
    }
    .hilfe-steps li { font-size: 0.9rem; line-height: 1.5; }
    .hilfe-steps li strong { display: block; margin-bottom: 3px; }
    .hilfe-steps li span  { color: var(--gray-500); font-size: 0.82rem; }

    .hilfe-tipp {
      background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;
      padding: 14px 16px; font-size: 0.82rem; color: #166534; display: flex; gap: 8px; align-items: flex-start;
    }
    .hilfe-tipp i { margin-top: 2px; flex-shrink: 0; }

    .hilfe-toolbar-legend {
      display: flex; flex-wrap: wrap; gap: 7px; margin-top: 8px;
    }
    .hilfe-toolbar-legend span {
      background: var(--gray-100); padding: 4px 10px; border-radius: 6px;
      font-size: 0.75rem; font-weight: 600; color: var(--gray-700);
    }

    .hilfe-status-chip {
      display: inline-block; padding: 2px 10px; border-radius: 5px; font-weight: 600; font-size: 0.8rem;
    }
    .hilfe-chip-amber { background: #fef3c7; color: #92400e; }
    .hilfe-chip-green { background: #dcfce7; color: #166534; }

    .hilfe-icon-chip {
      display: inline-block; padding: 2px 8px; border-radius: 5px; font-weight: 600; font-size: 0.8rem;
    }
    .hilfe-chip-green-soft { background: var(--green-pale); color: var(--green); }
    .hilfe-chip-red-soft   { background: #fef2f2; color: var(--red); }

    .hilfe-faq { display: flex; flex-direction: column; gap: 10px; }
    .faq-item {
      border: 1px solid var(--gray-200); border-radius: 8px; overflow: hidden;
    }
    .faq-item summary {
      padding: 14px 16px; cursor: pointer; font-weight: 600; font-size: 0.9rem;
      background: var(--gray-50); list-style: none; display: flex;
      justify-content: space-between; align-items: center; user-select: none;
    }
    .faq-item summary::-webkit-details-marker { display: none; }
    .faq-item summary i { color: var(--gray-400); font-size: 0.8rem; transition: transform .2s; flex-shrink: 0; }
    .faq-item[open] summary i { transform: rotate(180deg); }
    .faq-item[open] summary { background: var(--green-lite); color: var(--green); }
    .faq-item[open] summary i { color: var(--green); }
    .faq-body {
      padding: 14px 16px; font-size: 0.875rem; color: var(--gray-600); line-height: 1.65;
      border-top: 1px solid var(--gray-200);
    }
    .faq-body code { background: var(--gray-100); padding: 2px 8px; border-radius: 4px; font-size: 0.88em; }

    @media (max-width: 900px) {
      .hilfe-quick-grid { grid-template-columns: 1fr; }
    }

    /* ── DEPLOYMENT ── */
    .deploy-steps { display: flex; flex-direction: column; gap: 0; }
    .deploy-step {
      display: flex; gap: 16px; align-items: flex-start;
      background: var(--gray-50); border: 1px solid var(--gray-200);
      border-radius: 10px; padding: 18px; position: relative;
    }
    .deploy-step-num {
      width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, var(--green), var(--green-dark));
      color: white; font-weight: 800; font-size: 1rem;
      display: flex; align-items: center; justify-content: center;
    }
    .deploy-step-body h3 { font-size: 0.95rem; font-weight: 700; margin-bottom: 6px; color: var(--gray-800); }
    .deploy-step-body p  { font-size: 0.85rem; color: var(--gray-600); line-height: 1.6; }
    .deploy-step-arrow {
      text-align: center; padding: 6px 0; color: var(--gray-300); font-size: 1rem;
    }
    .deploy-step-tip {
      background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 7px;
      padding: 10px 14px; margin-top: 10px; font-size: 0.8rem; color: #166534;
      display: flex; gap: 7px; align-items: flex-start;
    }
    .deploy-step-tip i { flex-shrink: 0; margin-top: 2px; }

    .deploy-file-chip {
      background: var(--gray-800); color: #a3e635; padding: 3px 10px;
      border-radius: 5px; font-size: 0.75rem; font-family: 'Courier New', monospace;
    }

    .deploy-filetree {
      background: var(--gray-900); color: #d1fae5; padding: 18px 20px;
      border-radius: 10px; font-size: 0.8rem; line-height: 1.8;
      font-family: 'Courier New', monospace; overflow-x: auto;
      white-space: pre; margin: 0;
    }

    .deploy-checklist {
      display: flex; flex-direction: column; gap: 10px;
    }
    .deploy-check-item {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 14px; border: 1.5px solid var(--gray-200);
      border-radius: 8px; cursor: pointer; font-size: 0.875rem;
      color: var(--gray-700); transition: all .15s; background: white;
    }
    .deploy-check-item:hover { background: var(--gray-50); border-color: var(--gray-300); }
    .deploy-check-item input[type=checkbox] {
      width: 18px; height: 18px; accent-color: var(--green); flex-shrink: 0; cursor: pointer;
    }
    .deploy-check-item:has(input:checked) {
      background: #f0fdf4; border-color: #86efac; color: var(--gray-500);
      text-decoration: line-through;
    }

    @media (max-width: 700px) {
      .deploy-step { flex-direction: column; }
    }

    /* ── E-MAIL SETUP ── */
    .email-steps { display: flex; flex-direction: column; gap: 12px; margin-bottom: 4px; }
    .email-step {
      display: flex; gap: 14px; align-items: flex-start;
      padding: 14px; background: var(--gray-50); border: 1px solid var(--gray-200); border-radius: 9px;
    }
    .email-step-num {
      width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
      background: var(--amber); color: white; font-weight: 800; font-size: 0.85rem;
      display: flex; align-items: center; justify-content: center;
    }
    .email-step strong { font-size: 0.88rem; display: block; margin-bottom: 4px; }
    .email-link {
      display: inline-flex; align-items: center; gap: 6px;
      color: var(--green); font-weight: 600; font-size: 0.85rem;
      text-decoration: none; background: var(--green-pale); padding: 5px 12px;
      border-radius: 6px; border: 1px solid #c3e6c8; margin-top: 4px;
      transition: background .15s;
    }
    .email-link:hover { background: #c3e6c8; }
    .email-code { background: var(--gray-800); color: #a3e635; padding: 3px 10px; border-radius: 5px; font-size: 0.78rem; font-family: monospace; }
    .email-code-inline { background: var(--gray-100); padding: 1px 6px; border-radius: 4px; font-size: 0.82em; font-family: monospace; }
  </style>
</head>
<body>
<div id="adminApp">

  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-brand-icon"><i class="fas fa-leaf"></i></div>
      <div class="sidebar-brand-text">Steirer Pellets<small>Admin-Bereich</small></div>
    </div>
    <nav class="sidebar-nav">
      <button class="nav-item active" data-panel="preise">
        <i class="fas fa-tag"></i> Preise
      </button>
      <button class="nav-item" data-panel="rabattcodes">
        <i class="fas fa-percent"></i> Rabattcodes
      </button>
      <button class="nav-item" data-panel="artikel">
        <i class="fas fa-newspaper"></i> News-Artikel
      </button>
      <button class="nav-item" data-panel="editor">
        <i class="fas fa-edit"></i> Artikel schreiben
      </button>
      <button class="nav-item" data-panel="hilfe">
        <i class="fas fa-question-circle"></i> Hilfe &amp; Anleitung
      </button>
      <button class="nav-item" data-panel="deployment">
        <i class="fas fa-rocket"></i> Website veröffentlichen
      </button>
      <button class="nav-item" data-panel="email-setup" style="background:rgba(232,146,42,0.15);color:#fbbf24">
        <i class="fas fa-envelope" style="color:#fbbf24"></i> E-Mail einrichten
      </button>
    </nav>
    <div class="sidebar-footer">
      <button class="logout-btn" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Abmelden</button>
    </div>
  </aside>

  <!-- Main -->
  <main class="admin-main">

    <!-- ══ PREISE ══ -->
    <div class="section-panel active" id="panel-preise">
      <div class="page-header">
        <div>
          <h1><i class="fas fa-tag" style="color:var(--green);margin-right:8px"></i>Preise verwalten</h1>
          <p>Änderungen werden sofort auf der Website übernommen.</p>
        </div>
      </div>

      <?php
        $currentPreise = sp_read_json('preise.json', [
          'preis_gross' => 398, 'preis_klein' => 418, 'abschlauch' => 58, 'updated_at_label' => ''
        ]);
        $pg = htmlspecialchars($currentPreise['preis_gross'] ?? 398);
        $pk = htmlspecialchars($currentPreise['preis_klein'] ?? 418);
        $pa = htmlspecialchars($currentPreise['abschlauch'] ?? 58);
        $pu = htmlspecialchars($currentPreise['updated_at_label'] ?? '');
      ?>
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <h2><i class="fas fa-eye"></i> Aktuelle Preise (Vorschau)</h2>
        </div>
        <div class="card-body">
          <div class="price-preview">
            <div class="preview-badge">
              <span class="val" id="prev-gross"><?= $pg ?></span>
              <span class="lbl">€/t · Ab 4 Tonnen</span>
            </div>
            <div class="preview-badge">
              <span class="val" id="prev-klein"><?= $pk ?></span>
              <span class="lbl">€/t · Unter 4 Tonnen</span>
            </div>
            <div class="preview-badge">
              <span class="val" id="prev-absch"><?= $pa ?></span>
              <span class="lbl">€ Abschlauchgebühr</span>
            </div>
          </div>
          <p style="font-size:0.78rem;color:var(--gray-400);text-align:right" id="preisUpdatedAt"><?= $pu ? "Zuletzt geändert: $pu" : '' ?></p>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-pencil-alt"></i> Preise bearbeiten</h2>
        </div>
        <div class="card-body">
          <div class="price-form-grid">
            <div class="form-group">
              <label>Preis ab 4 Tonnen (€/t) *</label>
              <div class="input-prefix">
                <span>€</span>
                <input type="number" id="inp-gross" placeholder="398" min="100" max="999" step="1" value="<?= $pg ?>" />
              </div>
              <span class="hint">Großlieferung · empfohlener Angebotspreis</span>
            </div>
            <div class="form-group">
              <label>Preis unter 4 Tonnen (€/t) *</label>
              <div class="input-prefix">
                <span>€</span>
                <input type="number" id="inp-klein" placeholder="418" min="100" max="999" step="1" value="<?= $pk ?>" />
              </div>
              <span class="hint">Kleinlieferung · höherer Preis gerechtfertigt</span>
            </div>
            <div class="form-group">
              <label>Abschlauchgebühr (€, einmalig) *</label>
              <div class="input-prefix">
                <span>€</span>
                <input type="number" id="inp-absch" placeholder="58" min="0" max="500" step="1" value="<?= $pa ?>" />
              </div>
              <span class="hint">Einmalige Gebühr pro Lieferung</span>
            </div>
          </div>
          <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <button class="btn btn-primary" id="savePreiseBtn">
              <i class="fas fa-save"></i> Preise speichern
            </button>
            <span id="preisSaveHint" style="font-size:0.8rem;color:var(--gray-400)"></span>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ RABATTCODES ══ -->
    <div class="section-panel" id="panel-rabattcodes">
      <div class="page-header">
        <div>
          <h1><i class="fas fa-percent" style="color:var(--green);margin-right:8px"></i>Rabattcodes verwalten</h1>
          <p>Erstellen und verwalten Sie Rabattcodes für das Bestellformular.</p>
        </div>
      </div>

      <!-- Neuen Code erstellen -->
      <div class="card" style="margin-bottom:24px">
        <h3 style="margin:0 0 16px"><i class="fas fa-plus-circle"></i> Neuen Rabattcode erstellen</h3>
        <form id="discountForm" autocomplete="off">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
            <div>
              <label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.85rem">Code *</label>
              <input type="text" id="dc_code" placeholder="z.B. SOMMER25" required style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem;text-transform:uppercase" />
            </div>
            <div>
              <label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.85rem">Bezeichnung</label>
              <input type="text" id="dc_label" placeholder="z.B. Sommeraktion 2026" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem" />
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;margin-bottom:12px">
            <div>
              <label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.85rem">Rabatt in %</label>
              <input type="number" id="dc_percent" placeholder="z.B. 5" min="0" max="100" step="0.5" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem" />
            </div>
            <div>
              <label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.85rem">oder Fixbetrag €</label>
              <input type="number" id="dc_fixed" placeholder="z.B. 50" min="0" step="1" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem" />
            </div>
            <div>
              <label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.85rem">Max. Einlösungen</label>
              <input type="number" id="dc_max" placeholder="0 = unbegrenzt" min="0" step="1" value="0" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem" />
            </div>
            <div>
              <label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.85rem">Min. Tonnen</label>
              <input type="number" id="dc_min_menge" placeholder="0 = keine" min="0" step="1" value="0" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem" />
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px">
            <div>
              <label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.85rem">Gültig ab</label>
              <input type="date" id="dc_from" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem" />
            </div>
            <div>
              <label style="display:block;font-weight:600;margin-bottom:4px;font-size:0.85rem">Gültig bis</label>
              <input type="date" id="dc_to" style="width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:0.95rem" />
            </div>
            <div style="display:flex;align-items:end">
              <button type="submit" class="btn" style="width:100%;padding:10px 16px;background:var(--green);color:#fff;border:none;border-radius:8px;font-size:0.95rem;font-weight:600;cursor:pointer">
                <i class="fas fa-plus"></i> Code erstellen
              </button>
            </div>
          </div>
        </form>
        <div id="dcFormMsg" style="margin-top:8px;font-size:0.9rem"></div>
      </div>

      <!-- Liste bestehender Codes -->
      <div class="card">
        <h3 style="margin:0 0 16px"><i class="fas fa-list"></i> Bestehende Rabattcodes</h3>
        <div id="discountList" style="font-size:0.9rem">Lade…</div>
      </div>
    </div>

    <!-- ══ ARTIKEL-LISTE ══ -->
    <div class="section-panel" id="panel-artikel">
      <div class="page-header">
        <div>
          <h1><i class="fas fa-newspaper" style="color:var(--green);margin-right:8px"></i>News-Artikel</h1>
          <p>Alle Artikel verwalten – veröffentlichen, bearbeiten oder löschen.</p>
        </div>
        <button class="btn btn-primary" id="newArtikelBtn">
          <i class="fas fa-plus"></i> Neuer Artikel
        </button>
      </div>

      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-list"></i> Alle Artikel (<span id="artikelCount">0</span>)</h2>
          <div style="display:flex;gap:8px">
            <input type="text" id="artikelSearch" placeholder="Suchen…" style="padding:7px 12px;border:1.5px solid var(--gray-300);border-radius:7px;font-size:0.83rem;outline:none;font-family:inherit;width:200px" />
            <select id="artikelCatFilter" style="padding:7px 10px;border:1.5px solid var(--gray-300);border-radius:7px;font-size:0.83rem;font-family:inherit;outline:none">
              <option value="">Alle Kategorien</option>
              <option>Ratgeber</option>
              <option>Nachhaltigkeit</option>
              <option>Tipps &amp; Tricks</option>
              <option>Aktuell</option>
              <option>Produkt</option>
              <option>Video</option>
            </select>
          </div>
        </div>
        <div id="artikelTableWrap">
          <div class="loading-row"><i class="fas fa-circle-notch fa-spin"></i>Artikel werden geladen…</div>
        </div>
      </div>
    </div>

    <!-- ══ EDITOR ══ -->
    <div class="section-panel" id="panel-editor">
      <div class="page-header">
        <div>
          <h1 id="editorTitle"><i class="fas fa-edit" style="color:var(--green);margin-right:8px"></i>Neuer Artikel</h1>
          <p id="editorSub">Schreiben Sie einen neuen News-Beitrag.</p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <button class="btn btn-outline" id="backToListBtn">
            <i class="fas fa-arrow-left"></i> Zur Liste
          </button>
          <button class="btn btn-amber" id="saveDraftBtn">
            <i class="fas fa-file-alt"></i> Als Entwurf speichern
          </button>
          <button class="btn btn-primary" id="publishBtn">
            <i class="fas fa-paper-plane"></i> Veröffentlichen
          </button>
        </div>
      </div>

      <div class="editor-grid">
        <div class="editor-main">
          <!-- Titel -->
          <div class="card">
            <div class="card-body" style="padding:16px 20px">
              <div class="form-group">
                <label>Artikel-Titel *</label>
                <input type="text" id="edTitel" placeholder="z.B. Wie nachhaltig sind Holzpellets wirklich?" style="font-size:1.05rem;font-weight:600" />
              </div>
              <div class="form-group" style="margin-top:12px">
                <label>Teaser / Kurzbeschreibung *</label>
                <textarea id="edTeaser" rows="2" placeholder="Kurze Zusammenfassung für die Artikel-Übersicht (max. 160 Zeichen)…"></textarea>
              </div>
            </div>
          </div>

          <!-- Content Editor -->
          <div class="card">
            <div class="card-header">
              <h2><i class="fas fa-align-left"></i> Artikelinhalt</h2>
            </div>
            <div class="card-body" style="padding:16px 20px">
              <div class="editor-toolbar">
                <button onclick="fmt('bold')"><b>B</b></button>
                <button onclick="fmt('italic')"><i>I</i></button>
                <button onclick="fmtH('h2')">H2</button>
                <button onclick="fmtH('h3')">H3</button>
                <button onclick="fmt('insertUnorderedList')">• Liste</button>
                <button onclick="fmt('insertOrderedList')">1. Liste</button>
                <button onclick="fmtLink()"><i class="fas fa-link"></i> Link</button>
                <button onclick="fmtHR()">― Trennlinie</button>
                <button onclick="openImgDialog()" style="color:var(--green);font-weight:700"><i class="fas fa-image"></i> Bild</button>
                <button onclick="fmtEmbed()" style="color:#E1306C;font-weight:700"><i class="fab fa-instagram"></i> Embed</button>
                <button onclick="fmtPreisTag()" style="color:var(--amber);font-weight:700"><i class="fas fa-euro-sign"></i> Preis</button>
                <button onclick="document.getElementById('contentEditor').innerHTML = ''" style="color:var(--red)"><i class="fas fa-trash"></i> Leeren</button>
              </div>
              <div id="contentEditor" contenteditable="true"></div>
              <p style="font-size:0.72rem;color:var(--gray-400);margin-top:8px">
                <i class="fas fa-info-circle"></i> Tipp: H2-Überschriften werden automatisch ins Inhaltsverzeichnis aufgenommen.
              </p>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="editor-side">
          <div class="side-card">
            <h3><i class="fas fa-cog"></i> Einstellungen</h3>
            <div class="form-group" style="margin-bottom:12px">
              <label>Kategorie *</label>
              <select id="edKategorie">
                <option value="">Bitte wählen…</option>
                <option>Ratgeber</option>
                <option>Nachhaltigkeit</option>
                <option>Tipps &amp; Tricks</option>
                <option>Aktuell</option>
                <option>Produkt</option>
                <option>Video</option>
              </select>
            </div>
            <div class="form-group" id="videoUrlGroup" style="margin-bottom:12px;display:none">
              <label><i class="fas fa-video" style="color:var(--green);margin-right:4px"></i> Video-URL</label>
              <input type="url" id="edVideoUrl" placeholder="https://www.youtube.com/watch?v=... oder Instagram/TikTok-URL" />
              <p style="font-size:0.72rem;color:var(--gray-400);margin-top:4px">YouTube, Instagram oder TikTok Video-Link einfügen. Wird direkt in der Magazin-Übersicht abgespielt.</p>
            </div>
            <div class="form-group" style="margin-bottom:12px">
              <label>Autor</label>
              <input type="text" id="edAutor" value="Steirer Pellets Team" />
            </div>
            <div class="form-group" style="margin-bottom:12px">
              <label>Lesezeit (Minuten)</label>
              <input type="number" id="edLesezeit" value="5" min="1" max="60" />
            </div>
            <div class="form-group">
              <label>
                <input type="checkbox" id="edFeatured" style="margin-right:6px;accent-color:var(--green)" />
                Als Featured-Artikel markieren
              </label>
            </div>
          </div>

          <div class="side-card">
            <h3><i class="fas fa-tags"></i> Tags</h3>
            <div class="tag-input-wrap" id="tagWrap">
              <input type="text" id="tagInput" placeholder="Tag eingeben + Enter…" />
            </div>
            <p style="font-size:0.72rem;color:var(--gray-400);margin-top:6px">Enter drücken um Tag hinzuzufügen</p>
          </div>

          <div class="side-card">
            <h3><i class="fas fa-image"></i> Aufmacherbild</h3>

            <!-- Tabs: Upload / URL / Bibliothek -->
            <div class="img-tab-bar">
              <button class="img-tab active" data-imgtab="upload"><i class="fas fa-upload"></i> Upload</button>
              <button class="img-tab" data-imgtab="url"><i class="fas fa-link"></i> URL</button>
              <button class="img-tab" data-imgtab="lib"><i class="fas fa-photo-video"></i> Bibliothek</button>
            </div>

            <!-- Upload Tab -->
            <div class="img-tab-panel active" id="imgtab-upload">
              <div class="img-drop-zone" id="coverDropZone">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Bild hier hineinziehen oder<br/><strong>klicken zum Auswählen</strong></p>
                <p style="font-size:0.7rem;margin-top:4px">JPG, PNG, WebP – max. 5 MB</p>
                <input type="file" id="coverFileInput" accept="image/*" />
              </div>
            </div>

            <!-- URL Tab -->
            <div class="img-tab-panel" id="imgtab-url">
              <div class="form-group">
                <label>Bild-URL</label>
                <input type="text" id="edImage" placeholder="../images/mein-bild.jpg" />
                <span class="hint" style="margin-top:4px">Relative oder absolute URL</span>
              </div>
              <button class="btn btn-outline btn-sm" style="margin-top:8px;width:100%" onclick="loadCoverFromUrl()">
                <i class="fas fa-check"></i> URL übernehmen
              </button>
            </div>

            <!-- Bibliothek Tab -->
            <div class="img-tab-panel" id="imgtab-lib">
              <p style="font-size:0.75rem;color:var(--gray-400);margin-bottom:8px">Zuletzt verwendete Bilder – klicken zum Einfügen:</p>
              <div class="img-library" id="coverLibrary"></div>
            </div>

            <!-- Vorschau (gemeinsam für alle Tabs) -->
            <div class="cover-img-preview" id="coverPreviewWrap">
              <img id="coverPreviewImg" src="" alt="Vorschau" />
              <div class="cover-img-actions">
                <button class="btn btn-sm btn-outline" onclick="insertCoverIntoContent()">
                  <i class="fas fa-plus"></i> Auch im Text einfügen
                </button>
                <button class="btn btn-sm btn-danger" onclick="removeCoverImage()">
                  <i class="fas fa-trash"></i> Entfernen
                </button>
              </div>
            </div>

            <!-- Verstecktes Feld für die gespeicherte Bild-URL/DataURL -->
            <input type="hidden" id="coverImageData" />
          </div>

          <div class="side-card">
            <h3><i class="fas fa-info-circle"></i> Artikel-Info</h3>
            <div style="font-size:0.78rem;color:var(--gray-500);display:flex;flex-direction:column;gap:6px">
              <div>Slug: <code id="edSlugPreview" style="background:var(--gray-100);padding:2px 6px;border-radius:4px;font-size:0.72rem">–</code></div>
              <div>Status: <span id="edStatusLabel" style="font-weight:600;color:var(--gray-400)">Entwurf</span></div>
              <div id="edPublishedInfo" style="display:none">Veröffentlicht: <span id="edPublishedDate">–</span></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Hidden -->
      <input type="hidden" id="edArtikelId" value="" />
    </div>

    <!-- ══ HILFE ══ -->
    <div class="section-panel" id="panel-hilfe">
      <div class="page-header">
        <div>
          <h1><i class="fas fa-question-circle" style="color:var(--green);margin-right:8px"></i>Hilfe &amp; Anleitung</h1>
          <p>Alles was Sie wissen müssen, um die Website zu pflegen – ohne Programmierkenntnisse.</p>
        </div>
      </div>

      <!-- Schnellübersicht -->
      <div class="hilfe-quick-grid">
        <div class="card hilfe-quick-card" style="border-left:4px solid var(--green)">
          <div class="card-body" style="padding:20px">
            <div class="hilfe-quick-icon">💶</div>
            <h3>Preise ändern</h3>
            <p>Holzpellets-Preise und Abschlauchgebühr jederzeit anpassen.</p>
            <button class="btn btn-outline btn-sm" style="margin-top:12px" onclick="switchTo('preise')"><i class="fas fa-arrow-right"></i> Zu den Preisen</button>
          </div>
        </div>
        <div class="card hilfe-quick-card" style="border-left:4px solid #3b82f6">
          <div class="card-body" style="padding:20px">
            <div class="hilfe-quick-icon">📰</div>
            <h3>Artikel schreiben</h3>
            <p>Neue News-Beiträge erstellen – einfach wie in Word.</p>
            <button class="btn btn-outline btn-sm" style="margin-top:12px" onclick="startNewArtikel()"><i class="fas fa-arrow-right"></i> Neuer Artikel</button>
          </div>
        </div>
        <div class="card hilfe-quick-card" style="border-left:4px solid var(--amber)">
          <div class="card-body" style="padding:20px">
            <div class="hilfe-quick-icon">🌐</div>
            <h3>Website ansehen</h3>
            <p>Änderungen direkt auf der Live-Website überprüfen.</p>
            <a class="btn btn-outline btn-sm" style="margin-top:12px;text-decoration:none" href="../index.html" target="_blank"><i class="fas fa-external-link-alt"></i> Website öffnen</a>
          </div>
        </div>
      </div>

      <!-- Anleitung: Preise -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <h2><i class="fas fa-tag"></i> Anleitung: Preise ändern</h2>
          <span class="hilfe-badge hilfe-badge-green">⏱ ca. 1 Minute</span>
        </div>
        <div class="card-body">
          <ol class="hilfe-steps">
            <li>
              <strong>Klicken Sie links im Menü auf „Preise"</strong><br/>
              <span>Sie sehen sofort die aktuell gespeicherten Preise oben als Vorschau.</span>
            </li>
            <li>
              <strong>Tragen Sie die neuen Preise in die Felder ein</strong><br/>
              <span>„Preis ab 4 Tonnen" – das ist der Großmengenpreis (z.B. 398).<br/>
              „Preis unter 4 Tonnen" – muss etwas höher sein (z.B. 418).<br/>
              „Abschlauchgebühr" – die einmalige Liefergebühr (z.B. 58).</span>
            </li>
            <li>
              <strong>Klicken Sie auf den grünen Button „Preise speichern"</strong><br/>
              <span>✅ Fertig! Die Preise werden sofort auf der Website aktualisiert – Preistabelle, Rechner und Bestellformular.</span>
            </li>
          </ol>
          <div class="hilfe-tipp">
            <i class="fas fa-lightbulb"></i>
            <strong>Tipp:</strong> Der Preis ab 4 Tonnen <em>muss</em> niedriger sein als der Preis unter 4 Tonnen – sonst erscheint eine Fehlermeldung.
          </div>
        </div>
      </div>

      <!-- Anleitung: Artikel schreiben -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <h2><i class="fas fa-newspaper"></i> Anleitung: Neuen News-Artikel schreiben</h2>
          <span class="hilfe-badge hilfe-badge-blue">⏱ ca. 5–15 Minuten</span>
        </div>
        <div class="card-body">
          <ol class="hilfe-steps">
            <li>
              <strong>Klicken Sie links auf „Artikel schreiben" oder oben auf „Neuer Artikel"</strong><br/>
              <span>Der Editor öffnet sich – ähnlich wie Word oder eine E-Mail.</span>
            </li>
            <li>
              <strong>Titel eingeben</strong> <em>(Pflichtfeld)</em><br/>
              <span>z.B. „Holzpellets richtig lagern – 5 wichtige Tipps". Aus dem Titel wird automatisch der Link zur Seite erstellt.</span>
            </li>
            <li>
              <strong>Kurzbeschreibung (Teaser) eingeben</strong> <em>(Pflichtfeld)</em><br/>
              <span>1–2 Sätze, die den Artikel zusammenfassen. Wird in der News-Übersicht angezeigt.</span>
            </li>
            <li>
              <strong>Artikelinhalt schreiben</strong><br/>
              <span>Im großen Textfeld einfach losschreiben. Mit den Buttons in der Werkzeugleiste können Sie formatieren:</span>
              <div class="hilfe-toolbar-legend">
                <span><b>B</b> = Fett</span>
                <span><em>I</em> = Kursiv</span>
                <span>H2 = Große Überschrift</span>
                <span>H3 = Kleine Überschrift</span>
                <span>• Liste = Aufzählung</span>
                <span>🖼️ Bild = Foto einfügen</span>
                <span><i class="fab fa-instagram"></i> Embed = Social-Media-Beitrag einbetten</span>
                <span>€ Preis = Aktuellen Preis einfügen (immer aktuell)</span>
              </div>
            </li>
            <li>
              <strong>Aufmacherbild hinzufügen</strong> (rechte Spalte)<br/>
              <span>Klicken Sie auf die gestrichelte Fläche oder ziehen Sie ein Foto aus Ihrem Ordner hinein. Das Bild erscheint dann groß oben im Artikel.</span>
            </li>
            <li>
              <strong>Kategorie wählen</strong> (rechte Spalte, Pflichtfeld)<br/>
              <span>Wählen Sie eine passende Kategorie: <em>Ratgeber, Nachhaltigkeit, Tipps &amp; Tricks, Aktuell</em> oder <em>Produkt</em>.</span>
            </li>
            <li>
              <strong>Veröffentlichen oder als Entwurf speichern</strong><br/>
              <span>
                <span class="hilfe-status-chip hilfe-chip-amber">Als Entwurf speichern</span> → Artikel ist noch nicht sichtbar, später fertigstellen.<br/>
                <span class="hilfe-status-chip hilfe-chip-green" style="margin-top:5px;display:inline-block">Veröffentlichen</span> → Artikel erscheint sofort in den News.
              </span>
            </li>
          </ol>
        </div>
      </div>

      <!-- Anleitung: Bearbeiten/Löschen -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <h2><i class="fas fa-edit"></i> Anleitung: Artikel bearbeiten oder löschen</h2>
          <span class="hilfe-badge hilfe-badge-purple">⏱ ca. 2 Minuten</span>
        </div>
        <div class="card-body">
          <ol class="hilfe-steps">
            <li>
              <strong>Klicken Sie links auf „News-Artikel"</strong><br/>
              <span>Sie sehen eine Liste aller vorhandenen Artikel.</span>
            </li>
            <li>
              <strong>Bearbeiten: Klicken Sie auf das <span class="hilfe-icon-chip hilfe-chip-green-soft">✏️ Stift-Symbol</span></strong><br/>
              <span>Der Artikel öffnet sich im Editor. Änderungen vornehmen, dann „Veröffentlichen" klicken.</span>
            </li>
            <li>
              <strong>Löschen: Klicken Sie auf das <span class="hilfe-icon-chip hilfe-chip-red-soft">🗑️ Papierkorb-Symbol</span></strong><br/>
              <span>⚠️ Achtung: Gelöschte Artikel können nicht wiederhergestellt werden. Es erscheint eine Sicherheitsabfrage.</span>
            </li>
          </ol>
        </div>
      </div>

      <!-- FAQ -->
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-circle-question"></i> Häufige Fragen</h2>
        </div>
        <div class="card-body">
          <div class="hilfe-faq">

            <details class="faq-item">
              <summary>Wie oft kann ich die Preise ändern? <i class="fas fa-chevron-down"></i></summary>
              <div class="faq-body">
                So oft Sie möchten – es gibt keine Begrenzung. Die Änderung wird sofort auf der Website sichtbar. Kunden sehen immer den aktuellen Preis.
              </div>
            </details>

            <details class="faq-item">
              <summary>Wie viele Artikel kann ich schreiben? <i class="fas fa-chevron-down"></i></summary>
              <div class="faq-body">
                Unbegrenzt. Je mehr Artikel Sie schreiben, desto besser wird die Website in Google gefunden. <strong>Empfehlung:</strong> 1–2 Artikel pro Monat zu Themen rund um Holzpellets, Heizen, Nachhaltigkeit.
              </div>
            </details>

            <details class="faq-item">
              <summary>Was passiert wenn ich das Passwort vergesse? <i class="fas fa-chevron-down"></i></summary>
              <div class="faq-body">
                Der Login wird serverseitig verwaltet. Benutzername und Passwort können über die Server-Variablen <code>SP_ADMIN_USER</code> und <code>SP_ADMIN_PASS_HASH</code> neu gesetzt werden.
              </div>
            </details>

            <details class="faq-item">
              <summary>Kann ich Fotos direkt vom Handy hochladen? <i class="fas fa-chevron-down"></i></summary>
              <div class="faq-body">
                Ja! Öffnen Sie den Admin-Bereich auf Ihrem Handy im Browser, gehen Sie zu „Artikel schreiben" und klicken Sie auf die gestrichelte Fläche beim Aufmacherbild. Ihr Handy fragt dann, ob Sie ein Foto aus der Galerie auswählen oder direkt fotografieren möchten.
              </div>
            </details>

            <details class="faq-item">
              <summary>Wie groß sollten Bilder sein? <i class="fas fa-chevron-down"></i></summary>
              <div class="faq-body">
                <strong>Aufmacherbild:</strong> Ideal 1200 × 630 Pixel, Querformat, max. 5 MB.<br/>
                <strong>Bilder im Text:</strong> Ca. 800 × 500 Pixel reichen. Handy-Fotos sind oft zu groß – am besten vorher auf
                <a href="https://squoosh.app" target="_blank" style="color:var(--green)">squoosh.app</a> kostenlos verkleinern (kein Download nötig).
              </div>
            </details>

            <details class="faq-item">
              <summary>Was kann ich NICHT selbst ändern? <i class="fas fa-chevron-down"></i></summary>
              <div class="faq-body">
                Folgendes benötigt einen Webentwickler:
                <ul style="margin-top:8px;padding-left:18px;display:flex;flex-direction:column;gap:4px">
                  <li>Farben, Schriften oder das Layout der Website</li>
                  <li>Neue Abschnitte oder Seiten hinzufügen</li>
                  <li>Die Navigation (Menü) anpassen</li>
                  <li>Kontaktdaten (Adresse, Telefon, E-Mail) aktualisieren</li>
                  <li>Das Logo austauschen</li>
                  <li>Liefergebiete oder Kundenbewertungen ändern</li>
                </ul>
              </div>
            </details>

          </div>
        </div>
      </div>

    </div><!-- /panel-hilfe -->

    <!-- ══ DEPLOYMENT ══ -->
    <div class="section-panel" id="panel-deployment">
      <div class="page-header">
        <div>
          <h1><i class="fas fa-rocket" style="color:var(--green);margin-right:8px"></i>Website veröffentlichen</h1>
          <p>Schritt-für-Schritt Anleitung für das Hosting auf einem externen Server.</p>
        </div>
      </div>

      <!-- Status-Banner -->
      <div style="background:linear-gradient(135deg,#0f4d18,#1d6b28);border-radius:14px;padding:24px 28px;margin-bottom:28px;color:white;display:flex;gap:20px;align-items:center;flex-wrap:wrap">
        <div style="font-size:2.5rem">🌐</div>
        <div style="flex:1">
          <div style="font-size:1.1rem;font-weight:700;margin-bottom:4px">Externer Hosting-Betrieb</div>
          <div style="font-size:0.85rem;opacity:0.85;line-height:1.6">
            Diese Website wird auf Ihrem eigenen Server gehostet. Änderungen machen Sie weiterhin in Genspark –
            anschließend laden Sie nur die geänderten Dateien per FTP auf Ihren Server hoch.
          </div>
        </div>
        <div style="background:rgba(255,255,255,0.15);border-radius:10px;padding:14px 20px;text-align:center;min-width:140px">
          <div style="font-size:0.72rem;opacity:0.7;margin-bottom:4px">WORKFLOW</div>
          <div style="font-size:0.85rem;font-weight:600">Genspark → FTP → Server</div>
        </div>
      </div>

      <!-- Schritt-für-Schritt -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <h2><i class="fas fa-list-ol"></i> Der Update-Prozess in 3 Schritten</h2>
          <span class="hilfe-badge hilfe-badge-green">Für jeden Website-Update</span>
        </div>
        <div class="card-body">
          <div class="deploy-steps">

            <div class="deploy-step">
              <div class="deploy-step-num">1</div>
              <div class="deploy-step-body">
                <h3>Änderung in Genspark beauftragen</h3>
                <p>Schreiben Sie dem KI-Assistenten in Genspark, was geändert werden soll – z.B. „Ändere die Telefonnummer im Footer auf +43 3574 / 9999" oder „Füge einen neuen Abschnitt über unsere Lieferzeiten hinzu". Die KI setzt die Änderung sofort um.</p>
                <div class="deploy-step-tip"><i class="fas fa-info-circle"></i> Preise und Blog-Artikel können Sie selbst über diesen Admin-Bereich ändern – dafür brauchen Sie Genspark nicht.</div>
              </div>
            </div>

            <div class="deploy-step-arrow"><i class="fas fa-chevron-down"></i></div>

            <div class="deploy-step">
              <div class="deploy-step-num">2</div>
              <div class="deploy-step-body">
                <h3>Geänderte Dateien herunterladen</h3>
                <p>Genspark zeigt Ihnen, welche Dateien geändert wurden. Laden Sie diese Dateien herunter (über den Publish-Tab → „Als ZIP herunterladen" oder einzelne Dateien).</p>
                <div style="background:var(--gray-50);border:1px solid var(--gray-200);border-radius:8px;padding:12px 14px;margin-top:10px">
                  <div style="font-size:0.75rem;font-weight:700;color:var(--gray-500);margin-bottom:8px;text-transform:uppercase;letter-spacing:0.05em">Typisch geänderte Dateien:</div>
                  <div style="display:flex;flex-wrap:wrap;gap:6px">
                    <code class="deploy-file-chip">css/style.css</code>
                    <code class="deploy-file-chip">index.html</code>
                    <code class="deploy-file-chip">js/main.js</code>
                    <code class="deploy-file-chip">js/blog.js</code>
                    <code class="deploy-file-chip">images/[neues-bild]</code>
                  </div>
                </div>
              </div>
            </div>

            <div class="deploy-step-arrow"><i class="fas fa-chevron-down"></i></div>

            <div class="deploy-step">
              <div class="deploy-step-num">3</div>
              <div class="deploy-step-body">
                <h3>Dateien per FTP hochladen</h3>
                <p>Verbinden Sie sich mit Ihrem Server über ein FTP-Programm und laden Sie die geänderten Dateien in denselben Ordner wie die bestehenden Dateien hoch. Beim Hochladen <strong>überschreiben</strong> Sie die alten Dateien.</p>
                <div class="deploy-step-tip"><i class="fas fa-star"></i> <strong>Empfehlung:</strong> FileZilla (kostenlos) – <a href="https://filezilla-project.org/" target="_blank" style="color:var(--green)">filezilla-project.org</a></div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- FTP Anleitung -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <h2><i class="fas fa-server"></i> FTP-Upload mit FileZilla – Kurzanleitung</h2>
        </div>
        <div class="card-body">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div>
              <h4 style="font-size:0.85rem;font-weight:700;margin-bottom:10px;color:var(--gray-700)">📥 Einmalige Einrichtung</h4>
              <ol class="hilfe-steps" style="margin-bottom:0">
                <li>
                  <strong>FileZilla herunterladen &amp; installieren</strong><br/>
                  <span><a href="https://filezilla-project.org/" target="_blank" style="color:var(--green)">filezilla-project.org</a> → „Download FileZilla Client"</span>
                </li>
                <li>
                  <strong>FTP-Zugangsdaten vom Hosting-Anbieter holen</strong><br/>
                  <span>Host, Benutzername, Passwort und Port (meist 21 oder 22)</span>
                </li>
                <li>
                  <strong>In FileZilla: Datei → Servermanager → Neuer Server</strong><br/>
                  <span>Zugangsdaten eintragen, „Verbinden" klicken</span>
                </li>
              </ol>
            </div>
            <div>
              <h4 style="font-size:0.85rem;font-weight:700;margin-bottom:10px;color:var(--gray-700)">🔄 Bei jedem Update</h4>
              <ol class="hilfe-steps" style="margin-bottom:0">
                <li>
                  <strong>FileZilla öffnen und verbinden</strong><br/>
                  <span>Servermanager → Ihre gespeicherte Verbindung → Verbinden</span>
                </li>
                <li>
                  <strong>Rechte Seite: zum Webroot navigieren</strong><br/>
                  <span>meist <code style="background:var(--gray-100);padding:1px 5px;border-radius:3px">public_html/</code> oder <code style="background:var(--gray-100);padding:1px 5px;border-radius:3px">www/</code></span>
                </li>
                <li>
                  <strong>Geänderte Datei hochladen</strong><br/>
                  <span>Linke Seite (lokal) → Datei suchen → Rechtsklick → „Hochladen" → Überschreiben bestätigen</span>
                </li>
              </ol>
            </div>
          </div>
          <div class="hilfe-tipp" style="margin-top:16px">
            <i class="fas fa-shield-alt"></i>
            <div><strong>Sicherheits-Tipp:</strong> Verwenden Sie wenn möglich <strong>SFTP</strong> (Port 22) statt FTP (Port 21) – SFTP ist verschlüsselt und deutlich sicherer.</div>
          </div>
        </div>
      </div>

      <!-- Dateistruktur -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <h2><i class="fas fa-folder-tree"></i> Vollständige Dateistruktur</h2>
          <span style="font-size:0.75rem;color:var(--gray-400)">Alles muss auf dem Server vorhanden sein</span>
        </div>
        <div class="card-body">
          <pre class="deploy-filetree">/ (Webroot – meist public_html/ oder www/)
│
├── 📄 index.html              ← Startseite
├── 📄 mail.php                ← E-Mail für Bestellformular (PHP!)
│
├── 📁 css/
│   ├── style.css              ← Haupt-Design
│   ├── blog.css               ← Magazin-Design
│   └── region.css             ← Regionsseiten
│
├── 📁 js/
│   ├── main.js                ← Haupt-Funktionen
│   ├── blog.js                ← Magazin-Funktionen
│   ├── plz-data.js            ← Postleitzahlen
│   ├── region-data.js         ← Regionsdaten
│   └── region.js              ← Regionsseiten
│
├── 📁 images/
│   ├── logo.png               ← Firmenlogo
│   ├── pellets-hero.jpg       ← Hintergrundbild
│   ├── lkw-freigestellt.png   ← LKW-Foto
│   └── blog-fahrer-befüllung.jpg
│
├── 📁 blog/
│   ├── index.html             ← Magazin-Übersicht
│   └── artikel.html           ← Artikel-Detailseite
│
├── 📁 admin/
│   └── index.html             ← Dieser Admin-Bereich ⚠️
│
└── 📁 region/
    └── [regionale Unterseiten]</pre>
          <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;margin-top:14px;font-size:0.82rem;color:#92400e;display:flex;gap:8px">
            <i class="fas fa-exclamation-triangle" style="margin-top:2px;flex-shrink:0"></i>
            <div><strong>Wichtig:</strong> Den Ordner <code style="background:rgba(0,0,0,0.08);padding:1px 5px;border-radius:3px">admin/</code> sollten Sie auf dem Server zusätzlich mit einem <strong>Serverpasswort (.htaccess)</strong> schützen. Fragen Sie Ihren Hosting-Anbieter danach – das dauert nur 5 Minuten.</div>
          </div>
        </div>
      </div>

      <!-- API-Hinweis -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <h2><i class="fas fa-database"></i> Wichtig: Preise &amp; Blog-Daten</h2>
        </div>
        <div class="card-body">
          <p style="font-size:0.9rem;margin-bottom:14px">Preise und Blog-Artikel werden in der <strong>Genspark-Datenbank</strong> gespeichert. Das funktioniert auch wenn die Website extern gehostet wird – die Website ruft diese Daten automatisch ab.</p>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px">
              <div style="font-weight:700;font-size:0.85rem;color:var(--green);margin-bottom:6px">✅ Funktioniert automatisch</div>
              <ul style="font-size:0.82rem;color:var(--gray-600);padding-left:16px;display:flex;flex-direction:column;gap:3px">
                <li>Preisänderungen via Admin-Bereich</li>
                <li>Blog-Artikel schreiben &amp; veröffentlichen</li>
                <li>Bilder in Artikel hochladen</li>
              </ul>
            </div>
            <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:14px">
              <div style="font-weight:700;font-size:0.85rem;color:#92400e;margin-bottom:6px">⚠️ Voraussetzung</div>
              <ul style="font-size:0.82rem;color:var(--gray-600);padding-left:16px;display:flex;flex-direction:column;gap:3px">
                <li>Genspark-Projekt muss aktiv bleiben</li>
                <li>Dieser Admin-Bereich muss auf dem Server erreichbar sein</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Go-Live Checkliste -->
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-clipboard-check"></i> Go-Live Checkliste</h2>
          <span class="hilfe-badge hilfe-badge-blue">Vor der Freischaltung abhaken</span>
        </div>
        <div class="card-body">
          <div class="deploy-checklist">
            <label class="deploy-check-item"><input type="checkbox" /> Alle Dateien auf dem Server vorhanden</label>
            <label class="deploy-check-item"><input type="checkbox" /> SSL-Zertifikat aktiv (HTTPS &amp; grünes Schloss im Browser)</label>
            <label class="deploy-check-item"><input type="checkbox" /> Startseite lädt korrekt</label>
            <label class="deploy-check-item"><input type="checkbox" /> Bestellformular getestet (Test-Bestellung absenden)</label>
            <label class="deploy-check-item"><input type="checkbox" /> E-Mail-Empfang geprüft (Bestellbestätigung angekommen?)</label>
            <label class="deploy-check-item"><input type="checkbox" /> Admin-Bereich erreichbar unter <code>/admin/</code></label>
            <label class="deploy-check-item"><input type="checkbox" /> Magazin-Seite lädt (<code>/blog/</code>)</label>
            <label class="deploy-check-item"><input type="checkbox" /> Website auf Mobilgerät getestet</label>
            <label class="deploy-check-item"><input type="checkbox" /> Cookie-Banner erscheint beim ersten Besuch</label>
            <label class="deploy-check-item"><input type="checkbox" /> Admin-Ordner mit Serverpasswort (.htaccess) geschützt</label>
            <label class="deploy-check-item"><input type="checkbox" /> Google Search Console eingerichtet</label>
          </div>
          <div id="checklistProgress" style="margin-top:16px;background:var(--gray-100);border-radius:8px;padding:12px 16px;font-size:0.85rem;color:var(--gray-600)">
            <span id="checklistCount">0 / 11</span> erledigt
            <div style="background:var(--gray-200);border-radius:4px;height:6px;margin-top:8px;overflow:hidden">
              <div id="checklistBar" style="height:100%;background:linear-gradient(90deg,var(--green),#5abf6a);width:0%;transition:width .3s;border-radius:4px"></div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /panel-deployment -->

    <!-- ══ E-MAIL SETUP ══ -->
    <div class="section-panel" id="panel-email-setup">
      <div class="page-header">
        <div>
          <h1><i class="fas fa-envelope" style="color:var(--amber);margin-right:8px"></i>E-Mail-Benachrichtigung einrichten</h1>
          <p>Bestellungen sollen an <strong>andreas.edler@bioenergie.at</strong> gesendet werden.</p>
        </div>
      </div>

      <!-- Status-Anzeige -->
      <div id="emailStatusBanner" style="border-radius:14px;padding:20px 24px;margin-bottom:24px;display:flex;gap:16px;align-items:center;background:#fef3c7;border:1px solid #fde68a">
        <div style="font-size:2rem">⚠️</div>
        <div>
          <div style="font-weight:700;font-size:1rem;color:#92400e;margin-bottom:3px">E-Mail-Versand noch nicht aktiv</div>
          <div style="font-size:0.85rem;color:#92400e">Folgen Sie den 3 Schritten unten, um E-Mail-Benachrichtigungen zu aktivieren.</div>
        </div>
        <div id="emailStatusActive" style="display:none;font-size:2rem">✅</div>
      </div>

      <!-- Schritt 1: Web3Forms -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <h2><i class="fas fa-key"></i> Schritt 1: Kostenlosen Zugangsschlüssel anfordern</h2>
          <span class="hilfe-badge hilfe-badge-green">⏱ 2 Minuten</span>
        </div>
        <div class="card-body">
          <p style="font-size:0.9rem;margin-bottom:16px">
            Web3Forms ist ein kostenloser Dienst, der E-Mails direkt vom Browser-Formular an Ihre Adresse sendet – <strong>ohne eigenen Server</strong>.
          </p>

          <div class="email-steps">
            <div class="email-step">
              <div class="email-step-num">1</div>
              <div>
                <strong>Öffnen Sie diese Seite:</strong><br/>
                <a href="https://web3forms.com/" target="_blank" class="email-link">
                  <i class="fas fa-external-link-alt"></i> web3forms.com → „Create your Access Key"
                </a>
              </div>
            </div>
            <div class="email-step">
              <div class="email-step-num">2</div>
              <div>
                <strong>Tragen Sie Ihre E-Mail-Adresse ein:</strong><br/>
                <code class="email-code">andreas.edler@bioenergie.at</code>
                <span style="font-size:0.82rem;color:var(--gray-500);display:block;margin-top:4px">→ Klicken Sie auf „Create Access Key"</span>
              </div>
            </div>
            <div class="email-step">
              <div class="email-step-num">3</div>
              <div>
                <strong>Sie erhalten sofort eine Bestätigungs-E-Mail.</strong><br/>
                <span style="font-size:0.82rem;color:var(--gray-500)">Klicken Sie auf den Bestätigungslink. Danach erhalten Sie Ihren <strong>Access Key</strong> (sieht so aus: <code class="email-code-inline">xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx</code>)</span>
              </div>
            </div>
          </div>

          <a href="https://web3forms.com/" target="_blank" class="btn btn-primary" style="margin-top:16px">
            <i class="fas fa-external-link-alt"></i> web3forms.com öffnen
          </a>
        </div>
      </div>

      <!-- Schritt 2: Key eintragen -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <h2><i class="fas fa-paste"></i> Schritt 2: Access Key hier eintragen &amp; speichern</h2>
          <span class="hilfe-badge hilfe-badge-blue">⏱ 30 Sekunden</span>
        </div>
        <div class="card-body">
          <p style="font-size:0.85rem;color:var(--gray-500);margin-bottom:14px">
            Fügen Sie den kopierten Access Key unten ein. Er wird im Browser gespeichert und beim nächsten Schritt automatisch in die Website eingebaut.
          </p>
          <div class="form-group" style="max-width:480px">
            <label>Web3Forms Access Key</label>
            <div style="display:flex;gap:8px">
              <input type="text" id="w3fKeyInput" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                style="font-family:monospace;font-size:0.9rem" />
              <button class="btn btn-primary" id="saveW3fKeyBtn">
                <i class="fas fa-save"></i> Speichern
              </button>
            </div>
            <span class="hint" id="w3fKeyHint">Key aus der Bestätigungs-E-Mail von web3forms.com hier einfügen.</span>
          </div>
          <div id="w3fKeySaved" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-top:12px;font-size:0.85rem;color:#166534">
            <i class="fas fa-check-circle"></i> Key gespeichert! Jetzt Schritt 3 ausführen.
          </div>
        </div>
      </div>

      <!-- Schritt 3: In Website eintragen -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <h2><i class="fas fa-code"></i> Schritt 3: Key in die Website einbauen</h2>
          <span class="hilfe-badge hilfe-badge-purple">⏱ 1 Minute</span>
        </div>
        <div class="card-body">
          <p style="font-size:0.85rem;color:var(--gray-500);margin-bottom:16px">
            Klicken Sie auf den Button. Das System trägt den Key automatisch in die Website ein – der E-Mail-Versand ist danach sofort aktiv.
          </p>
          <div id="w3fApplySection">
            <div style="background:var(--gray-50);border:1px solid var(--gray-200);border-radius:8px;padding:14px 16px;margin-bottom:14px;font-size:0.85rem">
              <strong>Was passiert beim Klick:</strong>
              <ul style="margin-top:8px;padding-left:18px;color:var(--gray-600);display:flex;flex-direction:column;gap:3px">
                <li>Der Access Key wird in <code style="background:var(--gray-100);padding:1px 5px;border-radius:3px">js/main.js</code> eingetragen</li>
                <li>Ab sofort erhalten Sie bei jeder Bestellung eine E-Mail an <strong>andreas.edler@bioenergie.at</strong></li>
                <li>Der Kunde erhält automatisch eine Bestätigungs-E-Mail</li>
              </ul>
            </div>
            <button class="btn btn-primary" id="applyW3fKeyBtn" disabled>
              <i class="fas fa-bolt"></i> E-Mail-Versand aktivieren
            </button>
            <p id="applyHint" style="font-size:0.8rem;color:var(--gray-400);margin-top:8px">Zuerst Schritt 2 ausführen (Key speichern).</p>
          </div>
          <div id="w3fApplied" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;text-align:center">
            <div style="font-size:2rem;margin-bottom:8px">✅</div>
            <div style="font-weight:700;color:#166534;margin-bottom:4px">E-Mail-Versand ist aktiv!</div>
            <div style="font-size:0.82rem;color:#166534">Bestellungen werden ab sofort an <strong>andreas.edler@bioenergie.at</strong> gesendet.</div>
          </div>
        </div>
      </div>

      <!-- Test-Bestellung -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <h2><i class="fas fa-vial"></i> Schritt 4: Test-E-Mail senden</h2>
        </div>
        <div class="card-body">
          <p style="font-size:0.85rem;color:var(--gray-500);margin-bottom:14px">
            Senden Sie eine Test-Bestellbenachrichtigung direkt an Ihre E-Mail-Adresse.
          </p>
          <button class="btn btn-outline" id="sendTestEmailBtn" disabled>
            <i class="fas fa-paper-plane"></i> Test-E-Mail senden an andreas.edler@bioenergie.at
          </button>
          <div id="testEmailResult" style="display:none;margin-top:12px;border-radius:8px;padding:12px 16px;font-size:0.85rem"></div>
        </div>
      </div>

      <!-- Was kommt an -->
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-envelope-open-text"></i> So sieht die Bestell-E-Mail aus</h2>
        </div>
        <div class="card-body">
          <pre style="background:var(--gray-900);color:#d1fae5;padding:20px;border-radius:10px;font-size:0.8rem;line-height:1.7;overflow-x:auto;white-space:pre-wrap">Betreff: 🌲 Neue Pellets-Bestellung: 10 t – Max Mustermann (8580 Köflach)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🌲 NEUE PELLETS-BESTELLUNG
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📦 BESTELLUNG
  Menge:         10 Tonnen (lose)
  Lieferwoche:   KW15-2025
  Preis/Tonne:   398 €/t
  Gesamtpreis:   ca. 4.038 €

📍 LIEFERADRESSE
  Straße:        Musterstraße 12
  Ort:           8580 Köflach
  Zufahrt:       Einfahrt links, Kellerfenster

👤 KONTAKT
  Name:          Max Mustermann
  E-Mail:        max@beispiel.at
  Telefon:       +43 664 123 456

  Marketing OK:  Nein
  Eingegangen:   13.03.2025 09:42
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━</pre>
          <p style="font-size:0.82rem;color:var(--gray-500);margin-top:10px">
            <i class="fas fa-reply" style="color:var(--green)"></i>
            Die E-Mail enthält auch eine <strong>Antworten-an</strong>-Adresse des Kunden – Sie können direkt auf die Mail antworten und der Kunde erhält die Antwort.
          </p>
        </div>
      </div>

    </div><!-- /panel-email-setup -->

  </main>
</div>

<!-- Inline-Bild-Dialog (Bild in Artikeltext einfügen) -->
<div id="imgDialog">
  <div class="img-dialog-box">
    <h3><i class="fas fa-image"></i> Bild in Artikel einfügen</h3>
    <div class="img-dialog-tabs">
      <button class="img-dialog-tab active" data-dlgtab="upload">Upload</button>
      <button class="img-dialog-tab" data-dlgtab="url">URL</button>
      <button class="img-dialog-tab" data-dlgtab="lib">Bibliothek</button>
    </div>

    <!-- Upload -->
    <div class="img-dialog-panel active" id="dlgtab-upload">
      <div class="img-upload-area">
        <i class="fas fa-cloud-upload-alt"></i>
        <p>Klicken oder Bild hineinziehen</p>
        <input type="file" id="inlineFileInput" accept="image/*" />
      </div>
      <div id="inlineImgPreview"><img id="inlinePreviewImg" src="" alt="" /></div>
    </div>

    <!-- URL -->
    <div class="img-dialog-panel" id="dlgtab-url">
      <div class="form-group">
        <label>Bild-URL</label>
        <input type="text" id="inlineImgUrl" placeholder="https://… oder ../images/…" />
      </div>
      <div id="inlineUrlPreview" style="margin-top:8px;display:none">
        <img id="inlineUrlPreviewImg" src="" alt="" style="width:100%;max-height:120px;object-fit:cover;border-radius:6px" />
      </div>
    </div>

    <!-- Bibliothek -->
    <div class="img-dialog-panel" id="dlgtab-lib">
      <p style="font-size:0.75rem;color:var(--gray-400);margin-bottom:8px">Klicken = direkt einfügen</p>
      <div class="img-lib-grid" id="inlineLibGrid"></div>
    </div>

    <div class="img-size-row">
      <label>Größe:</label>
      <select id="inlineImgSize">
        <option value="100%">Volle Breite</option>
        <option value="75%">75 %</option>
        <option value="50%">50 %</option>
        <option value="33%">⅓ (klein)</option>
      </select>
      <label style="margin-left:8px">Ausrichtung:</label>
      <select id="inlineImgAlign">
        <option value="block">Zentriert</option>
        <option value="left">Links</option>
        <option value="right">Rechts</option>
      </select>
    </div>

    <div class="img-dialog-btns">
      <button class="btn btn-outline" id="imgDialogCancel">Abbrechen</button>
      <button class="btn btn-primary" id="imgDialogInsert"><i class="fas fa-plus"></i> Einfügen</button>
    </div>
  </div>
</div>

<!-- Confirm Modal -->
<div id="confirmModal">
  <div class="confirm-box">
    <i class="fas fa-exclamation-triangle"></i>
    <h3 id="confirmTitle">Artikel löschen?</h3>
    <p id="confirmText">Dieser Vorgang kann nicht rückgängig gemacht werden.</p>
    <div class="confirm-btns">
      <button class="btn btn-outline" id="confirmCancel">Abbrechen</button>
      <button class="btn btn-danger" id="confirmOk">Löschen</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div id="toast"><i class="fas fa-check-circle"></i> <span id="toastMsg"></span></div>

<script>
'use strict';

const CSRF_TOKEN = '<?php echo sp_generate_csrf(); ?>';

// ══════════════════════════════════════════════
// ══════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  const i = t.querySelector('i');
  t.className = type;
  i.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
  document.getElementById('toastMsg').textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3200);
}

function slugify(str) {
  return (str || '').toLowerCase()
    .replace(/ä/g,'ae').replace(/ö/g,'oe').replace(/ü/g,'ue').replace(/ß/g,'ss')
    .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
}

function fmtDate(ms) {
  if (!ms) return '–';
  return new Date(ms).toLocaleDateString('de-AT', { day:'2-digit', month:'long', year:'numeric' });
}

async function apiFetch(url, opts = {}) {
  const headers = { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN, ...(opts.headers || {}) };
  const res = await fetch(url, { ...opts, headers });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  if (res.status === 204) return null;
  return res.json();
}

// Confirm dialog
let confirmResolve = null;
function confirm(title, text) {
  return new Promise(resolve => {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmText').textContent = text;
    document.getElementById('confirmModal').classList.add('open');
    confirmResolve = resolve;
  });
}
document.getElementById('confirmCancel').addEventListener('click', () => {
  document.getElementById('confirmModal').classList.remove('open');
  if (confirmResolve) confirmResolve(false);
});
document.getElementById('confirmOk').addEventListener('click', () => {
  document.getElementById('confirmModal').classList.remove('open');
  if (confirmResolve) confirmResolve(true);
});

// ══════════════════════════════════════════════
// APP START
// ══════════════════════════════════════════════
loadPreise();
loadArtikel();

document.getElementById('logoutBtn').addEventListener('click', () => {
  window.location.href = 'index.php?logout=1';
});

// ══════════════════════════════════════════════
// NAVIGATION
// ══════════════════════════════════════════════
document.querySelectorAll('.nav-item[data-panel]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-' + btn.dataset.panel).classList.add('active');
    if (btn.dataset.panel === 'editor') resetEditor();
  });
});

function switchTo(panel) {
  document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
  document.querySelector(`.nav-item[data-panel="${panel}"]`)?.classList.add('active');
  document.getElementById('panel-' + panel).classList.add('active');
}

// ══════════════════════════════════════════════
// PREISE
// ══════════════════════════════════════════════
let preisRowId = null;
const PREIS_CACHE_KEY = 'sp_preise_cache';
const BLOG_CACHE_KEY = 'sp_blog_articles_cache';
const PREIS_API_URL = 'api-preise.php';
const ARTIKEL_API_URL = 'api-artikel.php';

async function loadPreise() {
  try {
    const json = await apiFetch(PREIS_API_URL);
    const row  = (json.data || [])[0];
    if (!row) return;
    preisRowId = row.id;
    document.getElementById('inp-gross').value = row.preis_gross || 398;
    document.getElementById('inp-klein').value = row.preis_klein || 418;
    document.getElementById('inp-absch').value = row.abschlauch  || 58;
    document.getElementById('prev-gross').textContent = row.preis_gross || 398;
    document.getElementById('prev-klein').textContent = row.preis_klein || 418;
    document.getElementById('prev-absch').textContent = row.abschlauch  || 58;
    localStorage.setItem(PREIS_CACHE_KEY, JSON.stringify(row));
    if (row.updated_at_label) document.getElementById('preisUpdatedAt').textContent = 'Zuletzt geändert: ' + row.updated_at_label;
  } catch(e) {
    try {
      const row = JSON.parse(localStorage.getItem(PREIS_CACHE_KEY) || 'null');
      if (row) {
        document.getElementById('inp-gross').value = row.preis_gross || 398;
        document.getElementById('inp-klein').value = row.preis_klein || 418;
        document.getElementById('inp-absch').value = row.abschlauch  || 58;
        document.getElementById('prev-gross').textContent = row.preis_gross || 398;
        document.getElementById('prev-klein').textContent = row.preis_klein || 418;
        document.getElementById('prev-absch').textContent = row.abschlauch  || 58;
        showToast('Preise aus lokalem Cache geladen.', 'error');
        return;
      }
    } catch (_) {}
    showToast('Preise konnten nicht geladen werden.', 'error');
  }
}

document.getElementById('savePreiseBtn').addEventListener('click', async () => {
  const gross = parseFloat(document.getElementById('inp-gross').value);
  const klein = parseFloat(document.getElementById('inp-klein').value);
  const absch = parseFloat(document.getElementById('inp-absch').value);
  if (!gross || !klein || isNaN(absch)) { showToast('Bitte alle Preisfelder ausfüllen.', 'error'); return; }
  if (gross >= klein) { showToast('Preis ab 4t muss kleiner sein als Preis unter 4t.', 'error'); return; }

  const hint = document.getElementById('preisSaveHint');
  hint.textContent = 'Wird gespeichert…';
  const now = new Date().toLocaleDateString('de-AT', { month:'long', year:'numeric' });

  try {
    const payload = { preis_gross: gross, preis_klein: klein, abschlauch: absch, updated_at_label: now };
    if (preisRowId) {
      await apiFetch(PREIS_API_URL, { method: 'POST', body: JSON.stringify({ id: preisRowId, ...payload }) });
    } else {
      const created = await apiFetch(PREIS_API_URL, { method: 'POST', body: JSON.stringify({ id: 'aktuell', ...payload }) });
      preisRowId = created.id;
    }
    document.getElementById('prev-gross').textContent = gross;
    document.getElementById('prev-klein').textContent = klein;
    document.getElementById('prev-absch').textContent = absch;
    document.getElementById('preisUpdatedAt').textContent = 'Zuletzt geändert: ' + now;
    localStorage.setItem(PREIS_CACHE_KEY, JSON.stringify(payload));
    hint.textContent = '✓ Gespeichert – wirkt sofort auf der Website.';
    showToast('Preise erfolgreich gespeichert!');
    setTimeout(() => hint.textContent = '', 4000);
  } catch(e) { hint.textContent = ''; showToast('Fehler beim Speichern!', 'error'); }
});

// ══════════════════════════════════════════════
// ARTIKEL-LISTE
// ══════════════════════════════════════════════
let allArtikel = [];

async function loadArtikel() {
  try {
    const json = await apiFetch(ARTIKEL_API_URL);
    allArtikel = (json.data || []).sort((a,b) => new Date(b.published_at || 0) - new Date(a.published_at || 0));
    localStorage.setItem(BLOG_CACHE_KEY, JSON.stringify(allArtikel));
    renderArtikelTable(allArtikel);
  } catch(e) {
    try {
      allArtikel = JSON.parse(localStorage.getItem(BLOG_CACHE_KEY) || '[]');
      if (allArtikel.length) {
        renderArtikelTable(allArtikel);
        showToast('Artikel aus lokalem Cache geladen.', 'error');
        return;
      }
    } catch (_) {}
    document.getElementById('artikelTableWrap').innerHTML = '<div class="loading-row"><i class="fas fa-exclamation-circle" style="color:red"></i>Fehler beim Laden.</div>';
  }
}

function catBadgeClass(cat) {
  const m = { 'Nachhaltigkeit':'cat-nach', 'Tipps & Tricks':'cat-tipps', 'Aktuell':'cat-aktuell', 'Produkt':'cat-produkt' };
  return m[cat] || '';
}

function renderArtikelTable(list) {
  document.getElementById('artikelCount').textContent = list.length;
  const wrap = document.getElementById('artikelTableWrap');
  if (!list.length) {
    wrap.innerHTML = `<div class="empty-state"><i class="fas fa-newspaper"></i><h3>Keine Artikel gefunden</h3><p>Noch kein Artikel geschrieben? Leg jetzt los!</p><button class="btn btn-primary" onclick="startNewArtikel()"><i class="fas fa-plus"></i> Ersten Artikel schreiben</button></div>`;
    return;
  }
  wrap.innerHTML = `<table class="articles-table">
    <thead><tr>
      <th>Titel</th><th>Kategorie</th><th>Autor</th><th>Datum</th><th>Status</th><th style="width:120px">Aktionen</th>
    </tr></thead>
    <tbody>${list.map(a => `
      <tr>
        <td class="article-title-cell">${a.title || '(Kein Titel)'}<small>/${a.slug || a.id}</small></td>
        <td><span class="cat-badge ${catBadgeClass(a.category)}">${a.category || '–'}</span></td>
        <td style="font-size:0.8rem;color:var(--gray-500)">${a.author || '–'}</td>
        <td style="font-size:0.8rem;color:var(--gray-500)">${fmtDate(a.published_at)}</td>
        <td>${a.status === 'draft'
          ? '<span class="status-dot" style="background:var(--gray-400)"></span><span style="font-size:0.78rem">Entwurf</span>'
          : '<span class="status-dot published"></span><span style="font-size:0.78rem">Veröffentlicht</span>'}</td>
        <td><div class="table-actions">
          <button class="btn btn-sm btn-outline" onclick="editArtikel('${a.id}')"><i class="fas fa-edit"></i></button>
          <button class="btn btn-sm btn-danger" onclick="deleteArtikel('${a.id}','${(a.title||'').replace(/'/g,"\\'")}')"><i class="fas fa-trash"></i></button>
        </div></td>
      </tr>`).join('')}
    </tbody></table>`;
}

// Filter
document.getElementById('artikelSearch').addEventListener('input', filterArtikel);
document.getElementById('artikelCatFilter').addEventListener('change', filterArtikel);

function filterArtikel() {
  const q   = document.getElementById('artikelSearch').value.toLowerCase();
  const cat = document.getElementById('artikelCatFilter').value;
  const filtered = allArtikel.filter(a =>
    (!q || (a.title||'').toLowerCase().includes(q) || (a.teaser||'').toLowerCase().includes(q)) &&
    (!cat || a.category === cat)
  );
  renderArtikelTable(filtered);
}

document.getElementById('newArtikelBtn').addEventListener('click', startNewArtikel);

function startNewArtikel() {
  resetEditor();
  switchTo('editor');
}

async function deleteArtikel(id, title) {
  const ok = await confirm('Artikel löschen?', `"${title}" wird dauerhaft gelöscht. Fortfahren?`);
  if (!ok) return;
  try {
    await apiFetch(`${ARTIKEL_API_URL}?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
    allArtikel = allArtikel.filter(a => a.id !== id);
    localStorage.setItem(BLOG_CACHE_KEY, JSON.stringify(allArtikel));
    renderArtikelTable(allArtikel);
    showToast('Artikel gelöscht.');
  } catch(e) { showToast('Fehler beim Löschen!', 'error'); }
}

// ══════════════════════════════════════════════
// EDITOR
// ══════════════════════════════════════════════
let editingId = null;
let editorTags = [];
let inlineImageSrc = '';

// Show/hide video URL field based on category
document.getElementById('edKategorie').addEventListener('change', function() {
  document.getElementById('videoUrlGroup').style.display = this.value === 'Video' ? 'block' : 'none';
});

function resetEditor() {
  editingId = null;
  editorTags = [];
  coverImageSrc = '';
  inlineImageSrc = '';
  document.getElementById('edArtikelId').value = '';
  document.getElementById('edTitel').value = '';
  document.getElementById('edTeaser').value = '';
  document.getElementById('contentEditor').innerHTML = '';
  document.getElementById('edKategorie').value = '';
  document.getElementById('edVideoUrl').value = '';
  document.getElementById('videoUrlGroup').style.display = 'none';
  document.getElementById('edAutor').value = 'Steirer Pellets Team';
  document.getElementById('edLesezeit').value = '5';
  document.getElementById('edFeatured').checked = false;
  document.getElementById('edImage').value = '';
  document.getElementById('coverImageData').value = '';
  document.getElementById('coverPreviewWrap').style.display = 'none';
  document.getElementById('coverPreviewImg').src = '';
  document.getElementById('edSlugPreview').textContent = '–';
  document.getElementById('edStatusLabel').textContent = 'Entwurf';
  document.getElementById('edStatusLabel').style.color = 'var(--gray-400)';
  document.getElementById('edPublishedInfo').style.display = 'none';
  document.getElementById('editorTitle').innerHTML = '<i class="fas fa-edit" style="color:var(--green);margin-right:8px"></i>Neuer Artikel';
  document.getElementById('editorSub').textContent = 'Schreiben Sie einen neuen News-Beitrag.';
  switchImgTab('upload');
  renderTagChips();
}

function editArtikel(id) {
  const art = allArtikel.find(a => a.id === id);
  if (!art) return;
  resetEditor();
  editingId = id;
  document.getElementById('edArtikelId').value = id;
  document.getElementById('edTitel').value    = art.title   || '';
  document.getElementById('edTeaser').value   = art.teaser  || '';
  document.getElementById('contentEditor').innerHTML = art.content || '';
  document.getElementById('edKategorie').value  = art.category     || '';
  document.getElementById('edAutor').value      = art.author       || 'Steirer Pellets Team';
  document.getElementById('edLesezeit').value   = art.reading_time || 5;
  document.getElementById('edFeatured').checked = !!art.featured;
  document.getElementById('edVideoUrl').value = art.video_url || '';
  document.getElementById('videoUrlGroup').style.display = art.category === 'Video' ? 'block' : 'none';
  editorTags = Array.isArray(art.tags) ? [...art.tags] : [];
  renderTagChips();
  updateSlugPreview();
  const isDraft = art.status === 'draft';
  document.getElementById('edStatusLabel').textContent = isDraft ? 'Entwurf' : 'Veröffentlicht';
  document.getElementById('edStatusLabel').style.color = isDraft ? 'var(--gray-400)' : '#22c55e';
  document.getElementById('edPublishedInfo').style.display = art.published_at ? 'block' : 'none';
  document.getElementById('edPublishedDate').textContent = fmtDate(art.published_at);
  document.getElementById('editorTitle').innerHTML = '<i class="fas fa-edit" style="color:var(--green);margin-right:8px"></i>Artikel bearbeiten';
  document.getElementById('editorSub').textContent = art.title || '';
  // Bild laden
  if (art.image) {
    document.getElementById('edImage').value = art.image;
    document.getElementById('coverImageData').value = art.image;
    setCoverImage(art.image);
    switchImgTab('url');
  }
  switchTo('editor');
}

// Slug preview
document.getElementById('edTitel').addEventListener('input', updateSlugPreview);
function updateSlugPreview() {
  const slug = slugify(document.getElementById('edTitel').value);
  document.getElementById('edSlugPreview').textContent = slug || '–';
}

// Tags
document.getElementById('tagInput').addEventListener('keydown', e => {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault();
    const val = e.target.value.trim().replace(/,$/, '');
    if (val && !editorTags.includes(val)) { editorTags.push(val); renderTagChips(); }
    e.target.value = '';
  }
});
function renderTagChips() {
  const wrap = document.getElementById('tagWrap');
  const input = document.getElementById('tagInput');
  wrap.innerHTML = '';
  editorTags.forEach((t, i) => {
    const chip = document.createElement('span');
    chip.className = 'tag-chip';
    chip.innerHTML = `${t}<button type="button" onclick="removeTag(${i})">×</button>`;
    wrap.appendChild(chip);
  });
  wrap.appendChild(input);
}
function removeTag(i) { editorTags.splice(i, 1); renderTagChips(); }

// ══════════════════════════════════════════════
// BILD-BIBLIOTHEK (localStorage)
// ══════════════════════════════════════════════
const IMG_LIB_KEY = 'sp_img_library';
const IMG_LIB_MAX = 20; // max Bilder in der Bibliothek

function getLibrary() {
  try { return JSON.parse(localStorage.getItem(IMG_LIB_KEY) || '[]'); } catch { return []; }
}

function addToLibrary(dataUrlOrUrl, filename) {
  const lib = getLibrary();
  // Duplikat-Prüfung
  if (lib.some(e => e.src === dataUrlOrUrl)) return;
  lib.unshift({ src: dataUrlOrUrl, name: filename || 'Bild', ts: Date.now() });
  if (lib.length > IMG_LIB_MAX) lib.splice(IMG_LIB_MAX);
  localStorage.setItem(IMG_LIB_KEY, JSON.stringify(lib));
}

function deleteFromLibrary(ts) {
  const lib = getLibrary().filter(e => e.ts !== ts);
  localStorage.setItem(IMG_LIB_KEY, JSON.stringify(lib));
}

function renderLibrary(containerId, onSelect) {
  const lib = getLibrary();
  const wrap = document.getElementById(containerId);
  if (!wrap) return;
  if (!lib.length) {
    wrap.innerHTML = `<div class="img-lib-empty"><i class="fas fa-images" style="font-size:1.5rem;display:block;margin-bottom:6px;color:var(--gray-300)"></i>Noch keine Bilder in der Bibliothek.</div>`;
    return;
  }
  wrap.innerHTML = lib.map(e => `
    <div class="img-lib-item" data-ts="${e.ts}">
      <img src="${e.src}" alt="${e.name}" title="${e.name}" loading="lazy" />
      <button class="lib-del" title="Aus Bibliothek entfernen" onclick="event.stopPropagation();removeLibItem(${e.ts},'${containerId}',null)">×</button>
    </div>`).join('');
  wrap.querySelectorAll('.img-lib-item').forEach(item => {
    item.addEventListener('click', () => {
      const ts = parseInt(item.dataset.ts);
      const entry = getLibrary().find(e => e.ts === ts);
      if (entry && onSelect) onSelect(entry.src);
    });
  });
}

function removeLibItem(ts, containerId, onSelect) {
  deleteFromLibrary(ts);
  // Alle Bibliotheken neu rendern
  renderLibrary('coverLibrary', (s) => { setCoverImage(s); });
  renderLibrary('inlineLibGrid', insertFromLibrary);
}

// ══════════════════════════════════════════════
// AUFMACHERBILD (Cover)
// ══════════════════════════════════════════════
let coverImageSrc = ''; // aktuelle Bild-URL oder DataURL

function setCoverImage(src, filename) {
  coverImageSrc = src;
  document.getElementById('coverImageData').value = src;
  const wrap = document.getElementById('coverPreviewWrap');
  const img  = document.getElementById('coverPreviewImg');
  if (src) {
    img.src = src;
    wrap.style.display = 'block';
    addToLibrary(src, filename || 'Aufmacherbild');
    renderLibrary('coverLibrary', (s) => { setCoverImage(s); });
    renderLibrary('inlineLibGrid', insertFromLibrary);
  } else {
    img.src = '';
    wrap.style.display = 'none';
  }
}

function removeCoverImage() {
  setCoverImage('');
  document.getElementById('edImage').value = '';
  document.getElementById('coverImageData').value = '';
  // Drop-Zone zurücksetzen
  const dz = document.getElementById('coverDropZone');
  dz.classList.remove('has-image');
  dz.querySelector('i').className = 'fas fa-cloud-upload-alt';
  dz.querySelector('p').innerHTML = 'Bild hier hineinziehen oder<br/><strong>klicken zum Auswählen</strong>';
  // Upload-Tab wieder anzeigen
  switchImgTab('upload');
}

function loadCoverFromUrl() {
  const url = document.getElementById('edImage').value.trim();
  if (!url) { showToast('Bitte eine URL eingeben.', 'error'); return; }
  setCoverImage(url);
  showToast('Bild-URL übernommen ✓');
}

function insertCoverIntoContent() {
  if (!coverImageSrc) return;
  insertImageHtml(coverImageSrc, '100%', 'block');
  showToast('Bild auch im Text eingefügt.');
}

// Drag & Drop auf Drop-Zone
const coverDropZone = document.getElementById('coverDropZone');
const coverFileInput = document.getElementById('coverFileInput');

coverDropZone.addEventListener('dragover', e => { e.preventDefault(); coverDropZone.classList.add('drag-over'); });
coverDropZone.addEventListener('dragleave', () => coverDropZone.classList.remove('drag-over'));
coverDropZone.addEventListener('drop', e => {
  e.preventDefault();
  coverDropZone.classList.remove('drag-over');
  const file = e.dataTransfer.files[0];
  if (file) loadFileAsCover(file);
});

coverFileInput.addEventListener('change', () => {
  const file = coverFileInput.files[0];
  if (file) loadFileAsCover(file);
});

function loadFileAsCover(file) {
  if (!file.type.startsWith('image/')) { showToast('Bitte nur Bilder hochladen (JPG, PNG, WebP).', 'error'); return; }
  if (file.size > 5 * 1024 * 1024) { showToast('Bild ist zu groß (max. 5 MB).', 'error'); return; }
  const reader = new FileReader();
  reader.onload = e => {
    setCoverImage(e.target.result, file.name);
    // Drop-Zone als "belegt" markieren
    coverDropZone.classList.add('has-image');
    coverDropZone.querySelector('i').className = 'fas fa-check-circle';
    coverDropZone.querySelector('p').innerHTML = `<strong>${file.name}</strong>`;
    switchImgTab('upload');
  };
  reader.readAsDataURL(file);
}

// Tab-Wechsel im Aufmacherbild-Bereich
function switchImgTab(tabName) {
  document.querySelectorAll('.img-tab').forEach(t => t.classList.toggle('active', t.dataset.imgtab === tabName));
  document.querySelectorAll('.img-tab-panel').forEach(p => p.classList.toggle('active', p.id === 'imgtab-' + tabName));
  if (tabName === 'lib') renderLibrary('coverLibrary', (s) => { setCoverImage(s); });
}

document.querySelectorAll('.img-tab').forEach(btn => {
  btn.addEventListener('click', () => switchImgTab(btn.dataset.imgtab));
});

// URL-Feld: Live-Vorschau beim Tippen
document.getElementById('edImage').addEventListener('input', () => {
  const url = document.getElementById('edImage').value.trim();
  if (url.length > 5) {
    document.getElementById('coverPreviewImg').src = url;
    document.getElementById('coverPreviewWrap').style.display = 'block';
  } else {
    document.getElementById('coverPreviewWrap').style.display = 'none';
  }
});

// ══════════════════════════════════════════════
// INLINE-BILD-DIALOG (Bild in Artikeltext)
// ══════════════════════════════════════════════
let savedRange = null; // gespeicherte Cursor-Position im Editor

function openImgDialog() {
  // Cursor-Position merken
  const sel = window.getSelection();
  if (sel.rangeCount) savedRange = sel.getRangeAt(0).cloneRange();
  // Dialog öffnen
  document.getElementById('imgDialog').classList.add('open');
  // Bibliothek füllen
  renderLibrary('inlineLibGrid', insertFromLibrary);
  // Inline-Vorschau zurücksetzen
  document.getElementById('inlineImgPreview').style.display = 'none';
  document.getElementById('inlinePreviewImg').src = '';
  document.getElementById('inlineImgUrl').value = '';
  document.getElementById('inlineUrlPreview').style.display = 'none';
  switchDlgTab('upload');
}

function switchDlgTab(tabName) {
  document.querySelectorAll('.img-dialog-tab').forEach(t => t.classList.toggle('active', t.dataset.dlgtab === tabName));
  document.querySelectorAll('.img-dialog-panel').forEach(p => p.classList.toggle('active', p.id === 'dlgtab-' + tabName));
}

document.querySelectorAll('.img-dialog-tab').forEach(btn => {
  btn.addEventListener('click', () => switchDlgTab(btn.dataset.dlgtab));
});

document.getElementById('imgDialogCancel').addEventListener('click', () => {
  document.getElementById('imgDialog').classList.remove('open');
});

// Klick außerhalb des Dialogs schließt ihn
document.getElementById('imgDialog').addEventListener('click', e => {
  if (e.target === document.getElementById('imgDialog')) document.getElementById('imgDialog').classList.remove('open');
});

// Datei-Upload im Dialog

document.getElementById('inlineFileInput').addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  if (!file.type.startsWith('image/')) { showToast('Bitte nur Bilder.', 'error'); return; }
  if (file.size > 5 * 1024 * 1024) { showToast('Max. 5 MB.', 'error'); return; }
  const reader = new FileReader();
  reader.onload = e => {
    inlineImageSrc = e.target.result;
    document.getElementById('inlinePreviewImg').src = inlineImageSrc;
    document.getElementById('inlineImgPreview').style.display = 'block';
    addToLibrary(inlineImageSrc, file.name);
    renderLibrary('inlineLibGrid', insertFromLibrary);
    renderLibrary('coverLibrary', (s) => { setCoverImage(s); });
  };
  reader.readAsDataURL(file);
});

// Drag & Drop im Dialog-Upload-Bereich
const dlgUploadArea = document.querySelector('#dlgtab-upload .img-upload-area');
dlgUploadArea.addEventListener('dragover', e => { e.preventDefault(); dlgUploadArea.style.borderColor = 'var(--green)'; dlgUploadArea.style.background = 'var(--green-lite)'; });
dlgUploadArea.addEventListener('dragleave', () => { dlgUploadArea.style.borderColor = ''; dlgUploadArea.style.background = ''; });
dlgUploadArea.addEventListener('drop', e => {
  e.preventDefault();
  dlgUploadArea.style.borderColor = '';
  dlgUploadArea.style.background = '';
  const file = e.dataTransfer.files[0];
  if (file) {
    if (!file.type.startsWith('image/')) { showToast('Bitte nur Bilder.', 'error'); return; }
    if (file.size > 5 * 1024 * 1024) { showToast('Max. 5 MB.', 'error'); return; }
    const reader = new FileReader();
    reader.onload = ev => {
      inlineImageSrc = ev.target.result;
      document.getElementById('inlinePreviewImg').src = inlineImageSrc;
      document.getElementById('inlineImgPreview').style.display = 'block';
      addToLibrary(inlineImageSrc, file.name);
      renderLibrary('inlineLibGrid', insertFromLibrary);
      renderLibrary('coverLibrary', (s) => { setCoverImage(s); });
    };
    reader.readAsDataURL(file);
  }
});

// URL-Vorschau im Dialog
document.getElementById('inlineImgUrl').addEventListener('input', function() {
  const url = this.value.trim();
  if (url.length > 5) {
    document.getElementById('inlineUrlPreviewImg').src = url;
    document.getElementById('inlineUrlPreview').style.display = 'block';
    inlineImageSrc = url;
  } else {
    document.getElementById('inlineUrlPreview').style.display = 'none';
  }
});

// Aus Bibliothek einfügen (direkt, ohne Dialog zu schließen)
function insertFromLibrary(src) {
  const size  = document.getElementById('inlineImgSize').value;
  const align = document.getElementById('inlineImgAlign').value;
  document.getElementById('imgDialog').classList.remove('open');
  restoreCursorAndInsert(src, size, align);
}

// "Einfügen"-Button im Dialog
document.getElementById('imgDialogInsert').addEventListener('click', () => {
  const activeTab = document.querySelector('.img-dialog-tab.active')?.dataset.dlgtab;
  let src = '';
  if (activeTab === 'upload') src = inlineImageSrc;
  else if (activeTab === 'url') src = document.getElementById('inlineImgUrl').value.trim();
  else if (activeTab === 'lib') {
    showToast('Bitte klicken Sie auf ein Bild in der Bibliothek.', 'error');
    return;
  }
  if (!src) { showToast('Kein Bild ausgewählt.', 'error'); return; }
  if (activeTab === 'url') addToLibrary(src, 'URL-Bild');
  document.getElementById('imgDialog').classList.remove('open');
  const size  = document.getElementById('inlineImgSize').value;
  const align = document.getElementById('inlineImgAlign').value;
  restoreCursorAndInsert(src, size, align);
});

function restoreCursorAndInsert(src, size, align) {
  const editor = document.getElementById('contentEditor');
  editor.focus();
  if (savedRange) {
    const sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange);
  }
  insertImageHtml(src, size, align);
  addToLibrary(src, 'Bild');
  renderLibrary('coverLibrary', (s) => { setCoverImage(s); });
  renderLibrary('inlineLibGrid', insertFromLibrary);
}

function insertImageHtml(src, size, align) {
  const alignStyle = align === 'left'
    ? 'float:left;margin:0 16px 12px 0'
    : align === 'right'
      ? 'float:right;margin:0 0 12px 16px'
      : 'display:block;margin:16px auto';
  const html = `<figure style="width:${size};${alignStyle};max-width:100%"><img src="${src}" style="width:100%;border-radius:8px;display:block" alt="" /><figcaption style="font-size:0.78rem;color:#6b7280;text-align:center;margin-top:4px;font-style:italic"></figcaption></figure><p><br></p>`;
  document.getElementById('contentEditor').focus();
  document.execCommand('insertHTML', false, html);
}

// ══════════════════════════════════════════════
// TOOLBAR
// ══════════════════════════════════════════════
function fmt(cmd) { document.getElementById('contentEditor').focus(); document.execCommand(cmd, false, null); }
function fmtH(tag) {
  const sel = window.getSelection();
  if (!sel.rangeCount) return;
  document.getElementById('contentEditor').focus();
  document.execCommand('formatBlock', false, tag);
}
function fmtLink() {
  const url = prompt('URL eingeben:', 'https://');
  if (url) { document.getElementById('contentEditor').focus(); document.execCommand('createLink', false, url); }
}
function fmtHR() {
  document.getElementById('contentEditor').focus();
  document.execCommand('insertHTML', false, '<hr/>');
}
function fmtEmbed() {
  const code = prompt('Social-Media Embed-Code einfügen:\n(Instagram/Facebook/TikTok → Beitrag öffnen → „Einbetten" → Code kopieren)');
  if (!code || !code.trim()) return;
  const wrapper = '<div class="social-embed">' + code.trim() + '</div>';
  document.getElementById('contentEditor').focus();
  document.execCommand('insertHTML', false, wrapper);
}
function fmtPreisTag() {
  const tags = {
    '1': '{{preis_gross}}',
    '2': '{{preis_klein}}',
    '3': '{{abschlauch}}'
  };
  const choice = prompt('Welchen Preis einfügen?\n1 = Großlieferung (€/t)\n2 = Kleinlieferung (€/t)\n3 = Abschlauchgebühr (€)');
  if (!choice || !tags[choice]) return;
  document.getElementById('contentEditor').focus();
  document.execCommand('insertHTML', false, '<strong>' + tags[choice] + '</strong>');
}

// ══════════════════════════════════════════════
// SPEICHERN
// ══════════════════════════════════════════════
async function saveArtikel(publish) {
  const title   = document.getElementById('edTitel').value.trim();
  const teaser  = document.getElementById('edTeaser').value.trim();
  const content = document.getElementById('contentEditor').innerHTML.trim();
  const cat     = document.getElementById('edKategorie').value;

  if (!title)   { showToast('Bitte einen Titel eingeben.', 'error'); return; }
  if (!teaser)  { showToast('Bitte einen Teaser eingeben.', 'error'); return; }
  if (!cat)     { showToast('Bitte eine Kategorie wählen.', 'error'); return; }
  if (!content || content === '<br>') { showToast('Bitte Inhalt schreiben.', 'error'); return; }

  // Bild: coverImageData hat Vorrang vor edImage
  const imageVal = document.getElementById('coverImageData').value.trim()
                || document.getElementById('edImage').value.trim()
                || '';

  const slug = slugify(title);
  const currentArtikel = editingId ? allArtikel.find(a => a.id === editingId) : null;
  const payload = {
    title,
    teaser,
    content,
    category:     cat,
    author:       document.getElementById('edAutor').value.trim() || 'Steirer Pellets Team',
    reading_time: parseInt(document.getElementById('edLesezeit').value) || 5,
    featured:     document.getElementById('edFeatured').checked,
    tags:         editorTags,
    image:        imageVal,
    video_url:    cat === 'Video' ? document.getElementById('edVideoUrl').value.trim() : '',
    slug,
    status:       publish ? 'published' : 'draft',
    published_at: publish ? (currentArtikel?.published_at || new Date().toISOString()) : (currentArtikel?.published_at || null),
  };

  try {
    let saved;
    if (editingId) {
      saved = await apiFetch(`${ARTIKEL_API_URL}?id=${encodeURIComponent(editingId)}`, {
        method: 'PUT',
        body: JSON.stringify({ id: editingId, ...payload })
      });
      allArtikel = allArtikel.map(a => a.id === editingId ? { ...a, ...saved } : a);
    } else {
      saved = await apiFetch(ARTIKEL_API_URL, {
        method: 'POST',
        body: JSON.stringify({ ...payload, id: slug || undefined })
      });
      allArtikel.unshift(saved);
      editingId = saved.id;
      document.getElementById('edArtikelId').value = saved.id;
    }
    localStorage.setItem(BLOG_CACHE_KEY, JSON.stringify(allArtikel));
    renderArtikelTable(allArtikel);
    document.getElementById('edStatusLabel').textContent = publish ? 'Veröffentlicht' : 'Entwurf';
    document.getElementById('edStatusLabel').style.color = publish ? '#22c55e' : 'var(--gray-400)';
    showToast(publish ? 'Artikel veröffentlicht! ✓' : 'Entwurf gespeichert.');
  } catch(e) { showToast('Fehler beim Speichern!', 'error'); console.error(e); }
}

document.getElementById('saveDraftBtn').addEventListener('click', () => saveArtikel(false));
document.getElementById('publishBtn').addEventListener('click', () => saveArtikel(true));
document.getElementById('backToListBtn').addEventListener('click', () => switchTo('artikel'));
document.getElementById('newArtikelBtn').addEventListener('click', () => { resetEditor(); switchTo('editor'); });

// Bibliothek beim Start laden
renderLibrary('coverLibrary', (s) => { setCoverImage(s); });
renderLibrary('inlineLibGrid', insertFromLibrary);

// ══════════════════════════════════════════════
// DEPLOYMENT CHECKLISTE
// ══════════════════════════════════════════════
function initChecklist() {
  const items = document.querySelectorAll('.deploy-check-item input[type=checkbox]');
  const STORAGE_KEY = 'sp_deploy_checklist';

  // Gespeicherten Stand laden
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
    items.forEach((cb, i) => { if (saved[i]) cb.checked = true; });
  } catch(e) {}

  function updateProgress() {
    const total   = items.length;
    const checked = [...items].filter(cb => cb.checked).length;
    document.getElementById('checklistCount').textContent = checked + ' / ' + total;
    document.getElementById('checklistBar').style.width = (checked / total * 100) + '%';
    // Speichern
    localStorage.setItem(STORAGE_KEY, JSON.stringify([...items].map(cb => cb.checked)));
    // Alle erledigt?
    if (checked === total) {
      document.getElementById('checklistProgress').style.background = '#f0fdf4';
      document.getElementById('checklistProgress').style.color = '#166534';
      document.getElementById('checklistCount').textContent = '✅ Alle ' + total + ' Punkte erledigt – bereit für Go-Live!';
    }
  }

  items.forEach(cb => cb.addEventListener('change', updateProgress));
  updateProgress();
}

// Checkliste initialisieren wenn Panel sichtbar wird
document.querySelector('.nav-item[data-panel="deployment"]').addEventListener('click', () => {
  setTimeout(initChecklist, 50);
});

// ══════════════════════════════════════════════
// E-MAIL SETUP
// ══════════════════════════════════════════════
const W3F_KEY_STORAGE = 'sp_w3f_access_key';
const DEFAULT_W3F_KEY = '8b18adc8-a507-499e-95a0-54c1485b341d';

function initEmailSetup() {
  const savedKey = localStorage.getItem(W3F_KEY_STORAGE) || DEFAULT_W3F_KEY || '';
  const input    = document.getElementById('w3fKeyInput');
  const applyBtn = document.getElementById('applyW3fKeyBtn');
  const testBtn  = document.getElementById('sendTestEmailBtn');

  if (savedKey && savedKey.length > 10) {
    input.value = savedKey;
    document.getElementById('w3fKeySaved').style.display = 'block';
    applyBtn.disabled = false;
    document.getElementById('applyHint').textContent = 'Key ist gespeichert – bereit zum Aktivieren.';
    // Prüfen ob Key bereits eingebaut
    if (savedKey !== 'PENDING_CONFIRMATION') {
      testBtn.disabled = false;
    }
  }

  // Gespeicherten & aktiven Key aus main.js prüfen
  checkEmailActive();
}

function checkEmailActive() {
  const key = localStorage.getItem(W3F_KEY_STORAGE) || DEFAULT_W3F_KEY || '';
  const isActive = key && key.length > 20 && key !== 'PENDING_CONFIRMATION';
  const banner = document.getElementById('emailStatusBanner');
  if (isActive) {
    banner.style.background = '#f0fdf4';
    banner.style.borderColor = '#bbf7d0';
    banner.querySelector('div:first-child').textContent = '✅';
    banner.querySelector('[style*="font-weight:700"]').textContent = 'E-Mail-Versand ist aktiv!';
    banner.querySelector('[style*="font-size:0.85rem"]').innerHTML = 'Bestellungen werden an <strong>andreas.edler@bioenergie.at</strong> gesendet.';
    document.getElementById('w3fApplied').style.display = 'block';
    document.getElementById('w3fApplySection').querySelector('button').style.display = 'none';
    document.getElementById('sendTestEmailBtn').disabled = false;
  }
}

document.querySelector('.nav-item[data-panel="email-setup"]').addEventListener('click', () => {
  setTimeout(initEmailSetup, 50);
});

// Key speichern
document.getElementById('saveW3fKeyBtn').addEventListener('click', () => {
  const key = document.getElementById('w3fKeyInput').value.trim();
  if (!key || key.length < 10) {
    showToast('Bitte einen gültigen Access Key eingeben.', 'error');
    return;
  }
  localStorage.setItem(W3F_KEY_STORAGE, key);
  document.getElementById('w3fKeySaved').style.display = 'block';
  document.getElementById('applyW3fKeyBtn').disabled = false;
  document.getElementById('applyHint').textContent = 'Key gespeichert – jetzt aktivieren!';
  showToast('Access Key gespeichert ✓');
});

// Key in main.js einbauen (wir patchen main.js dynamisch über den fetch-Aufruf)
document.getElementById('applyW3fKeyBtn').addEventListener('click', async () => {
  const key = localStorage.getItem(W3F_KEY_STORAGE) || DEFAULT_W3F_KEY || '';
  if (!key || key.length < 10) {
    showToast('Kein Key gespeichert.', 'error');
    return;
  }

  // Wir speichern den Key auch in einem zweiten localStorage-Slot,
  // der von main.js beim Laden abgerufen wird
  localStorage.setItem('sp_w3f_key_active', key);

  document.getElementById('w3fApplied').style.display = 'block';
  document.getElementById('w3fApplySection').querySelector('button').style.display = 'none';
  document.getElementById('sendTestEmailBtn').disabled = false;
  document.getElementById('applyHint').textContent = '';

  // Banner aktualisieren
  const banner = document.getElementById('emailStatusBanner');
  banner.style.background = '#f0fdf4';
  banner.style.borderColor = '#bbf7d0';
  banner.querySelector('div:first-child').textContent = '✅';
  banner.querySelector('[style*="font-weight:700"]').textContent = 'E-Mail-Versand ist aktiv!';
  banner.querySelector('[style*="font-size:0.85rem"]').innerHTML = 'Bestellungen werden an <strong>andreas.edler@bioenergie.at</strong> gesendet.';

  showToast('E-Mail-Versand aktiviert! ✅');
});

// Test-E-Mail senden
document.getElementById('sendTestEmailBtn').addEventListener('click', async () => {
  const key = localStorage.getItem('sp_w3f_key_active') || localStorage.getItem(W3F_KEY_STORAGE) || DEFAULT_W3F_KEY || '';
  if (!key || key.length < 10) {
    showToast('Bitte zuerst den Key aktivieren.', 'error');
    return;
  }

  const btn = document.getElementById('sendTestEmailBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Wird gesendet…';

  const result = document.getElementById('testEmailResult');

  try {
    const payload = {
      access_key:   key,
      subject:      '🌲 TEST: Neue Pellets-Bestellung – Steirer Pellets',
      from_name:    'Steirer Pellets Website (TEST)',
      replyto:      'andreas.edler@bioenergie.at',
      message:      [
        '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
        '🌲 TEST-BESTELLUNG (kein echter Kunde)',
        '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
        '',
        '📦 BESTELLUNG',
        '  Menge:         10 Tonnen (lose)',
        '  Lieferwoche:   KW15-2025',
        '  Preis/Tonne:   398 €/t',
        '  Gesamtpreis:   ca. 4.038 €',
        '',
        '📍 LIEFERADRESSE',
        '  Straße:        Testgasse 1',
        '  Ort:           8580 Köflach',
        '  Zufahrt:       Testnotiz',
        '',
        '👤 KONTAKT',
        '  Name:          Max Testmann',
        '  E-Mail:        test@beispiel.at',
        '  Telefon:       +43 664 000 000',
        '',
        '  Eingegangen:   ' + new Date().toLocaleString('de-AT'),
        '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━',
        '',
        'Dies ist eine automatische Test-Nachricht.',
        'Wenn Sie diese E-Mail erhalten, funktioniert der E-Mail-Versand korrekt!',
      ].join('\n'),
      Menge: '10 Tonnen (TEST)',
      Lieferwoche: 'KW15-2025',
      Gesamtpreis: 'ca. 4.038 €',
      Hinweis: '⚠️ DIES IST EINE TEST-BESTELLUNG',
    };

    const r = await fetch('https://api.web3forms.com/submit', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body:    JSON.stringify(payload)
    });
    const j = await r.json();

    if (j.success) {
      result.style.display = 'block';
      result.style.background = '#f0fdf4';
      result.style.border = '1px solid #bbf7d0';
      result.style.color = '#166534';
      result.innerHTML = '<i class="fas fa-check-circle"></i> <strong>Test-E-Mail erfolgreich gesendet!</strong> Bitte prüfen Sie Ihr Postfach unter andreas.edler@bioenergie.at (ggf. auch den Spam-Ordner).';
      showToast('Test-E-Mail gesendet! ✅');
    } else {
      throw new Error(j.message || 'Unbekannter Fehler');
    }
  } catch(err) {
    result.style.display = 'block';
    result.style.background = '#fef2f2';
    result.style.border = '1px solid #fca5a5';
    result.style.color = '#dc2626';
    result.innerHTML = `<i class="fas fa-exclamation-circle"></i> <strong>Fehler:</strong> ${err.message}<br/><span style="font-size:0.8rem">Bitte prüfen Sie, ob der Access Key korrekt ist und die E-Mail-Adresse auf web3forms.com bestätigt wurde.</span>`;
    showToast('Fehler beim Senden.', 'error');
  }

  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-paper-plane"></i> Test-E-Mail senden an andreas.edler@bioenergie.at';
});

// ══════════════════════════════════════════════
// RABATTCODES
// ══════════════════════════════════════════════

async function loadDiscounts() {
  const list = document.getElementById('discountList');
  if (!list) return;
  try {
    const r = await fetch('api-discounts.php', { headers: { 'X-CSRF-TOKEN': CSRF_TOKEN } });
    const j = await r.json();
    const codes = j.data || [];
    if (!codes.length) {
      list.innerHTML = '<p style="color:#999">Noch keine Rabattcodes erstellt.</p>';
      return;
    }
    list.innerHTML = `
      <table style="width:100%;border-collapse:collapse">
        <thead>
          <tr style="border-bottom:2px solid #e5e7eb;text-align:left">
            <th style="padding:8px 6px">Code</th>
            <th style="padding:8px 6px">Rabatt</th>
            <th style="padding:8px 6px">Gültig</th>
            <th style="padding:8px 6px">Eingelöst</th>
            <th style="padding:8px 6px">Status</th>
            <th style="padding:8px 6px">Aktionen</th>
          </tr>
        </thead>
        <tbody>
          ${codes.map(c => {
            const rabatt = c.discount_percent > 0 ? c.discount_percent + ' %' : c.discount_fixed + ' €';
            const von = c.valid_from || '–';
            const bis = c.valid_to || '–';
            const used = c.max_uses > 0 ? `${c.used_count}/${c.max_uses}` : `${c.used_count || 0}/∞`;
            const status = c.active
              ? '<span style="color:#16a34a;font-weight:600"><i class="fas fa-check-circle"></i> Aktiv</span>'
              : '<span style="color:#dc2626;font-weight:600"><i class="fas fa-times-circle"></i> Inaktiv</span>';
            return `<tr style="border-bottom:1px solid #f3f4f6">
              <td style="padding:10px 6px"><code style="background:#f3f4f6;padding:4px 8px;border-radius:4px;font-weight:700">${esc(c.code)}</code></td>
              <td style="padding:10px 6px">${rabatt}</td>
              <td style="padding:10px 6px;font-size:0.82rem">${esc(von)} – ${esc(bis)}</td>
              <td style="padding:10px 6px">${used}</td>
              <td style="padding:10px 6px">${status}</td>
              <td style="padding:10px 6px">
                <button onclick="toggleDiscount('${esc(c.code)}', ${!c.active})" style="background:none;border:none;cursor:pointer;color:#6b7280;font-size:0.85rem;margin-right:8px" title="${c.active ? 'Deaktivieren' : 'Aktivieren'}">
                  <i class="fas fa-${c.active ? 'pause' : 'play'}"></i>
                </button>
                <button onclick="deleteDiscount('${esc(c.code)}')" style="background:none;border:none;cursor:pointer;color:#dc2626;font-size:0.85rem" title="Löschen">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>`;
          }).join('')}
        </tbody>
      </table>`;
  } catch (e) {
    list.innerHTML = '<p style="color:#dc2626">Fehler beim Laden der Rabattcodes.</p>';
  }
}

function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

document.getElementById('discountForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const msg = document.getElementById('dcFormMsg');
  const code = document.getElementById('dc_code').value.trim().toUpperCase();
  const pct  = parseFloat(document.getElementById('dc_percent').value) || 0;
  const fix  = parseFloat(document.getElementById('dc_fixed').value) || 0;

  if (!code) { msg.innerHTML = '<span style="color:#dc2626">Bitte Code eingeben.</span>'; return; }
  if (pct <= 0 && fix <= 0) { msg.innerHTML = '<span style="color:#dc2626">Bitte Rabatt in % oder Fixbetrag angeben.</span>'; return; }

  try {
    const r = await fetch('api-discounts.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
      body: JSON.stringify({
        action:           'save',
        code,
        label:            document.getElementById('dc_label').value.trim() || code,
        discount_percent: pct,
        discount_fixed:   fix,
        max_uses:         parseInt(document.getElementById('dc_max').value) || 0,
        min_menge:        parseInt(document.getElementById('dc_min_menge').value) || 0,
        valid_from:       document.getElementById('dc_from').value || '',
        valid_to:         document.getElementById('dc_to').value || '',
        active:           true
      })
    });
    if (r.ok) {
      msg.innerHTML = '<span style="color:#16a34a"><i class="fas fa-check"></i> Rabattcode erstellt!</span>';
      document.getElementById('discountForm').reset();
      loadDiscounts();
      setTimeout(() => msg.innerHTML = '', 3000);
    } else {
      const j = await r.json();
      msg.innerHTML = `<span style="color:#dc2626">${j.error || 'Fehler'}</span>`;
    }
  } catch (e) {
    msg.innerHTML = '<span style="color:#dc2626">Netzwerkfehler.</span>';
  }
});

async function toggleDiscount(code, active) {
  await fetch('api-discounts.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
    body: JSON.stringify({ action: 'save', code, active })
  });
  loadDiscounts();
}

async function deleteDiscount(code) {
  if (!confirm(`Rabattcode "${code}" wirklich löschen?`)) return;
  await fetch('api-discounts.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
    body: JSON.stringify({ action: 'delete', code })
  });
  loadDiscounts();
}

// Initial laden wenn Panel sichtbar
const dcObserver = new MutationObserver(() => {
  const panel = document.getElementById('panel-rabattcodes');
  if (panel && panel.classList.contains('active')) loadDiscounts();
});
dcObserver.observe(document.querySelector('.admin-main'), { subtree: true, attributes: true, attributeFilter: ['class'] });
</script>
</body>
</html>
