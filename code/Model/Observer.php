<?php
/**
 * Observer model
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author		Fabrizio Branca <mail@fabrizio-branca.de>
 * @author      Toni Grigoriu <toni@tonigrigoriu.com>
 * @author      Stephan Hoyer <ste.hoyer@gmail.com>
 */
class Aoe_Static_Model_Observer
{
    /**
     * Check when varnish caching should be enabled.
     *
     * @param Varien_Event_Observer $observer
     * @return Aoe_Static_Model_Observer
     */
    public function processPreDispatch(Varien_Event_Observer $observer)
    {

        $helper = Mage::helper('aoestatic'); /* @var $helper Aoe_Static_Helper_Data */
        $event = $observer->getEvent(); /* @var $event Varien_Event */
        $controllerAction = $event->getControllerAction(); /* @var $controllerAction Mage_Core_Controller_Varien_Action */
        $fullActionName = $controllerAction->getFullActionName();

        $lifetime = $helper->isCacheableAction($fullActionName);

        $response = $controllerAction->getResponse(); /* @var $response Mage_Core_Controller_Response_Http */
        if ($lifetime) {
            // allow caching
            $response->setHeader('X-Magento-Lifetime', $lifetime, true); // Only for debugging and information
            $response->setHeader('Cache-Control', 'max-age='. $lifetime, true);
            $response->setHeader('aoestatic', 'cache', true);
        } else {
            // do not allow caching
            $cookie = Mage::getModel('core/cookie'); /* @var $cookie Mage_Core_Model_Cookie */

            $name = '';
            $loggedIn = false;
            $session = Mage::getSingleton('customer/session'); /* @var $session Mage_Customer_Model_Session  */
            if ($session->isLoggedIn()) {
                $loggedIn = true;
                $name = $session->getCustomer()->getName();
            }
            $response->setHeader('X-Magento-LoggedIn', $loggedIn ? '1' : '0', true); // Only for debugging and information
            $cookie->set('aoestatic_customername', $name, '3600', '/');
        }
        $response->setHeader('X-Magento-Action', $fullActionName, true); // Only for debugging and information

        return $this;
    }

    /**
     * Add layout handle 'aoestatic_cacheable' or 'aoestatic_notcacheable'
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeLoadLayout(Varien_Event_Observer $observer)
    {
        $helper = Mage::helper('aoestatic'); /* @var $helper Aoe_Static_Helper_Data */
        $event = $observer->getEvent(); /* @var $event Varien_Event */
        $controllerAction = $event->getAction(); /* @var $controllerAction Mage_Core_Controller_Varien_Action */
        $fullActionName = $controllerAction->getFullActionName();

        $lifetime = $helper->isCacheableAction($fullActionName);

        $handle = $lifetime ? 'aoestatic_cacheable' : 'aoestatic_notcacheable';

        $observer->getEvent()->getLayout()->getUpdate()->addHandle($handle);
    }

    /**
     * Returns current admin session.
     *
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * Purges complete Varnish cache if flag is set.
     *
     * @param $observer
     */
    public function cleanVarnishCache($observer)
    {
        $varnishHelper = Mage::helper('aoestatic'); /* @var $varnishHelper Magneto_Varnish_Helper_Data */
        $types = Mage::app()->getRequest()->getParam('types');
        if (Mage::app()->useCache('aoestatic') ) {
            if( (is_array($types) && in_array('aoestatic', $types)) || $types == "aoestatic") {
                $errors = $varnishHelper->purgeAll();
                if (count($errors) > 0) {
                    $this->_getSession()->addError(Mage::helper('adminhtml')->__("Error while purging Varnish cache:<br />" . implode('<br />', $errors)));
                } else {
                    $this->_getSession()->addSuccess(Mage::helper('adminhtml')->__("Varnish cache cleared!"));
                }
            }
            $varnishHelper->purgeByTags($types);
        }
    }
}
