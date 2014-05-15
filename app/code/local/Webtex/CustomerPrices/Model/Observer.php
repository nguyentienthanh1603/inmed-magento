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

class Webtex_CustomerPrices_Model_Observer
{
	public function productSaveAfter($observer)
	{
		$params = Mage::app()->getRequest()->getParams();
                $productId = $params['id'];
		foreach($params as $key => $value) {
			if(strpos($key, 'customerprices') === false || $value == 0) {
				continue;
			}
			foreach($value as $_item) {
			    $customerPrices = Mage::getModel('customerprices/prices');
			         $customerPrices->loadByCustomer($productId, $_item['customerId'],$_item['qty'])
					        ->setCustomerId($_item['customerId'])
					        ->setCustomerEmail(trim($_item['email']))
					        ->setProductId($productId)
					        ->setStoreId($_item['website_id'])
				                ->setPrice($_item['price'])
				                ->setQty($_item['qty'])
				                ->setSpecialPrice($_item['specialprice']);

			    if($_item['delete']=='1') {
			         $customerPrices->delete();
			    } else {
			         $customerPrices->save();
			    }
			}
		}
	}

    public function layeredPrice($observer)
    {
        $collection = $observer->getCollection();

        $query = Mage::app()->getRequest()->getQuery();
        if(empty($query ) || !isset($query['price'])) {
            return $this;
        }

        list($index, $range) = explode(',', $query['price']);
        $begin = ($index - 1) * $range;
        $to = $index * $range;

        if(($customer = Mage::getModel('customer/session')->getCustomer()) && $customer->getId()) {
            $col = "IF(cp_prices.special_price > 0, cp_prices.special_price,IF(cp_prices.price > 0,cp_prices.price,IF(price_index.final_price IS NULL, price_index.min_price,price_index.final_price)))";
            //$col = "IF(cp_prices.special_price > 0, cp_prices.special_price,IF(cp_prices.price > 0,cp_prices.price,IF(price_index.final_price > 0, price_index.final_price,price_index.min_price)))";
            //$col = "IF(cp_prices.special_price > 0, cp_prices.special_price,IF(cp_prices.price > 0,cp_prices.price,price_index.final_price))";

            $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
            $from = $collection->getSelect()->getPart('from');
            if(!in_array('cp_prices', array_keys($from))) {
                $collection->getSelect()
                       ->joinLeft(array('cp_prices' => $tablePrefix.'customerprices_prices'),
                            'cp_prices.product_id=e.entity_id AND cp_prices.customer_id='.$customer->getId().' and cp_prices.qty = 1',
                            array())
                        ->reset(Zend_Db_Select::WHERE)
                        ->where($col.' >= '.$begin)
                        ->where($col.' < '.$to);
            }
        }
    }
    
    public function sortByPrice($observer)
    {
        $collection = $observer->getCollection();
        $orderPart = $collection->getSelect()->getPart(Zend_Db_Select::ORDER);
        $isPriceOrder = false;
        foreach($orderPart as $v) {
            if(is_array($v) && $v[0] == 'price_index.min_price') {
                $isPriceOrder = true;
                $orderDir = $v[1];
            }
        }

        $customer = Mage::getModel('customer/session')->getCustomer();

        if(!$isPriceOrder || !$customer->getId()){
            return $this;
        }

        $from = $collection->getSelect()->getPart('from');
        if(!in_array('cp_prices', array_keys($from))) {
            $col = "IF(cp_prices.special_price > 0, cp_prices.special_price,IF(cp_prices.price > 0,cp_prices.price,IF(price_index.final_price IS NULL, price_index.min_price,price_index.final_price)))";
            //$col = "IF(cp_prices.special_price > 0, cp_prices.special_price,IF(cp_prices.price > 0,cp_prices.price,IF(price_index.final_price > 0, price_index.final_price,price_index.min_price)))";
            //$col = "IF(cp_prices.special_price > 0, cp_prices.special_price,IF(cp_prices.price > 0,cp_prices.price,price_index.final_price))";

            $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
            $collection->getSelect()
                   ->joinLeft(array('cp_prices' => $tablePrefix.'customerprices_prices'),
                        'cp_prices.product_id=e.entity_id AND cp_prices.customer_id='.$customer->getId(),
                        array());

            $collection->getSelect()
                ->reset(Zend_Db_Select::ORDER)
                ->order('CAST(min_price as signed)'.$orderDir);
        }
    }
	public function collectionLoadAfter($observer)
	{
	  $_session = Mage::getSingleton('adminhtml/session_quote');
	  $prices = Mage::getModel('customerprices/prices');
	  if($_session->getCustomerId()) {
	      foreach($observer->getCollection() as $_item) {
	          $price = $prices->getProductCustomerPrice($_item,$_session->getCustomerId());
	          $specialPrice = $prices->getProductCustomerSpecialPrice($_item,$_session->getCustomerId());
	          $_item->setPrice($price);
	          if($specialPrice > 0) {
	              $_item->setSpecialPrice($specialPrice);
	              $price = $specialPrice;
	          }
	      }
	   } else {
	      foreach($observer->getCollection() as $_item) {
	          $price = $prices->getProductPrice($_item);
	          $specialPrice = $prices->getProductSpecialPrice($_item);
	          $_item->setPrice($price);
	          if($specialPrice > 0) {
	              $_item->setSpecialPrice($specialPrice);
	              $price = $specialPrice;
	          }
	      }
	   }
          return $this;
	}
	
