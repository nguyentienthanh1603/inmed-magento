<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Model_Observer
{
    protected $_cached = false;

    protected $_placeholderFormat = '<!-- start fpc %s -->%s<!-- end fpc %s -->';

    protected $_placeholders = array();

    protected $_storages = array(
        'core/session',
        'customer/session',
        'catalog/session',
        'checkout/session',
        'tag/session'
    );

    public function disableCatalogSession()
    {
        Mage::getSingleton('catalog/session')->setParamsMemorizeDisabled(true);
    }

    public function onHttpResponseSendBefore(Varien_Event_Observer $observer)
    {
        $helper = $this->helper();
        $response = $observer->getEvent()->getResponse();
        if ($helper->isCacheable()) {
            Mage::getSingleton('core/translate')->setTranslateInline(false);
            if ($helper->isActionCacheable() && !$this->_cached) {
                $body = $response->getBody();
                foreach ($this->_placeholders as $hash => $placeholder) {
                    $pattern = sprintf('#' . $this->_placeholderFormat . '#s', $hash, '(.*?)', $hash);
                    $body = preg_replace($pattern, $placeholder, $body);
                }
                $helper->saveLayout($body);
                $this->_cached = true;
            }

            if ($response->canSendHeaders(false)) {
                $response->setHeader('Pragma', 'no-cache', true)
                    ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true);
            }
        }

        $cookie = $helper->cookie();
        $store = Mage::app()->getStore();
        if (!$cookie->get('store') || $cookie->get('store') != $store->getCode()) {
            // Save current store to ensure that it will be used by fpc.php
            $cookie->set('store', $store->getCode(), true);
        }

        // Will be deleted if hash is empty, which is good
        $cookie->set('layout', $helper->getLayoutHash());
    }

    public function onCoreBlockToHtmlAfter(Varien_Event_Observer $observer)
    {
        $helper = $this->helper();
        if ($helper->isCacheable() && !$this->_cached) {
            /* @var $block Mage_Core_Block_Abstract */
            $block = $observer->getEvent()->getBlock();
            $blockName = $block->getNameInLayout();
            if ($blockName == 'global_messages') {
                $block->setTemplate('bubble/fpc/messages.phtml');
                $observer->getTransport()->setHtml($block->renderView());
            } elseif ($helper->isBlockDynamic($blockName)) {
                // Save block html in cache
                $html = $observer->getTransport()->getHtml();
                $html = $helper->cleanHtml($html);
                $dynamicBlock = $helper->getBlockInstance($blockName);
                $helper->saveBlock($dynamicBlock->getName(), $html);

                if ($helper->isActionCacheable()) {
                    $placeholder = $dynamicBlock->getPlaceholder();
                    $hash = md5($placeholder);
                    $this->_placeholders[$hash] = $placeholder;
                    $html = sprintf($this->_placeholderFormat, $hash, $html, $hash);
                    $observer->getTransport()->setHtml($html);
                }
            }
        }
    }

    public function prepareMessages()
    {
        $helper = $this->helper();
        if ($helper->isCacheable()) {
            $messages = Mage::getModel('core/message_collection');
            foreach ($this->_storages as $storage) {
                $session = Mage::getSingleton($storage);
                foreach ($session->getMessages(true)->getItems() as $message) {
                    $messages->add($message);
                }
            }
            if ($messages->count()) {
                $html = Mage::app()->getLayout()
                    ->getMessagesBlock()
                    ->setMessages($messages)
                    ->setEscapeMessageFlag(true)
                    ->getGroupedHtml();
                $helper->cookie()->set('global_messages', utf8_decode($html), true, null, null, null, false);
            }
        }
    }

    public function onCoreCleanCache()
    {
        $this->helper()->clearCache();
    }

    public function onApplicationCleanCache(Varien_Event_Observer $observer)
    {
        $tags = $observer->getEvent()->getTags();
        if (empty($tags)) {
            $this->helper()->clearCache();
        }
    }

    public function onMassCacheEnable(Varien_Event_Observer $observer)
    {
        $controller = $observer->getEvent()->getControllerAction();
        $types = $controller->getRequest()->getParam('types');
        if (in_array('bubble_fpc', $types)) {
            $this->helper()->enableCache();
        }
    }

    public function onMassCacheDisable(Varien_Event_Observer $observer)
    {
        $controller = $observer->getEvent()->getControllerAction();
        $types = $controller->getRequest()->getParam('types');
        if (in_array('bubble_fpc', $types)) {
            $this->helper()->disableCache();
        }
    }

    public function onMassCacheRefresh(Varien_Event_Observer $observer)
    {
        $controller = $observer->getEvent()->getControllerAction();
        $types = $controller->getRequest()->getParam('types');
        if (in_array('bubble_fpc', $types)) {
            $this->helper()->clearCache();
        }
    }

    public function onProductSaveAfter(Varien_Event_Observer $observer)
    {
        $helper = $this->helper();
        if ($helper->isFpcEnabled()) {
            $product = $observer->getEvent()->getProduct();
            $helper->clearProductCache($product);
            $helper->clearSitemapCache();
            $helper->clearSearchCache();
        }
    }

    public function onCategorySaveAfter(Varien_Event_Observer $observer)
    {
        $helper = $this->helper();
        if ($helper->isFpcEnabled()) {
            $product = $observer->getEvent()->getCategory();
            $helper->clearCategoryCache($product);
            $helper->clearSitemapCache();
            $helper->clearSearchCache();
        }
    }

    public function onPageSaveAfter(Varien_Event_Observer $observer)
    {
        $helper = $this->helper();
        if ($helper->isFpcEnabled()) {
            $page = $observer->getEvent()->getObject();
            $helper->clearPageCache($page);
        }
    }

    public function onStockItemSaveAfter(Varien_Event_Observer $observer)
    {
        $helper = $this->helper();
        if ($helper->isFpcEnabled()) {
            $stockItem = $observer->getEvent()->getItem();
            $product = Mage::getModel('catalog/product')->load($stockItem->getProductId());
            if ($product->getId()) {
                $helper->clearProductCache($product);
                $helper->clearSitemapCache();
                $helper->clearSearchCache();
                $helper->clearHomeCache();
            }
        }
    }

    public function onReindexPriceAfter()
    {
        $helper = $this->helper();
        if ($helper->isFpcEnabled()) {
            $process = Mage::getModel('index/process')->load('catalog_product_price', 'indexer_code');
            if (!$process->getId() || $process->getMode() == Mage_Index_Model_Process::MODE_MANUAL) {
                $this->helper()->clearCache();
            }
            $helper->clearHomeCache();
        }
    }

    public function onReindexUrlAfter()
    {
        $helper = $this->helper();
        if ($helper->isFpcEnabled()) {
            $process = Mage::getModel('index/process')->load('catalog_url', 'indexer_code');
            if (!$process->getId() || $process->getMode() == Mage_Index_Model_Process::MODE_MANUAL) {
                $this->helper()->clearCache();
            }
        }
    }

    public function onReindexSearchAfter()
    {
        $helper = $this->helper();
        if ($helper->isFpcEnabled()) {
            $process = Mage::getModel('index/process')->load('catalogsearch_fulltext', 'indexer_code');
            if (!$process->getId() || $process->getMode() == Mage_Index_Model_Process::MODE_MANUAL) {
                $this->helper()->clearCache();
            }
        }
    }

    public function onReindexStockAfter()
    {
        $helper = $this->helper();
        if ($helper->isFpcEnabled()) {
            $process = Mage::getModel('index/process')->load('cataloginventory_stock', 'indexer_code');
            if (!$process->getId() || $process->getMode() == Mage_Index_Model_Process::MODE_MANUAL) {
                $this->helper()->clearCache();
            }
            $helper->clearHomeCache();
        }
    }

    public function initBlockObservers()
    {
        $helper = $this->helper();
        if ($helper->isFpcEnabled()) {
            foreach ($helper->getBlocks() as $block) {
                if ($block) {
                    /* @var $block Bubble_FPC_Model_Block_Abstract */
                    foreach ($block->getObservers() as $eventName => $method) {
                        $class = get_class($block);
                        $data = array(
                            'type'      => 'singleton',
                            'class'     => $class,
                            'method'    => $method,
                        );
                        foreach ($data as $key => $value) {
                            $path = sprintf('frontend/events/%s/observers/%s/%s', $eventName, $class, $key);
                            Mage::getConfig()->setNode($path, $value);
                        }
                    }
                }
            }
        }
    }

    /**
     * Fix for Magento 1.8.1.0 and form key validation on some actions.
     *
     * @param Varien_Event_Observer $observer
     */
    public function validateFormKey(Varien_Event_Observer $observer)
    {
        if ($this->helper()->isFpcEnabled()) {
            /** @var $action Mage_Core_Controller_Varien_Action */
            $action = $observer->getEvent()->getControllerAction();
            if ($action) {
                $formKey = Mage::getSingleton('core/session')->getFormKey();
                $action->getRequest()->setParam('form_key', $formKey);
            }
        }
    }

    /**
     * @return Bubble_FPC_Helper_Data
     */
    public function helper()
    {
        return Mage::helper('bubble_fpc');
    }
}