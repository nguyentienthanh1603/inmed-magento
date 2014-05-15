<?php
/**
 * @category    Fishpig
 * @package     Fishpig_Wordpress
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
 
class Fishpig_Wordpress_Model_Observer extends Varien_Object
{
	/**
	 * Flag used to ensure observers only run once per cycle
	 *
	 * @var static array
	 */
	static protected $_singleton = array();

	/**
	 * Save the associations
	 *
	 * @param Varien_Event_Observer $observer
	 * @return bool
	 */	
	public function saveAssociationsObserver(Varien_Event_Observer $observer)
	{
		if (!$this->_observerCanRun(__METHOD__)) {
			return false;
		}

		try {
			Mage::helper('wordpress/associations')->processObserver($observer);
		}
		catch (Exception $e) {
			Mage::helper('wordpress')->log($e);
		}
	}
	
	/**
	 * Inject links into the top navigation
	 *
	 * @param Varien_Event_Observer $observer
	 * @return bool
	 */
	public function injectTopmenuLinksObserver(Varien_Event_Observer $observer)
	{
		if (!$this->_observerCanRun(__METHOD__)) {
			return false;
		}
		
		if (Mage::getStoreConfigFlag('wordpress/menu/enabled')) {
			return $this->injectTopmenuLinks($observer->getEvent()->getMenu());
		}
	}

	/**
	 * Inject links into the Magento topmenu
	 *
	 * @param Varien_Data_Tree_Node $topmenu
	 * @return bool
	 */
	public function injectTopmenuLinks($topmenu, $menuId = null)
	{
		if (is_null($menuId)) {
			$menuId = Mage::getStoreConfig('wordpress/menu/id');
		}
		
		if (!$menuId) {
			return false;
		}

		$menu = Mage::getModel('wordpress/menu')->load($menuId);		
		
		if (!$menu->getId()) {
			return false;
		}
		
		return $menu->applyToTreeNode($topmenu);
	}
	
	/**
	 * Determine whether the observer method can run
	 * This stops methods being called twice in a single cycle
	 *
	 * @param string $method
	 * @return bool
	 */
	protected function _observerCanRun($method)
	{
		if (!isset(self::$_singleton[$method])) {
			self::$_singleton[$method] = true;
			
			return true;
		}
		
		return false;
	}

	/**
	 * Adds a category id and path layout handle
	 * @event controller_action_layout_load_before
	 * @param Varien_Event_Observer $observer
	 */
	public function addPathHandle(Varien_Event_Observer $observer)
	{

		$layout = Mage::getSingleton('core/layout');
		$update = $layout->getUpdate();

		if ( in_array('wordpress_default', $update->getHandles()) )
		{
			$urlString = Mage::helper('core/url')->getCurrentUrl();
			$url = Mage::getSingleton('core/url')->parseUrl($urlString);

			// remove start and end /
			$path = explode('/', rtrim(ltrim($url->getPath(), '/'), '/') );

			$handlePrefix = 'wordpress';

			$handle = '';
			for ( $i = 1; $i <= count($path); $i++ )
			{
				if ($i > 1)
				{
					$handle .= '_' . $path[$i-1];
				}
				else
				{
					$handle = $path[$i-1];
				}

//				echo $handlePrefix . '_' . $handle . "<br />";

				$update->addHandle($handlePrefix . '_' . $handle);
			}

		}
	}
}
