<?php

namespace Tests\Unit;

use App\Models\InvoiceItem;
use App\Services\InvoiceCalculationService;
use InvalidArgumentException;
use Tests\TestCase;

class InvoiceCalculationServiceTest extends TestCase
{
    private InvoiceCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceCalculationService();
    }

    /** Test 1 — Single item calculation */
    public function test_single_item_calculation(): void
    {
        $items = [
            [
                'quantity'        => 1,
                'unit_price'      => 500,
                'discount_amount' => 0,
                'tax_rate'        => 0,
            ],
        ];

        $result = $this->service->calculateInvoice($items);

        $this->assertSame(500.00, $result['subtotal']);
        $this->assertSame(0.00, $result['discount_amount']);
        $this->assertSame(0.00, $result['tax_amount']);
        $this->assertSame(500.00, $result['total_amount']);
        $this->assertCount(1, $result['items']);
        $this->assertSame(500.00, $result['items'][0]['line_total']);
    }

    /** Test 2 — Multiple items calculation */
    public function test_multiple_items_calculation(): void
    {
        $items = [
            ['quantity' => 1, 'unit_price' => 500, 'discount_amount' => 0, 'tax_rate' => 0],
            ['quantity' => 1, 'unit_price' => 700, 'discount_amount' => 0, 'tax_rate' => 0],
        ];

        $result = $this->service->calculateInvoice($items);

        $this->assertSame(1200.00, $result['subtotal']);
        $this->assertSame(0.00, $result['discount_amount']);
        $this->assertSame(0.00, $result['tax_amount']);
        $this->assertSame(1200.00, $result['total_amount']);
        $this->assertCount(2, $result['items']);
    }

    /** Test 3 — Discount deduction */
    public function test_discount_deduction(): void
    {
        $items = [
            [
                'quantity'        => 1,
                'unit_price'      => 700,
                'discount_amount' => 100,
                'tax_rate'        => 0,
            ],
        ];

        $result = $this->service->calculateInvoice($items);

        $this->assertSame(700.00, $result['subtotal']);
        $this->assertSame(100.00, $result['discount_amount']);
        $this->assertSame(0.00, $result['tax_amount']);
        $this->assertSame(600.00, $result['total_amount']);
        $this->assertSame(600.00, $result['items'][0]['line_total']);
    }

    /** Test 4 — Tax calculation */
    public function test_tax_calculation(): void
    {
        $items = [
            [
                'quantity'        => 2,
                'unit_price'      => 150,
                'discount_amount' => 0,
                'tax_rate'        => 18,
            ],
        ];

        $result = $this->service->calculateInvoice($items);

        $this->assertSame(300.00, $result['subtotal']);
        $this->assertSame(0.00, $result['discount_amount']);
        $this->assertSame(54.00, $result['tax_amount']);
        $this->assertSame(354.00, $result['total_amount']);
        $this->assertSame(354.00, $result['items'][0]['line_total']);
    }

    /** Test 5 — Discount applied before tax calculation */
    public function test_discount_applied_before_tax(): void
    {
        $items = [
            [
                'quantity'        => 2,
                'unit_price'      => 150,
                'discount_amount' => 20,
                'tax_rate'        => 18,
            ],
        ];

        $result = $this->service->calculateInvoice($items);

        $this->assertSame(300.00, $result['subtotal']);
        $this->assertSame(20.00, $result['discount_amount']);
        $this->assertSame(280.00, $result['items'][0]['taxable_amount']);
        $this->assertSame(50.40, $result['tax_amount']);
        $this->assertSame(330.40, $result['total_amount']);
        $this->assertSame(330.40, $result['items'][0]['line_total']);
    }

    /** Test 6 — Multiple items with independent tax rates */
    public function test_multiple_items_with_different_tax_rates(): void
    {
        $items = [
            // Standard consult: Tax-exempt (0%)
            ['quantity' => 1, 'unit_price' => 500, 'discount_amount' => 0, 'tax_rate' => 0],
            // Lab test: 5% tax
            ['quantity' => 1, 'unit_price' => 200, 'discount_amount' => 0, 'tax_rate' => 5],
            // Product / Supplement: 18% tax with 10 discount
            ['quantity' => 2, 'unit_price' => 50, 'discount_amount' => 10, 'tax_rate' => 18],
        ];

        $result = $this->service->calculateInvoice($items);

        // Subtotals: 500 + 200 + 100 = 800
        $this->assertSame(800.00, $result['subtotal']);
        // Discounts: 0 + 0 + 10 = 10
        $this->assertSame(10.00, $result['discount_amount']);
        // Taxes: 0 + (200 * 0.05 = 10.00) + ((100 - 10) * 0.18 = 16.20) = 26.20
        $this->assertSame(26.20, $result['tax_amount']);
        // Total: 800 - 10 + 26.20 = 816.20
        $this->assertSame(816.20, $result['total_amount']);

        // Individual line totals check
        $this->assertSame(500.00, $result['items'][0]['line_total']);
        $this->assertSame(210.00, $result['items'][1]['line_total']);
        $this->assertSame(106.20, $result['items'][2]['line_total']);
    }

    /** Test 7 — Decimal quantity support */
    public function test_decimal_quantity(): void
    {
        $items = [
            [
                'quantity'        => 1.5,
                'unit_price'      => 100,
                'discount_amount' => 0,
                'tax_rate'        => 0,
            ],
        ];

        $result = $this->service->calculateInvoice($items);

        $this->assertSame(150.00, $result['subtotal']);
        $this->assertSame(150.00, $result['total_amount']);
        $this->assertSame(1.5, $result['items'][0]['quantity']);
    }

    /** Test 8 — Zero discount behavior */
    public function test_zero_discount_does_not_affect_totals(): void
    {
        $itemWithZero = [
            'quantity'        => 1,
            'unit_price'      => 100,
            'discount_amount' => 0,
            'tax_rate'        => 10,
        ];

        $itemWithNull = [
            'quantity'        => 1,
            'unit_price'      => 100,
            'discount_amount' => null,
            'tax_rate'        => 10,
        ];

        $calcZero = $this->service->calculateItem($itemWithZero);
        $calcNull = $this->service->calculateItem($itemWithNull);

        $this->assertSame(100.00, $calcZero['line_subtotal']);
        $this->assertSame(0.00, $calcZero['discount_amount']);
        $this->assertSame(10.00, $calcZero['tax_amount']);
        $this->assertSame(110.00, $calcZero['line_total']);

        $this->assertSame($calcZero['line_total'], $calcNull['line_total']);
    }

    /** Test 9 — Invalid discount exceeding line subtotal throws exception */
    public function test_discount_exceeding_line_subtotal_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Discount amount (150) cannot exceed line subtotal (100).');

        $this->service->calculateItem([
            'quantity'        => 1,
            'unit_price'      => 100,
            'discount_amount' => 150,
            'tax_rate'        => 0,
        ]);
    }

    /** Test 10a — Invalid negative quantity throws exception */
    public function test_negative_quantity_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be a positive number greater than zero.');

        $this->service->calculateItem([
            'quantity'   => -1,
            'unit_price' => 100,
        ]);
    }

    /** Test 10b — Zero quantity throws exception */
    public function test_zero_quantity_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity must be a positive number greater than zero.');

        $this->service->calculateItem([
            'quantity'   => 0,
            'unit_price' => 100,
        ]);
    }

    /** Test 10c — Negative unit price throws exception */
    public function test_negative_unit_price_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unit price cannot be negative.');

        $this->service->calculateItem([
            'quantity'   => 1,
            'unit_price' => -50,
        ]);
    }

    /** Test 10d — Negative discount throws exception */
    public function test_negative_discount_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Discount amount cannot be negative.');

        $this->service->calculateItem([
            'quantity'        => 1,
            'unit_price'      => 100,
            'discount_amount' => -10,
        ]);
    }

    /** Test 10e — Negative tax rate throws exception */
    public function test_negative_tax_rate_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax rate must be between 0 and 100 percent.');

        $this->service->calculateItem([
            'quantity'   => 1,
            'unit_price' => 100,
            'tax_rate'   => -5,
        ]);
    }

    /** Test 10f — Tax rate exceeding 100 throws exception */
    public function test_tax_rate_exceeding_100_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax rate must be between 0 and 100 percent.');

        $this->service->calculateItem([
            'quantity'   => 1,
            'unit_price' => 100,
            'tax_rate'   => 105,
        ]);
    }

    /** Test 11 — Empty items collection returns zeroed totals */
    public function test_empty_items_returns_zero_totals(): void
    {
        $result = $this->service->calculateInvoice([]);

        $this->assertSame(0.00, $result['subtotal']);
        $this->assertSame(0.00, $result['discount_amount']);
        $this->assertSame(0.00, $result['tax_amount']);
        $this->assertSame(0.00, $result['total_amount']);
        $this->assertSame([], $result['items']);
    }

    /** Test 12a — Rounding edge case with fractional tax cents */
    public function test_rounding_edge_case_fractional_tax(): void
    {
        // 999.99 * 18% = 179.9982 -> rounded to 180.00
        $item = [
            'quantity'        => 1,
            'unit_price'      => 999.99,
            'discount_amount' => 0,
            'tax_rate'        => 18,
        ];

        $result = $this->service->calculateItem($item);

        $this->assertSame(999.99, $result['line_subtotal']);
        $this->assertSame(180.00, $result['tax_amount']);
        $this->assertSame(1179.99, $result['line_total']);
    }

    /** Test 12b — Rounding edge case with multiple items and tax cents accumulation */
    public function test_rounding_edge_case_multiple_fractional_items(): void
    {
        // Item 1: 33.33 * 5% = 1.6665 -> 1.67
        // Item 2: 33.33 * 5% = 1.6665 -> 1.67
        // Item 3: 33.33 * 5% = 1.6665 -> 1.67
        $items = [
            ['quantity' => 1, 'unit_price' => 33.33, 'discount_amount' => 0, 'tax_rate' => 5],
            ['quantity' => 1, 'unit_price' => 33.33, 'discount_amount' => 0, 'tax_rate' => 5],
            ['quantity' => 1, 'unit_price' => 33.33, 'discount_amount' => 0, 'tax_rate' => 5],
        ];

        $result = $this->service->calculateInvoice($items);

        $this->assertSame(99.99, $result['subtotal']);
        $this->assertSame(5.01, $result['tax_amount']); // 1.67 * 3 = 5.01
        $this->assertSame(105.00, $result['total_amount']); // 99.99 + 5.01 = 105.00
    }

    /** Test 13 — InvoiceItem Eloquent model compatibility */
    public function test_invoice_item_eloquent_model_compatibility(): void
    {
        $model = new InvoiceItem([
            'description'     => 'General Consultation',
            'quantity'        => 2.00,
            'unit_price'      => 120.00,
            'discount_amount' => 10.00,
            'tax_rate'        => 10.00,
            'sort_order'      => 1,
        ]);

        $result = $this->service->calculateItem($model);

        $this->assertSame(2.0, $result['quantity']);
        $this->assertSame(120.00, $result['unit_price']);
        $this->assertSame(10.00, $result['discount_amount']);
        $this->assertSame(10.0, $result['tax_rate']);
        $this->assertSame(240.00, $result['line_subtotal']);
        $this->assertSame(230.00, $result['taxable_amount']);
        $this->assertSame(23.00, $result['tax_amount']);
        $this->assertSame(253.00, $result['line_total']);
        $this->assertSame('General Consultation', $result['description']);
        $this->assertSame(1, $result['sort_order']);
    }
}
