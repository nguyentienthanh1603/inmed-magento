<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
abstract class Bubble_FPC_Model_Block_Abstract extends Varien_Object
{
    protected $_blockName = '';

    protected $_addCategoryId = false;

    protected $_addPageId = false;

    protected $_observers = array();

    protected $_defaultValue = null;

    protected $_value = null;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_blockName;
    }

    /**
     * @return string
     */
    public function getCookieName()
    {
        return str_replace('.', '_', $this->_blockName);
    }

    /**
     * @param $value
     * @return Mage_Core_Model_Cookie
     */
    public function setCookie($value)
    {
        return $this->_cookie()->set($this->getCookieName(), $value);
    }

    /**
     * @return Mage_Core_Model_Cookie
     */
    public function updateCookie()
    {
        return $this->setCookie($this->getHash());
    }

    /**
     * @return Mage_Core_Model_Cookie
     */
    public function deleteCookie()
    {
        return $this->_cookie()->delete($this->getCookieName());
    }

    /**
     * @return $this
     */
    public function clearCache()
    {
        return $this->_helper()->clearBlockCache($this->getName());
    }

    /**
     * @return array
     */
    public function getObservers()
    {
        return $this->_observers;
    }

    /**
     * @return string
     */
    public function getPlaceholder()
    {
        $placeholder = sprintf(
            "<?php \$this->getBlockHtml('%s', '%s', '%s', '%s', '%s', '%s'); ?>",
            $this->_helper()->getPackage(),
            $this->_helper()->getTheme(),
            $this->shouldAddCategoryId() ? $this->_helper()->getCategoryId() : '',
            $this->shouldAddPageId() ? $this->_helper()->getPageId() : '',
            $this->getName(),
            $this->getDefaultHash()
        );

        return $placeholder;
    }

    /**
     * @return bool
     */
    public function shouldAddCategoryId()
    {
        return $this->_addCategoryId;
    }

    /**
     * @return bool
     */
    public function shouldAddPageId()
    {
        return $this->_addPageId;
    }

    /**
     * @return Bubble_FPC_Helper_Data
     */
    protected function _helper()
    {
        return Mage::helper('bubble_fpc');
    }

    /**
     * @return Mage_Core_Model_Cookie
     */
    protected function _cookie()
    {
        return Mage::getSingleton('core/cookie');
    }

    /**
     * @return Mage_Customer_Model_Session
     */
    protected function _session()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * @return string
     */
    final public function getDefaultHash()
    {
        if (null === $this->_defaultValue) {
            $this->_defaultValue = $this->_getDefaultValue();
        }

        return $this->_helper()->crypt($this->_defaultValue);
    }

    /**
     * @return string
     */
    final public function getHash()
    {
        if (null === $this->_value) {
            $this->_value = $this->_getValue();
        }

        return $this->_helper()->crypt($this->_value);
    }

    /**
     * @return mixed
     */
    abstract protected function _getDefaultValue();

    /**
     * @return mixed
     */
    abstract protected function _getValue();
}