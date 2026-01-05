<?php

namespace App\Services;

/**
 * FileUploadService
 * Single-responsibility helper to validate and store uploads securely.
 *
 * ORGANIZATION:
 * 1) Properties
 * 2) Public API (upload, deleteFile)
 * 3) Helpers (filename generation, error messages)
 */
class FileUploadService
{
    private $uploadBaseDir = __DIR__ . '/../../public/uploads/';

    // ===================================================================
    // SECTION 1: PUBLIC API
    // ===================================================================

    public function upload($file, $uploadDir, $maxSizeBytes = 5242880, $allowedMimes = null): array
    {
        $allowedMimes = $allowedMimes ?? ['image/jpeg', 'image/png', 'image/webp'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'File upload error: ' . $this->getUploadErrorMessage($file['error'])
            ];
        }

        if ($file['size'] > $maxSizeBytes) {
            $maxSizeMB = round($maxSizeBytes / 1048576, 1);
            return [
                'success' => false,
                'filename' => null,
                'error' => "File too large. Maximum size: {$maxSizeMB}MB"
            ];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMimeType = finfo_file($finfo, $file['tmp_name']);
        if (!in_array($realMimeType, $allowedMimes, true)) {
            return [
                'success' => false,
                'filename' => null,
                'error' => "Invalid file type. Allowed: JPEG, PNG, WebP. Got: {$realMimeType}"
            ];
        }

        $uniqueName = $this->generateUniqueFilename($file['name']);
        $fullUploadDir = $this->uploadBaseDir . $uploadDir . '/';

        if (!is_dir($fullUploadDir) && !mkdir($fullUploadDir, 0755, true)) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Failed to create upload directory'
            ];
        }

        $destination = $fullUploadDir . $uniqueName;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Failed to save file'
            ];
        }

        return [
            'success' => true,
            'filename' => $uniqueName,
            'error' => null
        ];
    }

    public function deleteFile($filename, $uploadDir): bool
    {
        if (empty($filename)) {
            return false;
        }

        $filePath = $this->uploadBaseDir . $uploadDir . '/' . $filename;
        $realPath = realpath($filePath);
        $realUploadDir = realpath($this->uploadBaseDir . $uploadDir);

        if (!$realPath || !$realUploadDir || strpos($realPath, $realUploadDir) !== 0) {
            return false; // Path traversal attempt or file missing
        }

        return @unlink($realPath);
    }

    // ===================================================================
    // SECTION 2: HELPERS
    // ===================================================================

    private function generateUniqueFilename($originalFilename): string
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $nameWithoutExt = pathinfo($originalFilename, PATHINFO_FILENAME);
        $cleanName = preg_replace('/[^a-z0-9_-]/i', '', $nameWithoutExt);
        $timestamp = time();
        $randomString = bin2hex(random_bytes(4));
        return "{$timestamp}_{$randomString}_{$cleanName}.{$extension}";
    }

    private function getUploadErrorMessage($errorCode): string
    {
        $messages = [
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'File exceeds php.ini upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds HTML form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File upload was incomplete',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temp directory missing',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by PHP extension'
        ];

        return $messages[$errorCode] ?? 'Unknown upload error';
    }
}
