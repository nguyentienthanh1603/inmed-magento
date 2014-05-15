<?php
/**
 * Magestore
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category    Magestore
 * @package     Magestore_Customerattribute
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
class Magestore_Customerattribute_Block_Customer_Form_Renderer_Boolean extends Magestore_Customerattribute_Block_Customer_Form_Renderer_Select
{
    /**
     * Return array of select options
     *
     * @return array
     */
    public function getOptions()
    {
        return array(
            
            array(
                'value' => '0',
                'label' => Mage::helper('customerattribute')->__('No')
            ),
            array(
                'value' => '1',
                'label' => Mage::helper('customerattribute')->__('Yes')
            ),
        );
    }
}