<?php
class ControllerExtensionShippingDeliveryFee extends Controller {
    private $error = array();

    // Settings page
    public function index() {

        $this->load->language('/extension/shipping/delivery_fee');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('shipping_delivery_fee', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
        }

        $data['heading_title']    = $this->language->get('heading_title');

        $data['text_edit']        = $this->language->get('text_edit');
        $data['text_enabled']     = $this->language->get('text_enabled');
        $data['text_disabled']    = $this->language->get('text_disabled');
        $data['text_all_zones']   = $this->language->get('text_all_zones');
        $data['text_none']        = $this->language->get('text_none');

        $data['entry_geo_zone']   = $this->language->get('entry_geo_zone');
        $data['entry_status']     = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
        
        $data['button_save']      = $this->language->get('button_save');
        $data['button_cancel']    = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_shipping'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/shipping/delivery_fee', 'user_token=' . $this->session->data['user_token'], true)
        );

        // Tax class settings
        $this->load->model('localisation/tax_class');
        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        if (isset($this->request->post['shipping_delivery_fee_tax_class_id'])) {
        $data['shipping_delivery_fee_tax_class_id'] = $this->request->post['shipping_delivery_fee_tax_class_id'];
        } else {
        $data['shipping_delivery_fee_tax_class_id'] = $this->config->get('shipping_delivery_fee_tax_class_id');
        }
 
        // Geo zones settings
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['shipping_delivery_fee_geo_zone_id'])) {
        $data['shipping_delivery_fee_geo_zone_id'] = $this->request->post['shipping_delivery_fee_geo_zone_id'];
        } else {
        $data['shipping_delivery_fee_geo_zone_id'] = $this->config->get('shipping_delivery_fee_geo_zone_id');
        }
            if (isset($this->request->post['shipping_delivery_fee_geo_zone_id'])) {
        $data['shipping_delivery_fee_geo_zone_id'] = $this->request->post['shipping_delivery_fee_geo_zone_id'];
        } else {
        $data['shipping_delivery_fee_geo_zone_id'] = $this->config->get('shipping_delivery_fee_geo_zone_id');
        }
        
        // Set module settings
        if (isset($this->request->post['shipping_delivery_fee_origin'])) {
            $data['origin'] = $this->request->post['shipping_delivery_fee_origin'];
        } else {
            $data['origin'] = $this->config->get('shipping_delivery_fee_origin');
        }
        if (isset($this->request->post['shipping_delivery_fee_price_per_kilo'])) {
            $data['price_per_kilo'] = $this->request->post['shipping_delivery_fee_price_per_kilo'];
        } else {
            $data['price_per_kilo'] = $this->config->get('shipping_delivery_fee_price_per_kilo');
        }
        if (isset($this->request->post['shipping_delivery_fee_google_api_key'])) {
            $data['google_api_key'] = $this->request->post['shipping_delivery_fee_google_api_key'];
        } else {
            $data['google_api_key'] = $this->config->get('shipping_delivery_fee_google_api_key');
        }
        if (isset($this->request->post['shipping_delivery_fee_status'])) {
            $data['status'] = $this->request->post['shipping_delivery_fee_status'];
        } else {
            $data['status'] = $this->config->get('shipping_delivery_fee_status');
        } 		
        if (isset($this->request->post['shipping_delivery_fee_sort_order'])) {
            $data['sort_order'] = $this->request->post['shipping_delivery_fee_sort_order'];
        } else {
            $data['sort_order'] = $this->config->get('shipping_delivery_fee_sort_order');
        }

        $data['currency'] = $this->config->get('config_currency');
        
        
        // Form buttons
        $data['action']['save'] = $this->url->link('extension/shipping/delivery_fee', 'user_token=' . $this->session->data['user_token'], true);
        $data['action']['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping');
        
        // Set errors if any
        $data['error'] = $this->error;	

        // Load templates
        $data['header']         = $this->load->controller('common/header');
        $data['column_left']    = $this->load->controller('common/column_left');
        $data['footer']         = $this->load->controller('common/footer');
        
        // Output in HTML
        $htmlOutput = $this->load->view('extension/shipping/delivery_fee', $data);
        
        // Response
        $this->response->setOutput($htmlOutput);
    }

    // Validation
    public function validate() {
        // Check permissions
        if (!$this->user->hasPermission('modify', 'extension/shipping/delivery_fee')) {
            $this->error['permission'] = true;
            return false;
        }

        // Check Google API key's format is correct
        if(preg_match('/[^\w\d\s\,.#]/', $this->request->post['shipping_delivery_fee_origin'])) {
            $this->error['origin'] = true;
        }
        
        // Check if price per kilo is a numeric number
        if (!is_numeric($this->request->post['shipping_delivery_fee_price_per_kilo'])) {
            $this->error['price_per_kilo'] = true;
        }

        // Check if status is a bool
        if (!is_numeric($this->request->post['shipping_delivery_fee_status'])) {
            $this->error['status'] = true;
        }

        // Check if status is set and a number
        if (isset($this->request->post['shipping_delivery_sort_order']) && !is_numeric($this->request->post['shipping_delivery_sort_order'])) {
            $this->error['status'] = true;
        }
    
        // Check Google API key's format is correct
        if(!preg_match('/^\w*$/', $this->request->post['shipping_delivery_fee_google_api_key'])) {
            $this->error['google_api_key'] = true;
        }

        return !$this->error;
    }

    // Install extension function
    public function install() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting(
            'shipping_delivery_fee', 
            ['shipping_delivery_fee_status' => 1]
        );
    }
    
    // Uninstall extension function
    public function uninstall() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('shipping_delivery_fee');
    }

}