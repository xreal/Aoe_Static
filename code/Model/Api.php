<?php
class Aoe_Static_Model_Api extends Mage_Api_Model_Resource_Abstract {

    public function byTag($tagArray = array()){
        Mage::log($tagArray,null,'ApiCache.log');
        try{
            Mage::app()->cleanCache($tagArray);
        }catch(Exception $e){
            return $e;
        }

        return 'Cache cleared';
    }

}