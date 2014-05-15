<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Model_Block_Sidebar_Recently_Compared extends Bubble_FPC_Model_Block_Abstract
{
    protected $_blockName = 'right.reports.product.compared';

    protected $_observers = array(
        'catalog_product_compare_item_collection_clear' => 'updateCookie',
        'customer_logout'                               => 'deleteCookie',
    );

    /**
     * @return array
     */
    protected function _getDefaultValue()
    {
        return array();
    }

    /**
     * @return array
     */
    protected function _getValue()
    {
        $items = Mage::app()->getLayout()
            ->createBlock('reports/product_compared')
            ->getItemsCollection()
            ->getAllIds();

        return $items;
    }
}