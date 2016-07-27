<?php

class ModelPaymentGingerSofort extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('payment/ginger_sofort');

        $query = $this->db->query("SELECT * 
            FROM ".DB_PREFIX."zone_to_geo_zone 
            WHERE geo_zone_id = '".(int) $this->config->get('ginger_sofort_geo_zone_id')."' 
            AND country_id = '".(int) $address['country_id']."' 
            AND (zone_id = '".(int) $address['zone_id']."' 
            OR zone_id = '0');"
        );

        if ($this->config->get('ginger_sofort_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('ginger_sofort_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = [];

        if ($status) {
            $method_data = [
                'code' => 'ginger_sofort',
                'title' => $this->language->get('text_title'),
                'terms' => $this->language->get('text_payment_terms'),
                'sort_order' => $this->config->get('ginger_sofort_sort_order')
            ];
        }

        return $method_data;
    }
}
