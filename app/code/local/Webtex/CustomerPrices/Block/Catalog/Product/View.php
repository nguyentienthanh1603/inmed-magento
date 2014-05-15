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
 * Customer Price extension
 *
 * @category   Webtex
 * @package    Webtex_CustomerPrices
 * @author     Webtex Dev Team <dev@webtex.com>
 */

class Webtex_CustomerPrices_Block_Catalog_Product_View extends Mage_Catalog_Block_Product_View
{
    /**
     * Return true if product has options
     *
     * @return bool
     */
    public function hasOptions()
    {
        if($this->helper('customerprices')->isEnabled() && (($this->helper('customerprices')->isHidePrice() && !$this->helper('customer')->isLoggedIn()))) {
            return false;
        }
        if ($this->getProduct()->getTypeInstance(true)->hasOptions($this->getProduct())) {
            return true;
        }
        return false;
    }

    protected function _toHtml()
    {
        if(!(!$this->helper('customerprices')->isEnabled() || (!$this->helper('customerprices')->isHidePrice() || $this->helper('customer')->isLoggedIn())) && strpos($this->getTemplate(),'/addto') > 0 ) {
            return '';
        }
        return parent::_toHtml();
    }

    public function getCustomTierPriceHtml($product = null)
    {
        if (is_null($product)) {
            $product = $this->getProduct();
        }
        return $this->_getPriceBlock($product->getTypeId())
            ->setTemplate('customerprices/customertierprices.phtml')
            ->setProduct($product)
            ->setInGrouped($this->getProduct()->isGrouped())
            ->toHtml();
    }

}
