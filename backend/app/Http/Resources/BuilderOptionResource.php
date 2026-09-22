<?php

namespace App\Http\Resources;

use App\Domain\Compatibility\Results\CompatibilityResult;
use App\Services\BuilderOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BuilderOption
 */
class BuilderOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product' => new ProductResource($this->product),
            'status' => $this->status->value,
            'selected' => $this->selected,
            'issues' => array_map(fn (CompatibilityResult $issue) => $issue->toArray(), $this->issues),
        ];
    }
}
