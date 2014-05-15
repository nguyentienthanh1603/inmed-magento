<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Block_Adminhtml_Generate extends Mage_Adminhtml_Block_Template
{
    public function getStore()
    {
        return Mage::registry('fpc_store');
    }

    public function getGenerateUrl()
    {
        return $this->getUrl('*/*/generateUrl');
    }

    public function getUrls()
    {
        $store = $this->getStore();
        $categories = Mage::getResourceModel('sitemap/catalog_category')->getCollection($store->getId());
        $products = Mage::getResourceModel('sitemap/catalog_product')->getCollection($store->getId());
        $pages = Mage::getResourceModel('sitemap/cms_page')->getCollection($store->getId());
        $objects = array_merge($categories, $products, $pages);
        $urls = array();
        $baseUrl = $store->getBaseUrl();
        foreach ($objects as $object) {
            $urls[] = $baseUrl . $object->getUrl();
        }
        $urls = array_unique($urls);
        sort($urls);

        return $urls;
    }
}