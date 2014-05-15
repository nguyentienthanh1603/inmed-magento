<?php
class SGoodwin_IngredientSearch_IndexController extends Mage_Core_Controller_Front_Action{
    public function IndexAction() {
      
		$this->loadLayout();
		$this->getLayout()->getBlock("head")->setTitle($this->__("Ingredient Search"));
		$breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
		$breadcrumbs->addCrumb("home", array(
			"label" => $this->__("Home Page"),
			"title" => $this->__("Home Page"),
			"link"  => Mage::getBaseUrl()
		));

		$breadcrumbs->addCrumb("ingredient search", array(
				"label" => $this->__("Ingredient Search"),
				"title" => $this->__("Ingredient Search")
		));

		$block = $this->getLayout()->getBlock('ingredientsearch_index');

		// query string
		$query = strtoupper($this->returnQueryString()) ?: 'ALL';
		$block ->assign(['query' => $query ]);


		// get all ingredient categories
		$categories = $this->returnCategories($query);


		$block ->assign([
			'data' => $categories['categories'],
			'activeAlpha' => $categories['alpha']
		]);

		$this->renderLayout();
	  
    }

	private function returnCategories( $query )
	{
		// ingredients category = 19
		$cat = Mage::getModel('catalog/category')->load(19);

		// comma seperated
		$subcats = $cat->getChildren();


		if ( $query == 'ALL' )
		{
			foreach(explode(',',$subcats) as $subCatid)
			{
				$_category = Mage::getModel('catalog/category')->load($subCatid);
				if($_category->getIsActive())
				{
					$categories[] = $_category;

					// now do a letter search
					$letters = range('A', 'Z');
					foreach ( $letters AS $letter )
					{
						if ( $letter == substr( $_category->getName(), 0, 1))
						{
							$validLetters[] = $letter;
						}
					}

				}
			}
		}
		else
		{

			foreach(explode(',',$subcats) as $subCatid)
			{
				$_category = Mage::getModel('catalog/category')->load($subCatid);
				if($_category->getIsActive() && substr( $_category->getName(), 0, 1) == $query )
				{
					$categories[] = $_category;

				}

				if ($_category->getIsActive())
				{
					// now do a letter search
					$letters = range('A', 'Z');
					foreach ( $letters AS $letter )
					{
						if ( $letter == substr( $_category->getName(), 0, 1))
						{
							$validLetters[] = $letter;
						}
					}
				}
			}
		}

		return ['categories' => $categories, 'alpha' => $validLetters];
	}



	private function returnQueryString()
	{
		return $this->getRequest()->getParam('q');
	}
}