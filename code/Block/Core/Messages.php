<?php
class Aoe_Static_Block_Core_Messages extends Mage_Core_Block_Messages
{
    public function getGroupedHtml()
    {
        if (!Mage::helper('aoestatic')->cacheContent()) {
            return parent::getGroupedHtml();
        }
        return '<div class="placeholder" rel="messages"></div>';
    }
}
