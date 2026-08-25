<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

function s3Client(): S3Client
{
    static $client = null;

    if ($client === null) {
        $region = getenv('AWS_REGION') ?: 'us-east-1';

        $client = new S3Client([
            'version' => 'latest',
            'region'  => $region,
        ]);
    }

    return $client;
}

function s3Bucket(): string
{
    $bucket = getenv('AWS_S3_BUCKET');

    if (!$bucket) {
        throw new RuntimeException('AWS_S3_BUCKET is not configured.');
    }

    return $bucket;
}

function s3UploadImage(array $file, string $folder): string
{
    if (
        !isset($file['tmp_name'], $file['name'], $file['error']) ||
        $file['error'] !== UPLOAD_ERR_OK
    ) {
        throw new RuntimeException('Invalid image upload.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Image must not exceed 5 MB.');
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!isset($allowedTypes[$mime])) {
        throw new RuntimeException(
            'Only JPG, PNG and WEBP images are allowed.'
        );
    }

    $extension = $allowedTypes[$mime];
    $folder = trim($folder, '/');
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $key = $folder . '/' . $filename;

    try {
        s3Client()->putObject([
            'Bucket'       => s3Bucket(),
            'Key'          => $key,
            'SourceFile'   => $file['tmp_name'],
            'ContentType'  => $mime,
            'CacheControl' => 'public, max-age=31536000',
        ]);
    } catch (AwsException $e) {
        error_log('S3 upload error: ' . $e->getMessage());
        throw new RuntimeException('Image upload failed.');
    }

    return $key;
}

function s3Delete(string $key): bool
{
    $key = ltrim($key, '/');

    if ($key === '') {
        return false;
    }

    try {
        s3Client()->deleteObject([
            'Bucket' => s3Bucket(),
            'Key'    => $key,
        ]);

        return true;
    } catch (AwsException $e) {
        error_log('S3 delete error: ' . $e->getMessage());
        return false;
    }
}

function s3ImageUrl(?string $key, int $minutes = 60): string
{
    if (!$key) {
        return '';
    }

    $key = ltrim($key, '/');

    try {
        $command = s3Client()->getCommand('GetObject', [
            'Bucket' => s3Bucket(),
            'Key'    => $key,
        ]);

        $request = s3Client()->createPresignedRequest(
            $command,
            '+' . $minutes . ' minutes'
        );

        return (string) $request->getUri();
    } catch (Throwable $e) {
        error_log('S3 URL error: ' . $e->getMessage());
        return '';
    }
}
