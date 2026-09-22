<?php

namespace App\Rules;

use App\Domain\Configuration\SlotRules;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the shape of a Builder selection (docs/API.md § Selection format):
 *
 *   { "cpu": 3, "ram": { "id": 12, "quantity": 2 }, "storage": [4, { "id": 9, "quantity": 2 }] }
 *
 * Only shape, known slots and quantity limits are checked here. Whether the IDs exist is
 * not a validation error: unknown or inactive IDs are reported as `missing` (D-019).
 */
class ValidSelection implements ValidationRule
{
    public function __construct(private readonly SlotRules $slots) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            $fail('Cấu hình phải là một đối tượng dạng { "cpu": id, ... }.');

            return;
        }

        foreach ($value as $category => $entry) {
            $error = $this->slotError((string) $category, $entry);

            if ($error !== null) {
                $fail("{$category}: {$error}");
            }
        }
    }

    private function slotError(string $category, mixed $entry): ?string
    {
        if (! $this->slots->has($category)) {
            return 'loại linh kiện không hợp lệ.';
        }

        $several = $this->slots->acceptsSeveralProducts($category);
        $entries = is_array($entry) && array_is_list($entry) ? $entry : [$entry];

        if (! $several && count($entries) !== 1) {
            return 'chỉ được chọn một sản phẩm.';
        }

        $total = 0;

        foreach ($entries as $item) {
            [$id, $quantity] = is_array($item) ? [$item['id'] ?? null, $item['quantity'] ?? 1] : [$item, 1];

            if (! is_int($id) || $id < 1) {
                return 'mã sản phẩm phải là số nguyên dương.';
            }

            if (! is_int($quantity) || $quantity < 1) {
                return 'số lượng phải là số nguyên dương.';
            }

            if ($quantity > 1 && ! $this->slots->acceptsQuantity($category)) {
                return 'không được chọn số lượng lớn hơn 1.';
            }

            $total += $quantity;
        }

        if ($total > $this->slots->maxQuantity($category)) {
            return "tối đa {$this->slots->maxQuantity($category)}.";
        }

        return null;
    }
}
