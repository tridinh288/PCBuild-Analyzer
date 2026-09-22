<?php

namespace Tests\Unit\Domain\Compatibility;

use App\Domain\Compatibility\Specifications\AtLeast;
use App\Domain\Compatibility\Specifications\FitsWithin;
use App\Domain\Compatibility\Specifications\ValueInSet;
use PHPUnit\Framework\TestCase;

class SpecificationTest extends TestCase
{
    public function test_value_in_set_is_strict(): void
    {
        $spec = new ValueInSet(['am4', 'am5']);

        $this->assertTrue($spec->isSatisfiedBy('am5'));
        $this->assertFalse($spec->isSatisfiedBy('AM5'));
        $this->assertFalse($spec->isSatisfiedBy('lga1700'));
    }

    public function test_fits_within_includes_the_limit(): void
    {
        $spec = new FitsWithin(330);

        $this->assertTrue($spec->isSatisfiedBy(330));
        $this->assertFalse($spec->isSatisfiedBy(331));
    }

    public function test_at_least_includes_the_minimum(): void
    {
        $spec = new AtLeast(550);

        $this->assertTrue($spec->isSatisfiedBy(550));
        $this->assertFalse($spec->isSatisfiedBy(549));
    }

    public function test_and_or_not_combine(): void
    {
        // PSU "enough, but below the recommendation" band: >= 392 W and not >= 550 W.
        $warningBand = (new AtLeast(392))->and((new AtLeast(550))->not());

        $this->assertFalse($warningBand->isSatisfiedBy(300));
        $this->assertTrue($warningBand->isSatisfiedBy(450));
        $this->assertFalse($warningBand->isSatisfiedBy(650));

        $outsideBand = $warningBand->not();
        $this->assertTrue($outsideBand->isSatisfiedBy(300));

        $smallOrHuge = (new FitsWithin(200))->or(new AtLeast(1000));
        $this->assertTrue($smallOrHuge->isSatisfiedBy(150));
        $this->assertTrue($smallOrHuge->isSatisfiedBy(1200));
        $this->assertFalse($smallOrHuge->isSatisfiedBy(500));
    }
}
