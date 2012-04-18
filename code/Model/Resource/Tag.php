<?php
/**
 * Tag model
 *
 * @author      Peter Uhlich <p.uhlich@votum.de>
 */
class Aoe_Static_Model_Resource_Tag extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('aoestatic/tag', 'tag_id');
    }
}
