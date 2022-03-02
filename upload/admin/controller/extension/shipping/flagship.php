<?php

class ControllerExtensionShippingflagship extends Controller {
    private $error = array();

    public function install() {
        $this->load->model('extension/shipping/flagship');
        $this->model_extension_shipping_flagship->createFlagshipBoxesTable();
        $this->model_extension_shipping_flagship->createCouriersTable();
        $this->model_extension_shipping_flagship->createFlagshipShipmentsTable();
    }

    public function uninstall() {
        $this->load->model('extension/shipping/flagship');
        $this->model_extension_shipping_flagship->dropTables();
    }

    public function index() {
        $this->load->language('extension/shipping/flagship');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        $this->load->model('extension/shipping/flagship');

        //set smartship URLs here
        if(empty($this->config->get('smartship_api_url'))){
            $this->model_extension_shipping_flagship->addUrls();
        }

        $allBoxes = $this->model_extension_shipping_flagship->getAllBoxes();
        $data["boxes_count"] = count($allBoxes);
        $data["boxes"] = $allBoxes;
        $data['token_set'] = $this->isTokenSet();
        if($data['token_set'] && empty($this->request->post['shipping_flagship_token'])){
            $this->request->post['shipping_flagship_token'] = $this->config->get('shipping_flagship_token');
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('shipping_flagship', $this->request->post);
            $this->model_extension_shipping_flagship->addUrls();
            $this->setTestUrls();
            $this->session->data['success'] = $this->language->get('text_success');
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
        $data['shipping_flagship_test'] = $this->config->get('shipping_flagship_test');
        $data['shipping_flagship_token'] = $this->config->get('shipping_flagship_token');

        $data['shipping_flagship_postcode'] = empty($this->config->get('shipping_flagship_postcode')) ? 'H9R5P9' : $this->config->get('shipping_flagship_postcode');
        $data['shipping_flagship_status'] = $this->config->get('shipping_flagship_status');
        $data['shipping_flagship_packing'] = $this->config->get('shipping_flagship_packing');
        $data['shipping_flagship_fee'] = empty($this->config->get('shipping_flagship_fee')) ? 0 : $this->config->get('shipping_flagship_fee');
        $data['shipping_flagship_markup'] = empty($this->config->get('shipping_flagship_markup')) ? 0 : $this->config->get('shipping_flagship_markup');
        $data['shipping_flagship_sort_order'] = $this->config->get('shipping_flagship_sort_order');
        $data['shipping_flagship_residential'] = $this->config->get('shipping_flagship_residential');
        $data['show_couriers'] = $this->isTokenSet();
        $data['couriers'] = $this->getAvailableServices();
        $data['action_couriers'] = $this->url->link('extension/shipping/flagship/couriers', 'user_token=' . $this->session->data['user_token'], true);
        $data['test_token_check'] = $this->checkIfTokenIsTestToken();


        if (isset($this->request->post['shipping_flagship_postcode'])) {
            $data['shipping_flagship_postcode'] = $this->request->post['shipping_flagship_postcode'];
        }
        if(isset($this->request->post['shipping_flagship_test'])){
            $data['shipping_flagship_test'] = $this->request->post['shipping_flagship_test'];
        }
        if (isset($this->request->post['shipping_flagship_token'])) {
            $data['shipping_flagship_token'] = $this->request->post['shipping_flagship_token'];
        }
        if (isset($this->request->post['shipping_flagship_status'])) {
            $data['shipping_flagship_status'] = $this->request->post['shipping_flagship_status'];
        }
        if (isset($this->request->post['shipping_flagship_packing'])) {
            $data['shipping_flagship_packing'] = $this->request->post['shipping_flagship_packing'];
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
        if(isset($this->request->post['shipping_flagship_residential'])) {
            $data['shipping_flagship_residential'] = $this->request->post['shipping_flagship_residential'];
        }

        $data['error'] = $this->error;
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/shipping/flagship', $data));
    }

    public function setTestUrls() : bool {
        $this->load->model('extension/shipping/flagship');
        if($this->request->post['shipping_flagship_test'] == 1){
            $this->model_extension_shipping_flagship->setTestApiUrl();
            return TRUE;
        }
        return FALSE;
    }

    public function boxes() : int {
        $this->load->model('extension/shipping/flagship');
        $this->model_extension_shipping_flagship->addBox($this->request->post);
        $this->response->redirect($this->url->link('extension/shipping/flagship', 'user_token=' . $this->session->data['user_token'], true));
        return 0;
    }

    /*
     * Mixed return type
     */
    public function couriers(){

        $selectedCouriers = isset($this->request->post["shipping_flagship_couriers"]) ? implode(",",$this->request->post["shipping_flagship_couriers"]) : '' ;
        $this->load->model('extension/shipping/flagship');
        if($this->model_extension_shipping_flagship->areCouriersSet() === TRUE){

            $this->model_extension_shipping_flagship->updateCouriers($selectedCouriers);
            return $this->response->redirect($this->url->link('extension/shipping/flagship', 'user_token=' . $this->session->data['user_token'], true));
        }

        $this->model_extension_shipping_flagship->saveCouriers($selectedCouriers);

        $this->session->data['success'] = 'Couriers Saved';
        $this->response->redirect($this->url->link('extension/shipping/flagship', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function prepareshipment() : int {
        $order_id = $this->request->get['order_id'];
        $payload = $this->getPayload($order_id);
        $orderLink = $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$order_id, true);
        $this->load->model('extension/shipping/flagship');        
        $shipment = $this->model_extension_shipping_flagship->prepareShipment($payload,$order_id,$orderLink);
        $this->model_extension_shipping_flagship->updateFlagshipShipmentId($shipment->id,$order_id,$shipment->status);

        $data['flagship_shipment_id'] = $shipment->id;
        $this->response->redirect($orderLink);
        return 0;
    }

    public function updateShipment() : int {
        $order_id = $this->request->get['order_id'];
        $payload = $this->getPayload($order_id);
        $flagship_shipment_id = $this->getFlagshipShipmentId();
        $orderLink = $this->url->link('sale/order/info', '&user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$order_id, true);
        $this->load->model('extension/shipping/flagship');
        $shipment = $this->model_extension_shipping_flagship->updateShipment($flagship_shipment_id,$payload,$order_id,$orderLink);

        $this->response->redirect($orderLink);
    }

    public function confirmShipment() : int {
        $data = [];
        $title = 'Confirm FlagShip Shipment';
        $this->document->setTitle($title);
        $data['title'] = $title;
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $flagship_shipment_id = $this->getFlagshipShipmentId();
        $data['flagship_url'] = $this->config->get('smartship_web_url').'/dashboard';
        $data['cancel'] = $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$this->request->get['order_id'], true);
        $this->response->setOutput($this->load->view('extension/shipping/flagship_confirm_shipment', $data));
        return 0;
    }

    public function deleteBox() : int {
        $id = $this->request->get['id'];
        $this->load->model('extension/shipping/flagship');
        $this->model_extension_shipping_flagship->deleteBox($id);
        $this->response->redirect($this->url->link('extension/shipping/flagship', 'user_token=' . $this->session->data['user_token'], true));
        return 0;
    }

    protected function getFlagshipShipmentId() : int {
        $this->load->model('extension/shipping/flagship');
        $order_id = $this->request->get['order_id'];
        $flagship_shipment_id = $this->model_extension_shipping_flagship->getFlagshipShipmentId($order_id);
        return $flagship_shipment_id;
    }

    protected function getPayload(int $order_id) : array {
        $this->load->model('sale/order');
        $order_info = $this->model_sale_order->getOrder($order_id);
        $from = [
            "name" => substr($this->config->get('config_name'),0,29),
            "attn" => substr($this->config->get('config_owner'),0,20),
            "address" => substr($this->config->get('config_address'),0,stripos($this->config->get('config_address'),"\r\n")) == "" ? substr($this->config->get('config_address'),0,28) : substr(substr($this->config->get('config_address'),0,stripos($this->config->get('config_address'),"\r\n")),0,28),
            "suite" => "",
            "city" => "Toronto",
            "country" => "CA",
            "state" => "ON",
            "postal_code" => $this->config->get('shipping_flagship_postcode'),
            "phone" => $this->config->get('config_telephone')
        ];

        $toName = $order_info["shipping_company"] == NULL ? 
                    ($order_info["payment_company"] != NULL ? 
                        $order_info["payment_company"] : 
                        ($order_info["shipping_firstname"] == NULL ? 
                            $order_info['payment_firstname'].' '.$order_info['payment_lastname'] : 
                            $order_info["shipping_firstname"].' '.$order_info["shipping_lastname"])) : 
                        $order_info["shipping_company"];
        $toAttn = $order_info["shipping_firstname"] == NULL ? 
                    $order_info["payment_firstname"].' '.$order_info["payment_lastname"] : 
                    $order_info["shipping_firstname"].' '.$order_info["shipping_lastname"];
        $toAddress = $order_info["shipping_address_1"] == NULL ? $order_info["payment_address_1"] : $order_info["shipping_address_1"];
        $toSuite = $order_info["shipping_address_2"] == NULL ? $order_info["payment_address_2"] : $order_info["shipping_address_2"];
        $toCity = $order_info["shipping_city"] == NULL ? $order_info["payment_city"] : $order_info["shipping_city"];
        $toCountry = $order_info["shipping_iso_code_2"] == NULL ? $order_info["payment_iso_code_2"] : $order_info["shipping_iso_code_2"];
        $toState = $order_info["shipping_zone_code"] == NULL ? $order_info["payment_zone_code"] : $order_info["shipping_zone_code"];
        $toPostalCode = $order_info["shipping_postcode"] == NULL ? $order_info["payment_postcode"] : $order_info["shipping_postcode"];

        $to = [
            "name" => substr($toName,0,29),
            "attn" => substr($toAttn,0,20),
            "address" => substr($toAddress,0,29),
            "suite"=> substr($toSuite,0,17),
            "city" => substr($toCity,0,29),
            "country" => $toCountry,
            "state" => $toState,
            "postal_code" => $toPostalCode,
            "phone" => $order_info["telephone"],
            "is_commercial"=>"false"
        ];
        $products = $this->model_sale_order->getOrderProducts($order_id);
        $packages = $this->getPackages($products);
        $options = [
        "signature_required" => false,
        "reference" => "OpenCart Order# ".$order_info["order_id"],
        "address_correction"=> true
        ];
        $payment = [
            "payer" => "F"
        ];
        $payload = [
            "from" => $from,
            "to" => $to,
            "packages" => $packages,
            "options" => $options,
            "payment" => $payment
        ];
        return $payload;
    }
    protected function getPackages(array $products) : array {
        $this->load->model('catalog/product');
        $items = [];
        foreach ($products as $product) {
            $items = $this->getItems($product,$items);
        }
        $packages = [
            "items" => $this->config->get('shipping_flagship_packing') == 1 ? $this->getPackingPackages($items) : $items,
            "units" => 'imperial',
            "type" => "package",
            "content" => "goods",
        ];
        return $packages;
    }
    protected function getItems(array $product,array $items) : array {
        $this->load->model('extension/shipping/flagship');
        $imperialLengthClass = $this->model_extension_shipping_flagship->getImperialLengthClass();
        $imperialWeightClass = $this->model_extension_shipping_flagship->getImperialWeightClass();
        $orderProduct = $this->model_catalog_product->getProduct($product["product_id"]);
        for($i = 1; $i <= $product["quantity"]; $i++){
            $items[] = $this->getItemDetails($orderProduct,$imperialLengthClass,$imperialWeightClass);
        }
        return $items;
    }
    protected function getAllBoxes() : array {
        $this->load->model('extension/shipping/flagship');
        $boxes = $this->model_extension_shipping_flagship->getAllBoxes();
        for($i=0;$i<count($boxes);$i++){
            unset($boxes[$i]['id']);
        }
        return $boxes;
    }
    protected function getPackingPayload(array $items) : ?array {
        $boxes = $this->getAllBoxes();
        $units = 'imperial';
        if(count($boxes) == 0){
            return NULL;
        }
        $packingPayload = [
            'items' => $items,
            'boxes' => $boxes,
            'units' => $units
        ];
        return $packingPayload;
    }
    protected function getPackingPackages(array $items) : ?array {
        $payload = $this->getPackingPayload($items);
        if($payload == NULL){
            return [
                [
                    'length' => 1,
                    'width' => 1,
                    'height' => 1,
                    'weight' => 1,
                    'description' => 'Item 1'
                ]
            ];
        }

        $this->load->model('extension/shipping/flagship');
        $packings = $this->model_extension_shipping_flagship->packingRequest($payload);
        $packingPackages = [];
            foreach ($packings as $packing) {
                $packingPackages[] = [
                    'length' => ceil($packing->length),
                    'width' => ceil($packing->width),
                    'height' => ceil($packing->height),
                    'weight' => max($packing->weight,1),
                    'description' => $packing->box_model
                ];
            }

        return $packingPackages;
    }
    protected function getItemDetails(array $orderProduct,int $imperialLengthClass,int $imperialWeightClass) : array {

        $length = $orderProduct["length_class_id"] != $imperialLengthClass ? 
                        $this->length->convert($orderProduct["length"],$orderProduct["length_class_id"],$imperialLengthClass) : $orderProduct["length"];
        $width = $orderProduct["length_class_id"] != $imperialLengthClass ? 
                        $this->length->convert($orderProduct["width"],$orderProduct["length_class_id"],$imperialLengthClass) : $orderProduct["width"];
        $height = $orderProduct["length_class_id"] != $imperialLengthClass ? 
                        $this->length->convert($orderProduct["height"],$orderProduct["length_class_id"],$imperialLengthClass) : $orderProduct["height"];
        $weight = $orderProduct["weight_class_id"] != $imperialWeightClass ? 
                    $this->weight->convert($orderProduct["weight"],$orderProduct["weight_class_id"],$imperialWeightClass) : 
                    $orderProduct["weight"];
        return [
                "length" => ceil($length),
                "width"  => ceil($width),
                "height" => ceil($height),
                "weight" => max($weight,1),
                "description" => $orderProduct["name"]
            ];
    }

    protected function validate() : bool {
        if (!utf8_strlen($this->request->post['shipping_flagship_postcode'])) {
            $this->error['shipping_flagship_postcode'] = true;
        }
        if (!utf8_strlen($this->request->post['shipping_flagship_token'])) {
            $this->error['shipping_flagship_token'] = true;
        }
        if(utf8_strlen($this->request->post['shipping_flagship_token']) && !$this->validateToken($this->request->post['shipping_flagship_test'],$this->request->post['shipping_flagship_token'])){
            $this->error['token_validation'] = true;
        }
        if (!utf8_strlen($this->request->post['shipping_flagship_fee'])) {
            $this->error['shipping_flagship_fee'] = true;
        }
        if (!utf8_strlen($this->request->post['shipping_flagship_markup'])) {
            $this->error['shipping_flagship_markup'] = true;
        }
        if (!utf8_strlen($this->request->post['shipping_flagship_residential'])) {
            $this->error['shipping_flagship_residential'] = true;
        }
        return empty($this->error);
    }

    protected function checkIfTokenIsTestToken() : bool {
        $this->load->model('extension/shipping/flagship');
        $token = $this->config->get('shipping_flagship_token');
        $validateToken = !is_null($token) ?$this->model_extension_shipping_flagship->validateToken(1,$token) : FALSE;
        if($validateToken){
            return TRUE;
        }
        return FALSE;
    }

    protected function validateToken(int $testEnv,string $token) : bool {

        $this->load->model('extension/shipping/flagship');
        $validateToken = !is_null($token) ? $this->model_extension_shipping_flagship->validateToken($testEnv,$token) : FALSE;

        if($validateToken){
            $this->model_extension_shipping_flagship->setTestApiUrl();
        }

        return $validateToken;
    }

    protected function isTokenSame(string $token) : bool {
        return $this->config->get('shipping_flagship_token') == $this->request->post('shipping_flagship_token');
    }

    protected function isTokenSet() : bool {
        return empty($this->config->get('shipping_flagship_token')) ? false : true ;
    }

    protected function getAvailableServices() : ?array {
        if(!$this->isTokenSet()){
            return NULL;
        }
        $availableServicesArray = [];

        $this->load->model('extension/shipping/flagship');
        $availableServices = $this->model_extension_shipping_flagship->getAvailableServices() == NULL ? [] : $this->model_extension_shipping_flagship->getAvailableServices();

        $selectedCouriers = $this->model_extension_shipping_flagship->getSelectedCouriers();
        $selectedCouriers =  count($selectedCouriers->row) > 0 ? explode(",",$this->model_extension_shipping_flagship->getSelectedCouriers()->row["value"]) : [];

        foreach ($availableServices as $key => $courier) {
            $availableServicesArray = $this->prepareAvailableServices($key,$courier,$selectedCouriers,$availableServicesArray);
        }
        return $availableServicesArray;
    }

    protected function prepareAvailableServices(string $key,array $courier,array $selectedCouriers,array $availableServicesArray) : array {

        foreach ($courier as $value) {
            $courierName = $key == 'fedex' ? 'Fedex '.$value->courier_description : $value->courier_description;
            $availableServicesArray[] = [
                "name" => $key == 'fedex' ? 'Fedex '.$value->courier_description : $value->courier_description,
                "value" => $key == 'fedex' ? 'Fedex '.$value->courier_description : $value->courier_description,
                "selected" => in_array($courierName, $selectedCouriers) == 1 ? 'selected = selected' : '',
            ];
        }
        return $availableServicesArray;
    }
}
