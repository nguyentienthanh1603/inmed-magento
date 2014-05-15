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

/**
 * Customerattribute Edit Block
 * 
 * @category     Magestore
 * @package     Magestore_Customerattribute
 * @author      Magestore Developer
 */
class Magestore_Customerattribute_Block_Adminhtml_Customerattribute_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        
        $this->_objectId = 'attribute_id';
        $this->_blockGroup = 'customerattribute';
        $this->_controller = 'adminhtml_customerattribute';
        
        $this->_updateButton('save', 'label', Mage::helper('customerattribute')->__('Save Attribute'));
        $this->_updateButton('delete', 'label', Mage::helper('customerattribute')->__('Delete Attribute'));
        
        $this->_addButton('saveandcontinue', array(
            'label'        => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'    => 'saveAndContinueEdit()',
            'class'        => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('customerattribute_content') == null)
                    tinyMCE.execCommand('mceAddControl', false, 'customerattribute_content');
                else
                    tinyMCE.execCommand('mceRemoveControl', false, 'customerattribute_content');
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }
    
    /**
     * get text to show in header when edit an item
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('customerattribute_data')
            && Mage::registry('customerattribute_data')->getId()
        ) {
            return Mage::helper('customerattribute')->__("Edit Customer Attribute",
                                                $this->htmlEscape(Mage::registry('customerattribute_data')->getTitle())
            );
        }
        return Mage::helper('customerattribute')->__('New Customer Attribute');
    }
}