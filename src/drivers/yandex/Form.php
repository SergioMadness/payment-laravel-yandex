<?php namespace professionalweb\payment\drivers\yandex;

use professionalweb\payment\contracts\Form as IForm;

/**
 * Form for checkout widget
 */
class Form implements IForm
{
    /** @var string */
    private $returnUrl;

    /** @var string */
    private $confirmationToken;

    /**
     * @return string
     */
    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    /**
     * @param string $returnUrl
     *
     * @return $this
     */
    public function setReturnUrl(string $returnUrl): self
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfirmationToken(): string
    {
        return $this->confirmationToken;
    }

    /**
     * @param string $confirmationToken
     *
     * @return Form
     */
    public function setConfirmationToken(string $confirmationToken): Form
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * Render form
     *
     * @return string
     */
    public function render(): string
    {
        $id = uniqid('yoomoney', true);

        $result = '<script src="https://yookassa.ru/checkout-widget/v1/checkout-widget.js"></script>';
        $result .= '<div id="' . $id . '"></div>';
        $result .= '<script>
            //Инициализация виджета. Все параметры обязательные.
            const checkout = new window.YooMoneyCheckoutWidget({
              confirmation_token: \'' . $this->getConfirmationToken() . '\', //Токен, который перед проведением оплаты нужно получить от ЮKassa
              return_url: \'' . $this->getReturnUrl() . '\', //Ссылка на страницу завершения оплаты, это может быть любая ваша страница
              error_callback: function(error) {
                console.log(error)
              }
            });
        
            //Отображение платежной формы в контейнере
            checkout.render(\'' . $id . '\');
        </script>';

        return $result;
    }

    /**
     * Render fields
     *
     * @return string
     */
    public function renderFields(): string
    {
        return '';
    }

    /**
     * Form action
     *
     * @return string
     */
    public function getAction(): string
    {
        return '';
    }

    /**
     * Get form method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return '';
    }
}