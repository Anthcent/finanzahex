<?php

use App\Libraries\PrintingPaymentCalculator;
use PHPUnit\Framework\TestCase;

final class PrintingPaymentCalculatorTest extends TestCase
{
    public function testEquivalentFieldsRepresentOnePaymentOnly(): void
    {
        $payment = PrintingPaymentCalculator::normalize(2_500, 50, 50, 'usd');
        $result = PrintingPaymentCalculator::apply([
            'total_bs' => 5_000,
            'total_usd' => 100,
            'paid_bs' => 0,
            'paid_usd' => 0,
        ], $payment, 50);

        $this->assertSame(0.0, $payment['amount_bs']);
        $this->assertSame(50.0, $payment['amount_usd']);
        $this->assertSame('partial', $result['status']);
        $this->assertSame(2_500.0, $result['remaining_bs']);
    }

    public function testLegacyEquivalentPayloadIsNotDoubleCounted(): void
    {
        $payment = PrintingPaymentCalculator::normalize(2_500, 50, 50, null);
        $result = PrintingPaymentCalculator::apply([
            'total_bs' => 5_000,
            'total_usd' => 100,
            'paid_bs' => 0,
            'paid_usd' => 0,
        ], $payment, 50);

        $this->assertSame('usd', $payment['currency']);
        $this->assertSame('partial', $result['status']);
    }

    public function testFullRemainingPaymentClosesDebt(): void
    {
        $payment = PrintingPaymentCalculator::normalize(0, 50, 50, 'usd');
        $result = PrintingPaymentCalculator::apply([
            'total_bs' => 5_000,
            'total_usd' => 100,
            'paid_bs' => 0,
            'paid_usd' => 50,
        ], $payment, 50);

        $this->assertSame('paid', $result['status']);
        $this->assertSame(0.0, $result['remaining_bs']);
    }

    public function testOverpaymentIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $payment = PrintingPaymentCalculator::normalize(0, 60, 50, 'usd');
        PrintingPaymentCalculator::apply([
            'total_bs' => 5_000,
            'total_usd' => 100,
            'paid_bs' => 0,
            'paid_usd' => 50,
        ], $payment, 50);
    }
}
