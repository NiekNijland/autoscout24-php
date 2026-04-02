<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Tests;

use NiekNijland\AutoScout24\Data\DetailPriceInfo;
use PHPUnit\Framework\TestCase;

class DetailPriceInfoTest extends TestCase
{
    public function test_from_array_casts_integer_vat_rate_to_string(): void
    {
        $priceInfo = DetailPriceInfo::fromArray([
            'price' => 'EUR 10.000',
            'priceRaw' => 10000,
            'vatRate' => 21,
        ]);

        $this->assertSame('21', $priceInfo->vatRate);
    }
}
