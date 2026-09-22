<?php

namespace Tests\Unit\Support;

use App\Support\Images\CloudinaryImageStorage;
use App\Support\Images\ImageStorageException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The real Cloudinary client, with HTTP faked: no network (D-022).
 */
class CloudinaryImageStorageTest extends TestCase
{
    private const PRESETS = ['thumb' => 'c_fill,w_400,h_300,f_auto,q_auto', 'large' => 'c_limit,w_1000,f_auto,q_auto'];

    private function storage(?string $url = 'cloudinary://key123:secret456@demo-cloud'): CloudinaryImageStorage
    {
        return CloudinaryImageStorage::fromUrl($url, self::PRESETS);
    }

    public function test_urls_use_the_preset_transformation(): void
    {
        $this->assertSame(
            'https://res.cloudinary.com/demo-cloud/image/upload/c_fill,w_400,h_300,f_auto,q_auto/pcbuild/products/abc',
            $this->storage()->url('pcbuild/products/abc', 'thumb'),
        );
        $this->assertNull($this->storage()->url(null, 'thumb'));
        $this->assertNull($this->storage(null)->url('pcbuild/products/abc', 'thumb'));
    }

    public function test_signature_follows_the_cloudinary_algorithm(): void
    {
        $signed = $this->storage()->signed(['timestamp' => 1_700_000_000, 'folder' => 'pcbuild/products']);

        $this->assertSame(sha1('folder=pcbuild/products&timestamp=1700000000secret456'), $signed['signature']);
        $this->assertSame('key123', $signed['api_key']);
    }

    public function test_upload_posts_a_signed_request_and_returns_the_public_id(): void
    {
        Http::fake(['api.cloudinary.com/*' => Http::response(['public_id' => 'pcbuild/products/new'])]);

        $publicId = $this->storage()->upload(UploadedFile::fake()->createWithContent('a.png', 'bytes'), 'pcbuild/products');

        $this->assertSame('pcbuild/products/new', $publicId);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.cloudinary.com/v1_1/demo-cloud/image/upload'
            && str_contains($request->body(), 'name="signature"')
            && ! str_contains($request->body(), 'secret456'));
    }

    public function test_failed_upload_throws_a_user_facing_error(): void
    {
        Http::fake(['api.cloudinary.com/*' => Http::response(['error' => ['message' => 'Invalid signature']], 401)]);

        $this->expectException(ImageStorageException::class);

        $this->storage()->upload(UploadedFile::fake()->createWithContent('a.png', 'bytes'), 'pcbuild/products');
    }

    public function test_upload_without_configuration_is_refused_before_any_request(): void
    {
        Http::fake();

        try {
            $this->storage(null)->upload(UploadedFile::fake()->createWithContent('a.png', 'bytes'), 'pcbuild/products');
            $this->fail('Expected ImageStorageException');
        } catch (ImageStorageException $e) {
            $this->assertStringContainsString('CLOUDINARY_URL', $e->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_network_error_during_upload_becomes_a_user_facing_error(): void
    {
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $this->expectException(ImageStorageException::class);
        $this->expectExceptionMessage('Không kết nối được dịch vụ ảnh');

        $this->storage()->upload(UploadedFile::fake()->createWithContent('a.png', 'bytes'), 'pcbuild/products');
    }

    public function test_delete_without_configuration_does_nothing(): void
    {
        Http::fake();

        $this->storage(null)->delete('pcbuild/products/old');

        Http::assertNothingSent();
    }

    public function test_delete_failure_is_not_fatal(): void
    {
        Http::fake(['api.cloudinary.com/*' => Http::response([], 500)]);

        $this->storage()->delete('pcbuild/products/old');

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/image/destroy')
            && $request['public_id'] === 'pcbuild/products/old');
    }
}
