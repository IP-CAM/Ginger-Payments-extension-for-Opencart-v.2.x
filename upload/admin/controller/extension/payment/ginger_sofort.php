<?php

class ControllerExtensionPaymentGingerSofort extends Controller {

    const GINGER_MODULE = 'ginger_sofort';

    public function index()
    {
        $this->load->controller('extension/payment/ginger_ideal', static::getGingerModuleName());
    }

    static function getGingerModuleName()
    {
        return static::GINGER_MODULE;
    }
}
