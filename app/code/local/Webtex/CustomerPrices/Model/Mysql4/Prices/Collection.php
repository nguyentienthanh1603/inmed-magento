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

class Webtex_CustomerPrices_Model_Mysql4_Prices_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('customerprices/prices');
    }

    public function addProductFilter($productId) {
        $this->getSelect()->where('product_id = ' . $productId);
        $this->addOrder('customer_id')->addOrder('qty');

        return $this;
    }

    public function addCustomerFilter($customerId) {
        $this->getSelect()->where('customer_id = ' . $customerId);
        return $this;
    }

    public function addQtyFilter($qty) {
        if(is_array($qty)) {
           foreach($qty as $key => $value ){
                $this->getSelect()->where('qty '.$key . $value);
           }
        } else {
            $this->getSelect()->where('qty = ' . $qty);
        }
        return $this;
    }

    public function addMaxQtyFilter($qty) {
        $this->getSelect()->where('qty = (select MAX(qty) from customerprices_prices where qty <=' . $qty.' )');
        return $this;
    }
}