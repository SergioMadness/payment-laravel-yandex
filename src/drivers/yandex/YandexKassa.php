<?php namespace professionalweb\payment\drivers\yandex;

use YooKassa\Client;
use professionalweb\payment\contracts\PayProtocol;

/**
 * Class to work with Yandex.Kassa
 *
 * @package professionalweb\payment\drivers\yandex
 */
class YandexKassa implements PayProtocol
{
    /**
     * @var Client
     */
    private $client;

    /**
     * Shop ID
     *
     * @var int
     */
    private $shopId;

    /**
     * Shop secret key
     *
     * @var string
     */
    private $shopSecret;

    /**
     * Yandex.Kassa constructor.
     *
     * @param int|null    $shopId
     * @param string|null $shopSecret
     */
    public function __construct(?int $shopId = null, ?string $shopSecret = null)
    {
        $this->setShopId($shopId)->setShopPassword($shopSecret);
    }

    /**
     * @return int
     */
    public function getShopId(): ?int
    {
        return $this->shopId;
    }

    /**
     * Set Shop id
     *
     * @param int $shopId
     *
     * @return $this
     */
    public function setShopId(?int $shopId): self
    {
        $this->shopId = $shopId;

        return $this;
    }


    /**
     * Get payment URL
     *
     * @param mixed $params
     *
     * @return string
     * @throws \Exception
     */
    public function getPaymentUrl(array $params): string
    {
        $response = $this->getClient()->createPayment($this->prepareParams($params));

        return $response->getConfirmation()->getType() === 'embedded' ?
            $response->getConfirmation()->getConfirmationToken() :
            $response->getConfirmation()->getConfirmationUrl();
    }

    /**
     * Validate params
     *
     * @param mixed $params
     *
     * @return bool
     */
    public function validate(array $params): bool
    {
        return true;
    }


    /**
     * Get payment ID
     *
     * @return mixed
     */
    public function getPaymentId(): string
    {
        // TODO: Implement getPaymentId() method.
    }

    /**
     * Get shop secret key
     *
     * @return string
     */
    public function getShopPassword(): ?string
    {
        return $this->shopSecret;
    }

    /**
     * Set shop secret key
     *
     * @param string|null $shopSecret
     *
     * @return $this;
     */
    public function setShopPassword(?string $shopSecret): self
    {
        $this->shopSecret = $shopSecret;

        return $this;
    }


    /**
     * Prepare response on notification request
     *
     * @param mixed $requestData
     * @param int   $errorCode
     *
     * @return string
     */
    public function getNotificationResponse($requestData, $errorCode): string
    {
        return 'ok';
    }

    /**
     * Prepare response on check request
     *
     * @param array $requestData
     * @param int   $errorCode
     *
     * @return string
     */
    public function getCheckResponse($requestData, $errorCode): string
    {
        return 'ok';
    }

    /**
     * Create Kassa.Yandex client
     *
     * @return Client
     */
    protected function getClient(): Client
    {
        if ($this->client === null) {
            $this->client = new Client();
            $this->client->setAuth($this->getShopId(), $this->getShopPassword());
        }

        return $this->client;
    }

    /**
     * Prepare parameters
     *
     * @param array $params
     *
     * @return array
     */
    public function prepareParams(array $params): array
    {
        return $params;
    }
}