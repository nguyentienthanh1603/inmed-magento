<?php
class Magestore_Sociallogin_Block_Config_Redirecturl
	extends Mage_Adminhtml_Block_System_Config_Form_Field
{    
     protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
		$storeId = $this->getRequest()->getParam('store');
        $redirectUrl = Mage::getUrl('sociallogin/gologin/user', array('store'=>$storeId));	
        $html  = "<input readonly id='sociallogin_linklogin_redirecturl' class='input-text' value='".$redirectUrl."'>";        
        return $html;
    }
        
}
