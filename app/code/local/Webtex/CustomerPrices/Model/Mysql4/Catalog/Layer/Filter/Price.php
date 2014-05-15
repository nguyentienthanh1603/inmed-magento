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

class Webtex_CustomerPrices_Model_Mysql4_Catalog_Layer_Filter_Price extends Mage_Catalog_Model_Resource_Eav_Mysql4_Layer_Filter_Price
{
    public function getMaxPrice($filter)
    {   
        $select     = $this->_getSelect($filter);
        $connection = $this->_getReadAdapter();
        $response   = $this->_dispatchPreparePriceEvent($filter, $select);

        //$table = $this->_getIndexTableAlias();
        $i = Mage::getVersionInfo();
        if($i['minor'] < 7 ){
          $table = 'price_index';
        } else {
          $table = 'e';
        }

        $additional   = join('', $response->getAdditionalCalculations());
        $maxPriceExpr = new Zend_Db_Expr("MAX({$table}.min_price {$additional})");

        if(($customer = Mage::getModel('customer/session')->getCustomer()) && $customer->getId()){
            $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
            $from = $select->getPart('from');
            if(!in_array('cp_prices', array_keys($from))){
                $select->joinLeft(array('cp_prices' => $tablePrefix.'customerprices_prices'),
                                'cp_prices.product_id=e.entity_id AND cp_prices.customer_id='.$customer->getId().' and qty = 1',
                                array());
            }

            $col = "IF(cp_prices.special_price > 0, cp_prices.special_price,IF(cp_prices.price > 0,cp_prices.price,IF({$table}.final_price IS NULL, {$table}.min_price,{$table}.final_price)))";
            //$col = "IF(cp_prices.special_price > 0, cp_prices.special_price,IF(cp_prices.price > 0,cp_prices.price,IF(price_index.final_price > 0, price_index.final_price,price_index.min_price)))";
            //$col = "IF(cp_prices.special_price > 0, cp_prices.special_price,IF(cp_prices.price > 0,cp_prices.price,price_index.final_price))";

            $maxPriceExpr = new Zend_Db_Expr($col);
            $select->columns(array('price' => $maxPriceExpr));
            $res = max($connection->fetchAll($select));
            return $res['price'];
        }

        $select->columns(array($maxPriceExpr));
        return $connection->fetchOne($select) * $filter->getCurrencyRate();
    }

    public function getCount($filter, $range)
    {
        $select     = $this->_getSelect($filter);
        $connection = $this->_getReadAdapter();
        $response   = $this->_dispatchPreparePriceEvent($filter, $select);
        //$table      = $this->_getIndexTableAlias();
        $i = Mage::getVersionInfo();
        if($i['minor'] < 7 ){
          $table = 'price_index';
        } else {
          $table = 'e';
        }

        $additional = join('', $response->getAdditionalCalculations());
        $rate       = $filter->getCurrencyRate();
        $countExpr  = new Zend_Db_Expr('COUNT(*)');
        $rangeExpr  = new Zend_Db_Expr("FLOOR((({$table}.min_price {$additional}) * {$rate}) / {$range}) + 1");

        if(($customer = Mage::getModel('customer/session')->getCustomer()) && $customer->getId()){
            $from = $select->getPart('from');
            $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
            if(!in_array('cp_prices', array_keys($from))){
                $select->joinLeft(array('cp_prices' => $tablePrefix.'customerprices_prices'),
                                'cp_prices.product_id=e.entity_id AND cp_prices.customer_id='.$customer->getId().' and qty = 1',
                                array());
            }

            $col = "IF(cp_prices.special_price > 0, cp_prices.special_price,IF(cp_prices.price > 0,cp_prices.price,IF({$table}.final_price IS NULL, {$table}.min_price,{$table}.final_price)))";
            //$col = "IF(cp_prices.special_price > 0, cp_prices.special_price,IF(cp_prices.price > 0,cp_prices.price,IF(price_index.final_price > 0, price_index.final_price,price_index.min_price)))";
            //$col = "IF(cp_prices.special_price > 0, cp_prices.special_price,IF(cp_prices.price > 0,cp_prices.price,price_index.final_price))";

            $rangeExpr  = new Zend_Db_Expr("FLOOR((({$col} {$additional}) * {$rate}) / {$range}) + 1");
        }

        $select->columns(array(
            'range' => $rangeExpr,
            'count' => $countExpr
        ));
        $select->group('range');

        return $connection->fetchPairs($select);
    }
}