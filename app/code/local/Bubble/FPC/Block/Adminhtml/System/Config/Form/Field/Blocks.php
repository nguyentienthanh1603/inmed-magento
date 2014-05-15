<?php

class Bubble_FPC_Block_Adminhtml_System_Config_Form_Field_Blocks
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn('name', array(
            'label' => Mage::helper('bubble_fpc')->__('Block Name'),
            'style' => 'width:120px',
        ));
        $this->addColumn('model', array(
            'label' => Mage::helper('bubble_fpc')->__('Model'),
            'style' => 'width:180px',
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('bubble_fpc')->__('Add Block');

        parent::__construct();
    }
}