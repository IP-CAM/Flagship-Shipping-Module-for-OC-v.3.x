<?php

class ModelExtensionShippingflagship extends Model {

    function getQuote($address) {
        $this->load->language('extension/shipping/flagship');
        $method_data = [];
        $quote_data = [];

        $rates = $this->getRatesArray($address);
        $flatFee = $this->config->get('shipping_flagship_fee');
        $markup = $this->config->get('shipping_flagship_markup');
        foreach ($rates as $rate) {
            $cost = $rate->price->total;
            $cost += ($markup/100) * $cost;
            $cost += $flatFee;
            $quote_data[$rate->service->courier_name.'_'.$rate->service->courier_desc.'_'.$rate->service->courier_code] = [
                'code'         => 'flagship.'.$rate->service->courier_name.'_'.$rate->service->courier_desc.'_'.$rate->service->courier_code,
                'title'        => $rate->service->courier_name == 'FedEx' ? $rate->service->courier_name.' '.$rate->service->courier_desc : $rate->service->courier_desc,
                'cost'         => $cost,
                'tax_class_id' => 0,
                'text'         => $this->currency->format($cost, $this->session->data['currency'])
            ];
        }
        $method_data = [
            'code'       => 'flagship',
            'title'      => $this->language->get('text_title'),
            'quote'      => $quote_data,
            'sort_order' => $this->config->get('shipping_flagship_sort_order'),
            'error'      => false
        ];
        return $method_data;
    }

    protected function getRatesArray($address) : array {

        $ratesArray = [];
        $selectedCouriers = $this->getSelectedCouriers();
        $selectedRates = count($selectedCouriers) > 0 ? explode(",",$selectedCouriers[0]["value"]) : [];

        $rates = $this->getRates($this->getPayload($address));

        foreach ($rates as $rate) {
            $courierDescription =  strcasecmp($rate->service->courier_name,'fedex') === 0 ? 'Fedex '.$rate->service->courier_desc : $rate->service->courier_desc;
            $ratesArray[] = in_array($courierDescription, $selectedRates) == 1 ? $rate : NULL;
        }

        $ratesArray = array_filter($ratesArray, function($value){ return $value != NULL; });

        return $ratesArray;
    }

    /*
     * Mixed return type
     */

    protected function getRates(array $payload) {
        $url = $this->config->get('smartship_api_url').'/ship/rates';
        $token = $this->config->get('shipping_flagship_token');
        $response = $this->apiRequest($url,$payload,$token,'POST',30);
        return  array_key_exists("response", $response) ? $response["response"]->content : [];
    }

    protected function apiRequest(string $url,array $json, string $apiToken,string $method, int $timeout, string $flagshipFor='OpenCart',string $version='1.0.0') : array {

        $curl = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS  => json_encode($json),
            CURLOPT_HTTPHEADER => array(
                "X-Smartship-Token: ". $apiToken,
                "Content-Type: application/json",
                "X-F4".$flagshipFor."-Version: ".$version
                )
            ];
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        $responseArray = [
            "response"  => json_decode($response),
            "httpcode"  => $httpcode
        ];
        curl_close($curl);

