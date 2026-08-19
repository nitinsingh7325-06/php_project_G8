<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Local uploads with optional Google Cloud Storage.
 */
class StorageService
{
    public function storeProfileImage(array $file, int $userId): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed, true)) {
            return null;
        }
        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return null;
        }

        $ext = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $name = 'profile_' . $userId . '_' . time() . '.' . $ext;
        $dir = upload_path('profiles');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }

        $relative = 'uploads/profiles/' . $name;

        if (env('USE_CLOUD_STORAGE', false) && env('GCP_STORAGE_BUCKET') && class_exists(\Google\Cloud\Storage\StorageClient::class)) {
            try {
                $config = [];
                if ($creds = env('GCP_CREDENTIALS_PATH')) {
                    $config['keyFilePath'] = $creds;
                }
                $storage = new \Google\Cloud\Storage\StorageClient($config);
                $bucket = $storage->bucket(env('GCP_STORAGE_BUCKET'));
                $object = $bucket->upload(fopen($dest, 'r'), [
                    'name' => 'profiles/' . $name,
                    'predefinedAcl' => 'publicRead',
                ]);
                return $object->info()['mediaLink'] ?? ('https://storage.googleapis.com/' . env('GCP_STORAGE_BUCKET') . '/profiles/' . $name);
            } catch (\Throwable $e) {
                log_message('warning', 'GCS upload failed', ['error' => $e->getMessage()]);
            }
        }

        return $relative;
    }
}
