<?php
/**
 * Regenerate all thumbnails with watermark
 * This script regenerates all existing thumbnails with the new watermark feature
 */

session_start();
require_once 'functions.php';
require_once 'config.php';

// Handle Login
if (isset($_POST['login_password'])) {
    if ($_POST['login_password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "パスワードが違います。";
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: regenerate_thumbnails.php');
    exit;
}

// Check Auth
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
        <title>サムネイル再生成 - ログイン</title>
        <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">

    </head>
    <body>
        <div class="container">
            <h1 class="title">サムネイル再生成 - ログイン</h1>
            <div class="password-form">
                <form method="post">
                    <input type="password" name="login_password" id="passwordInput" placeholder="パスワード" required>
                    <button type="submit" class="submit-btn">ログイン</button>
                </form>
                <?php if (isset($error)) echo '<p class="error-message">' . htmlspecialchars($error) . '</p>'; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle regeneration
$regenerated = [];
$errors = [];

if (isset($_POST['regenerate'])) {
    $watermarkPosition = $_POST['watermark_position'] ?? 'center';
    $watermarkOpacity = intval($_POST['watermark_opacity'] ?? 45);
    $watermarkSize = intval($_POST['watermark_size'] ?? 100);

    // Save settings
    saveWatermarkSettings([
        'position' => $watermarkPosition,
        'opacity' => $watermarkOpacity,
        'size' => $watermarkSize
    ]);
    
    $files = glob(IMG_DIR . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    
    foreach ($files as $file) {
        $filename = basename($file);
        $thumbPath = THUMB_DIR . $filename;
        
        try {
            if (createThumbnail($file, $thumbPath, $watermarkPosition, $watermarkOpacity, $watermarkSize)) {
                $regenerated[] = $filename;
            } else {
                $errors[] = $filename . ' (生成失敗)';
            }
        } catch (Exception $e) {
            $errors[] = $filename . ' (' . $e->getMessage() . ')';
        }
    }
}

// Load current settings for form defaults
$currentSettings = loadWatermarkSettings();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
    <title>サムネイル再生成</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
</head>
<body>
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">サムネイルを再生成中...<br>完了までしばらくお待ちください</div>
    </div>

    <div class="container">
        <div class="admin-header">
            <h1 class="title" style="margin-bottom: 0;">サムネイル再生成</h1>
            <a href="?logout=1" class="submit-btn logout-btn">ログアウト</a>
        </div>

        <div class="admin-panel">
            <div class="admin-panel-header">
                <h2>透かし設定</h2>
                <span class="toggle-icon">▼</span>
            </div>
            <div class="admin-panel-content active">
                <form method="post">

                    
                    <div class="form-group">
                        <label>透かしの位置</label>
                        <select name="watermark_position" class="form-control">
                            <option value="center" <?= $currentSettings['position'] === 'center' ? 'selected' : '' ?>>中央</option>
                            <option value="top-left" <?= $currentSettings['position'] === 'top-left' ? 'selected' : '' ?>>左上</option>
                            <option value="top-right" <?= $currentSettings['position'] === 'top-right' ? 'selected' : '' ?>>右上</option>
                            <option value="bottom-left" <?= $currentSettings['position'] === 'bottom-left' ? 'selected' : '' ?>>左下</option>
                            <option value="bottom-right" <?= $currentSettings['position'] === 'bottom-right' ? 'selected' : '' ?>>右下</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>透かしのサイズ (10-100%)</label>
                        <input type="range" name="watermark_size" min="10" max="100" value="<?= htmlspecialchars($currentSettings['size']) ?>" class="form-control range-input" oninput="this.nextElementSibling.textContent = this.value">
                        <span class="range-value"><?= htmlspecialchars($currentSettings['size']) ?></span>
                    </div>

                    <div class="form-group">
                        <label>透かしの不透明度 (0-100)</label>
                        <input type="range" name="watermark_opacity" min="0" max="100" value="<?= htmlspecialchars($currentSettings['opacity']) ?>" class="form-control range-input" oninput="this.nextElementSibling.textContent = this.value">
                        <span class="range-value"><?= htmlspecialchars($currentSettings['opacity']) ?></span>
                    </div>
                    
                    <button type="submit" name="regenerate" class="submit-btn" onclick="return confirmAndGenerate();">
                        サムネイルを再生成
                    </button>
                </form>
            </div>
        </div>

        <?php if (!empty($regenerated) || !empty($errors)): ?>
            <div class="result-box">
                <h2>再生成結果</h2>
                
                <?php if (!empty($regenerated)): ?>
                    <h3 class="success-heading">成功: <?= count($regenerated) ?>件</h3>
                    <ul class="success-list">
                        <?php foreach ($regenerated as $file): ?>
                            <li>✓ <?= htmlspecialchars($file) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <h3 class="error-heading">エラー: <?= count($errors) ?>件</h3>
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li>✗ <?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="nav-buttons">
            <a href="admin.php" class="submit-btn">管理画面に戻る</a>
            <a href="index.php" class="submit-btn">ギャラリーを見る</a>
        </div>
    </div>
    <script src="js/script.js?v=<?= time() ?>"></script>
    <script>
        function confirmAndGenerate() {
            if (confirm('すべてのサムネイルを再生成します。よろしいですか？')) {
                document.getElementById('loading-overlay').style.display = 'flex';
                return true;
            }
            return false;
        }
    </script>
</body>
</html>
