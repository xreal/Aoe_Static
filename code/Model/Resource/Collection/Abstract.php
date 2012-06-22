<?php
$version = Mage::getVersionInfo();
if ($version['minor'] < 6) {
    abstract class Aoe_Static_Model_Resource_Collection_Abstract extends 
        Mage_Core_Model_Mysql4_Collection_Abstract
    { }
} else {
    abstract class Aoe_Static_Model_Resource_Collection_Abstract extends 
        Mage_Core_Model_Resource_Db_Collection_Abstract
    { }
}

