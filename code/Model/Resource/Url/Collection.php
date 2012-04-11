<?php
/**
 * URL collection model resource
 *
 * @author      Peter Uhlich <p.uhlich@votum.de>
 */
class Aoe_Static_Model_Resource_Url_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('aoestatic/url');
    }
}
