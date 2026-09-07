<?php

use App\Controllers\ReconciliationController;
use PHPUnit\Framework\TestCase;

final class ReconciliationImportTest extends TestCase
{
    private function callPrivate(string $method, ...$arguments)
    {
        $controller = new ReconciliationController();
        $reflection = new ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($controller, ...$arguments);
    }

    public function testCsvUsesDebitWhenCreditColumnIsEmpty(): void
    {
        $csv = "Fecha;Descripción;Crédito;Débito;Moneda\n07/09/2026;Compra;;1.234,50;BS";
        $rows = $this->callPrivate('parseCsvDataUrl', 'data:text/csv;base64,' . base64_encode($csv));

        $this->assertCount(1, $rows);
        $this->assertSame('1.234,50', $rows[0]['amount']);
        $this->assertSame('debit', $rows[0]['direction']);
    }

    public function testCsvUsesCreditWhenDebitColumnIsEmpty(): void
    {
        $csv = "Fecha,Descripción,Crédito,Débito,Moneda\n2026-09-07,Pago,250.00,,USD";
        $rows = $this->callPrivate('parseCsvDataUrl', 'data:text/csv;base64,' . base64_encode($csv));

        $this->assertCount(1, $rows);
        $this->assertSame('250.00', $rows[0]['amount']);
        $this->assertSame('credit', $rows[0]['direction']);
    }

    public function testNormalizedCsvAmountKeepsVenezuelanNumberFormat(): void
    {
        $row = [
            'date' => '07/09/2026',
            'amount' => '1.234,50',
            'direction' => 'debit',
            'currency' => 'BS',
            'reference' => '000123',
        ];
        $normalized = $this->callPrivate('normalizeRow', $row, 8, 'bank_statement');

        $this->assertSame(1234.5, $normalized['amount']);
        $this->assertSame('debit', $normalized['direction']);
        $this->assertSame('2026-09-07 00:00:00', $normalized['movement_date']);
    }

    public function testNormalizesSpanishCreditDirection(): void
    {
        $normalized = $this->callPrivate('normalizeRow', [
            'amount' => '50,00',
            'direction' => 'Crédito',
        ], 8, 'bank_statement');

        $this->assertSame('credit', $normalized['direction']);
    }
}
