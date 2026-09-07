<?php

use App\Libraries\PrintProductConfigurator;
use PHPUnit\Framework\TestCase;

final class PrintProductConfiguratorTest extends TestCase
{
    private array $product;

    protected function setUp(): void
    {
        $this->product = [
            'name' => 'Franela personalizada',
            'characteristics_json' => json_encode([
                ['name' => 'Talla', 'type' => 'select', 'required' => true, 'options' => [
                    ['label' => 'M', 'price_usd' => 0],
                    ['label' => 'XXL', 'price_usd' => 2],
                ]],
                ['name' => 'Color', 'type' => 'text', 'required' => true, 'options' => []],
            ]),
        ];
    }

    public function testValidatesAndPricesSelectedVariants(): void
    {
        $result = PrintProductConfigurator::calculate($this->product, ['Talla' => 'XXL', 'Color' => 'Negro'], 50);

        $this->assertSame(100.0, $result['adjustment_bs']);
        $this->assertSame(2.0, $result['adjustment_usd']);
        $this->assertSame(['Talla' => 'XXL', 'Color' => 'Negro'], $result['selections']);
    }

    public function testRejectsMissingRequiredVariant(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PrintProductConfigurator::calculate($this->product, ['Color' => 'Negro'], 50);
    }

    public function testRejectsUnknownOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PrintProductConfigurator::calculate($this->product, ['Talla' => 'XXXL', 'Color' => 'Negro'], 50);
    }
}
