<?php

use App\Libraries\TransactionValue;
use PHPUnit\Framework\TestCase;

final class TransactionValueTest extends TestCase
{
    public function testConvertsDollarOnlyMovementUsingItsStoredRate(): void
    {
        $transaction = ['amount' => 0, 'amount_usd' => 50, 'exchange_rate' => 50];

        $this->assertSame(2500.0, TransactionValue::inBolivars($transaction));
        $this->assertSame(50.0, TransactionValue::inDollars($transaction));
    }

    public function testConvertsBolivarOnlyMovementToDollars(): void
    {
        $transaction = ['amount' => 2500, 'amount_usd' => 0, 'exchange_rate' => 50];

        $this->assertSame(2500.0, TransactionValue::inBolivars($transaction));
        $this->assertSame(50.0, TransactionValue::inDollars($transaction));
    }

    public function testEquivalentDualFieldsAreNotCountedTwice(): void
    {
        $transaction = ['amount' => 2500, 'amount_usd' => 50, 'exchange_rate' => 50];

        $this->assertSame(2500.0, TransactionValue::inBolivars($transaction));
        $this->assertSame(50.0, TransactionValue::inDollars($transaction));
    }

    public function testMixedCurrencyMovementAddsBothActualPayments(): void
    {
        $transaction = ['amount' => 700, 'amount_usd' => 10, 'exchange_rate' => 50];

        $this->assertSame(1200.0, TransactionValue::inBolivars($transaction));
        $this->assertSame(24.0, TransactionValue::inDollars($transaction));
    }

    public function testUsesFallbackRateForLegacyRowsWithoutRate(): void
    {
        $transaction = ['amount' => 0, 'amount_usd' => 10, 'exchange_rate' => 0];

        $this->assertSame(600.0, TransactionValue::inBolivars($transaction, 60));
    }
}
