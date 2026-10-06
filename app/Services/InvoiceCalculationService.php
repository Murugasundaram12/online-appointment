<?php

namespace App\Services;

use App\Models\InvoiceItem;
use InvalidArgumentException;

class InvoiceCalculationService
{
    /**
     * Calculate line item totals from an array or InvoiceItem model.
     *
     * Financial Formula:
     * line_subtotal  = round(quantity * unit_price, 2)
     * taxable_amount = round(line_subtotal - discount_amount, 2)
     * tax_amount     = round(taxable_amount * (tax_rate / 100), 2)
     * line_total     = round(taxable_amount + tax_amount, 2)
     *
     * @param array|InvoiceItem $item
     * @return array
     * @throws InvalidArgumentException
     */
    public function calculateItem(array|InvoiceItem $item): array
    {
        $quantity = $this->extractValue($item, 'quantity', 1.0);
        $unitPrice = $this->extractValue($item, 'unit_price', 0.0);
        $discountAmount = $this->extractValue($item, 'discount_amount', 0.0);
        $taxRate = $this->extractValue($item, 'tax_rate', 0.0);

        if (!is_numeric($quantity) || (float) $quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be a positive number greater than zero.');
        }

        if (!is_numeric($unitPrice) || (float) $unitPrice < 0) {
            throw new InvalidArgumentException('Unit price cannot be negative.');
        }

        if (!is_numeric($discountAmount) || (float) $discountAmount < 0) {
            throw new InvalidArgumentException('Discount amount cannot be negative.');
        }

        if (!is_numeric($taxRate) || (float) $taxRate < 0 || (float) $taxRate > 100) {
            throw new InvalidArgumentException('Tax rate must be between 0 and 100 percent.');
        }

        $qty = (float) $quantity;
        $price = (float) $unitPrice;
        $discount = (float) $discountAmount;
        $rate = (float) $taxRate;

        $lineSubtotal = round($qty * $price, 2);

        if ($discount > $lineSubtotal) {
            throw new InvalidArgumentException("Discount amount ({$discount}) cannot exceed line subtotal ({$lineSubtotal}).");
        }

        $taxableAmount = round($lineSubtotal - $discount, 2);
        $taxAmount = round($taxableAmount * ($rate / 100), 2);
        $lineTotal = round($taxableAmount + $taxAmount, 2);

        $result = [
            'quantity'        => $qty,
            'unit_price'      => round($price, 2),
            'discount_amount' => round($discount, 2),
            'tax_rate'        => round($rate, 2),
            'line_subtotal'   => $lineSubtotal,
            'taxable_amount'  => $taxableAmount,
            'tax_amount'      => $taxAmount,
            'line_total'      => $lineTotal,
        ];

        // Retain metadata attributes if provided
        if (is_array($item)) {
            foreach (['service_id', 'staff_id', 'description', 'sort_order', 'quotation_item_id'] as $attr) {
                if (array_key_exists($attr, $item)) {
                    $result[$attr] = $item[$attr];
                }
            }
        } elseif ($item instanceof InvoiceItem) {
            $result['service_id'] = $item->service_id;
            $result['staff_id'] = $item->staff_id;
            $result['description'] = $item->description;
            $result['sort_order'] = $item->sort_order;
            $result['quotation_item_id'] = $item->quotation_item_id;
            if ($item->exists) {
                $result['id'] = $item->id;
                $result['invoice_id'] = $item->invoice_id;
            }
        }

        return $result;
    }

    /**
     * Calculate invoice totals from an iterable collection of line items.
     *
     * Financial Formula:
     * subtotal        = round(SUM(line_subtotal), 2)
     * discount_amount = round(SUM(discount_amount), 2)
     * tax_amount      = round(SUM(tax_amount), 2)
     * total_amount    = round(subtotal - discount_amount + tax_amount, 2)
     *
     * @param iterable $items
     * @return array
     * @throws InvalidArgumentException
     */
    public function calculateInvoice(iterable $items): array
    {
        $subtotal = 0.0;
        $discountAmount = 0.0;
        $taxAmount = 0.0;
        $calculatedItems = [];

        foreach ($items as $item) {
            $calculatedItem = $this->calculateItem($item);
            $subtotal += $calculatedItem['line_subtotal'];
            $discountAmount += $calculatedItem['discount_amount'];
            $taxAmount += $calculatedItem['tax_amount'];
            $calculatedItems[] = $calculatedItem;
        }

        $subtotal = round($subtotal, 2);
        $discountAmount = round($discountAmount, 2);
        $taxAmount = round($taxAmount, 2);
        $totalAmount = round($subtotal - $discountAmount + $taxAmount, 2);

        return [
            'subtotal'        => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount'      => $taxAmount,
            'total_amount'    => $totalAmount,
            'items'           => $calculatedItems,
        ];
    }

    /**
     * Safely extract a value from an array or InvoiceItem model.
     *
     * @param array|InvoiceItem $item
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function extractValue(array|InvoiceItem $item, string $key, mixed $default): mixed
    {
        if ($item instanceof InvoiceItem) {
            $val = $item->getAttribute($key);
            return $val !== null ? $val : $default;
        }

        return array_key_exists($key, $item) && $item[$key] !== null ? $item[$key] : $default;
    }
}
