<?php

/**
 * Data helper
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author      Toni Grigoriu <toni@tonigrigoriu.com>
 */
class Aoe_Static_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Check if a fullActionName is configured as cacheable
     *
     * @param string $fullActionName
     * @return false|int false if not cacheable, otherwise lifetime in seconds
     */
    public function isCacheableAction($fullActionName=null)
    {
        if (is_null($fullActionName)) {
            $fullActionName = $this->getFullActionName();
        }
        $cacheActionsString = Mage::getStoreConfig('system/aoe_static/cache_actions');
        foreach (explode(',', $cacheActionsString) as $singleActionConfiguration) {
            list($actionName, $lifeTime) = explode(';', $singleActionConfiguration);
            if (trim($actionName) == $fullActionName) {
                return intval(trim($lifeTime));
            }
        }
        return false;
	}

    /**
     * Function to determine, if we are in cache context. Returns true, if
     * we are currently building content that will be written to cache.
     *
     * @return boolean
     **/
    public function cacheContent()
    {
        return !$this->isAjaxCallback() and $this->isCacheableAction();
    }

    /**
     * Determines, if we are currenly generating content for ajax callback.
     *
     * @return boolean
     **/
    public function isAjaxCallback()
    {
        return 'phone_call_index' == $this->getFullActionName();
    }

    /**
     * Returns full action name of current request like so:
     * ModuleName_ControllerName_ActionName
     *
     * @return string
     **/
    public function getFullActionName()
    {
        return implode('_', array(
            Mage::app()->getRequest()->getModuleName(),
            Mage::app()->getRequest()->getControllerName(),
            Mage::app()->getRequest()->getActionName(),
        ));
    }
}
