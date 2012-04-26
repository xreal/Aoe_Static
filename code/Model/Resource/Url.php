<?php
/**
 * URL model
 */
class Aoe_Static_Model_Resource_Url extends Aoe_Static_Model_Resource_Abstract
{
    protected function _construct()
    {
        $this->_init('aoestatic/url', 'url_id');
    }
}
