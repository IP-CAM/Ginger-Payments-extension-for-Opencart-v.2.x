<?php

/**
 * Class ControllerPaymentGingerSofort
 */
class ControllerExtensionPaymentGingerSofort extends Controller
{
    /**
     * Default currency for Ginger Order
     */
    const DEFAULT_CURRENCY = 'EUR';

    /**
     * Ginger Payments module name
     */
    const MODULE_NAME = 'ginger_sofort';

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

        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['action'] = $this->url->link('extension/payment/'.static::MODULE_NAME.'/confirm');

        return $this->load->view('extension/payment/'.static::MODULE_NAME, $data);
    }

    /**
     * Order Confirm Action
     */
    public function confirm()
    {
        try {
            $this->load->model('checkout/order');
            $orderInfo = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            if ($orderInfo) {
                $gingerOrderData = $this->gingerHelper->getOrderData($orderInfo, $this);
                $gingerOrder = $this->createGingerOrder($gingerOrderData);
                $checkoutUrl = $gingerOrder->firstTransactionPaymentUrl();

                $this->response->redirect($checkoutUrl);
            }
        } catch (\Exception $e) {
            $this->session->data['error'] = $e->getMessage();
            $this->response->redirect($this->url->link('checkout/checkout'));
        }
    }

    /**
     * Callback Action
     */
    public function callback()
    {
        $this->load->model('checkout/order');

        $gingerOrder = $this->ginger->getOrder($this->request->get['order_id']);
        $orderInfo = $this->model_checkout_order->getOrder($gingerOrder->getMerchantOrderId());

        if ($orderInfo) {
            $this->model_checkout_order->addOrderHistory(
                $gingerOrder->getMerchantOrderId(),
                $this->gingerHelper->getOrderStatus($gingerOrder->getStatus(), $this->config),
                'Ginger Payments SOFORT order: '.$gingerOrder->id()->toString(),
                true
            );
            $this->response->redirect($this->url->link('checkout/success'));
        }
    }

    /**
     * Generate Ginger Payments order.
     *
     * @param array
     * @return \GingerPayments\Payment\Order
     */
    protected function createGingerOrder(array $orderData)
    {
        return $this->ginger->createSofortOrder(
            $orderData['amount'],            // Amount in cents
            $orderData['currency'],          // Currency
            $orderData['payment_details'],   // Payment method details
            $orderData['description'],       // Description
            $orderData['merchant_order_id'], // Merchant Order Id
            $orderData['return_url'],        // Return URL
            null,                            // Expiration Period
            $orderData['customer'],          // Customer information
            null,                            // Extra information
            $orderData['webhook_url']        // Webhook URL
        );
    }
}