	public function productLoadAfter($observer)
	{
	    $prices = Mage::getModel('customerprices/prices');
            $product = $observer->getEvent()->getProduct();
            $_session = Mage::getSingleton('adminhtml/session_quote');
            if($_session->getCustomerId()) {
               $product->setPrice($prices->getProductCustomerPrice($product,$_session->getCustomerId()));
	       $product->setSpecialPrice($prices->getProductCustomerSpecialPrice($product,$_session->getCustomerId()));
	    } else {
	       $price = $prices->getProductPrice($product);
	       $specialPrice = $prices->getProductSpecialPrice($product);
               $product->setPrice($price);
	       if($specialPrice > 0) {
	          $product->setSpecialPrice($specialPrice);
	          $price= $specialPrice;
	       }
	    }
	    return $this;
	}

	public function processFrontFinalPrice($observer)
	{
           $_session = Mage::getSingleton('adminhtml/session_quote');
	   $customer = $_session->getCustomerId();
	   if($customer) {
	    $product = $observer->getEvent()->getProduct();
	    $prices = Mage::getModel('customerprices/prices');
	          $price     = $prices->getProductCustomerPrice($product,$customer,$product->getQty());
	          $sPrice    = $prices->getProductCustomerSpecialPrice($product,$customer,$product->getQty());
                  $basePrice = $price;
                  $specialPrice = $sPrice;
                  $product->setPrice($basePrice);
                  $product->setSpecialPrice($specialPrice);
                  if($specialPrice){
                      $product->setCalculatedFinalPrice(min($basePrice,$specialPrice));
                      $product->setCalculationPrice(min($basePrice,$specialPrice));
                      $product->setData('final_price',min($basePrice,$specialPrice));
                  } else {
                      $product->setCalculatedFinalPrice($basePrice);
                      $product->setCalculationPrice($basePrice);
                      $product->setData('final_price',$basePrice);
                  }
            }
          return $this;
        }


	public function processBackFinalPrice($observer)
	{
           $_session = Mage::getSingleton('adminhtml/session_quote');
	   $customer = $_session->getCustomerId();
	   if($customer) {
	    $product = $observer->getEvent()->getProduct();
	    $prices = Mage::getModel('customerprices/prices');
	          $price     = $prices->getProductCustomerPrice($product,$customer,$product->getQty());
	          $sPrice    = $prices->getProductCustomerSpecialPrice($product,$customer,$product->getQty());
                  $basePrice = $price;
                  $specialPrice = $sPrice;
                  $product->setPrice($basePrice);
                  $product->setSpecialPrice($specialPrice);
                  if($specialPrice){
                      $product->setCalculatedFinalPrice(min($basePrice,$specialPrice));
                      $product->setCalculationPrice(min($basePrice,$specialPrice));
                      $product->setData('final_price',min($basePrice,$specialPrice));
                  } else {
                      $product->setCalculatedFinalPrice($basePrice);
                      $product->setCalculationPrice($basePrice);
                      $product->setData('final_price',$basePrice);
                  }
            }
          return $this;
        }

}