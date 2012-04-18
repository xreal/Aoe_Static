<?php
class Aoe_Static_Model_Api extends Mage_Api_Model_Resource_Abstract
{
    public function byTag($tagArray = array())
    {
        try{
            Mage::app()->cleanCache($tagArray);
        }catch(Exception $e){
            return $e;
        }
        return 'Cache cleared';
    }

}
