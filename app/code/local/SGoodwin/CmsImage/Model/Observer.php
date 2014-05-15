<?php

class SGoodwin_CmsImage_Model_Observer
{
	public function cmsImageField($observer)
	{
		//get CMS model with data
		$model = Mage::registry('cms_page');
		//get form instance
		$form = $observer->getForm();
		//create new custom fieldset 'atwix_content_fieldset'
		$fieldset = $form->addFieldset('sgoodwin_content_fieldset', array('legend'=>Mage::helper('cms')->__('Page Image'),'class'=>'fieldset-wide'));
		//add new field
		$fieldset->addField('page_image', 'image', array(
			'name'      => 'page_image',
			'label'     => Mage::helper('cms')->__('Page Image'),
			'title'     => Mage::helper('cms')->__('Page Image'),
			'disabled'  => false,
			//set field value
			'value'     => $model->getPageImage()
		));

	}
}