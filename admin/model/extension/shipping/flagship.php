<?php

class ModelExtensionShippingFlagship extends Model{

    public function addFieldToOrder() : int {
        $query = $this->db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                                    WHERE table_name = '".DB_PREFIX."order'
                                    AND table_schema = '".DB_DATABASE."'
                                    AND column_name = 'flagship_shipment_id'");
        if($query == 0)
            $this->db->query("ALTER TABLE `".DB_PREFIX."order` ADD COLUMN `flagship_shipment_id` INT(10)");
        return 0;
    }

    public function updateFlagshipShipmentId(int $flagship_shipment_id,int $order_id) : int {
        $this->db->query("UPDATE `".DB_PREFIX."order` SET `flagship_shipment_id` =".$flagship_shipment_id." WHERE `order_id`=".$order_id);
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

    public function deleteBox($id) : int {
        $query = $this->db->query("Delete from ".DB_PREFIX."flagship_boxes WHERE `id` = ".$id);
        return 0;
    }
}
