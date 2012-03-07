<?php 
class Aoe_Static_Model_Url extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('aoestatic/url');
    }   

    public function loadOrCreateUrl($path)
    {
        $url = Mage::getModel('aoestatic/url')
            ->getCollection()
            ->addFieldToFilter('url', $path)
            ->getFirstItem();
        if (!$url->getId()) {
            $url->setUrl($path)->save();
        }
        return $url;
    }

    public function setTags($tags)
    {
        $this->deleteExistingTags();
        foreach ($tags as $tag) {
            $urlTag = Mage::getModel('aoestatic/urltag')
                ->setUrlId($this->getId())
                ->setTagId($tag->getId());
            $urlTag->save();
        }
        return $this;
    }

    protected function deleteExistingTags()
    {
        $urlTags = Mage::getModel('aoestatic/urltag')->getCollection()
            ->addFieldToFilter('url_id', $this->getId());
        foreach ($urlTags as $tag) {
            $tag->delete();
        }
    }
}

