<?php
class ModelExtensionShippingDeliveryFee extends Model {

    function getQuote($address) {
        $this->load->language('extension/shipping/delivery_fee');
        /**
         * Query for finding if the customer is from the same zone as selected by the admin in the backend
         * @var [type]
         */
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get('shipping_delivery_fee_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");

        if (!$this->config->get('shipping_delivery_fee_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $cost    = 0;
        $api_key = $this->config->get('shipping_delivery_fee_google_api_key');
        $origin  = str_replace(' ', '+', $this->config->get('shipping_delivery_fee_origin'));
        $dest    = str_replace(' ', '+', $address['address_1'].'+'.$address['city'].'+'.$address['country'].'+'.$address['postcode']);
        $url     = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins=". $origin ."&destinations=". $dest ."&key=" . $api_key;
        $ch      = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $data = json_decode(curl_exec($ch));
        
        if(isset($data->rows[0]->elements[0]->distance->value)) {

            // Calculate price per kilometer.
            $distance = $data->rows[0]->elements[0]->distance->value;
            $ppk      = $this->config->get('shipping_delivery_fee_price_per_kilo');
            $cost     = round(($distance / 1000) * $ppk);
            $rounded  = round($cost);

            // Inject css to hide manditory input.
            echo '<style>#collapse-shipping-method [name="shipping_method"] { display: none; } #collapse-shipping-method label { pointer-events: none; cursor: text; padding-left: 0; }  </style>';

        }elseif ($data->rows[0]->elements[0]->status == 'ZERO_RESULTS') {
            // No route found.
            $status = false;
        }elseif ($data->rows[0]->elements[0]->status == 'NOT_FOUND') {
            // Address not found.
            $status = false;
        }else {
            // Catch all
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $quote_data = array();

            $quote_data['delivery_fee'] = array(
                'code'         => 'delivery_fee.delivery_fee',
                'title'        => $this->language->get('text_description'),
                'cost'         => $cost,
                'tax_class_id' => $this->config->get('shipping_delivery_fee_tax_class_id'),
                'text'         => $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_delivery_fee_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
            );

            $method_data = array(
                'code'       => 'delivery_fee',
                'title'      => $this->language->get('text_title'),
                'quote'      => $quote_data,
                'sort_order' => $this->config->get('shipping_delivery_fee_sort_order'),
                'error'      => false
            );
        }

        return $method_data;
    }
}