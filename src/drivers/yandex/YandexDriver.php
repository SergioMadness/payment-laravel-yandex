<?php namespace professionalweb\payment\drivers\yandex;

use Alcohol\ISO4217;
use Illuminate\Support\Arr;
use Illuminate\Http\Response;
use professionalweb\payment\Form;
use Illuminate\Contracts\Support\Arrayable;
use professionalweb\payment\contracts\Receipt;
use professionalweb\payment\contracts\PayService;
use professionalweb\payment\contracts\PayProtocol;
use professionalweb\payment\contracts\Form as IForm;
use professionalweb\payment\models\PayServiceOption;
use professionalweb\payment\interfaces\YandexService;
use professionalweb\payment\contracts\recurring\RecurringPayment;

/**
 * Payment service. Pay, Check, etc
 * @package professionalweb\payment\drivers\yandex
 */
class YandexDriver implements PayService, YandexService, RecurringPayment
{
    /**
     * All right
     */
    public const CODE_SUCCESS = 0;

    /**
     * Signature is corrupted
     */
    public const CODE_CORRUPTED_SIGN = 1;

    /**
     * Order not found
     */
    public const CODE_ORDER_NOT_FOUND = 100;

    /**
     * Can't understand request
     */
    public const CODE_BAD_PARAMS = 200;

    /**
     * Module config
     *
     * @var array
     */
    private $config;

    /**
     * Notification info
     *
     * @var array
     */
    protected $response;

    /**
     * @var PayProtocol
     */
    private $transport;

    /**
     * Last error code
     *
     * @var int
     */
    private $lastError = 0;

    /**
     * Flag Yandex need to remember payment requisites
     *
     * @var bool
     */
    private $needRecurring = false;

    /**
     * @var string
     */
    private $userId;

    public function __construct($config)
    {
        $this->setConfig($config);
    }

    /**
     * Pay
     *
     * @param int        $orderId
     * @param int        $paymentId
     * @param float      $amount
     * @param int|string $currency
     * @param string     $paymentType
     * @param string     $successReturnUrl
     * @param string     $failReturnUrl
     * @param string     $description
     * @param array      $extraParams
     * @param Receipt    $receipt
     *
     * @return string
     */
    public function getPaymentLink($orderId,
                                   $paymentId,
                                   float $amount,
                                   string $currency = self::CURRENCY_RUR_ISO,
                                   string $paymentType = self::PAYMENT_TYPE_CARD,
                                   string $successReturnUrl = '',
                                   string $failReturnUrl = '',
                                   string $description = '',
                                   array $extraParams = [],
                                   Receipt $receipt = null): string
    {
        if (is_numeric($currency)) {
            $cur = (new ISO4217())->getByNumeric($currency);
            $currency = $cur['alpha3'];
        }

        $paymentType = $this->getPaymentMethod($paymentType);
        $params = [
            'amount'              => [
                'value'    => $amount,
                'currency' => $currency,
            ],
            'metadata'            => [
                'orderId'   => $orderId,
                'paymentId' => $paymentId,
            ],
            'confirmation'        => [
                'type'       => 'redirect',
                'return_url' => $successReturnUrl,
            ],
            'payment_method_data' => [
                'type' => $paymentType,
            ],
            'description'         => $description,
            'capture'             => true,
        ];
        if ($paymentType === self::PAYMENT_TYPE_QIWI && isset($extraParams['phone'])) {
            $params['payment_method_data']['phone'] = $extraParams['phone'];
        }
        if ($this->needRecurring()) {
            $params['save_payment_method'] = true;
        }
        if ($receipt instanceof Arrayable) {
            $params['receipt'] = (string)$receipt;
        }
        $params = array_merge($params, $extraParams);

        return $this->getTransport()->getPaymentUrl($params);
    }

