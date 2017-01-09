<?php

/**
 * Class Gingerpayments
 */
class Gingerpayments
{
    /**
     * Default currency for Ginger Order
     */
    const DEFAULT_CURRENCY = 'EUR';

    /**
     * @var string
     */
    protected $paymentMethod;

    /**
     * Ginger Payments Order statuses
     */
    const GINGER_STATUS_EXPIRED = 'expired';
    const GINGER_STATUS_NEW = 'new';
    const GINGER_STATUS_PROCESSING = 'processing';
    const GINGER_STATUS_COMPLETED = 'completed';
    const GINGER_STATUS_CANCELLED = 'cancelled';
    const GINGER_STATUS_ERROR = 'error';

    /**
     * @param string $paymentMethod
     */
    public function __construct($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @param object $config
     * @return \GingerPayments\Payment\Client
     */
    public function getGingerClient($config)
    {
        require_once(DIR_SYSTEM.'library/gingerpayments/ginger-php/vendor/autoload.php');

        $ginger = \GingerPayments\Payment\Ginger::createClient(
            $config->get($this->getPaymentSettingsFieldName('api_key'))
        );

        if ($config->get($this->getPaymentSettingsFieldName('bundle_cacert'))) {
            $ginger->useBundledCA();
        }

        return $ginger;
    }

    /**
     * Method maps Ginger order status to OpenCart specific
     *
     * @param string $gingerOrderStatus
     * @return string
     */
    public function getOrderStatus($gingerOrderStatus, $config)
    {
        switch ($gingerOrderStatus) {
            case Gingerpayments::GINGER_STATUS_NEW:
                $orderStatus = $config->get($this->getPaymentSettingsFieldName('order_status_id_new'));
                break;
            case Gingerpayments::GINGER_STATUS_EXPIRED:
                $orderStatus = $config->get($this->getPaymentSettingsFieldName('order_status_id_expired'));
                break;
            case Gingerpayments::GINGER_STATUS_PROCESSING:
                $orderStatus = $config->get($this->getPaymentSettingsFieldName('order_status_id_processing'));
                break;
            case Gingerpayments::GINGER_STATUS_COMPLETED:
                $orderStatus = $config->get($this->getPaymentSettingsFieldName('order_status_id_completed'));
                break;
            case Gingerpayments::GINGER_STATUS_CANCELLED:
                $orderStatus = $config->get($this->getPaymentSettingsFieldName('order_status_id_cancelled'));
                break;
            case Gingerpayments::GINGER_STATUS_ERROR:
                $orderStatus = $config->get($this->getPaymentSettingsFieldName('order_status_id_error'));
                break;
            default:
                $orderStatus = $config->get($this->getPaymentSettingsFieldName('order_status_id_new'));
                break;
        }

        return $orderStatus;
    }

    /**
     * @param array $orderInfo
     * @return array
     */
    public function getCustomerInformation(array $orderInfo)
    {
        $customer = array(
            'address_type' => 'customer',
            'country' => $orderInfo['payment_iso_code_2'],
            'email_address' => $orderInfo['email'],
            'first_name' => $orderInfo['payment_firstname'],
            'last_name' => $orderInfo['payment_lastname'],
            'merchant_customer_id' => $orderInfo['customer_id'],
            'phone_numbers' => [$orderInfo['telephone']],
            'address' => implode("\n", array_filter(array(
                $orderInfo['payment_company'],
                $orderInfo['payment_address_1'],
                $orderInfo['payment_address_2'],
                $orderInfo['payment_firstname']." ".$orderInfo['payment_lastname'],
                $orderInfo['payment_postcode']." ".$orderInfo['payment_city']
            ))),
            'locale' => self::formatLocale($orderInfo['language_code'])
        );

        return $customer;
    }

    /**
     * @param array $orderInfo
     * @return string
     */
    public function getOrderDescription(array $orderInfo, $language)
    {
        $language->load('extension/payment/'.$this->paymentMethod);

        return $language->get('text_transaction').$orderInfo['order_id'];
    }

    /**
     * @param array $orderInfo
     * @param object $currency
     * @return int
     */
    public function getAmountInCents($orderInfo, $currency)
    {
        $amount = $currency->format(
            $orderInfo['total'],
            $orderInfo['currency_code'],
            $orderInfo['currency_value'],
            false
        );

        return (int) (100 * round($amount, 2, PHP_ROUND_HALF_UP));
    }

    /**
     * @param string $fieldName
     * @return string
     */
    public function getPaymentSettingsFieldName($fieldName)
    {
        return $this->paymentMethod.'_'.$fieldName;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return static::DEFAULT_CURRENCY;
    }

    /**
     * @param string $locale
     * @return mixed
     */
    public function formatLocale($locale)
    {
        return strstr($locale, '-', true);
    }

    /**
     * @param array $orderInfo
     * @param object $paymentMethod
     * @return array
     */
    public function getOrderData(array $orderInfo, $paymentMethod)
    {
        $webhookUrl = $paymentMethod->config->get($this->getPaymentSettingsFieldName('send_webhook'))
            ? $paymentMethod->url->link('extension/payment/'.$this->paymentMethod.'/callback') : null;

        $issuerId = array_key_exists('issuer_id', $paymentMethod->request->post)
            ? $paymentMethod->request->post['issuer_id'] : null;

        return [
            'amount' => $this->getAmountInCents($orderInfo, $paymentMethod->currency),
            'currency' => $this->getCurrency(),
            'merchant_order_id' => $orderInfo['order_id'],
            'return_url' => $paymentMethod->url->link('extension/payment/'.$this->paymentMethod.'/callback'),
            'description' => $this->getOrderDescription($orderInfo, $paymentMethod->language),
            'customer' => $this->getCustomerInformation($orderInfo),
            'issuer_id' => $issuerId,
            'webhook_url' => $webhookUrl,
            'payment_info' => []
        ];
    }
}
