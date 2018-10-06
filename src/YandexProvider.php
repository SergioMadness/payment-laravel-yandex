<?php namespace professionalweb\payment;

use Illuminate\Support\ServiceProvider;
use professionalweb\payment\contracts\PayService;
use professionalweb\payment\contracts\PaymentFacade;
use professionalweb\payment\interfaces\YandexService;
use professionalweb\payment\drivers\yandex\YandexKassa;
use professionalweb\payment\drivers\yandex\YandexDriver;

/**
 * Yandex payment provider
 * @package professionalweb\payment
 */
class YandexProvider extends ServiceProvider
{
    public function boot(): void
    {
        app(PaymentFacade::class)->registerDriver(YandexService::PAYMENT_YANDEX, YandexService::class);
    }

    /**
     * Bind two classes
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(YandexService::class, function ($app) {
            return (new YandexDriver(config('payment.yandex', [])))->setTransport(
                new YandexKassa(
                    config('payment.yandex.merchantId'),
                    config('payment.yandex.secretKey')
                )
            );
        });
        $this->app->bind(PayService::class, function ($app) {
            return (new YandexDriver(config('payment.yandex', [])))->setTransport(
                new YandexKassa(
                    config('payment.yandex.merchantId'),
                    config('payment.yandex.secretKey')
                )
            );
        });
        $this->app->bind(YandexDriver::class, function ($app) {
            return (new YandexDriver(config('payment.yandex', [])))->setTransport(
                new YandexKassa(
                    config('payment.yandex.merchantId'),
                    config('payment.yandex.secretKey')
                )
            );
        });
        $this->app->bind('\professionalweb\payment\Yandex', function ($app) {
            return (new YandexDriver(config('payment.yandex', [])))->setTransport(
                new YandexKassa(
                    config('payment.yandex.merchantId'),
                    config('payment.yandex.secretKey')
                )
            );
        });
    }
}