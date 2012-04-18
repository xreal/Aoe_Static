<?php
/**
 * URL-to-Tag model
 *
 * @author      Peter Uhlich <p.uhlich@votum.de>
 */
class Aoe_Static_Model_Resource_Urltag extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('aoestatic/urltag', 'urltag_id');
    }
}
