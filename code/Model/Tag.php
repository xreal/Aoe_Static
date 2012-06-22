<?php 
class Aoe_Static_Model_Tag extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('aoestatic/tag');
    }   

    /**
     * Loads collection and creates and adds 
     * items which are not yet in db to it.
     * 
     * @param mixed $pageTags 
     * @return void
     */
    public function loadTagsCollection($pageTags)
    {
        $pageTags = array_unique($pageTags);
        $collection = Mage::getModel('aoestatic/tag')->getCollection();
        if(count($pageTags) > 0){
            $collection->addFieldToFilter('tag', $pageTags);
        }
        $existingTags = array();
        foreach ($collection as $tag) {
            if (in_array($tag->getTag(), $pageTags)) {
                $existingTags[] = $tag->getTag();
            }
        }
        $newTags = array_diff($pageTags, $existingTags);
        // create all unexisting tags
        foreach ($newTags as $tag) {
            $tag = Mage::getModel('aoestatic/tag')->setTag($tag);
            $tag->save();
            $collection->addItem($tag);
        }
        return $collection;
    }
}
