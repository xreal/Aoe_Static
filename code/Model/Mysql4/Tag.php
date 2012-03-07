<?php
class Aoe_Static_Model_Mysql4_Tag extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('aoestatic/tag', 'tag_id');
    }   
}