        if(($httpcode >= 400 && $httpcode < 600) || ($httpcode === 0) || ($response === false) || ($httpcode === 209)){
            return [];
        }
        return $responseArray;
    }


    protected function getPayload(array $address) : array {
        $from = [
            "city" => 'Montreal',
            "country" => $this->getCountryCode($this->config->get('config_country_id')),
            "state" => $this->getZoneCode($this->config->get('config_zone_id')),
            "postal_code" => $this->config->get('shipping_flagship_postcode'),
            "is_commercial" => true
        ];
        $to = [
            "city" => $address['city'],
            "country" => $address['iso_code_2'],
            "state" => $address['zone_code'],
            "postal_code" => $address['postcode'],
            "is_commercial" => false
        ];
        $packages = [
            "items" => $this->config->get('shipping_flagship_packing') == 1 ? $this->getPackageItems() : $this->getItems(),
            "units"=> 'imperial',
            "type"=>"package",
            "content"=>"goods"
        ];
        $payment = [
            "payer" => "F"
        ];
        $options = [
            "address_correction" => true
        ];
        $payload = [
            "from" => $from,
            "to" => $to,
            "packages" => $packages,
            "payment" => $payment,
            "options" => $options
        ];       
        return $payload;
    }
    protected function getPackageItems() : array {
        $packageItems = [];
        try{
            $packings = $this->getPackings();
            return $this->checkPackings($packings);
        } catch(PackingException $e){
            $this->session->data['error'] = $e->getMessage();
            return [];
        }
    }
    protected function checkPackings($packings) : array {
        if($packings == NULL){
            return [
                [
                    "length" => 1,
                    "width" => 1,
                    "height" => 1,
                    "weight" => 1,
                    "description" => "Item 1"
                ]
            ];
        }
        foreach ($packings as $packing) {
            $packageItems[] = [
                "length" => ceil($packing->length),
                "width" => ceil($packing->width),
                "height" => ceil($packing->height),
                "weight" => max($packing->weight,1),
                "description" => $packing->box_model
            ];
        }
        return $packageItems;
    }
    protected function getPackings() : ?array {
        $items = $this->getItems();
        $boxes = $this->getAllBoxes();
        if(count($boxes) == 0){
            return NULL;
        }
        $packingPayload = [
            "items" => $items,
            "boxes" => $boxes,
            "units" => 'imperial'
        ];     
        $packings = $this->packingRequest($packingPayload);
        return $packings;
    }

    protected function packingRequest(array $payload) : ?array {
        $url = $this->config->get('smartship_api_url').'/ship/packing';
        $token = $this->config->get('shipping_flagship_token');
        $response = $this->apiRequest($url,$payload,$token,'POST',30);
        return array_key_exists("response", $response) ? $response["response"]->content->packages : NULL ;
    }

    protected function getItems() : array {
        $items = [];
        $products = $this->cart->getProducts();
        $imperialLengthClass = $this->getImperialLengthClass();
        $imperialWeightClass = $this->getImperialWeightClass();
        
        foreach ($products as $orderProduct) {
            $length = $orderProduct["length_class_id"] != $imperialLengthClass ? 
                        $this->length->convert($orderProduct["length"],$orderProduct["length_class_id"],$imperialLengthClass) : $orderProduct["length"];
            $width = $orderProduct["length_class_id"] != $imperialLengthClass ? 
                            $this->length->convert($orderProduct["width"],$orderProduct["length_class_id"],$imperialLengthClass) : $orderProduct["width"];
            $height = $orderProduct["length_class_id"] != $imperialLengthClass ? 
                            $this->length->convert($orderProduct["height"],$orderProduct["length_class_id"],$imperialLengthClass) : $orderProduct["height"];
            $weight = $orderProduct["weight_class_id"] != $imperialWeightClass ? 
                    $this->weight->convert($orderProduct["weight"],$orderProduct["weight_class_id"],$imperialWeightClass) : 
                    $orderProduct["weight"];

            $items[] = [
                "length" => ceil($length),
                "width" => ceil($width),
                "height" => ceil($height),
                "weight" => $weight,
                "description" => $orderProduct["name"]
            ];
        }
        return $items;
    }
    protected function getCountryCode(int $country_id) : string {
        $this->load->model('localisation/country');
        $country = $this->model_localisation_country->getCountry($country_id);
        return $country['iso_code_2'];
    }
    protected function getZoneCode(int $zone_id) : string {
        $this->load->model('localisation/zone');
        $zone = $this->model_localisation_zone->getZone($zone_id);
        return $zone['code'];
    }
    protected function getImperialLengthClass() : int {
        $query = $this->db->query("SELECT length_class_id FROM ".DB_PREFIX."length_class_description where unit = 'in'");
        return $query->row['length_class_id'];
    }
    protected function getImperialWeightClass() : int {
        $query = $this->db->query("SELECT weight_class_id FROM ".DB_PREFIX."weight_class_description where unit = 'lb'");
        return $query->row['weight_class_id'];
    }
    protected function getAllBoxes() : array {
        $query = $this->db->query("SELECT box_model,length,width,height,weight,max_weight from ".DB_PREFIX."flagship_boxes");
        return $query->rows;
    }

    protected function getSelectedCouriers() : array {
        $query = $this->db->query("SELECT value FROM `".DB_PREFIX."flagship_couriers` ");
        return $query->rows;
    }
}
