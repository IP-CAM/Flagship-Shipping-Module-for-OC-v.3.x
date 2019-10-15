<?php

require_once(DIR_STORAGE . 'vendor/autoload.php');

use Flagship\Shipping\Flagship;
use Flagship\Shipping\Exceptions\ValidateTokenException;

class ControllerExtensionShippingFlagship extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/shipping/flagship');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');
        $this->load->model('extension/shipping/flagship');
        $this->model_extension_shipping_flagship->createFlagshipBoxesTable();

        //set smartship URLs here
        if(empty($this->config->get('smartship_api_url'))){
            $this->model_extension_shipping_flagship->addUrls();
        }

        $allBoxes = $this->model_extension_shipping_flagship->getAllBoxes();
        $data["boxes_count"] = count($allBoxes);
        $data["boxes"] = $allBoxes;

        $data['token_set'] = $this->isTokenSet();

        if($data['token_set']){
            $this->request->post['shipping_flagship_token'] = $this->config->get('shipping_flagship_token');
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $this->model_setting_setting->editSetting('shipping_flagship', $this->request->post);
            $this->model_extension_shipping_flagship->addUrls();

            $this->session->data['success'] = $this->language->get('text_success');
            $this->model_extension_shipping_flagship->addFieldToOrder();
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
        }

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
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/shipping/flagship', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/shipping/flagship', 'user_token=' . $this->session->data['user_token'], true);
        $data['action_boxes'] = $this->url->link('extension/shipping/flagship/boxes', 'user_token=' . $this->session->data['user_token'], true);
        $data['action_delete_box'] = $this->url->link('extension/shipping/flagship/deleteBox', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);
        $data['shipping_flagship_token'] = $this->config->get('shipping_flagship_token');
        $data['shipping_flagship_postcode'] = empty($this->config->get('shipping_flagship_postcode')) ? 'H9R5P9' : $this->config->get('shipping_flagship_postcode');
        $data['shipping_flagship_status'] = $this->config->get('shipping_flagship_status');
        $data['shipping_flagship_fee'] = empty($this->config->get('shipping_flagship_fee')) ? 0 : $this->config->get('shipping_flagship_fee');
        $data['shipping_flagship_markup'] = empty($this->config->get('shipping_flagship_markup')) ? 0 : $this->config->get('shipping_flagship_markup');
        $data['shipping_flagship_sort_order'] = $this->config->get('shipping_flagship_sort_order');

        if (isset($this->request->post['shipping_flagship_postcode'])) {
            $data['shipping_flagship_postcode'] = $this->request->post['shipping_flagship_postcode'];
        }

        if (isset($this->request->post['shipping_flagship_token'])) {
            $data['shipping_flagship_token'] = $this->request->post['shipping_flagship_token'];
        }

        if (isset($this->request->post['shipping_flagship_status'])) {
            $data['shipping_flagship_status'] = $this->request->post['shipping_flagship_status'];
        }

        if (isset($this->request->post['shipping_flagship_fee'])) {
            $data['shipping_flagship_fee'] = $this->request->post['shipping_flagship_fee'];
        }

        if (isset($this->request->post['shipping_flagship_markup'])) {
            $data['shipping_flagship_markup'] = $this->request->post['shipping_flagship_markup'];
        }

        if (isset($this->request->post['shipping_flagship_sort_order'])) {
            $data['shipping_flagship_sort_order'] = $this->request->post['shipping_flagship_sort_order'];
        }

        $data['error'] = $this->error;
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/flagship', $data));
    }

    public function boxes(){
        $this->load->model('extension/shipping/flagship');
        $this->model_extension_shipping_flagship->addBox($this->request->post);
        $this->response->redirect($this->url->link('extension/shipping/flagship', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function deleteBox(){
        $id = $this->request->get['id'];
        $this->load->model('extension/shipping/flagship');
        $this->model_extension_shipping_flagship->deleteBox($id);
        $this->response->redirect($this->url->link('extension/shipping/flagship', 'user_token=' . $this->session->data['user_token'], true));
    }

    protected function validate() : bool {

        if (!utf8_strlen($this->request->post['shipping_flagship_postcode'])) {
            $this->error['shipping_flagship_postcode'] = true;
        }
        if (!utf8_strlen($this->request->post['shipping_flagship_token'])) {
            $this->error['shipping_flagship_token'] = true;
        }
        if(utf8_strlen($this->request->post['shipping_flagship_token']) && $this->validateToken($this->request->post['shipping_flagship_token'])){
            $this->error['token_validation'] = true;
        }
        if (!utf8_strlen($this->request->post['shipping_flagship_fee'])) {
            $this->error['shipping_flagship_fee'] = true;
        }
        if (!utf8_strlen($this->request->post['shipping_flagship_markup'])) {
            $this->error['shipping_flagship_markup'] = true;
        }

        return empty($this->error);
    }

    protected function validateToken($token) : int {
        $url = $this->config->get('smartship_api_url');
        $flagship = new Flagship($token,$url,'Opencart','1.0.0');
        try{
            $returnCode = $flagship->validateTokenRequest($token)->execute() == 200 ? 0 : 1;
            return $returnCode;
        } catch (ValidateTokenException $e){
            return 1;
        }
    }

    protected function isTokenSet() : bool {
        return empty($this->config->get('shipping_flagship_token')) ? false : true ;
    }

}
