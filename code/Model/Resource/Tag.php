<?php
/**
 * Tag model
 */
class Aoe_Static_Model_Resource_Tag extends Aoe_Static_Model_Resource_Abstract
{
    protected function _construct()
    {
        $this->_init('aoestatic/tag', 'tag_id');
    }
}
