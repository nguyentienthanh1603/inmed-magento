<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Model_Block_Sidebar_Cart extends Bubble_FPC_Model_Block_Abstract
{
    protected $_blockName = 'cart_sidebar';

    protected $_observers = array(
        'sales_quote_save_after'    => 'updateCookie',
        'customer_logout'           => 'deleteCookie',
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
        $items = array();
        $quoteItems = Mage::getSingleton('checkout/session')
            ->getQuote()
            ->getAllVisibleItems();
        foreach ($quoteItems as $item) {
            $items[] = array(
                'product_id'    => $item->getProductId(),
                'qty'           => $item->getQty(),
                'total'         => $item->getRowTotal(),
            );
        }

        return $items;
    }
}