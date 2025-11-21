#!/usr/bin/env php
<?php
/**
 * 画像ディレクトリ移行スクリプト
 * img/ から upload/ へ既存の画像とサムネイルを移行します
 */

echo "=== 画像ディレクトリ移行スクリプト ===\n\n";

// ディレクトリ定義
$oldImgDir = 'img/';
$oldThumbDir = 'img/thumb/';
$newImgDir = 'upload/';
$newThumbDir = 'upload/thumb/';

// 新しいディレクトリを作成
echo "1. 新しいディレクトリを作成中...\n";
if (!file_exists($newImgDir)) {
    mkdir($newImgDir, 0755, true);
    echo "   ✓ {$newImgDir} を作成しました\n";
} else {
    echo "   - {$newImgDir} は既に存在します\n";
}

if (!file_exists($newThumbDir)) {
    mkdir($newThumbDir, 0755, true);
    echo "   ✓ {$newThumbDir} を作成しました\n";
} else {
    echo "   - {$newThumbDir} は既に存在します\n";
}

// .gitkeep ファイルを作成
file_put_contents($newImgDir . '.gitkeep', '');
file_put_contents($newThumbDir . '.gitkeep', '');
echo "   ✓ .gitkeep ファイルを作成しました\n\n";

// 画像ファイルを移行
echo "2. 画像ファイルを移行中...\n";
$imageFiles = glob($oldImgDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
$movedCount = 0;
$skippedCount = 0;

foreach ($imageFiles as $oldPath) {
    $filename = basename($oldPath);
    $newPath = $newImgDir . $filename;
    
    if (file_exists($newPath)) {
        echo "   - スキップ: {$filename} (既に存在)\n";
        $skippedCount++;
        continue;
    }
    
    if (rename($oldPath, $newPath)) {
        echo "   ✓ 移行: {$filename}\n";
        $movedCount++;
    } else {
        echo "   ✗ エラー: {$filename} の移行に失敗\n";
    }
}

echo "\n   画像ファイル: {$movedCount} 個移行, {$skippedCount} 個スキップ\n\n";

// サムネイルファイルを移行
echo "3. サムネイルファイルを移行中...\n";
$thumbFiles = glob($oldThumbDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
$movedThumbCount = 0;
$skippedThumbCount = 0;

foreach ($thumbFiles as $oldPath) {
    $filename = basename($oldPath);
    $newPath = $newThumbDir . $filename;
    
    if (file_exists($newPath)) {
        echo "   - スキップ: {$filename} (既に存在)\n";
        $skippedThumbCount++;
        continue;
    }
    
    if (rename($oldPath, $newPath)) {
        echo "   ✓ 移行: {$filename}\n";
        $movedThumbCount++;
    } else {
        echo "   ✗ エラー: {$filename} の移行に失敗\n";
    }
}

echo "\n   サムネイル: {$movedThumbCount} 個移行, {$skippedThumbCount} 個スキップ\n\n";

// 完了メッセージ
echo "=== 移行完了 ===\n";
echo "画像ファイル: {$movedCount} 個\n";
echo "サムネイル: {$movedThumbCount} 個\n\n";

if ($movedCount > 0 || $movedThumbCount > 0) {
    echo "次のステップ:\n";
    echo "1. functions.php の定数を更新してください\n";
    echo "2. download.php のパス検証を更新してください\n";
    echo "3. 動作確認後、古い img/ ディレクトリを削除できます\n";
}

echo "\n";
?>
