<?php

/**
 * Class ControllerPaymentGingerSepa
 */
class ControllerExtensionPaymentGingerSepa extends Controller
{
    /**
     * Default currency for Ginger Order
     */
    const DEFAULT_CURRENCY = 'EUR';

    /**
     * Ginger Payments module name
     */
    const MODULE_NAME = 'ginger_sepa';

    /**
     * Ginger Payments bank transfer details
     */
    const GINGER_BIC = 'RABONL2U';
    const GINGER_IBAN = 'NL65RABO0168706814';
    const GINGER_HOLDER = 'St. Derdengelden Ginger Payments';
    const GINGER_RESIDENCE = 'Utrecht';

    /**
     * @var \GingerPayments\Payment\Client
     */
    protected $ginger;

    /**
     * @var Gingerpayments
     */
    protected $gingerHelper;

    /**
     * ControllerPaymentGinger constructor.
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->gingerHelper = new Gingerpayments(static::MODULE_NAME);
        $this->ginger = $this->gingerHelper->getGingerClient($this->config);
    }

    /**
     * Index Action
     * @return mixed
     */
    public function index()
    {
        $this->language->load('extension/payment/'.static::MODULE_NAME);
        $this->load->model('checkout/order');

        try {
            $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            if ($orderInfo) {
                $gingerOrderData = $this->gingerHelper->getOrderData($orderInfo, $this);
                $gingerOrder = $this->createGingerOrder($gingerOrderData);
                $paymentReference = $this->getBankPaymentReference($gingerOrder);

                $this->model_checkout_order->addOrderHistory(
                    $gingerOrder->getMerchantOrderId(),
                    $this->gingerHelper->getOrderStatus($gingerOrder->getStatus(), $this->config),
                    'Ginger Payments Bank Transfer order: '.$gingerOrder->id()->toString(),
                    true
                );
                $this->model_checkout_order->addOrderHistory(
                    $gingerOrder->getMerchantOrderId(),
                    $this->gingerHelper->getOrderStatus($gingerOrder->getStatus(), $this->config),
                    'Ginger Payments Bank Transfer Reference ID: '.$paymentReference,
                    true
                );
            }
        } catch (\Exception $e) {
            $this->session->data['error'] = $e->getMessage();
        }

        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['ginger_bank_details'] = $this->language->get('ginger_bank_details');
        $data['ginger_payment_reference'] = $this->language->get('ginger_payment_reference').$paymentReference;
        $data['ginger_iban'] = $this->language->get('ginger_iban').static::GINGER_IBAN;
        $data['ginger_bic'] = $this->language->get('ginger_bic').static::GINGER_BIC;
        $data['ginger_account_holder'] = $this->language->get('ginger_account_holder').static::GINGER_HOLDER;
        $data['ginger_residence'] = $this->language->get('ginger_residence').static::GINGER_RESIDENCE;
        $data['text_description'] = $this->language->get('text_description');
        $data['action'] = $this->url->link('checkout/success');

        return $this->load->view('extension/payment/'.static::MODULE_NAME, $data);
    }

    /**
     * Generate Ginger Payments order.
     *
     * @param array
     * @return \GingerPayments\Payment\Order
     */
    protected function createGingerOrder(array $orderData)
    {
        return $this->ginger->createSepaOrder(
            $orderData['amount'],            // Amount in cents
            $orderData['currency'],          // Currency
            $orderData['payment_info'],      // Payment information
            $orderData['description'],       // Description
            $orderData['merchant_order_id'], // Merchant Order Id
            $orderData['return_url'],        // Return URL
            null,                            // Expiration Period
            $orderData['customer'],          // Customer information
            null,                            // Extra information
            $orderData['webhook_url']        // Webhook URL
        );
    }

    /**
     * Method gets payment reference from Ginger order.
     *
     * @param \GingerPayments\Payment\Order $gingerOrder
     * @return mixed
     */
    protected function getBankPaymentReference(\GingerPayments\Payment\Order $gingerOrder)
    {
        $gingerOrder = $gingerOrder->toArray();

        return $gingerOrder['transactions'][0]['payment_method_details']['reference'];
    }
}
