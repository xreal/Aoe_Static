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

    public function getHelper()
    {
        return Mage::helper('aoestatic');
    }

    /**
     * Listens to application_clean_cache event and gets notified when a product/category/cms
     * model is saved.
     *
     * @param $observer Mage_Core_Model_Observer
     * @return Magneto_Varnish_Model_Observer
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
        $urls = array();

        if ($tags == array()) {
            $errors = $helper->purgeAll();
            if (!empty($errors)) {
                Mage::getSingleton('adminhtml/session')->addError("Varnish Purge failed");
            } else {
                Mage::getSingleton('adminhtml/session')->addSuccess("The Varnish cache storage has been flushed.");
            }
            return;
        }
        // compute the urls for affected entities
        foreach ((array)$tags as $tag) {
            //catalog_product_100 or catalog_category_186
            $tag_fields = explode('_', $tag);
            if (count($tag_fields)==3) {
                if ($tag_fields[1]=='product') {
                    // get urls for product
                    $product = Mage::getModel('catalog/product')->load($tag_fields[2]);
                    $urls = array_merge($urls, $this->_getUrlsForProduct($product));
                } elseif ($tag_fields[1]=='category') {
                    $category = Mage::getModel('catalog/category')->load($tag_fields[2]);
                    $category_urls = $this->_getUrlsForCategory($category);
                    $urls = array_merge($urls, $category_urls);
                } elseif ($tag_fields[1]=='page') {
                    $urls = $this->_getUrlsForCmsPage($tag_fields[2]);
                }
            }
        }

        if (!empty($urls)) {
            $errors = $this->getHelper()->purge($urls);
            if (!empty($errors)) {
                Mage::getSingleton('adminhtml/session')->addError(
                    "Some Varnish purges failed: <br/>" . implode("<br/>", $errors));
            } else {
                $count = count($relativeUrls);
                if ($count > 5) {
                    $relativeUrls = array_slice($relativeUrls, 0, 5);
                    $relativeUrls[] = '...';
                    $relativeUrls[] = "(Total number of purged urls: $count)";
                }
                Mage::getSingleton('adminhtml/session')->addSuccess($helper->__("%s sites have been purged", count($relativeUrls)));
            }
        }
        $this->done = true;
        return $this;
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
