<?php

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Guards the internal consistency of config/hardware.php. Plain PHPUnit: no framework needed.
 */
class HardwareConfigTest extends TestCase
{
    private const TYPES = ['integer', 'boolean', 'enum', 'enum_list'];

    private const FILTERS = ['exact', 'min', 'max', 'boolean', 'contains'];

    private static function config(): array
    {
        return require __DIR__.'/../../../config/hardware.php';
    }

    public function test_categories_are_the_fixed_set(): void
    {
        $this->assertSame(
            ['cpu', 'motherboard', 'ram', 'gpu', 'storage', 'psu', 'case', 'cooler'],
            array_keys(self::config()['categories']),
        );
    }

    public function test_every_category_has_a_builder_slot(): void
    {
        $config = self::config();

        $this->assertEqualsCanonicalizing(array_keys($config['categories']), array_keys($config['slots']));
    }

    public static function specProvider(): iterable
    {
        foreach (self::config()['categories'] as $category => $definition) {
            foreach ($definition['specs'] as $key => $spec) {
                yield "{$category}.{$key}" => [$category, $key, $spec];
            }
        }
    }

    #[DataProvider('specProvider')]
    public function test_spec_definition_is_valid(string $category, string $key, array $spec): void
    {
        $enums = self::config()['enums'];

        $this->assertNotEmpty($spec['label'] ?? null, 'label is required');
        $this->assertContains($spec['type'], self::TYPES);

        if (in_array($spec['type'], ['enum', 'enum_list'], true)) {
            $this->assertArrayHasKey($spec['enum'] ?? '', $enums, 'enum must reference config enums');
        }

        if ($spec['type'] === 'integer') {
            $this->assertIsInt($spec['min'] ?? null);
            $this->assertIsInt($spec['max'] ?? null);
            $this->assertLessThan($spec['max'], $spec['min']);
        }

        if (isset($spec['filter'])) {
            $this->assertContains($spec['filter'], self::FILTERS);
            $this->assertSame($spec['filter'] === 'contains', $spec['type'] === 'enum_list',
                'contains filters are for enum_list specs only');
        }

        if (is_array($spec['required'] ?? null)) {
            $other = array_key_first($spec['required']['when']);
            $this->assertArrayHasKey($other, self::config()['categories'][$category]['specs']);
        }
    }

    public function test_scoring_weights_sum_to_one_for_every_purpose(): void
    {
        $weights = self::config()['scoring']['weights'];

        $this->assertEqualsCanonicalizing(['gaming', 'programming', 'workstation', 'general_use'], array_keys($weights));

        foreach ($weights as $purpose => $set) {
            $this->assertEqualsWithDelta(1.0, array_sum($set), 0.0001, "{$purpose} weights");
        }
    }

    public function test_psu_steps_are_ascending(): void
    {
        $steps = self::config()['power']['psu_steps'];
        $sorted = $steps;
        sort($sorted);

        $this->assertSame($sorted, $steps);
    }
}
