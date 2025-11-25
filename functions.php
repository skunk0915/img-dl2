<?php
// functions.php

define('DATA_FILE', 'data.json');
define('IMG_DIR', 'upload/');
define('THUMB_DIR', 'upload/thumb/');
define('WATERMARK_FILE', 'img/wm.png');

define('WATERMARK_SETTINGS_FILE', 'watermark_settings.json');

/**
 * Load data from JSON file
 */
function loadData() {
    if (!file_exists(DATA_FILE)) {
        return [];
    }
    $json = file_get_contents(DATA_FILE);
    return json_decode($json, true) ?: [];
}

/**
 * Save data to JSON file
 */
function saveData($data) {
    file_put_contents(DATA_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Load watermark settings
 */
function loadWatermarkSettings() {
    $defaults = [
        'position' => 'center',
        'opacity' => 45,
        'size' => 100
    ];
    
    if (!file_exists(WATERMARK_SETTINGS_FILE)) {
        return $defaults;
    }
    
    $json = file_get_contents(WATERMARK_SETTINGS_FILE);
    $settings = json_decode($json, true);
    
    return array_merge($defaults, $settings ?: []);
}

/**
 * Save watermark settings
 */
function saveWatermarkSettings($settings) {
    file_put_contents(WATERMARK_SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Create a thumbnail image
 */
function createThumbnail($source, $destination, $watermarkPosition = 'bottom-right', $watermarkOpacity = 70, $watermarkSize = 30) {
    list($width, $height, $type) = getimagesize($source);
    
    $newWidth = $width;
    $newHeight = $height;
    // $maxSide = 300;

    // if ($width > $height) {
    //     $newWidth = $maxSide;
    //     $newHeight = intval($height * ($maxSide / $width));
    // } else {
    //     $newHeight = $maxSide;
    //     $newWidth = intval($width * ($maxSide / $height));
    // }

    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = @imagecreatefrompng($source);
            // Preserve transparency
            imagecolortransparent($thumb, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($source);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }

    imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Apply Watermark
    // Apply Watermark
    if (file_exists(WATERMARK_FILE)) {
        $watermark = @imagecreatefrompng(WATERMARK_FILE);
        $origWmWidth = imagesx($watermark);
        $origWmHeight = imagesy($watermark);

        // Calculate target size (e.g., 30% of thumbnail width)
        $scaleRatio = $watermarkSize / 100;
        $wmWidth = intval($newWidth * $scaleRatio);
        $wmHeight = intval($origWmHeight * ($wmWidth / $origWmWidth));

        // Resize watermark
        $resizedWatermark = imagecreatetruecolor($wmWidth, $wmHeight);
        imagealphablending($resizedWatermark, false);
        imagesavealpha($resizedWatermark, true);
        imagecopyresampled($resizedWatermark, $watermark, 0, 0, 0, 0, $wmWidth, $wmHeight, $origWmWidth, $origWmHeight);
        
        imagedestroy($watermark); // Free original watermark
        $watermark = $resizedWatermark; // Use resized one

        // Calculate position
        $x = 0;
        $y = 0;
        $padding = 10;

        switch ($watermarkPosition) {
            case 'top-left':
                $x = $padding;
                $y = $padding;
                break;
            case 'top-right':
                $x = $newWidth - $wmWidth - $padding;
                $y = $padding;
                break;
            case 'bottom-left':
                $x = $padding;
                $y = $newHeight - $wmHeight - $padding;
                break;
            case 'bottom-right':
                $x = $newWidth - $wmWidth - $padding;
                $y = $newHeight - $wmHeight - $padding;
                break;
            case 'center':
                $x = ($newWidth - $wmWidth) / 2;
                $y = ($newHeight - $wmHeight) / 2;
                break;
            default: // bottom-right default
                $x = $newWidth - $wmWidth - $padding;
                $y = $newHeight - $wmHeight - $padding;
        }

        // Apply opacity by modifying alpha channel pixel by pixel
        // This preserves transparency of both the watermark and the background
        if ($watermarkOpacity < 100) {
            // Iterate over every pixel
            for ($x_px = 0; $x_px < $wmWidth; $x_px++) {
                for ($y_px = 0; $y_px < $wmHeight; $y_px++) {
                    // Get current color and alpha
                    $colorIndex = imagecolorat($watermark, $x_px, $y_px);
                    $alpha = ($colorIndex >> 24) & 0x7F;
                    
                    // Calculate new alpha
                    // 127 is fully transparent, 0 is fully opaque
                    if ($alpha < 127) {
                        // Calculate alpha based on opacity
                        // new_alpha = 127 - ( (127 - current_alpha) * (opacity / 100) )
                        $newAlpha = 127 - intval((127 - $alpha) * ($watermarkOpacity / 100));
                        
                        // Re-allocate color with new alpha
                        $color = imagecolorsforindex($watermark, $colorIndex);
                        $newColor = imagecolorallocatealpha(
                            $watermark, 
                            $color['red'], 
                            $color['green'], 
                            $color['blue'], 
                            $newAlpha
                        );
                        
                        // Set pixel
                        imagesetpixel($watermark, $x_px, $y_px, $newColor);
                    }
                }
            }
        }

        // Copy watermark onto thumbnail
        // imagecopy respects alpha channel when destination has alpha blending enabled
        // We must enable blending on the destination so the watermark is composited OVER the background
        // instead of replacing the pixels (which would lose the background info).
        imagealphablending($thumb, true);
        imagecopy($thumb, $watermark, $x, $y, 0, 0, $wmWidth, $wmHeight);
        
        imagedestroy($watermark);
    }

    // Save with high compression (low quality)
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $destination, 60);
            break;
        case IMAGETYPE_PNG:
            // PNG quality is 0-9 (compression level), 9 is highest compression
            imagepng($thumb, $destination, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $destination);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($thumb, $destination, 60);
            break;
    }

    imagedestroy($thumb);
    imagedestroy($sourceImage);
    return true;
}

/**
 * Get list of images with sorting and filtering
 */
function getImages($sort = 'date_desc', $filterTags = []) {
    $data = loadData();
    $images = [];
    
    // Sync with file system (in case files were added/removed manually or before this system)
    $files = glob(IMG_DIR . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    foreach ($files as $file) {
        $filename = basename($file);
        if (!isset($data[$filename])) {
            // Add missing file to data
            $data[$filename] = [
                'tags' => [],
                'upload_date' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        // Check if thumbnail exists, if not create it
        if (!file_exists(THUMB_DIR . $filename)) {
            createThumbnail($file, THUMB_DIR . $filename);
        }
    }
    
    // Clean up data for deleted files
    foreach ($data as $filename => $info) {
        if (!file_exists(IMG_DIR . $filename)) {
            unset($data[$filename]);
        }
    }
    saveData($data); // Save synced data

    // Filter
    foreach ($data as $filename => $info) {
        if (!empty($filterTags)) {
            $hasTag = false;
            foreach ($filterTags as $tag) {
                if (in_array($tag, $info['tags'])) {
                    $hasTag = true;
                    break;
                }
            }
            if (!$hasTag) continue;
        }
        $images[] = [
            'filename' => $filename,
            'path' => IMG_DIR . $filename,
            'thumb' => THUMB_DIR . $filename,
            'tags' => $info['tags'],
            'upload_date' => $info['upload_date']
        ];
    }

    // Sort
    usort($images, function($a, $b) use ($sort) {
        switch ($sort) {
            case 'name_asc':
                return strnatcasecmp($a['filename'], $b['filename']);
            case 'name_desc':
                return strnatcasecmp($b['filename'], $a['filename']);
            case 'date_asc':
                return strcmp($a['upload_date'], $b['upload_date']);
            case 'date_desc':
            default:
                return strcmp($b['upload_date'], $a['upload_date']);
        }
    });

    return $images;
}

/**
 * Get all unique tags
 */
function getAllTags() {
    $data = loadData();
    $tags = [];
    foreach ($data as $info) {
        if (isset($info['tags'])) {
            foreach ($info['tags'] as $tag) {
                $tags[$tag] = true;
            }
        }
    }
    return array_keys($tags);
}

/**
 * Token Management
 */
define('TOKENS_FILE', 'tokens.json');

function loadTokens() {
    if (!file_exists(TOKENS_FILE)) {
        return [];
    }
    $json = file_get_contents(TOKENS_FILE);
    return json_decode($json, true) ?: [];
}

function saveTokens($tokens) {
    file_put_contents(TOKENS_FILE, json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function generateToken($filePath) {
    $tokens = loadTokens();
    
    // Clean expired tokens first
    $now = time();
    foreach ($tokens as $t => $data) {
        if ($data['expires'] < $now) {
            unset($tokens[$t]);
        }
    }
    
    // Generate new token
    $token = bin2hex(random_bytes(16));
    $expires = $now + (15 * 60); // 15 minutes
    
    $tokens[$token] = [
        'path' => $filePath,
        'expires' => $expires
    ];
    
    saveTokens($tokens);
    return $token;
}

function validateToken($token) {
    $tokens = loadTokens();
    $now = time();
    
    if (isset($tokens[$token])) {
        if ($tokens[$token]['expires'] >= $now) {
            return $tokens[$token]['path'];
        } else {
            // Expired
            unset($tokens[$token]);
            saveTokens($tokens);
        }
    }
    return false;
}
?>
