<?php
// ============================================================
// FAKE BANK APP — DELIBERATELY VULNERABLE, FOR LOCAL LAB USE ONLY
// Do not expose this outside your own machine/lab network.
// ============================================================

// Simulate an authenticated session the same way a real bank app would:
// a session cookie that JS CAN read (no HttpOnly), so you can see exactly
// what the XSS is able to steal.
if (!isset($_COOKIE['session'])) {
    setcookie('session', 'sess_' . bin2hex(random_bytes(8)), 0, '/');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$session = $_COOKIE['session'];
$query = isset($_GET['q']) ? $_GET['q'] : '';

// ---- Stored XSS vector: a "support message" board ----
// Messages are saved to a file and re-displayed to EVERY visitor,
// unsanitized. No click/social-engineering needed once it's stored.
$messages_file = __DIR__ . '/messages.txt';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $entry = $_POST['message'] . "\n---\n";
    file_put_contents($messages_file, $entry, FILE_APPEND);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$stored_messages = file_exists($messages_file) ? file_get_contents($messages_file) : '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>FakeBank — Online Banking</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f0f2f5; margin:0; }
        .navbar { background:#0b3d91; color:white; padding:15px 30px; font-size:20px; font-weight:bold; }
        .container { max-width:700px; margin:40px auto; background:white; padding:30px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);}
        input[type=text] { width:70%; padding:8px; }
        button { padding:8px 16px; background:#0b3d91; color:white; border:none; border-radius:4px; cursor:pointer; }
        .result { margin-top:20px; padding:15px; background:#f9f9f9; border-left:4px solid #0b3d91; }
        .session-box { margin-top:30px; font-size:13px; color:#555; background:#fffbe6; padding:10px; border:1px dashed #d4ac0d; }
        .balance { font-size:24px; color:#0b3d91; margin-bottom:20px; }
    </style>
</head>
<body>
    <div class="navbar">FakeBank Online</div>
    <div class="container">
        <div class="balance">Balance: $4,210.56</div>

        <form method="GET">
            <label>Search transactions:</label><br><br>
            <input type="text" name="q" placeholder="e.g. Amazon" value="">
            <button type="submit">Search</button>
        </form>

        <?php if ($query !== ''): ?>
        <div class="result">
            Search results for: <?php echo $query; /* <-- VULNERABLE: no htmlspecialchars(), raw output */ ?>
            <br><br>
            <em>No matching transactions found.</em>
        </div>
        <?php endif; ?>

        <div class="session-box">
            <strong>Lab visibility panel (not part of a real bank UI):</strong><br>
            Your current session cookie is: <code><?php echo htmlspecialchars($session); ?></code><br>
            This is deliberately readable by JavaScript (no HttpOnly flag) so you can
            observe exactly what a cookie-stealing XSS payload captures.
        </div>

        <hr style="margin:30px 0;">

        <h3>Contact Support</h3>
        <form method="POST">
            <textarea name="message" rows="3" style="width:90%;" placeholder="Describe your issue..."></textarea><br><br>
            <button type="submit">Submit</button>
        </form>

        <h4>Recent support messages (visible to any visitor / "admin" who opens this page):</h4>
        <div class="result" style="white-space:pre-wrap;">
<?php echo $stored_messages; /* <-- VULNERABLE: stored, unsanitized, rendered for every visitor */ ?>
        </div>
    </div>
</body>
</html>
