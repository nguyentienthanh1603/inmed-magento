<?php

/**
 * Webtex
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Webtex EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.webtex.com/LICENSE-1.0.html
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@webtex.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.webtex.com/ for more information
 * or send an email to sales@webtex.com
 *
 * @category   Webtex
 * @package    Webtex_CustomerPrices
 * @copyright  Copyright (c) 2010 Webtex (http://www.webtex.com/)
 * @license    http://www.webtex.com/LICENSE-1.0.html
 */

/**
 * Customer Prics extension
 *
 * @category   Webtex
 * @package    Webtex_CustomerPrices
 * @author     Webtex Dev Team <dev@webtex.com>
 */
class Webtex_CustomerPrices_Model_Prices extends Mage_Core_Model_Abstract {

    protected static $_url = null;
    protected $_product = null;
    protected $_pricesCollection = null;

    public function _construct() {
        parent::_construct();
        $this->_init('customerprices/prices');
    }

    public function getPricesCollection($productId) {
        if (is_null($this->_pricesCollection)) {
            $this->_pricesCollection = Mage::getResourceModel('customerprices/prices_collection')
                            ->addProductFilter($productId);
        }

        return $this->_pricesCollection;
    }

    public function deleteByCustomer($productId, $customerId) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();

        $connection->delete($tablePrefix . 'customerprices_prices', 'product_id = ' . $productId . ' and customer_id = ' . $customerId);
    }

    public function deleteByProduct($productId) {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();

        $connection->delete($tablePrefix . 'customerprices_prices', 'product_id = ' . $productId);
    }

    public function getProductPrice($product, $qty = 1)
    {
        if (!Mage::helper('customer')->isLoggedIn() || !($product->getId())) {
            return $product->getData('price');
        }

        if(is_null($qty)) {
            $qty = 1;
        }
    
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();

        $customerId = Mage::helper('customer')->getCustomer()->getId();

        $query = $connection->select()->from($tablePrefix . 'customerprices_prices')
                            ->where('product_id = ' . $product->getId() . ' AND customer_id = ' . $customerId . ' AND qty <= '. $qty);
        $row = $connection->fetchRow($query);

        if(isset($row['price']) && $row['price'] > 0){
		return $row['price'];
	}
        return $product->getData('price');
    }

    public function getProductSpecialPrice($product, $qty = 1)
    {
        if (!Mage::helper('customer')->isLoggedIn() || !($product->getId())) {
            return $product->getData('special_price');
        }

        if(is_null($qty)) {
            $qty = 1;
        }

        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();

        $customerId = Mage::helper('customer')->getCustomer()->getId();

        $query = $connection->select()->from($tablePrefix . 'customerprices_prices')
                            ->where('product_id = ' . $product->getId() . ' AND customer_id = ' . $customerId . ' and qty <= '.$qty);
        $row = $connection->fetchRow($query);
	if(isset($row['special_price']) && $row['special_price'] > 0){
	    return $row['special_price'];
	}
        return $product->getData('special_price');
    }

    public function getProductCustomerPrice($product,$customerId,$qty = 1)
    {
        if(is_null($qty)) {
            $qty = 1;
        }

        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $query = $connection->select()->from($tablePrefix . 'customerprices_prices')
                            ->where('product_id = ' . $product->getId() . ' AND customer_id = ' . $customerId .' and qty <= '.$qty);
        $row = $connection->fetchRow($query);
        if(isset($row['price']) && $row['price'] > 0){
		return $row['price'];
	}
        return $product->getData('price');
    }

    public function getProductCustomerSpecialPrice($product,$customerId, $qty = 1)
    {
        if(is_null($qty)) {
            $qty = 1;
        }

        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();

        $query = $connection->select()->from($tablePrefix . 'customerprices_prices')
                            ->where('product_id = ' . $product->getId() . ' AND customer_id = ' . $customerId .' and qty <= '.$qty);
        $row = $connection->fetchRow($query);
	if(isset($row['special_price']) && $row['special_price'] > 0){
	    return $row['special_price'];
	}
        return $product->getData('special_price');
    }

    public function loadByCustomer($productId, $customerId, $qty = 1)
    {
        if(is_null($qty)) {
            $qty = 1;
        }
        $this->setData($this->getResource()->loadByCustomer($productId, $customerId, $qty));
        return $this;
    }

}