<?php

class ControllerExtensionPaymentGingerSepa extends Controller {

    const GINGER_MODULE = 'ginger_sepa';

    public function index()
    {
        $this->load->controller('extension/payment/ginger_ideal', static::getGingerModuleName());
    }

    static function getGingerModuleName()
    {
        return static::GINGER_MODULE;
    }
}
