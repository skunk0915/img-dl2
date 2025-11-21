<?php 
header('Content-Type: text/html; charset=UTF-8'); 
require_once 'functions.php';

$sort = $_GET['sort'] ?? 'date_desc';
$allTags = getAllTags();
// Default to empty (show all) if not set.
$filterTags = $_GET['filter_tags'] ?? [];
$images = getImages($sort, $filterTags);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>画像ギャラリー</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <img src="logo.png" alt="" class="logo">

        <!-- フィルタリング・ソートフォーム -->
        <div class="admin-panel" style="margin-bottom: 40px;">
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
                    <input type="range" id="sizeSlider" min="100" max="500" value="250" class="form-control" style="padding: 0;">
                </div>

                <div class="form-group">
                    <label>タグフィルター</label>
                    <div class="tag-checkboxes">
                        <?php foreach ($allTags as $tag): ?>
                            <label class="tag-label">
                                <input type="checkbox" name="filter_tags[]" value="<?= htmlspecialchars($tag) ?>" <?= in_array($tag, $filterTags) ? 'checked' : '' ?> onchange="this.form.submit()">
                                <?= htmlspecialchars($tag) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="gallery">
            <?php foreach ($images as $img): ?>
                <div class="gallery-item" data-thumb="<?= htmlspecialchars($img['thumb']) ?>" data-original="<?= htmlspecialchars($img['path']) ?>" data-filename="<?= htmlspecialchars($img['filename']) ?>">
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
