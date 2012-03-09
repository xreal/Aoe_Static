<?php
/**
 * Observer model
 *
 * @category    Aoe
 * @package     Aoe_Static
 * @author      Stephan Hoyer <ste.hoyer@gmail.com>
 * @see         https://github.com/madalinoprea/magneto-varnish
 */
class Aoe_Static_Model_Cache
{
    var $done = false;
    var $tags = array();
    var $isCacheableAction = true;

    /**
     * Collect all layout tags that where used generating the content
     *
     * @param $observer Mage_Core_Model_Observer
     * @return Aoe_Static_Model_Cache
     **/
    public function collectTags($observer)
    {
        //cache check if cachable to improve performance
        $this->isCachableAction = $this->isCacheableAction
            && $this->getHelper()->isCacheableAction();

        if ($this->isCachableAction) {
            $this->tags = array_merge($this->tags, 
                $observer->getBlock()->getCacheTags());
        }
        return $this;
    }

    /**
     * Saves tags off current url to database.
     * 
     * @param mixed $observer 
     * @return Aoe_Static_Model_Cache
     */
    public function saveTags($observer)
    {
        if ($this->isCachableAction) {
            $tags = Mage::getModel('aoestatic/tag')
                ->loadTagsCollection($this->tags);
            $currentUrl = Mage::helper('core/url')->getCurrentUrl();
            $url = Mage::getModel('aoestatic/url')
                ->loadOrCreateUrl($currentUrl);
            $url->setTags($tags);
        }
        return $this;
    }

    public function getHelper()
    {
        return Mage::helper('aoestatic');
    }

    /**
     * Sets purge flag for each url
     * 
     * @param array $urls 
     * @param int $priority 
     * @return Aoe_Static_Model_Cache
     */
    public function markToPurge($urls=array(), $priority=0)
    {
        $count = count($urls);
        if ($count == 0) {
            return;
        }
        foreach ($urls as $url) {
            if (is_null($url->getPurgePrio()) || $priority > $url->getPurgePrio()) {
                $url->setPurgePrio($priority)->save();
            }
        }
        $helper = $this->getHelper();
        $msg = 1 == $count 
            ? $helper->__("1 site has been marked to be purged.")
            : $helper->__("%s sites have been marked to be purged.", $count);
        Mage::getSingleton('adminhtml/session')->addSuccess($msg);
        return $this;
    }

    /**
     * Listens to application_clean_cache event and gets notified when a product/category/cms
     * model is saved.
     *
     * @param $observer Mage_Core_Model_Observer
     * @return Aoe_Static_Model_Cache
     */
    public function purgeCache($observer)
    {
        //Only purge cache once per request
        if ($this->done) {
            return;
        }
        $helper = $this->getHelper();
        // If Varnish is not enabled on admin don't do anything
        if (!$helper->isActive()) {
            return;
        }

        $tags = $observer->getTags();

        if ($tags == array()) {
            $errors = $helper->purgeAll();
            if (!empty($errors)) {
                Mage::getSingleton('adminhtml/session')->addError("Varnish Purge failed");
            } else {
                Mage::getSingleton('adminhtml/session')->addSuccess("The Varnish cache storage has been flushed.");
            }
            return;
        }
        $this->purgeByTags($tags);
        $this->done = true;
    }

    /**
     * Purges cache by tags
     * 
     * @param Array\String $tags 
     * @return Aoe_Static_Model_Cache
     */
    public function purgeByTags($tags, $priority=0)
    {
        $urls = Mage::getModel('aoestatic/url')->getUrlsByTagStrings($tags);
        $this->purge($urls, $priority);
    }

    /**
     * Wrapper to purge cache synconiously or async depending on configuration
     * 
     * @param mixed $urls 
     * @param int $priority 
     * @return void
     */
    public function purge($urls, $priority=0)
    {
        $purgeSynconiously = Mage::getStoreConfig('system/aoe_static/purge_synconiously');
        if ($purgeSynconiously) {
            $this->syncronPurge($urls);
        } else {
            $this->markToPurge($urls, $priority);
        }
        return $this;
    }

