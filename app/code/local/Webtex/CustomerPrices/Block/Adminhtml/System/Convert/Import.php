<?php

class Webtex_CustomerPrices_Block_Adminhtml_System_Convert_Import extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

		$this->_blockGroup = 'customerprices';
        $this->_controller = 'adminhtml_system_convert';
		$this->_mode = 'import';

        $this->_updateButton('save', 'label', Mage::helper('adminhtml')->__('Import Prices'));
        $this->_removeButton('delete');
        $this->_removeButton('back');
    }

    public function getHeaderText()
    {
        return Mage::helper('adminhtml')->__('Prices per Customer Import');
    }
}
