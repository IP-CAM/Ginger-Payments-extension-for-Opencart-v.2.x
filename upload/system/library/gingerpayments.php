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
     * @param $apiKey
     * @return \GingerPayments\Payment\Client
     */
    public function getGingerClient($apiKey)
    {
        require_once(DIR_SYSTEM.'library/gingerpayments/ginger-php/vendor/autoload.php');

        return \GingerPayments\Payment\Ginger::createClient($apiKey);
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
            )))
        );

        return $customer;
    }

    /**
     * @param array $orderInfo
     * @return string
     */
    public function getOrderDescription(array $orderInfo, $language)
    {
        $language->load('payment/'.$this->paymentMethod);

        return $language->get('text_transaction').$orderInfo['order_id'];
    }

    /**
     * @param int $total
     * @param Currency $currency
     * @return int
     */
    public function getAmountInCents($total, $currency)
    {
        $amount = $currency->format($total, $this->getCurrency(), false, false);

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
}
