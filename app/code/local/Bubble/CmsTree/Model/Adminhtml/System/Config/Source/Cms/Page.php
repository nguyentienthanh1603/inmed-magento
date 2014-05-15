<?php
/**
 * @category    Bubble
 * @package     Bubble_CmsTree
 * @version     1.2.6
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_CmsTree_Model_Adminhtml_System_Config_Source_Cms_Page
    extends Mage_Adminhtml_Model_System_Config_Source_Cms_Page
{
    public function toOptionArray()
    {
        if (!$this->_options) {
            foreach (Mage::app()->getStores(true) as $store) {
                $this->_options = array_merge((array) $this->_options, $this->getOptionIds($store));
            }
        }

        return $this->_options;
    }

    public function getOptionIds($store)
    {
        $collection = Mage::getModel('cms/page')->getCollection()
            ->addFieldToFilter('store_id', $store->getId())
            ->setOrder('position', Varien_Data_Collection::SORT_ORDER_ASC)
            ->setOrder('path', Varien_Data_Collection::SORT_ORDER_ASC);

        $options = array();
        foreach ($collection as $page) {
            $label = trim(str_repeat('--', $page->getLevel() - 1) . ' ' . $page->getTitle());
            if ($page->getLevel() == 1) {
                $label .= sprintf(' (%s)', $store->getCode() == 'admin' ? 'global' : $store->getCode());
            }
            $options[] = array(
                'value' => $page->getId(),
                'label' => $label,
            );
        }

        return $options;
    }
}