<?php
class Aoe_Static_Model_Mysql4_Url extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('aoestatic/url', 'url_id');
    }   
}
