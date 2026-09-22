<?php

namespace App\Console\Commands;

use App\Models\Build;
use App\Models\BuildItem;
use App\Models\Product;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Checks that the current database (MySQL locally, TiDB in production) behaves as
 * the code expects: JSON spec filters, the computed total price, and foreign keys
 * (docs/DATABASE.md § 5). Each SQL result is compared with the same filter done in PHP.
 *
 * Needs seeded data. Read-only except for one insert inside a rolled-back transaction.
 */
class VerifyDatabaseCommand extends Command
{
    protected $signature = 'app:verify-database';

    protected $description = 'Verify JSON queries, computed totals and foreign keys on the current database';

    private int $failures = 0;

    public function handle(): int
    {
        $this->line('Connection: '.DB::connection()->getName().' — '.DB::scalar('SELECT VERSION()'));

        if (Product::count() === 0 || Build::count() === 0) {
            $this->error('No data. Run: php artisan migrate --seed');

            return self::FAILURE;
        }

        $products = Product::with('category')->get();
        $ofCategory = fn (string $slug) => $products->filter(fn (Product $p) => $p->category->slug === $slug);

        $this->compare('Enum filter (cpu socket = am5)',
            fn () => $this->inCategory('cpu')->where('specs->socket', 'am5'),
            $ofCategory('cpu')->filter(fn ($p) => $p->specs['socket'] === 'am5'));

        $this->compare('Numeric min (cpu cores >= 8)',
            fn () => $this->inCategory('cpu')->where('specs->cores', '>=', 8),
            $ofCategory('cpu')->filter(fn ($p) => $p->specs['cores'] >= 8));

        $this->compare('Numeric max (gpu length_mm <= 300)',
            fn () => $this->inCategory('gpu')->where('specs->length_mm', '<=', 300),
            $ofCategory('gpu')->filter(fn ($p) => $p->specs['length_mm'] <= 300));

        $this->compare('Numeric min, multi-digit (storage capacity_gb >= 1000)',
            fn () => $this->inCategory('storage')->where('specs->capacity_gb', '>=', 1000),
            $ofCategory('storage')->filter(fn ($p) => $p->specs['capacity_gb'] >= 1000));

        $this->compare('Boolean (cpu has_integrated_graphics = true)',
            fn () => $this->inCategory('cpu')->where('specs->has_integrated_graphics', true),
            $ofCategory('cpu')->filter(fn ($p) => $p->specs['has_integrated_graphics'] === true));

        $this->compare('Boolean (cpu has_integrated_graphics = false)',
            fn () => $this->inCategory('cpu')->where('specs->has_integrated_graphics', false),
            $ofCategory('cpu')->filter(fn ($p) => $p->specs['has_integrated_graphics'] === false));

        $this->compare('Array contains (cooler supported_sockets has lga1851)',
            fn () => $this->inCategory('cooler')->whereJsonContains('specs->supported_sockets', 'lga1851'),
            $ofCategory('cooler')->filter(fn ($p) => in_array('lga1851', $p->specs['supported_sockets'], true)));

        $this->checkTotalPrice();
        $this->checkForeignKey();

        if ($this->failures > 0) {
            $this->error("{$this->failures} check(s) failed.");

            return self::FAILURE;
        }

        $this->info('All checks passed.');

        return self::SUCCESS;
    }

    private function inCategory(string $slug): Builder
    {
        return Product::query()->whereRelation('category', 'slug', $slug);
    }

    /**
     * @param  Closure(): Builder  $query
     * @param  iterable<Product>  $expected
     */
    private function compare(string $label, Closure $query, iterable $expected): void
    {
        $expectedIds = collect($expected)->pluck('id')->sort()->values()->all();

        try {
            $actualIds = $query()->pluck('id')->sort()->values()->all();
        } catch (Throwable $e) {
            $this->report($label, false, $e->getMessage());

            return;
        }

        $this->report($label, $actualIds === $expectedIds,
            sprintf('expected %d row(s), got %d', count($expectedIds), count($actualIds)));
    }

    private function checkTotalPrice(): void
    {
        $label = 'Computed total price (subquery, sort, having, paginate)';

        try {
            $expected = Build::with('items.product')->get()
                ->mapWithKeys(fn (Build $b) => [$b->id => $b->items->sum(fn ($i) => $i->product->price * $i->quantity)]);
            $threshold = (int) $expected->median();

            $page = Build::query()->withTotalPrice()
                ->having('total_price', '>=', $threshold)
                ->orderBy('total_price')
                ->paginate(3);

            $totalsMatch = $page->getCollection()->every(fn (Build $b) => (int) $b->total_price === $expected[$b->id]);
            $countMatches = $page->total() === $expected->filter(fn ($t) => $t >= $threshold)->count();
            $sorted = $page->getCollection()->pluck('total_price')->map(fn ($t) => (int) $t)->all();
            $isSorted = $sorted === collect($sorted)->sort()->values()->all();

            $this->report($label, $totalsMatch && $countMatches && $isSorted,
                "totals match: {$this->yesNo($totalsMatch)}, paginator total: {$this->yesNo($countMatches)}, sorted: {$this->yesNo($isSorted)}");
        } catch (Throwable $e) {
            $this->report($label, false, $e->getMessage());
        }
    }

    private function checkForeignKey(): void
    {
        $label = 'Foreign key enforced (build_items.product_id)';
        $enforced = false;

        DB::beginTransaction();

        try {
            BuildItem::create(['build_id' => Build::value('id'), 'product_id' => Product::max('id') + 1_000_000]);
        } catch (QueryException) {
            $enforced = true;
        } finally {
            DB::rollBack();
        }

        $this->report($label, $enforced, $enforced ? 'insert rejected' : 'insert with unknown product was accepted');
    }

    private function report(string $label, bool $passed, string $detail): void
    {
        $passed || $this->failures++;
        $this->line(sprintf('  %s %s <fg=gray>(%s)</>', $passed ? '<fg=green>PASS</>' : '<fg=red>FAIL</>', $label, $detail));
    }

    private function yesNo(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }
}
