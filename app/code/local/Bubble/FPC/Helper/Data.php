<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_CACHEABLE_ACTIONS    = 'bubble_fpc/general/cacheable_actions';
    const XML_PATH_DYNAMIC_BLOCKS       = 'bubble_fpc/general/dynamic_blocks';

    /**
     * Cacheable actions.
     *
     * @var array
     */
    protected $_actions = null;

    /**
     * Dynamic blocks.
     *
     * @var array
     */
    protected $_blocks = null;

    /**
     * List of cleared items.
     *
     * @var array
     */
    protected $_clearedItems = array();

    /**
     * @param Varien_Object $product
     * @return $this
     */
    public function clearProductCache(Varien_Object $product)
    {
        if ($product->getId()) {
            // Product urls
            foreach (Mage::app()->getStores() as $store) {
                /* @var $urls Mage_Core_Model_Resource_Url_Rewrite_Collection */
                $urls = Mage::getModel('core/url_rewrite')->getCollection()
                    ->addStoreFilter($store, false)
                    ->addFieldToFilter('product_id', $product->getId());
                foreach ($urls as $url) {
                    $this->clearCacheItem($url->getRequestPath(), $store);
                }
            }

            // Category urls
            $resource = Mage::getResourceModel('catalog/category_indexer_product');
            $conn = $resource->getReadConnection();
            $select = $conn->select()
                ->distinct(true)
                ->from($resource->getMainTable(), array('category_id'))
                ->where('product_id = :product_id');
            $categoryIds = $conn->fetchCol($select, array('product_id' => $product->getId()));
            /* @var $urls Mage_Catalog_Model_Resource_Category_Collection */
            $collection = Mage::getModel('catalog/category')->getCollection()
                ->addIdFilter($categoryIds);
            foreach ($collection as $category) {
                $this->clearCategoryCache($category);
            }
        }

        return $this;
    }

    /**
     * @param Varien_Object $category
     * @return $this
     */
    public function clearCategoryCache(Varien_Object $category)
    {
        if ($category->getId()) {
            foreach (Mage::app()->getStores() as $store) {
                /* @var $urls Mage_Core_Model_Resource_Url_Rewrite_Collection */
                $urls = Mage::getModel('core/url_rewrite')->getCollection()
                    ->addStoreFilter($store, false)
                    ->addFieldToFilter('category_id', $category->getId())
                    ->addFieldToFilter('product_id', array('null' => true));
                foreach ($urls as $url) {
                    $this->clearCacheItem($url->getRequestPath(), $store);
                }
            }
        }

        return $this;
    }

    /**
     * @param Varien_Object $page
     * @return $this
     */
    public function clearPageCache(Varien_Object $page)
    {
        if ($page->getId()) {
            foreach (Mage::app()->getStores() as $store) {
                $identifier = $page->getIdentifier();
                if (empty($identifier)) {
                    $this->clearCacheItem(FPC_HOME_FILENAME, $store);
                } else {
                    $this->clearCacheItem($identifier, $store);
                }
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function clearHomeCache()
    {
        foreach (Mage::app()->getStores() as $store) {
            $this->clearCacheItem(FPC_HOME_FILENAME, $store);
        }
    }

    /**
     * @return $this
     */
    public function clearSitemapCache()
    {
        foreach (Mage::app()->getStores() as $store) {
            $this->clearCacheItem('catalog/seo_sitemap/product', $store);
            $this->clearCacheItem('catalog/seo_sitemap/category', $store);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function clearSearchCache()
    {
        foreach (Mage::app()->getStores() as $store) {
            $this->clearCacheItem('catalogsearch/advanced/index', $store);
            $this->clearCacheItem('catalogsearch/advanced', $store);
            $this->clearCacheItem('catalogsearch/result/index', $store);
            $this->clearCacheItem('catalogsearch/result', $store);
        }

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function clearBlockCache($name)
    {
        $block = $this->getBlockInstance($name);
        if ($block) {
            foreach (Mage::app()->getStores() as $store) {
                $this->clearCacheItem($block->getName(), $store, FPC_BLOCKS_DIR);
            }
        }

        return $this;
    }

    /**
     * @param $path
     * @param $store
     * @return $this
     */
    public function clearCacheItem($path, $store, $scope = FPC_LAYOUTS_DIR)
    {
        try {
            $key = md5(serialize(func_get_args()));
            if (in_array($key, $this->_clearedItems)) {
                return $this; // Already done, skipping
            }
            $this->_clearedItems[] = $key;
            $baseDir = $this->getStoreDir($store);
            if ($scope) {
                $baseDir .= DS . $scope;
            }
            if (!is_dir($baseDir)) {
                return $this;
            }
            $pathinfo = pathinfo($path);
            $path = '';
            if ($pathinfo['dirname'] != '.') {
                $path = implode(DS, explode('/', $pathinfo['dirname'])) . DS;
            }
            $path .= $pathinfo['filename'];
            $fills = array();
            if ($scope == FPC_LAYOUTS_DIR) {
                $fills[] = FPC_REQUEST_HASH_LENGTH + 1; // hash + hostname
            } else {
                $fills[] = FPC_DEFAULT_HASH_LENGTH + 2; // hash + package + theme
                $fills[] = FPC_DEFAULT_HASH_LENGTH + 4; // hash + package + theme [+ category id + page id]
            }
            foreach ($fills as $fill) {
                $pattern = $baseDir . DS . implode(DS, array_fill(0, $fill, '*')) . DS . $path . '*' . FPC_FILE_EXTENSION;
                foreach (glob($pattern) as $name) {
                    @unlink($name);
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isFpcEnabled()
    {
        return defined('FPC_ENABLED') && Mage::app()->useCache('bubble_fpc');
    }

    /**
     * @return bool
     */
    public function isCacheable()
    {
        return $this->isFpcEnabled() && !$this->getRequest()->isAjax();
    }

    /**
     * @return bool
     */
    public function isStoreCacheable($store)
    {
        return !file_exists($this->_getStoreDisableFile($store));
    }

    /**
     * @return bool
     */
    public function enableCache()
    {
        return @unlink($this->getFpcDir() . DS . FPC_DISABLE_FLAG_FILE);
    }

    /**
     * @return int
     */
    public function disableCache()
    {
        return @file_put_contents($this->getFpcDir() . DS . FPC_DISABLE_FLAG_FILE, '');
    }

    /**
     * @return int
     */
    public function enableStoreCache($store)
    {
        return @unlink($this->_getStoreDisableFile($store));
    }

    /**
     * @return int
     */
    public function disableStoreCache($store)
    {
        return @file_put_contents($this->_getStoreDisableFile($store), '');
    }

    /**
     * @return string
     */
    public function getFpcDir()
    {
        return Mage::app()->getConfig()->getVarDir('fpc');
    }

    /**
     * @param null $store
     * @return string
     */
    public function getStoreDir($store = null)
    {
        return $this->getFpcDir() . DS . Mage::app()->getStore($store)->getCode();
    }

    /**
     * @param null $store
     * @return string
     */
    public function getLayoutDir($store = null)
    {
        return $this->getStoreDir($store) . DS . FPC_LAYOUTS_DIR;
    }

    /**
     * @param null $store
     * @return string
     */
    public function getBlockDir($store = null)
    {
        return $this->getStoreDir($store) . DS . FPC_BLOCKS_DIR;
    }

    /**
     * @return Mage_Core_Controller_Request_Http
     */
    public function getRequest()
    {
        return Mage::app()->getRequest();
    }

    /**
     * @param string $delimiter
     * @return string
     */
    public function getFullActionName($delimiter = '/')
    {
        /* @var $request Mage_Core_Controller_Request_Http */
        $request = $this->getRequest();

        return $request->getRequestedRouteName() . $delimiter .
            $request->getRequestedControllerName() . $delimiter .
            $request->getRequestedActionName();
    }

    /**
     * @param $name
     * @return array|bool
     */
    public function getBlockDirs($name)
    {
        $hash = $this->getBlockHash($name);
        if (!$hash) {
            return array();
        }

        return str_split($hash, 1);
    }

    /**
     * @param $name
     * @return bool|string
     */
    public function getBlockHash($name)
    {
        $block = $this->getBlockInstance($name);
        if ($block) {
            return $block->getHash();
        }

        return false;
    }

    /**
     * @param $name
     * @return Bubble_FPC_Model_Block_Abstract
     */
    public function getBlockInstance($name)
    {
        $blocks = $this->getBlocks();
        if (isset($blocks[$name])) {
            return $blocks[$name];
        }

        return false;
    }

    /**
     * @return array
     */
    public function getCacheableActions()
    {
        if (null === $this->_actions) {
            $this->_actions = array();
            $collection = Mage::getModel('bubble_fpc/action')->getCollection()
                ->addFieldToFilter('is_active', 1);
            foreach ($collection as $action) {
                $this->_actions[] = $action->getName();
            }
        }

        return $this->_actions;
    }

    /**
     * @param $model
     * @return Bubble_FPC_Model_Block_Abstract
     */
    public function getBlockModel($model)
    {
        return Mage::getSingleton($model);
    }

    /**
     * @return array|null
     */
    public function getBlocks()
    {
        if (null === $this->_blocks) {
            $this->_blocks = array();
            $collection = Mage::getModel('bubble_fpc/block')->getCollection()
                ->addFieldToFilter('is_active', 1);
            foreach ($collection as $block) {
                $dynamicBlock = $this->getBlockModel($block->getModel());
                if ($dynamicBlock && $dynamicBlock instanceof Bubble_FPC_Model_Block_Abstract) {
                    $this->_blocks[$dynamicBlock->getName()] = $dynamicBlock;
                }
            }
        }

        return $this->_blocks;
    }

    /**
     * @param bool $fullActionName
     * @return bool
     */
    public function isActionCacheable($fullActionName = false)
    {
        if (false === $fullActionName) {
            $fullActionName = $this->getFullActionName();
        }
        $cacheableActions = $this->getCacheableActions();

        return in_array($fullActionName, $cacheableActions);
    }

    /**
     * @param $name
     * @return bool
     */
    public function isBlockDynamic($name)
    {
        $blocks = $this->getBlocks();

        return array_key_exists($name, $blocks);
    }

    /**
     * @param $name
     * @return bool|string
     */
    public function getBlockContent($name)
    {
        try {
            $io = $this->_getBlockCacheDir($name);
            $file = $name . FPC_FILE_EXTENSION;
            if ($io->fileExists($file)) {
                return $io->read($file);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return false;
    }

    /**
     * @param $name
     * @param $html
     * @param bool $force
     */
    public function saveBlock($name, $html, $force = false)
    {
        try {
            $io = $this->_getBlockCacheDir($name);
            $file = $name . FPC_FILE_EXTENSION;
            if ($force || !$io->fileExists($file)) {
                $io->write($file, $html);
            }

            $block = $this->getBlockInstance($name);
            if ($block) {
                $block->updateCookie();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * @param $html
     * @return $this
     */
    public function saveLayout($html)
    {
        if (!$html) {
            return $this;
        }

        /* @var $request Mage_Core_Controller_Request_Http */
        $request = $this->getRequest();
        $requestString = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $hash = $this->getLayoutHash();
        $hash .= $this->crypt(trim($_SERVER['REQUEST_URI'], '/'), FPC_REQUEST_HASH_LENGTH);
        $layoutDirs = str_split($hash, 1);
        $requestDirs = explode('/', $requestString);
        $file = array_pop($requestDirs);
        if (!$file) {
            $file = FPC_HOME_FILENAME;
        }
        $file = pathinfo($file, PATHINFO_FILENAME);
        $query = $request->getServer('QUERY_STRING');
        if ($query) {
            $file .= '?' . urlencode($query);
        }
        $file .= FPC_FILE_EXTENSION;
        try {
            $io = $this->_getStoreCacheDir();
            $dir = $this->getLayoutDir();
            if (!$io->fileExists($dir, false)) {
                $io->mkdir($dir);
            }
            $io->cd($dir);
            if (!empty($layoutDirs)) {
                $layoutDirs[] = $_SERVER['HTTP_HOST'];
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
                    $layoutDirs[] = 'https';
                }
                $layoutDirs = array_merge($layoutDirs, $requestDirs);
                $dir = $dir . DS . implode(DS, $layoutDirs);
                $io->mkdir($dir);
                $io->cd($dir);
            }
            $file = $dir . DS . $file;
            if (!$io->fileExists($file)) {
                $io->write($file, $html);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getGlobalParams()
    {
        $params = array();
        $store = Mage::app()->getStore();
        if ($store->getCurrentCurrencyCode() != $store->getDefaultCurrencyCode()) {
            $params['currency'] = $store->getCurrentCurrencyCode();
        }
        if ($groupId = $this->session()->getCustomerGroupId()) {
            $params['group'] = $groupId;
        }

        return $params;
    }

    /**
     * @return string
     */
    public function getLayoutHash()
    {
        $params = $this->getGlobalParams();

        return !empty($params) ? $this->crypt($params) : '';
    }

    /**
     * @return $this
     */
    public function clearCache()
    {
        foreach (Mage::app()->getStores() as $store) {
            Varien_Io_File::rmdirRecursive($this->getStoreDir($store));
        }

        return $this;
    }

    /**
     * @param $value
     * @return string
     */
    public function crypt($value, $length = FPC_DEFAULT_HASH_LENGTH)
    {
//        return Bubble_FPC::crypt($value, $length);
		if (!is_scalar($value)) {
			$value = serialize($value);
		}

		return substr(sha1($value), 0, $length);
    }

    /**
     * @param $html
     * @return mixed
     */
    public function cleanHtml($html)
    {
        $html = Mage::getSingleton('core/url')->sessionUrlVar($html);

        return $html;
    }

    /**
     * @param null $store
     * @return Varien_Io_File
     */
    protected function _getStoreCacheDir($store = null)
    {
        $io = new Varien_Io_File();
        $io->open(array('path' => $this->getFpcDir()));
        $storeDir = $this->getStoreDir($store);
        if (!$io->fileExists($storeDir, false)) {
            $io->mkdir($storeDir);
        }
        $io->cd($storeDir);

        return $io;
    }

    /**
     * @param $store
     * @return string
     */
    protected function _getStoreDisableFile($store)
    {
        return $this->getFpcDir() . DS . Mage::app()->getStore($store)->getCode() . FPC_DISABLE_FILE_EXTENSION;
    }

    /**
     * @param $name
     * @return Varien_Io_File
     */
    protected function _getBlockCacheDir($name)
    {
        $io = $this->_getStoreCacheDir();
        $blockDir = $this->getBlockDir();
        if (!$io->fileExists($blockDir, false)) {
            $io->mkdir($blockDir);
        }
        $io->cd($blockDir);
        $dirs = $this->getBlockDirs($name);
        if (empty($dirs)) {
            Mage::throwException('Could not handle block ' . $name . ' for full page caching.');
        }
        $dirs[] = $this->getPackage();
        $dirs[] = $this->getTheme();
        $block = $this->getBlockInstance($name);
        if ($block
            && ($block->shouldAddCategoryId() || $block->shouldAddPageId())
            && ($this->getCategoryId() || $this->getPageId()))
        {
            $dirs[] = $this->getCategoryId();
            $dirs[] = $this->getPageId();
        }
        $dir = $blockDir . DS . implode(DS, $dirs);
        $io->mkdir($dir);
        $io->cd($dir);

        return $io;
    }

    /**
     * @return string
     */
    public function getPackage()
    {
        return Mage::getSingleton('core/design_package')->getPackageName();
    }

    /**
     * @return string
     */
    public function getTheme()
    {
        return Mage::getSingleton('core/design_package')->getTheme('layout');
    }

    /**
     * @return mixed
     */
    public function getCategoryId()
    {
        return (int) Mage::getSingleton('catalog/layer')->getCurrentCategory()->getId();
    }

    /**
     * @return mixed
     */
    public function getPageId()
    {
        return (int) Mage::getSingleton('cms/page')->getId();
    }

    /**
     * @return Mage_Core_Model_Cookie
     */
    public function cookie()
    {
        return Mage::getSingleton('core/cookie');
    }

    /**
     * @return Mage_Customer_Model_Session
     */
    public function session()
    {
        return Mage::getSingleton('customer/session');
    }
}