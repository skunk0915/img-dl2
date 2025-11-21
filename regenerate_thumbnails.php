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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>サムネイル再生成 - ログイン</title>
        <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">

    </head>
    <body>
        <div class="container">
            <h1 class="title">サムネイル再生成 - ログイン</h1>
            <div class="password-form" style="max-width: 400px; margin: 0 auto;">
                <form method="post">
                    <input type="password" name="login_password" id="passwordInput" placeholder="パスワード" required>
                    <button type="submit" class="submit-btn" style="margin-top: 20px; width: 100%;">ログイン</button>
                </form>
                <?php if (isset($error)) echo '<p class="error-message" style="text-align:center;">' . htmlspecialchars($error) . '</p>'; ?>
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
    $watermarkText = $_POST['watermark_text'] ?? 'NEOWNDROP';
    $watermarkPosition = $_POST['watermark_position'] ?? 'bottom-right';
    $watermarkOpacity = intval($_POST['watermark_opacity'] ?? 70);
    
    $files = glob(IMG_DIR . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    
    foreach ($files as $file) {
        $filename = basename($file);
        $thumbPath = THUMB_DIR . $filename;
        
        try {
            if (createThumbnail($file, $thumbPath, $watermarkText, $watermarkPosition, $watermarkOpacity)) {
                $regenerated[] = $filename;
            } else {
                $errors[] = $filename . ' (生成失敗)';
            }
        } catch (Exception $e) {
            $errors[] = $filename . ' (' . $e->getMessage() . ')';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>サムネイル再生成</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">

    <style>
        .result-box {
            background: var(--surface);
            padding: 20px;
            border-radius: 20px;
            margin: 20px 0;
        }
        .success-list, .error-list {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 10px;
        }
        .success-list li {
            color: #4caf50;
            padding: 5px 0;
        }
        .error-list li {
            color: var(--error);
            padding: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 class="title" style="margin-bottom: 0;">サムネイル再生成</h1>
            <a href="?logout=1" class="submit-btn" style="text-decoration: none; padding: 10px 20px; font-size: 0.9rem;">ログアウト</a>
        </div>

        <div class="admin-panel">
            <div class="admin-panel-header">
                <h2>透かし設定</h2>
                <span class="toggle-icon">▼</span>
            </div>
            <div class="admin-panel-content active">
                <form method="post">
                    <div class="form-group">
                        <label>透かしテキスト</label>
                        <input type="text" name="watermark_text" value="NEOWNDROP" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>透かしの位置</label>
                        <select name="watermark_position" class="form-control">
                            <option value="center">中央</option>
                            <option value="top-left">左上</option>
                            <option value="top-right">右上</option>
                            <option value="bottom-left">左下</option>
                            <option value="bottom-right">右下</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>透かしの不透明度 (0-100)</label>
                        <input type="range" name="watermark_opacity" min="0" max="100" value="70" class="form-control" style="padding: 0;" oninput="this.nextElementSibling.textContent = this.value">
                        <span style="display: inline-block; margin-left: 10px; color: var(--text-secondary);">70</span>
                    </div>
                    
                    <button type="submit" name="regenerate" class="submit-btn" onclick="return confirm('すべてのサムネイルを再生成します。よろしいですか？');">
                        サムネイルを再生成
                    </button>
                </form>
            </div>
        </div>

        <?php if (!empty($regenerated) || !empty($errors)): ?>
            <div class="result-box">
                <h2>再生成結果</h2>
                
                <?php if (!empty($regenerated)): ?>
                    <h3 style="color: #4caf50;">成功: <?= count($regenerated) ?>件</h3>
                    <ul class="success-list">
                        <?php foreach ($regenerated as $file): ?>
                            <li>✓ <?= htmlspecialchars($file) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <h3 style="color: var(--error); margin-top: 20px;">エラー: <?= count($errors) ?>件</h3>
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li>✗ <?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="admin.php" class="submit-btn" style="text-decoration: none; padding: 10px 20px;">管理画面に戻る</a>
            <a href="index.php" class="submit-btn" style="text-decoration: none; padding: 10px 20px; margin-left: 10px;">ギャラリーを見る</a>
        </div>
    </div>
    <script src="js/script.js?v=<?= time() ?>"></script>
</body>
</html>
