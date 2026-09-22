<?php

namespace App\Support\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cloudinary through its REST Upload API with signed requests (D-037).
 *
 * The official PHP SDK v2/v3 requires Guzzle 7 while Laravel 13 ships Guzzle 8, so this class
 * uses Laravel's HTTP client directly. Callers only know ImageStorage, so switching back to the
 * SDK later changes this class only.
 */
class CloudinaryImageStorage implements ImageStorage
{
    private const API = 'https://api.cloudinary.com/v1_1';

    private const DELIVERY = 'https://res.cloudinary.com';

    /**
     * @param  array<string, string>  $presets  preset name => transformation
     */
    public function __construct(
        private readonly ?string $cloudName,
        private readonly ?string $apiKey,
        private readonly ?string $apiSecret,
        private readonly array $presets,
    ) {}

    /**
     * @param  array<string, string>  $presets
     */
    public static function fromUrl(?string $url, array $presets): self
    {
        $parts = $url ? parse_url($url) : [];

        return new self(
            $parts['host'] ?? null,
            isset($parts['user']) ? urldecode($parts['user']) : null,
            isset($parts['pass']) ? urldecode($parts['pass']) : null,
            $presets,
        );
    }

    public function upload(UploadedFile $file, string $folder): string
    {
        $this->ensureConfigured();
        $params = ['folder' => $folder, 'timestamp' => time()];

        try {
            $response = Http::timeout(30)
                ->attach('file', $file->getContent(), $file->getClientOriginalName())
                ->post($this->endpoint('image/upload'), $this->signed($params));
        } catch (Throwable $e) {
            throw new ImageStorageException('Không kết nối được dịch vụ ảnh, vui lòng thử lại.', previous: $e);
        }

        $publicId = $response->json('public_id');

        if ($response->failed() || ! is_string($publicId)) {
            Log::warning('Cloudinary upload failed', ['status' => $response->status(), 'error' => $response->json('error.message')]);

            throw new ImageStorageException('Không tải được ảnh lên, vui lòng thử lại.');
        }

        return $publicId;
    }

    /**
     * Best effort: a leftover image must not block replacing or deleting a product.
     */
    public function delete(string $publicId): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        try {
            $response = Http::timeout(15)->asForm()
                ->post($this->endpoint('image/destroy'), $this->signed(['public_id' => $publicId, 'timestamp' => time()]));

            if ($response->failed()) {
                Log::warning('Cloudinary delete failed', ['public_id' => $publicId, 'status' => $response->status()]);
            }
        } catch (Throwable $e) {
            Log::warning('Cloudinary delete failed', ['public_id' => $publicId, 'error' => $e->getMessage()]);
        }
    }

    public function url(?string $publicId, string $preset): ?string
    {
        if ($publicId === null || $this->cloudName === null) {
            return null;
        }

        return sprintf('%s/%s/image/upload/%s/%s', self::DELIVERY, $this->cloudName, $this->presets[$preset], $publicId);
    }

    /**
     * Cloudinary signature: SHA-1 of the sorted "key=value" pairs joined by "&", plus the secret.
     * file, api_key and resource_type are not signed.
     *
     * @param  array<string, string|int>  $params
     * @return array<string, string|int>
     */
    public function signed(array $params): array
    {
        ksort($params);
        $payload = implode('&', array_map(fn ($key, $value) => "{$key}={$value}", array_keys($params), $params));

        return [...$params, 'api_key' => $this->apiKey, 'signature' => sha1($payload.$this->apiSecret)];
    }

    private function endpoint(string $path): string
    {
        return self::API."/{$this->cloudName}/{$path}";
    }

    private function isConfigured(): bool
    {
        return $this->cloudName !== null && $this->apiKey !== null && $this->apiSecret !== null;
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new ImageStorageException('Chưa cấu hình dịch vụ ảnh (CLOUDINARY_URL).');
        }
    }
}
