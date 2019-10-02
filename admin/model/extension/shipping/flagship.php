<?php

class ModelExtensionShippingFlagship extends Model{

    public function addFieldToOrder() : int {
        $query = $this->db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                                    WHERE table_name = '".DB_PREFIX."order'
                                    AND table_schema = '".DB_DATABASE."'
                                    AND column_name = 'flagship_shipment_id'");
        if($query == 0)
            $this->db->query("ALTER TABLE ".DB_PREFIX."order ADD COLUMN flagship_shipment_id INT(10)");
        return 0;
    }

    public function updateFlagshipShipmentId(int $flagship_shipment_id,int $order_id) : int {
        $this->db->query("UPDATE ".DB_PREFIX."order SET flagship_shipment_id=".$flagship_shipment_id." WHERE order_id=".$order_id);
        return 0;
    }

    public function getFlagshipShipmentId(int $order_id) : ?int {
        $query = $this->db->query("SELECT `flagship_shipment_id` FROM ".DB_PREFIX."order WHERE order_id = ".$order_id);
        return $query->row['flagship_shipment_id'];
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

}
