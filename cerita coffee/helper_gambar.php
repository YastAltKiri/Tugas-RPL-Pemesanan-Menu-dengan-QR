<?php


function muatGambarDariFile(string $tmpPath, string $mimeType)
{
    return match ($mimeType) {
        'image/jpeg' => imagecreatefromjpeg($tmpPath),
        'image/png' => imagecreatefrompng($tmpPath),
        'image/webp' => imagecreatefromwebp($tmpPath),
        'image/gif' => imagecreatefromgif($tmpPath),
        default => throw new Exception("Format gambar tidak didukung. Gunakan JPG, PNG, WEBP, atau GIF."),
    };
}

function validasiFileUpload(array $file): void
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $pesanError = match ($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                "Ukuran gambar melebihi batas maksimal server (cek 'upload_max_filesize' di php.ini).",
            UPLOAD_ERR_PARTIAL => "Upload gambar terputus di tengah jalan, coba lagi.",
            UPLOAD_ERR_NO_TMP_DIR => "Server tidak punya folder temporary untuk upload.",
            UPLOAD_ERR_CANT_WRITE => "Server gagal menulis file ke disk.",
            UPLOAD_ERR_EXTENSION => "Upload dihentikan oleh ekstensi PHP.",
            default => "Upload gambar gagal (kode error: {$file['error']}).",
        };
        throw new Exception($pesanError);
    }

    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxSize) {
        throw new Exception("Ukuran gambar maksimal 2MB.");
    }
}

// ===== MENU (JPG) =====

function simpanGambarMenu(array $file, string $idMenu): void
{
    if (!isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return;
    }
    validasiFileUpload($file);

    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception("File yang diupload bukan gambar yang valid.");
    }

    $image = muatGambarDariFile($file['tmp_name'], $imageInfo['mime']);
    if (!$image) {
        throw new Exception("Gagal memproses gambar.");
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $background = imagecreatetruecolor($width, $height);
    imagefill($background, 0, 0, imagecolorallocate($background, 255, 255, 255));
    imagecopy($background, $image, 0, 0, 0, 0, $width, $height);

    $uploadDir = __DIR__ . '/assets/uploads/menu';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    imagejpeg($background, $uploadDir . '/' . $idMenu . '.jpg', 85);

    imagedestroy($image);
    imagedestroy($background);
}

function hapusGambarMenu(string $idMenu): void
{
    $path = __DIR__ . '/assets/uploads/menu/' . $idMenu . '.jpg';
    if (file_exists($path)) {
        unlink($path);
    }
}

// ===== MEJA / QR (PNG, lossless) =====

function simpanGambarMeja(array $file, string $idMeja): void
{
    if (!isset($file['tmp_name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return;
    }
    validasiFileUpload($file);

    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception("File yang diupload bukan gambar yang valid.");
    }

    $image = muatGambarDariFile($file['tmp_name'], $imageInfo['mime']);
    if (!$image) {
        throw new Exception("Gagal memproses gambar.");
    }

    $uploadDir = __DIR__ . '/assets/uploads/meja';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    imagepng($image, $uploadDir . '/' . $idMeja . '.png', 0);

    imagedestroy($image);
}

function hapusGambarMeja(string $idMeja): void
{
    $path = __DIR__ . '/assets/uploads/meja/' . $idMeja . '.png';
    if (file_exists($path)) {
        unlink($path);
    }
}