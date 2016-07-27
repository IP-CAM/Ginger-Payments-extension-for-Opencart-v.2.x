<?php

/**
 * Class ControllerPaymentGingerBancontact
 */
class ControllerPaymentGingerBancontact extends Controller
{
    /**
     * Default currency for Ginger Order
     */
    const DEFAULT_CURRENCY = 'EUR';

    /**
     * Ginger Payments module name
     */
    const MODULE_NAME = 'ginger_bancontact';

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

        $this->ginger = $this->gingerHelper->getGingerClient(
            $this->config->get(
                $this->gingerHelper->getPaymentSettingsFieldName('api_key')
            )
        );
    }
    
    /**
     * Index Action
     * @return mixed
     */
    public function index()
    {
        $this->language->load('payment/'.static::MODULE_NAME);

        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['action'] = $this->url->link('payment/'.static::MODULE_NAME.'/confirm');

        return $this->load->view('payment/'.static::MODULE_NAME, $data);
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
                $gingerOrderData = $this->getGingerOrderData($orderInfo);
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
                'Ginger Payments Bancontact order: '.$gingerOrder->id()->toString(),
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
        return $this->ginger->createBancontactOrder(
            $orderData['amount'],            // Amount in cents
            $orderData['currency'],          // Currency
            $orderData['description'],       // Description
            $orderData['merchant_order_id'], // Merchant Order Id
            $orderData['return_url'],        // Return URL
            null,                            // Expiration Period
            $orderData['customer']           // Customer information
        );
    }

    /**
     * @param array $orderInfo
     * @return array
     */
    protected function getGingerOrderData(array $orderInfo)
    {
        return [
            'amount' => $this->gingerHelper->getAmountInCents($orderInfo['total'], $this->currency),
            'currency' => $this->gingerHelper->getCurrency(),
            'merchant_order_id' => $orderInfo['order_id'],
            'return_url' => $this->url->link('payment/'.static::MODULE_NAME.'/callback'),
            'description' => $this->gingerHelper->getOrderDescription($orderInfo, $this->language),
            'customer' => $this->gingerHelper->getCustomerInformation($orderInfo),
        ];
    }

    /**
     * @param array $orderInfo
     * @return string
     */
    protected function getOrderDescription(array $orderInfo)
    {
        $this->language->load('payment/'.static::MODULE_NAME);

        return $this->language->get('text_transaction').$orderInfo['order_id'];
    }
}
