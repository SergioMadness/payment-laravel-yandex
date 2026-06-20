<?php namespace professionalweb\payment\drivers\yandex;

use professionalweb\payment\drivers\receipt\ReceiptItem as IReceiptItem;

/**
 * Receipt item
 *
 * @see https://yookassa.ru/developers/payment-acceptance/receipts/54fz/parameters-values
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
     * НДС по ставке 10%
     */
    public const TAX_VAT_10 = 3;

    /**
     * НДС по ставке 20%
     */
    public const TAX_VAT_20 = 4;

    /**
     * НДС по расчетной ставке 10/110
     */
    public const TAX_VAT_110 = 5;

    /**
     * НДС по расчетной ставке 20/120
     */
    public const TAX_VAT_120 = 6;

    /**
     * НДС по ставке 5%
     */
    public const TAX_VAT_5 = 7;

    /**
     * НДС по ставке 7%
     */
    public const TAX_VAT_7 = 8;

    /**
     * НДС по расчетной ставке 5/105
     */
    public const TAX_VAT_105 = 9;

    /**
     * НДС по расчетной ставке 7/107
     */
    public const TAX_VAT_107 = 10;

    /**
     * НДС по ставке 22%
     */
    public const TAX_VAT_22 = 11;

    /**
     * НДС по расчетной ставке 22/122
     */
    public const TAX_VAT_122 = 12;

    /**
     * @deprecated С 2019 года ставка 18% заменена на 20%. Используйте TAX_VAT_20.
     */
    public const TAX_VAT_18 = self::TAX_VAT_20;

    /**
     * @deprecated С 2019 года расчетная ставка 18/118 заменена на 20/120. Используйте TAX_VAT_120.
     */
    public const TAX_VAT_118 = self::TAX_VAT_120;

    /**
     * Признак способа расчёта (payment_mode)
     *
     * @var string
     */
    private $paymentMode = 'full_prepayment';

    /**
     * Признак предмета расчёта (payment_subject)
     *
     * @var string
     */
    private $paymentSubject = 'service';

    /**
     * Мера количества предмета расчёта (measure). Обязательна для ФФД 1.2.
     *
     * @var string
     */
    private $measure = 'piece';

    /**
     * Set payment mode (признак способа расчёта)
     *
     * @param string $paymentMode
     *
     * @return $this
     */
    public function setPaymentMode(string $paymentMode): self
    {
        $this->paymentMode = $paymentMode;

        return $this;
    }

    /**
     * Set payment subject (признак предмета расчёта)
     *
     * @param string $paymentSubject
     *
     * @return $this
     */
    public function setPaymentSubject(string $paymentSubject): self
    {
        $this->paymentSubject = $paymentSubject;

        return $this;
    }

    /**
     * Set measure (мера количества). Обязательна для ФФД 1.2.
     *
     * @param string $measure
     *
     * @return $this
     */
    public function setMeasure(string $measure): self
    {
        $this->measure = $measure;

        return $this;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'description'     => mb_substr($this->getName(), 0, 128),
            'quantity'        => (string)$this->getQty(),
            'amount'          => [
                'value'    => number_format((float)$this->getPrice(), 2, '.', ''),
                'currency' => $this->getCurrency(),
            ],
            'vat_code'        => $this->getTax(),
            'payment_mode'    => $this->paymentMode,
            'payment_subject' => $this->paymentSubject,
            'measure'         => $this->measure,
        ];
    }
}
