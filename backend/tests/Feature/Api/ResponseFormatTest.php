<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Support\Http\ApiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ResponseFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_envelope(): void
    {
        $this->getJson('/api/status')
            ->assertOk()
            ->assertExactJson(['success' => true, 'data' => ['name' => config('app.name')]]);
    }

    public function test_unknown_route_returns_404_envelope(): void
    {
        $this->getJson('/api/does-not-exist')
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'message' => 'Không tìm thấy dữ liệu.', 'errors' => []]);
    }

    public function test_missing_model_returns_404_envelope(): void
    {
        Route::get('/api/test-products/{product}', fn (Product $product) => ApiResponse::success($product))
            ->middleware('api');

        $this->getJson('/api/test-products/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_validation_error_returns_422_with_field_errors(): void
    {
        Route::post('/api/test-validation', fn () => request()->validate(['name' => 'required']));

        $this->postJson('/api/test-validation', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Dữ liệu không hợp lệ.')
            ->assertJsonStructure(['errors' => ['name']]);
    }

    public function test_unauthenticated_admin_request_returns_401(): void
    {
        Route::get('/api/admin/test-protected', fn () => 'secret')->middleware(['api', 'auth:sanctum']);

        $this->getJson('/api/admin/test-protected')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Bạn cần đăng nhập để tiếp tục.');
    }

    public function test_unexpected_error_hides_details_when_debug_is_off(): void
    {
        config(['app.debug' => false]);
        Route::get('/api/test-crash', fn () => throw new RuntimeException('database password leaked'));

        $this->getJson('/api/test-crash')
            ->assertStatus(500)
            ->assertExactJson(['success' => false, 'message' => 'Đã xảy ra lỗi máy chủ.', 'errors' => []]);
    }

    public function test_validation_messages_are_in_vietnamese(): void
    {
        $this->getJson('/api/components')
            ->assertUnprocessable()
            ->assertJsonPath('errors.category.0', 'Trường loại linh kiện là bắt buộc.');
    }

    public function test_not_found_message_depends_on_the_model(): void
    {
        $this->getJson('/api/builds/nope')->assertNotFound()->assertJsonPath('message', 'Không tìm thấy cấu hình.');
        $this->getJson('/api/components/nope')->assertNotFound()->assertJsonPath('message', 'Không tìm thấy linh kiện.');
        $this->getJson('/api/categories/nope/filters')->assertNotFound()->assertJsonPath('message', 'Không tìm thấy loại linh kiện.');
    }

    public function test_malformed_json_body_returns_400(): void
    {
        $this->call('POST', '/api/builder/analyze', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            '{"selected": {"cpu": 1,}')
            ->assertStatus(400)
            ->assertJsonPath('message', 'Dữ liệu JSON gửi lên không hợp lệ.');
    }

    public function test_wrong_http_method_returns_405_envelope(): void
    {
        $this->getJson('/api/builder/analyze')->assertStatus(405)->assertJsonPath('success', false);
    }

    public function test_paginated_resource_collection_adds_pagination_meta(): void
    {
        Product::factory()->count(3)->create();

        $body = ApiResponse::success(JsonResource::collection(Product::query()->paginate(2)))->getData(true);

        $this->assertCount(2, $body['data']);
        $this->assertSame(['current_page' => 1, 'per_page' => 2, 'total' => 3, 'last_page' => 2], $body['meta']);
    }
}