    /**
     * Trigger purge of all urls directly at varnish instance
     * 
     * @param Array|Collection $urls 
     * @return Aoe_Static_Model_Cache
     */
    public function syncronPurge($urls)
    {
        $helper = $this->getHelper();
        if (!empty($urls)) {
            $errors = $this->getHelper()->purge($urls);
            if (!empty($errors)) {
                $msg = $helper->__(
                    "Some Varnish purges failed: %s",
                    $this->getListHtml($errors)
                );
                Mage::getSingleton('adminhtml/session')->addError($msg);
            } else {
                $count = count($urls);
                $msg = $helper->__( "%s sites have been purged.", $count);
                Mage::getSingleton('adminhtml/session')->addSuccess($msg);
            }
        }
        return $this;
    }

    /**
     * This method is used by the cron to trigger purges by priority
     * 
     * @return void
     */
    public function processPurge()
    {
        $urls = Mage::getModel('aoestatic/url')->getUrlsToPurgeByPrio();
        $pageSize = Mage::getStoreConfig('system/aoe_static/page_size');
        $pageCount = Mage::getStoreConfig('system/aoe_static/page_count');
        $urlsToPurge = array();
        foreach ($urls as $url) {
            if (count($urlsToPurge) < $pageSize) {
                $urlsToPurge[] = $url;
            } else {
                $this->syncronPurge($urls);
                $urls = array();
                $pageCount--;
            }
            if ($pageCount == 0) {
                break;
            }
        }
    }
    /**
     * Returns all the urls related to product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    protected function _getUrlsForProduct($product)
    {
        $urls = array();

        // Collect all rewrites
        $rewrites = Mage::getModel('core/url_rewrite')->getCollection();
        if (!Mage::getConfig('catalog/seo/product_use_categories')) {
            $rewrites->getSelect()
                ->where("id_path = 'product/{$product->getId()}'");
        } else {
            // Also show full links with categories
            $rewrites->getSelect()
                ->where("id_path = 'product/{$product->getId()}' OR id_path like 'product/{$product->getId()}/%'");
        }
        foreach($rewrites as $r) {
            unset($routeParams);
            $routePath = '';
            $routeParams['_direct'] = $r->getRequestPath();
            $routeParams['_store'] = $r->getStoreId();
            $url = Mage::getUrl($routePath, $routeParams);
            $urls[] = $url;
        }
        return $urls;
    }

    /**
     * Returns all the urls pointing to the category
     *
     * @param $category
     * @return array
     */
    protected function _getUrlsForCategory($category)
    {
        $urls = array();
        // Collect all rewrites
        $rewrites = Mage::getModel('core/url_rewrite')->getCollection();
        $rewrites->getSelect()->where("id_path = 'category/{$category->getId()}'");
        foreach($rewrites as $r) {
            $routeParams['_direct'] = $r->getRequestPath();
            $routeParams['_store'] = $r->getStoreId();
            $routeParams['_nosid'] = True;
            $url = Mage::getUrl('', $routeParams);
            $urls[] = $url;
        }
        return $urls;
    }

    /**
     * Returns all urls related to this cms page
     *
     * @param string $cmsPageId
     * @return array
     */
    protected function _getUrlsForCmsPage($cmsPageId)
    {
        $urls = array();
        $page = Mage::getModel('cms/page')->load($cmsPageId);
        foreach ($page->getStoreId() as $store) {
            $store = Mage::app()->getStore($store);
            $baseUrl = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
            if ($page->getId()) {
                $urls[] = $baseUrl . $page->getIdentifier();
            }
            //Current page is homepage?
            $homePageId = Mage::getStoreConfig('web/default/cms_home_page');
            if ($homePageId == $page->getIdentifier()) {
                $urls[] = $baseUrl;
            }
        }
        return $urls;
    }
}
