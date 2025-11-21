<?php 
session_start();
header('Content-Type: text/html; charset=UTF-8'); 
require_once 'functions.php';

$sort = $_GET['sort'] ?? 'date_desc';
$allTags = getAllTags();
// Default to empty (show all) if not set.
$filterTags = $_GET['filter_tags'] ?? [];
$images = getImages($sort, []);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
    <title>画像ギャラリー</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">

</head>
<body>
    <!-- ローディング画面 -->
    <div id="loadingScreen" class="loading-screen">
        <div class="loading-spinner"></div>
        <p class="loading-text">読み込み中...</p>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
            <a href="admin.php" class="nav-btn submit-btn" style="position: absolute; top: 20px; right: 20px;text-decoration: none; padding: 10px 20px; font-size: 0.9rem; background: var(--primary);">管理画面</a>
        <?php endif; ?>
        <img src="img/logo.png" alt="" class="logo">

        <!-- フィルタリング・ソートフォーム -->

        <div class="admin-panel" style="margin-bottom: 40px;">
            <div class="admin-panel-header">
                <h2>検索</h2>
                <span class="toggle-icon">▼</span>
            </div>
            <div class="admin-panel-content">
                <form method="get" id="filterForm">
                    <div class="form-group">
                        <label>ソート</label>
                        <select name="sort" class="form-control" onchange="this.form.submit()">
                            <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>アップロード日 (新しい順)</option>
                            <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>アップロード日 (古い順)</option>
                            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>ファイル名 (昇順)</option>
                            <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>ファイル名 (降順)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>画像サイズ変更</label>
                        <input type="range" id="sizeSlider" min="1" max="10" value="4" class="form-control" style="padding: 0;">
                    </div>

                    <div class="form-group">
                        <label>タグフィルター</label>
                        <div class="match-mode-switch">
                            <label class="match-mode-option active" id="option-any">
                                <input type="radio" name="match_mode" value="any" checked>OR
                            </label>
                            <label class="match-mode-option" id="option-all">
                                <input type="radio" name="match_mode" value="all">AND
                            </label>
                        </div>
                        <div class="tag-checkboxes">
                            <?php foreach ($allTags as $tag): ?>
                                <label class="tag-label">
                                    <input type="checkbox" name="filter_tags[]" value="<?= htmlspecialchars($tag) ?>" <?= in_array($tag, $filterTags) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($tag) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="gallery" id="gallery" style="display: none;">
            <?php foreach ($images as $img): ?>
                <div class="gallery-item" data-thumb="<?= htmlspecialchars($img['thumb']) ?>" data-original="<?= htmlspecialchars($img['path']) ?>" data-filename="<?= htmlspecialchars($img['filename']) ?>" data-tags='<?= json_encode($img['tags'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                    <img src="<?= htmlspecialchars($img['thumb']) ?>" alt="<?= htmlspecialchars($img['filename']) ?>">
                    <div class="tag-overlay">
                        <?php foreach ($img['tags'] as $tag): ?>
                            <span class="tag-badge"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 画像拡大モーダル -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <img id="modalImage" src="" alt="">
            <button id="downloadBtn" class="download-btn">ダウンロード</button>
        </div>
    </div>

    <!-- パスワード入力モーダル -->
    <div id="passwordModal" class="modal">
        <div class="modal-content password-modal-content">
            <span class="close-password">&times;</span>
            <h2>ダウンロードにはパスワードが必要です</h2>
            <p class="notice">画像をダウンロードするには、パスワードを入力してください。</p>
            <div class="password-form">
                <input type="password" id="passwordInput" placeholder="パスワードを入力">
                <button id="passwordSubmit" class="submit-btn">ダウンロード</button>
            </div>
            <p id="errorMessage" class="error-message"></p>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
