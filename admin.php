<?php
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
    header('Location: admin.php');
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
        <title>管理画面ログイン</title>
        <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">

    </head>
    <body>
        <div class="container">
            <h1 class="title">管理画面ログイン</h1>
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

// Handle Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    $tagsInput = $_POST['tags'] ?? '';
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $filename = $file['name'];
            if (file_exists(IMG_DIR . $filename)) {
                $filename = time() . '_' . $filename;
            }
            
            $dest = IMG_DIR . $filename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                createThumbnail($dest, THUMB_DIR . $filename, 'NEOWNDROP', 'center', 70);
                
                $data = loadData();
                $tags = array_filter(array_map('trim', explode(',', $tagsInput)));
                $data[$filename] = [
                    'tags' => array_values($tags),
                    'upload_date' => date('Y-m-d H:i:s')
                ];
                saveData($data);
                
                $message = "アップロードしました: " . htmlspecialchars($filename);
            } else {
                $error = "ファイルの移動に失敗しました。";
            }
        } else {
            $error = "許可されていないファイル形式です。";
        }
    } else {
        $error = "アップロードエラー: " . $file['error'];
    }
}

// Handle Delete
if (isset($_POST['delete_file'])) {
    $filename = $_POST['delete_file'];
    $filename = basename($filename);
    
    if (file_exists(IMG_DIR . $filename)) {
        unlink(IMG_DIR . $filename);
    }
    if (file_exists(THUMB_DIR . $filename)) {
        unlink(THUMB_DIR . $filename);
    }
    
    $data = loadData();
    if (isset($data[$filename])) {
        unset($data[$filename]);
        saveData($data);
    }
    
    $message = "削除しました: " . htmlspecialchars($filename);
}

// Handle Tag Update
if (isset($_POST['update_tags'])) {
    $filename = $_POST['filename'];
    $tagsInput = $_POST['tags'];
    
    $data = loadData();
    if (isset($data[$filename])) {
        $tags = array_filter(array_map('trim', explode(',', $tagsInput)));
        $data[$filename]['tags'] = array_values($tags);
        saveData($data);
        $message = "タグを更新しました: " . htmlspecialchars($filename);
    }
}

// Get Images
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
    <title>画像管理画面</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">

    <style>
        .admin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .admin-panel { background: var(--surface); padding: 20px; border-radius: 20px; margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block;  color: var(--text-secondary); }
        .form-control { width: 100%; padding: 10px; border-radius: 10px; border: none; background: var(--bg-dark); color: var(--text-primary); }
        .tag-checkboxes { display: flex; flex-wrap: wrap; gap: 10px; }
        .tag-label { background: var(--bg-dark); padding: 5px 10px; border-radius: 15px; cursor: pointer; user-select: none; }
        .tag-label input { margin-right: 5px; }
        .admin-gallery-item { position: relative; cursor: pointer; }
        .delete-btn { z-index: 10; }
        .item-info { padding: 10px; font-size: 0.8rem; color: var(--text-secondary); }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-header">
            <h1 class="title" style="margin-bottom: 0;">管理画面</h1>
            <div style="display: flex; gap: 10px;">
                <a href="index.php" class="submit-btn" style="text-decoration: none; padding: 10px 20px; font-size: 0.9rem; background: var(--primary);">メイン画面</a>
                <a href="?logout=1" class="submit-btn" style="text-decoration: none; padding: 10px 20px; font-size: 0.9rem;">ログアウト</a>
            </div>
        </div>

        <?php if (isset($message)) echo '<p style="color: #4caf50; margin-bottom: 20px;">' . $message . '</p>'; ?>
        <?php if (isset($error)) echo '<p class="error-message" style="margin-bottom: 20px;">' . $error . '</p>'; ?>

        <div class="admin-panel">
            <div class="admin-panel-header">
                <h2>新規アップロード</h2>
                <span class="toggle-icon">▼</span>
            </div>
            <div class="admin-panel-content">
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>画像ファイル</label>
                        <input type="file" name="image" accept="image/*" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>タグ (カンマ区切り)</label>
                        <input type="text" name="tags" placeholder="例: 風景, 夏, 海" class="form-control">
                    </div>
                    <button type="submit" class="submit-btn">アップロード</button>
                </form>
            </div>
        </div>

        <div class="admin-panel">
            <div class="admin-panel-header">
                <h2>一覧・検索</h2>
                <span class="toggle-icon">▼</span>
            </div>
            <div class="admin-panel-content">
                <form method="get">
                    <div class="form-group">
                        <label>画像サイズ変更</label>
                        <input type="range" id="sizeSlider" min="1" max="10" value="4" class="form-control" style="padding: 0;">
                    </div>
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
                        <label>タグフィルター</label>
                        <div class="match-mode-switch">
                            <label class="match-mode-option active" id="option-any-admin">
                                <input type="radio" name="match_mode" value="any" checked>OR
                            </label>
                            <label class="match-mode-option" id="option-all-admin">
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

        <div class="gallery">
            <?php foreach ($images as $img): ?>
                <div class="gallery-item admin-gallery-item" data-tags='<?= json_encode($img['tags'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>' onclick="openEditModal('<?= htmlspecialchars($img['path']) ?>', '<?= htmlspecialchars($img['filename']) ?>', '<?= htmlspecialchars(implode(', ', $img['tags'])) ?>')">
                    <img src="<?= htmlspecialchars($img['path']) ?>" alt="<?= htmlspecialchars($img['filename']) ?>">
                    
                    <div class="tag-overlay">
                        <?php foreach ($img['tags'] as $tag): ?>
                            <span class="tag-badge"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <form method="post" onsubmit="event.stopPropagation(); return confirm('本当に削除しますか？');" style="display:inline;">
                        <input type="hidden" name="delete_file" value="<?= htmlspecialchars($img['filename']) ?>">
                        <button type="submit" class="delete-btn" onclick="event.stopPropagation();">削除</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content admin-modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <img id="editModalImage" src="" alt="" class="admin-modal-image">
            <form method="post" class="admin-modal-form">
                <input type="hidden" name="filename" id="editModalFilename">
                <input type="hidden" name="update_tags" value="1">
                <input type="text" name="tags" id="editModalTags" class="form-control" placeholder="タグ (カンマ区切り)">
                <button type="submit" class="submit-btn">更新</button>
            </form>
        </div>
    </div>


    <script src="js/script.js?v=<?= time() ?>"></script>
</body>
</html>
