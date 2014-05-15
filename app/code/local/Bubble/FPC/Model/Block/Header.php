<?php
/**
 * @category    Bubble
 * @package     Bubble_FPC
 * @version     1.2.18
 * @copyright   Copyright (c) 2014 BubbleCode (http://www.bubbleshop.net)
 */
class Bubble_FPC_Model_Block_Header extends Bubble_FPC_Model_Block_Abstract
{
    protected $_blockName = 'header';

    protected $_addCategoryId = true;

    protected $_addPageId = true;

    protected $_observers = array(
        'controller_action_postdispatch_directory_currency_switch' => 'updateCookie',
        'sales_quote_save_after' => 'updateCookie',
        'customer_logout' => 'deleteCookie',
    );

    /**
     * @return array
     */
    protected function _getDefaultValue()
    {
        return array(
            'currency'      => Mage::app()->getStore()->getCurrentCurrencyCode(),
            'is_logged_in'  => false,
            'cart_items'    => 0,
        );
    }

    /**
     * @return array
     */
    protected function _getValue()
    {
        $isLoggedIn = $this->_session()->isLoggedIn();
        $value = array(
            'currency'      => Mage::app()->getStore()->getCurrentCurrencyCode(),
            'is_logged_in'  => $isLoggedIn,
            'cart_items'    => (int) Mage::getSingleton('checkout/cart')->getSummaryQty(),
        );

        if (!$isLoggedIn) {
            return $value; // this is enough for guest users
        }

        $value['customer_name'] = $this->_session()->getCustomer()->getName();

        return $value;
    }
}