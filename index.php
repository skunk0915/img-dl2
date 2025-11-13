<?php header('Content-Type: text/html; charset=UTF-8'); ?>
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

        <div class="gallery">
            <?php
            $imageDir = 'img/';
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (is_dir($imageDir)) {
                $files = scandir($imageDir);
                foreach ($files as $file) {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowedExts)) {
                        $imagePath = $imageDir . $file;
                        echo '<div class="gallery-item" data-image="' . htmlspecialchars($imagePath) . '" data-filename="' . htmlspecialchars($file) . '">';
                        echo '<img src="' . htmlspecialchars($imagePath) . '" alt="' . htmlspecialchars($file) . '">';
                        echo '</div>';
                    }
                }
            }
            ?>
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
