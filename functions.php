<?php
// functions.php

define('DATA_FILE', 'data.json');
define('IMG_DIR', 'img/');
define('THUMB_DIR', 'img/thumb/');

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
 * Create a thumbnail image with watermark
 */
function createThumbnail($source, $destination, $watermarkText = '© 2025', $watermarkPosition = 'bottom-right', $watermarkOpacity = 70) {
    list($width, $height, $type) = getimagesize($source);
    
    $newWidth = 0;
    $newHeight = 0;
    $maxSide = 300;

    if ($width > $height) {
        $newWidth = $maxSide;
        $newHeight = intval($height * ($maxSide / $width));
    } else {
        $newHeight = $maxSide;
        $newWidth = intval($width * ($maxSide / $height));
    }

    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($source);
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

    // Add watermark
    if (!empty($watermarkText)) {
        // Enable alpha blending for watermark
        imagealphablending($thumb, true);
        
        // Font settings
        $fontSize = 12;
        $fontPath = null;
        
        // Try to use a TrueType font if available
        $possibleFonts = [
            '/System/Library/Fonts/Helvetica.ttc',
            '/System/Library/Fonts/HelveticaNeue.ttc',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf'
        ];
        
        foreach ($possibleFonts as $font) {
            if (file_exists($font)) {
                $fontPath = $font;
                break;
            }
        }
        
        // Calculate text dimensions
        if ($fontPath) {
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $watermarkText);
            $textWidth = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);
        } else {
            // Fallback to built-in font
            $textWidth = imagefontwidth(3) * strlen($watermarkText);
            $textHeight = imagefontheight(3);
        }
        
        // Calculate position
        $padding = 10;
        switch ($watermarkPosition) {
            case 'bottom-left':
                $x = $padding;
                $y = $newHeight - $padding - $textHeight;
                break;
            case 'top-right':
                $x = $newWidth - $textWidth - $padding;
                $y = $padding;
                break;
            case 'top-left':
                $x = $padding;
                $y = $padding;
                break;
            case 'center':
                $x = ($newWidth - $textWidth) / 2;
                $y = ($newHeight - $textHeight) / 2;
                break;
            case 'bottom-right':
            default:
                $x = $newWidth - $textWidth - $padding;
                $y = $newHeight - $padding - $textHeight;
                break;
        }
        
        // Create semi-transparent background for better visibility
        $bgOpacity = intval($watermarkOpacity * 0.8 * 127 / 100);
        $bgColor = imagecolorallocatealpha($thumb, 0, 0, 0, 127 - $bgOpacity);
        imagefilledrectangle($thumb, $x - 5, $y - 3, $x + $textWidth + 5, $y + $textHeight + 3, $bgColor);
        
        // Add watermark text
        $textOpacity = intval((100 - $watermarkOpacity) * 127 / 100);
        $textColor = imagecolorallocatealpha($thumb, 255, 255, 255, $textOpacity);
        
        if ($fontPath) {
            imagettftext($thumb, $fontSize, 0, $x, $y + $textHeight, $textColor, $fontPath, $watermarkText);
        } else {
            imagestring($thumb, 3, $x, $y, $watermarkText, $textColor);
        }
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
?>
