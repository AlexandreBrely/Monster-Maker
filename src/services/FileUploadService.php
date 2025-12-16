<?php

namespace App\Services;

/**
 * FileUploadService
 * 
 * WHAT IS THIS?
 * A centralized service for handling ALL file uploads in the application.
 * Before this service, we had duplicate upload code in 3 different controllers.
 * Now we have ONE place to maintain upload logic.
 * 
 * FOR BEGINNERS:
 * Think of a "service" as a helper class that does ONE specific job really well.
 * This service's job is: "safely upload files to the server"
 * 
 * WHY CENTRALIZE UPLOADS?
 * 1. DRY Principle (Don't Repeat Yourself) - write once, use everywhere
 * 2. Security - fix a bug in one place, it's fixed everywhere
 * 3. Consistency - all uploads follow the same rules
 * 4. Testability - easier to write tests for one service vs multiple controllers
 * 
 * SECURITY FEATURES:
 * - Validates MIME type (checks actual file content, not just extension)
 * - Size limits (default 5MB)
 * - Unique filenames with timestamps (prevents overwrites and conflicts)
 * - Path traversal protection (can't upload outside designated directory)
 * 
 * HOW TO USE:
 * $service = new FileUploadService();
 * $result = $service->upload($_FILES['image'], 'monsters');
 * if ($result['success']) {
 *     echo "Uploaded: " . $result['filename'];
 * }
 */
class FileUploadService
{
    /**
     * Default upload directory (relative to public folder)
     * 
     * FOR BEGINNERS:
     * __DIR__ is a PHP magic constant that means "the directory this file is in"
     * /../.. means "go up 2 directories"
     * So this resolves to: PROJECT_ROOT/public/uploads/
     * 
     * WHY PUBLIC/UPLOADS?
     * - Public folder is accessible via web browser (users can view uploaded images)
     * - Uploads folder keeps all user-uploaded files organized in one place
     */
    private $uploadBaseDir = __DIR__ . '/../../public/uploads/';

    /**
     * Upload a file with security validation
     * 
     * FOR BEGINNERS - WHAT HAPPENS WHEN YOU UPLOAD A FILE:
     * 1. User clicks "Choose File" and selects image.jpg
     * 2. Browser sends file to server (stored temporarily in /tmp/)
     * 3. PHP puts file info in $_FILES array
     * 4. This method validates and moves file to permanent location
     * 
     * PARAMETERS EXPLAINED:
     * @param array $file - The $_FILES array element (e.g., $_FILES['image'])
     *                      Contains: ['name', 'type', 'tmp_name', 'error', 'size']
     * 
     * @param string $uploadDir - Subdirectory name (e.g., 'monsters', 'avatars', 'lairs')
     *                            Final path will be: public/uploads/monsters/filename.jpg
     * 
     * @param int $maxSizeBytes - Maximum file size in bytes (default 5MB = 5,242,880 bytes)
     *                            Why bytes? File sizes are always measured in bytes on computers.
     * 
     * @param array $allowedMimes - Allowed MIME types (default: common image types)
     *                              MIME type = the ACTUAL file type (not just the extension)
     *                              Example: 'image/jpeg' is JPEG, 'image/png' is PNG
     * 
     * RETURN VALUE:
     * @return array - Always returns an array with these keys:
     *   [
     *     'success' => true/false (did upload work?),
     *     'filename' => 'uniquename.jpg' (if success) or null (if failed),
     *     'error' => 'error message' (if failed) or null (if success)
     *   ]
     * 
     * WHY THIS RETURN FORMAT?
     * - Consistent: caller always knows what to expect
     * - Informative: includes both result and error message
     * - Safe: never throws exceptions, always returns structured data
     */
    public function upload($file, $uploadDir, $maxSizeBytes = 5242880, $allowedMimes = null): array
    {
        // Set default allowed MIME types if caller didn't specify
        if ($allowedMimes === null) {
            $allowedMimes = [
                'image/jpeg',  // .jpg, .jpeg files
                'image/png',   // .png files
                'image/gif',   // .gif files (animated images)
                'image/webp'   // .webp files (modern, smaller than JPEG/PNG)
            ];
        }

        // === STEP 1: Check for upload errors ===
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'File upload error: ' . $this->getUploadErrorMessage($file['error'])
            ];
        }

        // === STEP 2: Validate file size ===
        if ($file['size'] > $maxSizeBytes) {
            $maxSizeMB = round($maxSizeBytes / 1048576, 1);
            return [
                'success' => false,
                'filename' => null,
                'error' => "File too large. Maximum size: {$maxSizeMB}MB"
            ];
        }

        // === STEP 3: Validate MIME type (security check) ===
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($realMimeType, $allowedMimes)) {
            return [
                'success' => false,
                'filename' => null,
                'error' => "Invalid file type. Allowed: JPEG, PNG, GIF, WebP. Got: {$realMimeType}"
            ];
        }

        // === STEP 4: Generate unique filename ===
        $uniqueName = $this->generateUniqueFilename($file['name']);

        // === STEP 5: Ensure upload directory exists ===
        $fullUploadDir = $this->uploadBaseDir . $uploadDir . '/';
        if (!is_dir($fullUploadDir)) {
            if (!mkdir($fullUploadDir, 0755, true)) {
                return [
                    'success' => false,
                    'filename' => null,
                    'error' => 'Failed to create upload directory'
                ];
            }
        }

        // === STEP 6: Move file from temp to permanent location ===
        $destination = $fullUploadDir . $uniqueName;
        
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => false,
                'filename' => null,
                'error' => 'Failed to save file'
            ];
        }

        // === STEP 7: Success! ===
        return [
            'success' => true,
            'filename' => $uniqueName,
            'error' => null
        ];
    }

    /**
     * Generate unique filename to prevent collisions
     * 
     * FOR BEGINNERS:
     * Pattern: timestamp_randomstring_originalname.ext
     * Example: 1702747200_a1b2c3d4_dragon.jpg
     * 
     * @param string $originalFilename Original filename from upload
     * @return string Unique filename safe for filesystem
     */
    private function generateUniqueFilename($originalFilename): string
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $nameWithoutExt = pathinfo($originalFilename, PATHINFO_FILENAME);
        $cleanName = preg_replace('/[^a-z0-9_-]/i', '', $nameWithoutExt);
        $timestamp = time();
        $randomString = bin2hex(random_bytes(4));
        $uniqueName = "{$timestamp}_{$randomString}_{$cleanName}.{$extension}";

        return $uniqueName;
    }

    /**
     * Get human-readable error message for upload error code
     */
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

    /**
     * Delete a file from uploads directory
     * 
     * FOR BEGINNERS - SECURITY:
     * This has path traversal protection to prevent hackers from deleting
     * system files or files outside the uploads directory.
     * 
     * @param string $filename Filename to delete
     * @param string $uploadDir Subdirectory (e.g., 'monsters', 'avatars')
     * @return bool True if deleted, false if failed or not found
     */
    public function deleteFile($filename, $uploadDir): bool
    {
        if (empty($filename)) {
            return false;
        }

        $filePath = $this->uploadBaseDir . $uploadDir . '/' . $filename;
        $realPath = realpath($filePath);
        $realUploadDir = realpath($this->uploadBaseDir . $uploadDir);

        if (!$realPath || strpos($realPath, $realUploadDir) !== 0) {
            return false; // Path traversal attempt or file doesn't exist
        }

        return @unlink($realPath);
    }
}
