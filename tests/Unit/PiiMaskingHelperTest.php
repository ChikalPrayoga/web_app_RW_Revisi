<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Security\PiiMaskingHelper;
use PHPUnit\Framework\TestCase;

class PiiMaskingHelperTest extends TestCase
{
    public function test_mask_nik_produces_four_front_eight_masked_four_back(): void
    {
        $nik = '3216011505900021';
        $masked = PiiMaskingHelper::maskNik($nik);

        $this->assertEquals('3216xxxxxxxx0021', $masked);
    }

    public function test_mask_no_kk_produces_four_front_eight_masked_four_back(): void
    {
        $noKk = '3216010101230012';
        $masked = PiiMaskingHelper::maskNoKk($noKk);

        $this->assertEquals('3216xxxxxxxx0012', $masked);
    }

    public function test_mask_handles_null_or_empty(): void
    {
        $this->assertNull(PiiMaskingHelper::maskNik(null));
        $this->assertNull(PiiMaskingHelper::maskNik(''));
    }
}
