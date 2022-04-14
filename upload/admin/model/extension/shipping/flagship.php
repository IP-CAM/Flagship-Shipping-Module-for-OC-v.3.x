<?php


class ModelExtensionShippingflagship extends Model{

    public function createFlagshipShipmentsTable() : bool {
        $query = "CREATE TABLE IF NOT EXISTS `".DB_PREFIX."flagship_shipments` (
                    `id` INT(2) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `order_id` INT(2) UNSIGNED NOT NULL DEFAULT '0',
                    `flagship_shipment_id` INT(2) UNSIGNED DEFAULT NULL,
                    `shipment_status` VARCHAR(10) DEFAULT NULL,
                    PRIMARY KEY (`id`)
                )";
        $this->db->query($query);
        return true;
    }

    public function dropTables() : bool {
        $dropFlagshipBoxes = "DROP TABLE `".DB_PREFIX."flagship_boxes`";
        $dropFlagshipShipments = "DROP TABLE `".DB_PREFIX."flagship_shipments`";
        $dropFlagshipCouriers = "DROP TABLE `".DB_PREFIX."flagship_couriers`";
        $this->db->query($dropFlagshipBoxes);
        $this->db->query($dropFlagshipShipments);
        $this->db->query($dropFlagshipCouriers);
        return true;
    }

    public function updateFlagshipShipmentId(int $flagship_shipment_id,int $order_id, string $shipment_status) : int {
        $query = "INSERT INTO `".DB_PREFIX."flagship_shipments` SET `order_id`=".$order_id.", `flagship_shipment_id`=".$flagship_shipment_id.", `shipment_status`='".$shipment_status."'";
        $this->db->query($query);
        return 0;
    }

    public function getFlagshipShipmentId(int $order_id) : int {
        $query = $this->db->query("SELECT `flagship_shipment_id` FROM ".DB_PREFIX."flagship_shipments WHERE order_id = ".$order_id);
        return count($query->row) > 0 ? $query->row['flagship_shipment_id'] : 0;
    }

    public function getShipmentStatus(int $order_id) : ?string {
        $query = $this->db->query("SELECT `shipment_status` FROM `".DB_PREFIX."flagship_shipments` where `order_id`=".$order_id);
        return count($query->row) > 0 ? $query->row["shipment_status"] : NULL;
    }

    public function updateShipmentStatus(int $shipment_id,$orderId) : int {
        if($shipment_id != 0){

            $shipmentStatus = $this->getFlagshipShipmentStatus($shipment_id,$orderId);
            $query = "UPDATE `".DB_PREFIX."flagship_shipments` set `shipment_status`='".$shipmentStatus."' where `flagship_shipment_id`=".$shipment_id;
            $this->db->query($query);
        }
        return 0;
    }

    public function updateShipment(int $flagship_shipment_id,array $payload,int $orderId,string $orderLink) : \stdClass {
        $url = $this->config->get('smartship_api_url').'/ship/shipments/'.$flagship_shipment_id;
        $token = $this->config->get('shipping_flagship_token');

        $shipment = $this->apiRequest($url,$payload,$token,'PUT',30,'1.0.7',$orderId,$orderLink);
        return $shipment["response"]->content;
    }

    public function addUrls() : bool {
        $this->db->query("INSERT INTO ".DB_PREFIX."setting SET `store_id` = '0', `code` = 'shipping_flagship', `key` = 'smartship_api_url', `value` = 'https://api.smartship.io' ");
        $this->db->query("INSERT INTO ".DB_PREFIX."setting SET `store_id` = '0', `code` = 'shipping_flagship', `key` = 'smartship_web_url', `value` = 'https://smartship-ng.flagshipcompany.com' ");
        return true;
    }

    public function getImperialLengthClass() : int {
        $query = $this->db->query("SELECT `length_class_id` FROM ".DB_PREFIX."length_class_description where unit = 'in'");
        return $query->row['length_class_id'];
    }

    public function getImperialWeightClass() : int {
        $query = $this->db->query("SELECT `weight_class_id` FROM ".DB_PREFIX."weight_class_description where unit = 'lb'");
        return $query->row['weight_class_id'];
    }

    public function isFlagshipInstalled() : int {
        $query = $this->db->query("SELECT `code` FROM ".DB_PREFIX."extension WHERE `code` = 'flagship'");
        return count($query->rows);
    }

    public function createFlagshipBoxesTable() : bool {
        $query = $this->db->query("
                CREATE TABLE IF NOT EXISTS `".DB_PREFIX."flagship_boxes` (
                    `id` INT(2) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `box_model` VARCHAR(25) NULL DEFAULT 'NULL',
                    `length` INT(2) UNSIGNED NOT NULL DEFAULT '1',
                    `width` INT(2) UNSIGNED NOT NULL DEFAULT '1',
                    `height` INT(2) UNSIGNED NOT NULL DEFAULT '1',
                    `weight` INT(2) UNSIGNED NOT NULL DEFAULT '1',
                    `max_weight` INT(2) UNSIGNED NOT NULL DEFAULT '1',
                    PRIMARY KEY (`id`)
                )
            ");
        return true;
    }

    public function addBox(array $box) : bool {
        $query = $this->db->query("
                INSERT INTO `".DB_PREFIX."flagship_boxes` SET box_model = '".$box['box_model']."', length = ".$box['length'].", width = ".$box['width'].", height = ".$box['height'].", weight = ".$box['weight'].", max_weight = ".$box['max_weight']);
        return true;
    }

    public function getAllBoxes() : array {
        $query = $this->db->query("SELECT * from ".DB_PREFIX."flagship_boxes");
        return $query->rows;
    }

    public function deleteBox(int $id) : int {
        $query = $this->db->query("Delete from ".DB_PREFIX."flagship_boxes WHERE `id` = ".$id);
        return 0;
    }

    public function createCouriersTable() : bool {
        $query = $this->db->query("
            CREATE TABLE IF NOT EXISTS `".DB_PREFIX."flagship_couriers` (
                `id` INT(2) UNSIGNED NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(50) NULL DEFAULT 'NULL',
                `value` TEXT NOT NULL DEFAULT '',
                PRIMARY KEY (`id`)
            )
        ");
        return true;
    }

    public function saveCouriers(string $selectedCouriers) : bool {
        $query = $this->db->query("
            INSERT INTO `".DB_PREFIX."flagship_couriers` SET code='shipping_flagship_couriers', value='".$selectedCouriers."'");
        return true;
    }

    public function areCouriersSet() : bool {
        $query = $this->getSelectedCouriers();
        return count($query->row) > 0 ? TRUE : FALSE;
    }

    public function updateCouriers(string $selectedCouriers){
        $query = "UPDATE `".DB_PREFIX."flagship_couriers` SET value='".$selectedCouriers."' WHERE code = 'shipping_flagship_couriers'";
        return $this->db->query($query);
    }

    public function getSelectedCouriers() : \stdClass {
        return $this->db->query("SELECT * FROM `".DB_PREFIX."flagship_couriers`");
    }

    public function prepareShipment(array $payload, int $orderId, string $orderLink) : \stdClass {
        $url = $this->config->get('smartship_api_url').'/ship/prepare';
        $token = $this->config->get('shipping_flagship_token');
        $shipment = $this->apiRequest($url,$payload,$token,'POST',30,'1.0.7',$orderId,$orderLink);
        return $shipment["response"]->content;
    }

    public function packingRequest(array $payload) : array {
        $url = $this->config->get('smartship_api_url').'/ship/packing';
        $token = $this->config->get('shipping_flagship_token');
        $response = $this->apiRequest($url,$payload,$token,'POST',30);
        return $response["response"]->content->packages;
    }

    public function validateToken(string $testEnv, string $token) : bool {
        $url = $testEnv == 1 ? 'https://test-api.smartship.io' : 'https://api.smartship.io';
        $response = $this->apiRequest($url.'/check-token',[],$token, 'GET',30);
        if(count($response) == 0){
            return FALSE;
        }
        return $response['httpcode'] == 200 ? TRUE : FALSE;
    }

    public function setTestApiUrl() : bool {
        $checkFieldExists = $this->db->query("SELECT `value` FROM `".DB_PREFIX."setting` WHERE `key` = 'smartship_api_url'");
        if(count($checkFieldExists->row) == 0){
            $this->db->query("INSERT INTO ".DB_PREFIX."setting SET `store_id` = '0', `code` = 'shipping_flagship', `key` = 'smartship_api_url', `value` = 'https://test-api.smartship.io' ");
            return TRUE;
        }
        $this->db->query("UPDATE ".DB_PREFIX."setting SET `value` = 'https://test-api.smartship.io' WHERE `key` = 'smartship_api_url' ");
        $this->setTestWebUrl();
        return TRUE;
    }

    public function getAvailableServices() : ?\stdClass {
        $url = $this->config->get('smartship_api_url').'/ship/available_services';
        $token = $this->config->get('shipping_flagship_token');
        $response = $this->apiRequest($url,[],$token,'GET',30);

        return array_key_exists("response",$response) ? $response["response"]->content : NULL;
    }

    protected function setTestWebUrl() : bool{
        $checkFieldExists = $this->db->query("SELECT `value` FROM `".DB_PREFIX."setting` WHERE `key` = 'smartship_web_url'");
        if(count($checkFieldExists->row) == 0){
            $this->db->query("INSERT INTO ".DB_PREFIX."setting SET `store_id` = '0', `code` = 'shipping_flagship', `key` = 'smartship_web_url', `value` = 'https://test-smartshipng.flagshipcompany.com' ");
            return TRUE;
        }
        $this->db->query("UPDATE ".DB_PREFIX."setting SET `value` = 'https://test-smartshipng.flagshipcompany.com' WHERE `key` = 'smartship_web_url' ");
        return TRUE;
    }

    protected function getFlagshipShipmentStatus(int $shipment_id, int $orderId) : ?string {
        $url = $this->config->get('smartship_api_url').'/ship/shipments/'.$shipment_id;
        $token = $this->config->get('shipping_flagship_token');

        $shipment = $this->apiRequest($url,[],$token,'GET',10,'1.0.7',$orderId);
        $status = $shipment["response"]->content->status;
        return $status;
    }

    protected function apiRequest(string $url,array $json, string $apiToken,string $method, int $timeout, string $version='1.0.0', int $orderId=0, string $orderLink='') : array {

        $curl = curl_init();
        $storeName = $this->config->get('config_name');
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
                "X-Smartship-Token: ".$apiToken,
                "Content-Type: application/json",
                "X-F4OpenCart-Version: ".$version,
                "X-App-Name: OpenCart",
                "X-Order-Id: ".$orderId,
                "X-Store-Name: ".$storeName,
                "X-Order-Link: ".$orderLink
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
}