    /**
     * Validate request
     *
     * @param array $data
     *
     * @return bool
     */
    public function validate(array $data): bool
    {
        return ($this->lastError = $this->getTransport()->validate($data)) === 0;
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfig(): ?array
    {
        return $this->config;
    }

    /**
     * Set driver configuration
     *
     * @param array $config
     *
     * @return $this
     */
    public function setConfig(?array $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Parse notification
     *
     * @param array $data
     *
     * @return PayService
     */
    public function setResponse(array $data): PayService
    {
        $data['DateTime'] = date('Y-m-d H:i:s');
        $this->response = $data;

        return $this;
    }

    /**
     * Get response param by name
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed|string
     */
    public function getResponseParam(string $name, $default = '')
    {
        return Arr::get($this->response['object'] ?? [], $name, $default);
    }

    /**
     * Get order ID
     *
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->getResponseParam('metadata.orderId');
    }

    /**
     * Get operation status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->getResponseParam('status', 'succeeded');
    }

    /**
     * Is payment succeed
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->getResponseParam('status') === 'succeeded';
    }

    /**
     * Get transaction ID
     *
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->getResponseParam('id', '');
    }

    /**
     * Get transaction amount
     *
     * @return float
     */
    public function getAmount(): float
    {
        return $this->getResponseParam('amount.value', 0);
    }

    /**
     * Get error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->getResponseParam('action', 'cancelOrder') !== 'cancelOrder' ? 0 : 1;
    }

    /**
     * Get payment provider
     *
     * @return string
     */
    public function getProvider(): string
    {
        return $this->getResponseParam('payment_method.type', '');
    }

    /**
     * Get PAn
     *
     * @return string
     */
    public function getPan(): string
    {
        return $this->getResponseParam('payment_method.card.first6') . '******' . $this->getResponseParam('payment_method.card.last4');
    }

    /**
     * Get payment datetime
     *
     * @return string
     */
    public function getDateTime(): string
    {
        return $this->getResponseParam('DateTime');
    }

    /**
     * Get transport
     *
     * @return PayProtocol
     */
    public function getTransport(): PayProtocol
    {
        return $this->transport;
    }

    /**
     * Set transport
     *
     * @param PayProtocol $transport
     *
     * @return $this
     */
    public function setTransport(PayProtocol $transport): PayService
    {
        $this->transport = $transport;

        return $this;
    }

    /**
     * Prepare response on notification request
     *
     * @param int $errorCode
     *
     * @return string
     */
    public function getNotificationResponse(int $errorCode = null): Response
    {
        return response($this->getTransport()->getNotificationResponse($this->response, $errorCode ?? $this->getLastError()));
    }

    /**
     * Prepare response on check request
     *
     * @param int $errorCode
     *
     * @return string
     */
    public function getCheckResponse(int $errorCode = null): Response
    {
        return response($this->getTransport()->getCheckResponse($this->response, $errorCode ?? $this->getLastError()));
    }

    /**
     * Get last error code
     *
     * @return int
     */
    public function getLastError(): int
    {
        return $this->lastError;
    }

    /**
     * Get param by name
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParam(string $name)
    {
        return $this->getResponseParam($name);
    }

    /**
     * Get name of payment service
     *
     * @return string
     */
    public function getName(): string
    {
        return self::PAYMENT_YANDEX;
    }

    /**
     * Get payment id
     *
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->getResponseParam('metadata.paymentId');
    }

    /**
     * Get payment type for Yandex by constant value
     *
     * @param string $type
     *
     * @return string
     */
    public function getPaymentMethod(string $type): string
    {
        $map = [
            self::PAYMENT_TYPE_CARD         => 'bank_card',
            self::PAYMENT_TYPE_CASH         => 'cash',
            self::PAYMENT_TYPE_MOBILE       => 'mobile_balance',
            self::PAYMENT_TYPE_QIWI         => 'qiwi',
            self::PAYMENT_TYPE_SBERBANK     => 'sberbank',
            self::PAYMENT_TYPE_YANDEX_MONEY => 'yandex_money',
            self::PAYMENT_TYPE_ALFABANK     => 'alfabank',
        ];

        return $map[$type] ?? $map[self::PAYMENT_TYPE_CARD];
    }

    /**
     * Payment system need form
     * You can not get url for redirect
     *
     * @return bool
     */
    public function needForm(): bool
    {
        return false;
    }

    /**
     * Generate payment form
     *
     * @param int     $orderId
     * @param int     $paymentId
     * @param float   $amount
     * @param string  $currency
     * @param string  $paymentType
     * @param string  $successReturnUrl
     * @param string  $failReturnUrl
     * @param string  $description
     * @param array   $extraParams
     * @param Receipt $receipt
     *
     * @return IForm
     */
    public function getPaymentForm($orderId,
                                   $paymentId,
                                   float $amount,
                                   string $currency = self::CURRENCY_RUR,
                                   string $paymentType = self::PAYMENT_TYPE_CARD,
                                   string $successReturnUrl = '',
                                   string $failReturnUrl = '',
                                   string $description = '',
                                   array $extraParams = [],
                                   Receipt $receipt = null): IForm
    {
        return new Form();
    }

    /**
     * Get pay service options
     *
     * @return array
     */
    public static function getOptions(): array
    {
        return [
            (new PayServiceOption())->setAlias('merchantId')->setLabel('ShopId')->setType(PayServiceOption::TYPE_STRING),
            (new PayServiceOption())->setAlias('secretKey')->setLabel('SecretKey')->setType(PayServiceOption::TYPE_STRING),
        ];
    }

    /**
     * Get payment token
     *
     * @return string
     */
    public function getRecurringPayment(): string
    {
        return $this->getResponseParam('payment_method.id');
    }

    /**
     * Remember payment fo recurring payments
     *
     * @return RecurringPayment
     */
    public function makeRecurring(): RecurringPayment
    {
        $this->needRecurring = true;

        return $this;
    }

    /**
     * Check payment need to be recurrent
     *
     * @return bool
     */
    public function needRecurring(): bool
    {
        return $this->needRecurring;
    }

    /**
     * Set user id payment will be assigned
     *
     * @param string $id
     *
     * @return RecurringPayment
     */
    public function setUserId(string $id): RecurringPayment
    {
        $this->userId = $id;

        return $this;
    }

    /**
     * Get user account id
     *
     * @return null|string
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * Initialize recurring payment
     *
     * @param string $token
     * @param string $paymentId
     * @param float  $amount
     * @param string $description
     * @param string $currency
     * @param array  $extraParams
     *
     * @return bool
     */
    public function initPayment(string $token, string $paymentId, float $amount, string $description, string $currency = PayService::CURRENCY_RUR_ISO, array $extraParams = []): bool
    {
        $params = [
            'amount'            => [
                'value'    => $amount,
                'currency' => $currency,
            ],
            'payment_method_id' => $token,
            'description'       => $description,
            'metadata'          => array_merge($extraParams, [
                'AccountId' => $this->getUserId(),
                'PaymentId' => $paymentId,
            ]),
        ];

        $this->getTransport()->getPaymentUrl($params);

        return true;
    }
}