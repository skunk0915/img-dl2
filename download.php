<?php
require_once 'config.php';

// JSONレスポンスヘッダー
header('Content-Type: application/json; charset=UTF-8');

// POSTリクエストの確認
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '不正なリクエストです'
    ]);
    exit;
}

// パラメータの取得
$password = $_POST['password'] ?? '';
$imagePath = $_POST['image'] ?? '';
$filename = $_POST['filename'] ?? '';

// パスワードの検証
if (empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'パスワードを入力してください'
    ]);
    exit;
}

// パスワードチェック
if ($password !== DOWNLOAD_PASSWORD) {
    echo json_encode([
        'success' => false,
        'message' => 'パスワードが正しくありません'
    ]);
    exit;
}

// 画像パスの検証
if (empty($imagePath) || empty($filename)) {
    echo json_encode([
        'success' => false,
        'message' => '画像が指定されていません'
    ]);
    exit;
}

// ディレクトリトラバーサル対策
$imagePath = str_replace(['../', '..\\'], '', $imagePath);

// ファイルの存在確認
if (!file_exists($imagePath)) {
    echo json_encode([
        'success' => false,
        'message' => 'ファイルが見つかりません'
    ]);
    exit;
}

// 許可された拡張子の確認
$allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExts)) {
    echo json_encode([
        'success' => false,
        'message' => '許可されていないファイル形式です'
    ]);
    exit;
}

// imgディレクトリ内のファイルのみ許可
if (strpos(realpath($imagePath), realpath('img/')) !== 0) {
    echo json_encode([
        'success' => false,
        'message' => '不正なファイルパスです'
    ]);
    exit;
}

// パスワードが正しい場合、ダウンロードURLを返す
echo json_encode([
    'success' => true,
    'message' => 'ダウンロードを開始します',
    'download_url' => $imagePath
]);
exit;
?>
