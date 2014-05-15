<?php

class Webtex_CustomerPrices_ConvertController extends Mage_Adminhtml_Controller_Action
{
    public function exportAction()
    {
        $this->_title($this->__('Prices per Customer Export'));

        $this->loadLayout();
        $this->_setActiveMenu('system/convert/customer_prices/export');
        $this->_addContent($this->getLayout()->createBlock('customerprices/adminhtml_system_convert_export'));
        $this->renderLayout();
    }

	public function importAction()
    {
        $this->_title($this->__('Prices per Customer Import'));

        $this->loadLayout();
        $this->_setActiveMenu('system/convert/customer_prices/import');
        $this->_addContent($this->getLayout()->createBlock('customerprices/adminhtml_system_convert_import'));
        $this->renderLayout();
    }

	public function saveExportAction()
	{
		$request = $this->getRequest();

		$exportConfig = new Varien_Object();
		
		$path		= $request->getParam('file_path', false);
		$delimiter	= $request->getParam('delimiter', false);
		$enclosure	= $request->getParam('enclosure', false);

		$exportConfig->setFilePath($path)
					->setDelimiter($delimiter)
					->setEnclosure($enclosure);

		try {
			$this->_getCsvFile($path, $delimiter, $enclosure);
                        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('customerprices')->__('Prices where succesfully exported to %s', $path));
        }
        catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        catch (Exception $e) {
            //Mage::getSingleton('adminhtml/session')->addError(Mage::helper('customerprices')->__('An error occurred while exporting prices.'));
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('customerprices')->__($e->getMessage()));
        }
        $this->getResponse()->setRedirect($this->getUrl("*/*/export"));
		
	}

	public function saveImportAction()
	{
		$request = $this->getRequest();
		$importConfig = new Varien_Object();

		$path		= '';
		$delimiter	= $request->getParam('delimiter', false);
		$enclosure	= $request->getParam('enclosure', false);

		$importConfig->setFilePath($path)
					->setDelimiter($delimiter)
					->setEnclosure($enclosure);

		try {
			$file = $_FILES['file']['name'];
			$path = Mage::getBaseDir('var').DS.'import'.DS;
			$uploader = new Varien_File_Uploader('file');
			$uploader->setAllowRenameFiles(false);
			$uploader->setFilesDispersion(false);
			$uploader->save($path, $file);

			$io = new Varien_Io_File();
			$io->open(array('path' => $path));
			$io->streamOpen($path.$file, 'r');
			$io->streamLock(true);

			$map = $io->streamReadCsv($delimiter, $enclosure);
			$prodModel = Mage::getSingleton('catalog/product');
			$db = Mage::getSingleton('core/resource')->getConnection('core_write');
			$db->query('set foreign_key_checks = 0');
			while($data = $io->streamReadCsv($delimiter, $enclosure)){
				$prod = $prodModel->loadByAttribute('sku', $data[2]);
				if(!$prod || !$prod->getId()){
					continue;
				}
				$prices = Mage::getModel('customerprices/prices');
				$prices->loadByCustomer($prod->getId(), $data[0],$data[3]);
				$prices->setProductId($prod->getId());
				$prices->setCustomerId($data[0]);
				$prices->setCustomerEmail($data[1]);
				$prices->setQty($data[3]);
				$prices->setPrice($data[4]);
				$prices->setSpecialPrice($data[5]);
				$prices->save();
			}

            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('customerprices')->__('Prices where succesfully imported '));
        }
        catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        catch (Exception $e) {
            //Mage::getSingleton('adminhtml/session')->addError(Mage::helper('customergroupsprice')->__($e->getMessage().'An error occurred while importing prices.'));
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->getResponse()->setRedirect($this->getUrl("*/*/import"));

	}

    protected function _getCsvFile($file, $delimiter, $enclosure)
    {
		$io = new Varien_Io_File();
		$fullPath = Mage::getBaseDir() . $file;
		$parts = pathinfo($fullPath);
		if(!isset($parts['extension']) || strtolower($parts['extension']) != 'csv'){
			Mage::throwException('Error in file extension. Only *.csv files are supported');
		}

		$io->open(array('path' => $parts['dirname']));
                $io->streamOpen($fullPath, 'w+');
                $io->streamLock(true);

		$header = array('user_id' => 'User ID',
		                'email'   => 'Email',
		                'sku'     => 'SKU',
		                'qty'     => 'QTY',
		                'price'   => 'User Price',
		                'sprice'   => 'User Special Price',
		                );

		$io->streamWriteCsv($header, $delimiter, $enclosure);

                $prices = Mage::getModel('customerprices/prices')->getCollection()->addOrder('customer_id','ASC');

                $tablePrefix = (string) Mage::getConfig()->getTablePrefix();

                $prices->getSelect()->joinLeft(array('product' => Mage::getSingleton('core/resource')->getTableName('catalog_product_entity')),
                                               'main_table.product_id = product.entity_id',
                                               array('product.sku'));
                $content = array();
		foreach($prices as $price) {
		        $content['user_id'] = $price['customer_id'];
		        $content['email']   = $price['customer_email'];
		        $content['sku']     = $price['sku'];
		        $content['qty']     = $price['qty'];
		        $content['price']   = $price['price'];
		        $content['sprice']  = $price['special_price'];
			$io->streamWriteCsv($content, $delimiter, $enclosure);
		}
		
		$io->streamUnlock();
               $io->streamClose();
	}
}
