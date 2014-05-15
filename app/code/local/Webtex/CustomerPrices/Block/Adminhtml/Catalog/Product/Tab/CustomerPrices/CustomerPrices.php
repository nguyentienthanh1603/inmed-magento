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
 * Customer Prices extension
 *
 * @category   Webtex
 * @package    Webtex_CustomerPrices
 * @author     Webtex Dev Team <dev@webtex.com>
 */
class Webtex_CustomerPrices_Block_Adminhtml_Catalog_Product_Tab_CustomerPrices_CustomerPrices
    extends Mage_Adminhtml_Block_Widget
{
    protected $_product;
    protected $_customers;
    protected $_websites;

    /**
     * Define tier price template file
     *
     */
    public function __construct()
    {
        $this->_setCustomers();
        $this->_product = Mage::registry('product');
        $this->setTemplate('webtex/customerprices/catalog/product/customerpricesgrid.phtml');
    }
    
    public function _prepareLayout()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label'     => Mage::helper('catalog')->__('Add Price'),
                'onclick'   => 'return customerPriceControl.showCustomerGrid();',
                'class'     => 'add'
            ));

        $button->setName('add_customer_price_item_button');

        $this->setChild('add_button', $button);
        return parent::_prepareLayout();
    
    }

    public function getProduct()
    {
        return $this->_product;
    }

    public function getValues()
    {
        if($this->_product->getId()) {
            $collection = Mage::getModel('customerprices/prices')->getPricesCollection($this->_product->getId());
            return $collection;
        } else {
            return false;
        }
    }

    public function getCustomerEmail()
    {
       $customerEmails = array();
       foreach($this->_customers as $_customer)
       {
          $customerEmails[] = array('customerId' => $_customer['entity_id'],
                                    'customerEmail' => $_customer['email'],
                                    );
       }
       return $customerEmails;
    }

    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }
    
    public function _setCustomers()
    {
        if(!$this->_customers) {
            $this->_customers = array();
        }
        
        $collection = Mage::getResourceModel('customer/customer_collection')
            ->addNameToSelect()
            ->addAttributeToSelect('email');
        
        foreach($collection as $customer)
        {
            $this->_customers[] = $customer->getData();
        }
    }

    public function getWebsites()
    {
        if (!is_null($this->_websites)) {
            return $this->_websites;
        }

        $this->_websites = array(
            0   => array(
                'name'      => Mage::helper('catalog')->__('All Websites'),
                'currency'  => Mage::app()->getBaseCurrencyCode()
            )
        );

        $websites           = Mage::app()->getWebsites(false);
        $productWebsiteIds  = $this->getProduct()->getWebsiteIds();
        foreach ($websites as $website) {
            /* @var $website Mage_Core_Model_Website */
            if (!in_array($website->getId(), $productWebsiteIds)) {
                continue;
            }
            $this->_websites[$website->getId()] = array(
                'name'      => $website->getName(),
                'currency'  => $website->getBaseCurrencyCode()
            );
        }
        
        return $this->_websites;
    }

    public function getDefaultWebsite()
    {
        return Mage::app()->getStore($this->getProduct()->getStoreId())->getWebsiteId();
    }

    public function allowChangeWebsite()
    {
        return false;
    }
}
