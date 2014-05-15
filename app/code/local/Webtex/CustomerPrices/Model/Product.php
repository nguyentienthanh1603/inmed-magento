<?php

class Webtex_CustomerPrices_Model_Product extends Mage_Catalog_Model_Product
{
    public function getCustomTierPrice($qty=null)
    {
        if (!Mage::helper('customer')->isLoggedIn()) {
            return $this->getPrice();
        }
        $customerId = Mage::helper('customer')->getCustomer()->getId();
        if($qty){
            $prices = Mage::getResourceModel('customerprices/prices_collection')
                                        ->addProductFilter($this->getId())
                                        ->addCustomerFilter($customerId)
                                        ->addMaxQtyFilter($qty);
        } else {
            $prices = Mage::getResourceModel('customerprices/prices_collection')
                                        ->addProductFilter($this->getId())
                                        ->addCustomerFilter($customerId)
                                        ->addQtyFilter(array('>' => '1'));
        }
        return ($prices) ? $prices->getData() : array();
    }

    public function getFormatedCustomTierPrice($qty=null)
    {
        $price = $this->getCustomTierPrice($qty);
        if (is_array($price)) {
            foreach ($price as $index => $value) {
                $price[$index]['formated_price'] = Mage::app()->getStore()->convertPrice($price[$index]['price'], true);
            }
        }
        else {
            $price = Mage::app()->getStore()->formatPrice($price);
        }

        return $price;
    }

    public function prepareForCartAdvanced(Varien_Object $buyRequest, $product = null, $processMode = null)
    {
        if($product){
            $qty = $buyRequest->getQty();
            $product->getFinalPrice($qty, $product);
        }
        return parent::prepareForCartAdvanced($buyRequest, $product, $processMode);
    }

}
