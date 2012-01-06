<?php

/**
 * Data helper
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author      Toni Grigoriu <toni@tonigrigoriu.com>
 * @author      Stephan Hoyer <ste.hoyer@gmail.com>
 */
class Aoe_Static_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Chechs, if varnish is currently active
     *
     * @return boolean
     */
    public function isActive()
    {
        return Mage::app()->useCache('aoestatic');
    }

    /**
     * Check if a fullActionName is configured as cacheable
     *
     * @param string $fullActionName
     * @return false|int false if not cacheable, otherwise lifetime in seconds
     */
    public function isCacheableAction($fullActionName=null)
    {
        if (!$this->isActive()) {
            return false;
        }
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
     */
    public function cacheContent()
    {
        if (!$this->isActive()) {
            return false;
        }
        return !$this->isAjaxCallback() and $this->isCacheableAction();
    }

    /**
     * Determines, if we are currenly generating content for ajax callback.
     *
     * @return boolean
     */
    public function isAjaxCallback()
    {
        if (!$this->isActive()) {
            return false;
        }
        return 'phone_call_index' == $this->getFullActionName();
    }

    /**
     * Returns full action name of current request like so:
     * ModuleName_ControllerName_ActionName
     *
     * @return string
     */
    public function getFullActionName()
    {
        return implode('_', array(
            Mage::app()->getRequest()->getModuleName(),
            Mage::app()->getRequest()->getControllerName(),
            Mage::app()->getRequest()->getActionName(),
        ));
    }

    /**
     * Return varnish servers from configuration
     *
     * @return array
     */
    public function getVarnishServers()
    {
        $serverConfig = Mage::getStoreConfig('system/aoe_static/varnish_servers');
        $varnishServers = array();

        foreach (explode(',', $serverConfig) as $value ) {
            $varnishServers[] = trim($value);
        }

        if (0 == count($varnishServers)) {
            return array('127.0.0.1:80');
        }
        return $varnishServers;
    }

    /**
     * Purges all cache on all Varnish servers.
     *
     * @return array errors if any
     */
    public function purgeAll()
    {
        return $this->purge(array('.*'));
    }

    /**
     * Purge an array of urls on all varnish servers.
     *
     * @param array $urls
     * @return array with all errors
     */
    public function purge(array $urls)
    {
        $varnishServers = $this->getVarnishServers();
        $errors = array();

        // Init curl handler
        $curlHandlers = array(); // keep references for clean up
        $mh = curl_multi_init();

        foreach ((array)$varnishServers as $varnishServer) {
            foreach ($urls as $url) {
                $varnishUrl = "http://" . $varnishServer . $url;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $varnishUrl);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PURGE');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

                curl_multi_add_handle($mh, $ch);
                $curlHandlers[] = $ch;
            }
        }

        do {
            $n = curl_multi_exec($mh, $active);
        } while ($active);

        // Error handling and clean up
        foreach ($curlHandlers as $ch) {
            $info = curl_getinfo($ch);

            if (curl_errno($ch)) {
                $errors[] = "Cannot purge url {$info['url']} due to error" . curl_error($ch);
            } else if ($info['http_code'] != 200 && $info['http_code'] != 404) {
                $errors[] = "Cannot purge url {$info['url']}, http code: {$info['http_code']}. curl error: " . curl_error($ch);
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $errors;
    }
}
