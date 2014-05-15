<?php
class Webtex_CustomerPrices_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{
    protected function _addFinalPrice()
    {
        $prices = Mage::getModel('customerprices/prices');
        foreach ($this->_items as $product) {
            $cust_price = $prices->getProductPrice($product,$product->getQty());
            $cust_special_price = $prices->getProductSpecialPrice($product,$product->getQty());
            if($cust_price) {
                $product->setPrice($cust_price);
            }
            if($cust_special_price) {
                $product->setSpecialPrice($cust_special_price);
            }
            $basePrice = $product->getPrice();
            $specialPrice = $product->getSpecialPrice();
            $specialPriceFrom = $product->getSpecialFromDate();
            $specialPriceTo = $product->getSpecialToDate();
            if ($this->isEnabledFlat()) {
                $rulePrice = null;
                if ($product->getData('_rule_price') != $basePrice) {
                    $rulePrice = $product->getData('_rule_price');
                }
            } else {
                $rulePrice = $product->getData('_rule_price');
            }

            $finalPrice = $product->getPriceModel()->calculatePrice(
                $basePrice,
                $specialPrice,
                $specialPriceFrom,
                $specialPriceTo,
                $rulePrice,
                null,
                null,
                $product->getId()
            );
            $product->setCalculatedFinalPrice($finalPrice);
        }

        return $this;
    }
}
