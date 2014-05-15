<?php

class Webtex_CustomerPrices_Block_Adminhtml_System_Convert_Import_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>Mage::helper('customerprices')->__('Import Settings')));

        $fieldset->addField('file', 'file', array(
                'name'  	=> 'file',
                'label'		=> Mage::helper('customerprices')->__('Path to file'),
                'title' 	=> Mage::helper('customerprices')->__('Path to file'),
				'required'	=> true
            )
        );

		$fieldset->addField('delimiter', 'text', array(
                'name'  	=> 'delimiter',
                'label' 	=> Mage::helper('customerprices')->__('Value delimiter'),
                'title' 	=> Mage::helper('customerprices')->__('Value delimiter'),
				'required'	=> true
            )
        );

		$fieldset->addField('enclosure', 'text', array(
                'name'  	=> 'enclosure',
                'label' 	=> Mage::helper('customerprices')->__('Enclose Values In'),
					'title' => Mage::helper('customerprices')->__('Enclose Values In'),
				'required'	=> true
            )
        );

	$importConfig = array('file' => '/var/customerprices/customerprices.csv',
	                      'delimiter' => ';',
	                      'enclosure' => '"',
	                      );

	$form->setValues($importConfig);
        $form->setAction($this->getUrl('*/convert/saveImport'));
        $form->setMethod('post');
        $form->setUseContainer(true);
        $form->setId('edit_form');
	$form->setEnctype('multipart/form-data');

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
