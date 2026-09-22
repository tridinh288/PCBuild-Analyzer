<?php

namespace Tests\Unit\Support;

use App\Support\Hardware\SpecSchema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SpecSchemaTest extends TestCase
{
    private function errors(string $category, array $specs): array
    {
        $rules = $this->app->make(SpecSchema::class)->rulesFor($category);

        return Validator::make(['specs' => $specs], $rules)->errors()->toArray();
    }

    private function validCpu(array $overrides = []): array
    {
        return [
            'socket' => 'am5', 'cores' => 6, 'threads' => 12, 'tdp' => 65,
            'has_integrated_graphics' => true, 'includes_cooler' => true, 'performance_tier' => 60,
            ...$overrides,
        ];
    }

    public function test_valid_specs_pass(): void
    {
        $this->assertSame([], $this->errors('cpu', $this->validCpu()));
    }

    public function test_unknown_spec_key_is_rejected(): void
    {
        $this->assertArrayHasKey('specs', $this->errors('cpu', $this->validCpu(['color' => 'red'])));
    }

    public function test_missing_required_spec_is_rejected(): void
    {
        $specs = $this->validCpu();
        unset($specs['socket']);

        $this->assertArrayHasKey('specs.socket', $this->errors('cpu', $specs));
    }

    public function test_numbers_with_units_are_rejected(): void
    {
        $this->assertArrayHasKey('specs.tdp', $this->errors('cpu', $this->validCpu(['tdp' => '65W'])));
        $this->assertArrayHasKey('specs.tdp', $this->errors('cpu', $this->validCpu(['tdp' => '65'])));
    }

    public function test_booleans_must_be_real_booleans(): void
    {
        $this->assertArrayHasKey('specs.includes_cooler', $this->errors('cpu', $this->validCpu(['includes_cooler' => 1])));
    }

    public function test_enum_value_must_be_a_known_code(): void
    {
        $this->assertArrayHasKey('specs.socket', $this->errors('cpu', $this->validCpu(['socket' => 'AM5'])));
    }

    public function test_integer_bounds_are_enforced(): void
    {
        $this->assertArrayHasKey('specs.performance_tier', $this->errors('cpu', $this->validCpu(['performance_tier' => 101])));
    }

    public function test_enum_list_items_must_be_known_codes(): void
    {
        $errors = $this->errors('cooler', [
            'type' => 'air', 'supported_sockets' => ['am5', 'socket-x'], 'max_tdp' => 150, 'height_mm' => 155,
        ]);

        $this->assertArrayHasKey('specs.supported_sockets.1', $errors);
    }

    public function test_conditionally_required_spec(): void
    {
        $aio = ['type' => 'aio', 'supported_sockets' => ['am5'], 'max_tdp' => 250];

        $this->assertArrayHasKey('specs.radiator_mm', $this->errors('cooler', $aio));
        $this->assertSame([], $this->errors('cooler', [...$aio, 'radiator_mm' => 240]));
    }

    public function test_category_defaults_come_from_config(): void
    {
        $schema = $this->app->make(SpecSchema::class);

        $this->assertSame(['name' => 'Bo mạch chủ', 'sort_order' => 2], $schema->categoryDefaults('motherboard'));
    }
}
