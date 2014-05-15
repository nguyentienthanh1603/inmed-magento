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

class Webtex_CustomerPrices_Block_Adminhtml_Catalog_Product_Tab_CustomerPrices_CustomerGrid_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('customerPricesCustomerSearchGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('entity_id');
    }


    protected function _prepareCollection()
    {
        $customers = array();
        $customers[] = 0;
        
        if($selected = $this->_getSelectedCustomers()) {
            foreach($selected as $item) {
                $customers[] = $item['customer_id'];
            }
        }

        $collection = Mage::getResourceModel('customer/customer_collection')
            ->addNameToSelect()
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('group_id');
            //->addFieldToFilter('entity_id',array('nin'=> $customers ));
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('customer')->__('ID'),
            'width'     => '50px',
            'index'     => 'entity_id',
            'type'  => 'number',
        ));

        $this->addColumn('name', array(
            'header'    => Mage::helper('customer')->__('Name'),
            'index'     => 'name'
        ));
        $this->addColumn('email', array(
            'header'    => Mage::helper('customer')->__('Email'),
            'width'     => '250',
            'index'     => 'email'
        ));

        $groups = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt'=> 0))
            ->load()
            ->toOptionHash();

        $this->addColumn('group', array(
            'header'    =>  Mage::helper('customer')->__('Group'),
            'width'     =>  '100',
            'index'     =>  'group_id',
            'type'      =>  'options',
            'options'   =>  $groups,
        ));


        $this->addColumn('in_prices', array(
            'header'    => Mage::helper('sales')->__('Select'),
            'header_css_class' => 'a-center',
            'type'      => 'checkbox',
            'values'    => $this->_getSelectedCustomers(),
            'name'      => 'in_prices',
            'align'     => 'center',
            'index'     => 'entity_id',
            'sortable'  => false,
        ));


        return parent::_prepareColumns();
    }

    public function _getSelectedCustomers()
    {
        if($product = Mage::registry('product')) {
            $productId = $product->getId();
        } else {
            $productId = Mage::app()->getRequest()->getParam('id');
        }
        if($productId) {
            $customers = Mage::getModel('customerprices/prices')
                ->getCollection()
                ->addProductFilter($productId)
                ->addFieldToSelect('customer_id')
                ->load();
            return $customers;
        } else {
            return false;
        }
    }

    public function getGridUrl()
    {
        return $this->getUrl('customerprices/catalog_product/customersgrid', array('_current'=> true));
    }
}
