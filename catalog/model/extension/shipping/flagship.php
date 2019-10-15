<?php

require_once(DIR_STORAGE . 'vendor/autoload.php');

use Flagship\Shipping\Flagship;
use Flagship\Shipping\Exceptions\PackingException;
use Flagship\Shipping\Exceptions\QuoteException;

class ModelExtensionShippingFlagship extends Model {

    function getQuote($address) {

        $this->load->language('extension/shipping/flagship');

        $method_data = [];
        $quote_data = [];
        $api_url = $this->config->get('smartship_api_url');

        $flagship = new Flagship($this->config->get('shipping_flagship_token'),$api_url,'Opencart','1.0.0');
        
        try{
            $rates = $flagship->createQuoteRequest($this->getPayload($address))->execute()->sortByPrice();

            $flatFee = $this->config->get('shipping_flagship_fee');
            $markup = $this->config->get('shipping_flagship_markup');

            foreach ($rates as $rate) {
                $cost = $rate->getTotal();
                $cost += ($markup/100) * $cost;
                $cost += $flatFee;
                $quote_data[$rate->getCourierName().'_'.$rate->getCourierDescription()] = [
                    'code'         => 'flagship.'.$rate->getCourierName().'_'.$rate->getCourierDescription(),
                    'title'        => $rate->getCourierName() == 'FedEx' ? $rate->getCourierName().' '.$rate->getCourierDescription() : $rate->getCourierDescription(),
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
        } catch(QuoteException $e){
            $this->session->data['error'] = $e->getMessage();
        }
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
            "items" => $this->getPackageItems(),
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

    protected function getPackageItems() : array{

        $packageItems = [];
        try{

            $packings = $this->getPackings();
            return $this->checkPackings($packings);

        } catch(PackingException $e){
            $this->session->data['error'] = $e->getMessage();
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
                "length" => $packing->getLength(),
                "width" => $packing->getWidth(),
                "height" => $packing->getHeight(),
                "weight" => $packing->getWeight(),
                "description" => $packing->getBoxModel()
            ];
        }
        return $packageItems;
    }

    protected function getPackings() : ?\Flagship\Shipping\Collections\PackingCollection {
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

        $api_url = $this->config->get('smartship_api_url');
        $flagship = new Flagship($this->config->get('shipping_flagship_token'),$api_url,'Opencart','1.0.0');
        $packings = $flagship->packingRequest($packingPayload)->execute();

        return $packings;
    }

    protected function getItems() : array {
        $items = [];
        $products = $this->cart->getProducts();

        $imperialLengthClass = $this->getImperialLengthClass();
        $imperialWeightClass = $this->getImperialWeightClass();

        foreach ($products as $product) {
            $items[] = [
                "length" => $product["length"] == 0 ? 1 : ($product['length_class_id'] != $imperialLengthClass ? ceil($this->length->convert($product["length"],$product['length_class_id'],$imperialLengthClass)) : ceil($product["length"]) ),

                "width" => $product["width"] == 0 ? 1 : ($product['length_class_id'] != $imperialLengthClass ? ceil($this->length->convert($product["width"],$product['length_class_id'],$imperialLengthClass)) : ceil($product["width"])),

                "height" => $product["height"] == 0 ? 1 : ($product['length_class_id'] != $imperialLengthClass ? ceil($this->length->convert($product["height"],$product['length_class_id'],$imperialLengthClass)) : ceil($product["height"])),

                "weight" => $product["weight"] < 1 ? 1 : ($product['weight_class_id'] != $imperialWeightClass ? $this->weight->convert($product["weight"],$product['weight_class_id'],$imperialWeightClass) : $product["weight"]),

                "description" => $product["name"]
            ];
        }

        return $items;
    }

    protected function getCountryCode($country_id) : string {

        $this->load->model('localisation/country');
        $country = $this->model_localisation_country->getCountry($country_id);
        return $country['iso_code_2'];
    }

    protected function getZoneCode($zone_id) : string {

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
}
