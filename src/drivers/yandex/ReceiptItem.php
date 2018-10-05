<?php namespace professionalweb\payment\drivers\yandex;

use professionalweb\payment\drivers\receipt\ReceiptItem as IReceiptItem;

/**
 * Receipt item
 * @package professionalweb\payment\drivers\yandex
 */
class ReceiptItem extends IReceiptItem
{
    /**
     * без НДС
     */
    public const TAX_NO_VAT = 1;

    /**
     * НДС по ставке 0%
     */
    public const TAX_VAT_0 = 2;

    /**
     * НДС чека по ставке 10%
     */
    public const TAX_VAT_10 = 3;

    /**
     * НДС чека по ставке 18%
     */
    public const TAX_VAT_18 = 4;

    /**
     * НДС чека по расчетной ставке 10/110
     */
    public const TAX_VAT_110 = 5;

    /**
     * НДС чека по расчетной ставке 18/118
     */
    public const TAX_VAT_118 = 6;

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'quantity'    => (string)$this->getQty(),
            'amount'      => [
                'value'    => $this->getPrice(),
                'currency' => $this->getCurrency(),
            ],
            'vat_code'    => $this->getTax(),
            'description' => mb_substr($this->getName(), 0, 128),
        ];
    }
}