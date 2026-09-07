<?php

namespace Tests\Unit;

use App\Libraries\CurrencyOperationCalculator;
use PHPUnit\Framework\TestCase;

final class CurrencyOperationCalculatorTest extends TestCase
{
    public function testPurchaseIncludesCommissionInTotalAndEffectiveRate(): void
    {
        $result = CurrencyOperationCalculator::purchase(1000, 2.5, 10);

        $this->assertSame(25.0, $result['commission_bs']);
        $this->assertSame(1025.0, $result['total_bs']);
        $this->assertSame(102.5, $result['effective_rate']);
    }

    public function testRejectsInvalidValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CurrencyOperationCalculator::purchase(1000, -1, 10);
    }
}
