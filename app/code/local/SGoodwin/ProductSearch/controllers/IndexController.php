<?php
class SGoodwin_ProductSearch_IndexController extends Mage_Core_Controller_Front_Action{
    public function IndexAction() {
      
		$this->loadLayout();
		$this->getLayout()->getBlock("head")->setTitle($this->__("Products"));
			$breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
		$breadcrumbs->addCrumb("home", array(
				"label" => $this->__("Home Page"),
				"title" => $this->__("Home Page"),
				"link"  => Mage::getBaseUrl()
		   ));

		$breadcrumbs->addCrumb("product search", array(
				"label" => $this->__("Products"),
				"title" => $this->__("Products")
		   ));

		$block = $this->getLayout()->getBlock('productsearch_index');

		// query string
		$query = strtoupper($this->returnQueryString()) ?: 'ALL';
		$block ->assign(['query' => $query ]);

		// products
		$products = $this->returnProducts( $query );

		$alphabet = $this->returnAlphabet();

		$block ->assign([
			'data' => $products,
			'activeAlpha' => $alphabet
		]);

		$this->renderLayout();
	  
    }

	private function returnProducts($query)
	{
		$collection = Mage::getModel('catalog/product')->getCollection();
		$collection->addAttributeToSelect('*');
		$collection->addAttributeToSort('name', 'ASC');

		if ( $query != "ALL" )
		{
			$collection->addAttributeToFilter('name', array('like' => $query.'%'));
		}
		$collection->addAttributeToFilter('visibility' , Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);

		return $collection;

	}

	private function returnAlphabet()
	{
		$collection = Mage::getModel('catalog/product')->getCollection();
		$collection->addAttributeToSelect('*');
		$collection->addAttributeToSort('name', 'ASC');
		$collection->addAttributeToFilter('visibility' , Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);

		$validLetters = [];
		foreach ( $collection AS $product )
		{
			$letters = range('A', 'Z');
			foreach ( $letters AS $letter )
			{
				if ( $letter == substr( $product->getName(), 0, 1))
				{
					$validLetters[] = $letter;
				}
			}
		}

		return array_unique($validLetters);

	}

	private function returnQueryString()
	{
		return $this->getRequest()->getParam('q');
	}
}