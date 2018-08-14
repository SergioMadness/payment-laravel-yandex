<?php namespace professionalweb\payment\drivers\yandex;

use Illuminate\Contracts\Support\Arrayable;
use professionalweb\payment\contracts\PayService;
use professionalweb\payment\contracts\PayProtocol;

/**
 * Payment service. Pay, Check, etc
 * @package AlpinaDigital\Services
 */
class YandexDriver implements PayService
{
    /**
     * All right
     */
    const CODE_SUCCESS = 0;

    /**
     * Signature is corrupted
     */
    const CODE_CORRUPTED_SIGN = 1;

    /**
     * Order not found
     */
    const CODE_ORDER_NOT_FOUND = 100;

    /**
     * Can't understand request
     */
    const CODE_BAD_PARAMS = 200;

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
     * @param string     $successReturnUrl
     * @param string     $failReturnUrl
     * @param string     $description
     * @param array      $extraParams
     * @param Arrayable  $receipt
     *
     * @return string
     * @throws \Exception
     */
    public function getPaymentLink($orderId,
                                   $paymentId,
                                   $amount,
                                   $currency = self::CURRENCY_RUR_ISO,
                                   $paymentType = self::PAYMENT_TYPE_CARD,
                                   $successReturnUrl = '',
                                   $failReturnUrl = '',
                                   $description = '',
                                   $extraParams = [],
                                   $receipt = null)
    {
        if ($receipt instanceof Arrayable) {
            $extraParams['ym_merchant_receipt'] = (string)$receipt;
        }

        return $this->getTransport()->getPaymentUrl(array_merge([
            'orderNumber'    => $orderId,
            'customerNumber' => $orderId,
            'sum'            => $amount,
            'PaymentId'      => $paymentId,
            'shopSuccessURL' => $successReturnUrl,
            'shopDefaultUrl' => $successReturnUrl,
            'shopFailURL'    => $failReturnUrl,
        ], $extraParams));
    }

    /**
     * Validate request
     *
     * @param array $data
     *
     * @return bool
     */
    public function validate($data)
    {
        return ($this->lastError = $this->getTransport()->validate($data)) === 0;
    }

    /**
     * Get configuration
     *
     * @return array
     */
    public function getConfig()
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
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Parse notification
     *
     * @param array $data
     *
     * @return mixed
     */
    public function setResponse($data)
    {
        $data['DateTime'] = date('Y-m-d H:i:s');
        $this->response = $data;

        return $this;
    }

    /**
     * Get response param by name
     *
     * @param string $name
     * @param string $default
     *
     * @return mixed|string
     */
    public function getResponseParam($name, $default = '')
    {
        return isset($this->response[$name]) ? $this->response[$name] : $default;
    }

    /**
     * Get order ID
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->getResponseParam('orderNumber');
    }

    /**
     * Get operation status
     *
     * @return string
     */
    public function getStatus()
    {
        return null;
    }

    /**
     * Is payment succeed
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->getResponseParam('action', 'cancelOrder') !== 'cancelOrder';
    }

    /**
     * Get transaction ID
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->getResponseParam('invoiceId');
    }

    /**
     * Get transaction amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->getResponseParam('orderSumAmount');
    }

    /**
     * Get error code
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->getResponseParam('action', 'cancelOrder') !== 'cancelOrder' ? 0 : 1;
    }

    /**
     * Get payment provider
     *
     * @return string
     */
    public function getProvider()
    {
        return $this->getResponseParam('paymentType');
    }

    /**
     * Get PAn
     *
     * @return string
     */
    public function getPan()
    {
        return $this->getResponseParam('cdd_pan_mask');
    }

    /**
     * Get payment datetime
     *
     * @return string
     */
    public function getDateTime()
    {
        return $this->getResponseParam('DateTime');
    }

    /**
     * Get transport
     *
     * @return PayProtocol
     */
    public function getTransport()
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
    public function setTransport(PayProtocol $transport)
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
    public function getNotificationResponse($errorCode = null)
    {
        return $this->getTransport()->getNotificationResponse($this->response, $errorCode !== null ? $errorCode : $this->getLastError());
    }

    /**
     * Prepare response on check request
     *
     * @param int $errorCode
     *
     * @return string
     */
    public function getCheckResponse($errorCode = null)
    {
        return $this->getTransport()->getCheckResponse($this->response, $errorCode !== null ? $errorCode : $this->getLastError());
    }

    /**
     * Get last error code
     *
     * @return int
     */
    public function getLastError()
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
    public function getParam($name)
    {
        return $this->getResponseParam($name);
    }

    /**
     * Get name of payment service
     *
     * @return string
     */
    public function getName()
    {
        return 'yandex';
    }

    /**
     * Payment system need form
     * You can not get url for redirect
     *
     * @return bool
     */
    public function needForm()
    {
        return false;
    }

    /**
     * Generate payment form
     *
     * @param int       $orderId
     * @param int       $paymentId
     * @param float     $amount
     * @param string    $currency
     * @param string    $paymentType
     * @param string    $successReturnUrl
     * @param string    $failReturnUrl
     * @param string    $description
     * @param array     $extraParams
     * @param Arrayable $receipt
     *
     * @return string
     */
    public function getPaymentForm($orderId,
                                   $paymentId,
                                   $amount,
                                   $currency = self::CURRENCY_RUR,
                                   $paymentType = self::PAYMENT_TYPE_CARD,
                                   $successReturnUrl = '',
                                   $failReturnUrl = '',
                                   $description = '',
                                   $extraParams = [],
                                   $receipt = null)
    {
        // TODO: Implement getPaymentForm() method.
    }

    /**
     * Get payment id
     *
     * @return string
     */
    public function getPaymentId()
    {
        // TODO: Implement getPaymentId() method.
    }
}