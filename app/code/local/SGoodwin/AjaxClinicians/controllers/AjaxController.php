<?php

class SGoodwin_AjaxClinicians_AjaxController extends Mage_Core_Controller_Front_Action
{

	public function indexAction()
	{
		$country    = Mage::app()->getRequest()->getPost('country');
		$state    = Mage::app()->getRequest()->getPost('state');
		$zip      = Mage::app()->getRequest()->getPost('zip');
		$city     = Mage::app()->getRequest()->getPost('city');
		$name     = Mage::app()->getRequest()->getPost('name');

		$sql_state   = '';
		$sql_zip     = '';
		$sql_city    = '';
		$sql_name    = '';
		$matching    = 0;

		// todo: add the website id


		$sql = 'SELECT DISTINCT ce.entity_id, ce.group_id, cei.value AS active '
			. 'FROM customer_entity ce '
			. 'INNER JOIN customer_address_entity cae ON cae.parent_id = ce.entity_id '
			. 'INNER JOIN customer_address_entity_varchar caev ON caev.entity_id = cae.entity_id '
			. 'INNER JOIN customer_entity_int cei ON cei.entity_id = ce.entity_id '
			. 'WHERE ce.is_active = 1 AND ce.group_id = 4 AND cei.attribute_id = 187';

		if ($country)
		{
			$sql_state = ' caev.value = "'.$country.'"';
			$matching++;
		}

		if ($state)
		{
			$sql_state = ' caev.value = "'.$state.'"';
			$matching++;
		}
		if ($zip)
		{
			$sql_zip = !$state ? "": " OR";
			$sql_zip .= ' caev.value LIKE "%'.$zip.'%"';
			$matching++;
		}
		if ($city)
		{
			$sql_city = !$state && !$zip ? "": " OR";
			$sql_city .= ' caev.value LIKE "%'.$city.'%"';
			$matching++;
		}
		if ($name)
		{
			$sql_name = !$state && !$zip && !$city ? "": " OR";
			$sql_name .= ' caev.value LIKE "%'.$name.'%"';
			$matching++;
		}

		$sql .= $state || $zip || $city || $name ?
						' AND ('. $sql_state . $sql_zip . $sql_city . $sql_name . ')'
						: '';

//		$sql .= ' HAVING matching >= '.$matching;

		$sql .= ' limit 10';

//		var_dump($sql);
//		exit();
		// before do query, make sure there is data to search for
		if ( $state || $zip || $city || $name )
		{
			$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
			$connection->query($sql);

			$_clinicians = [];

			foreach ($connection->fetchAll($sql) as $arr_row)
			{
				// show only active
				if ( $arr_row['active'] )
				{
					$customer_data = Mage::getModel('customer/customer')->load($arr_row['entity_id']);
					$customer_address = Mage::getModel('customer/address')->load($customer_data->getDefaultBilling());

	//				var_dump($customer_data);

					// customer object name
					$getClinicianGroup = 'getClinicianGroup'.ucwords(Mage::app()->getWebsite()->getCode());
	//				echo $customer_data->$getClinicianGroup();

					$_clinicians[] = [
						'name'              => $customer_data->getLastname() . ' ' . $customer_data->getFirstname(),
						'city'              => $customer_address->getData('city'),
						'region'            => $customer_address->getData('region'),
						'clinician_group_id'  => $customer_data->$getClinicianGroup(), // use new object name
						'clinician_id'      => $arr_row['entity_id']
					];
				}
			}
			$this->loadLayout();//->assign('data', $_clinicians);
			$block = $this->getLayout()->getBlock('root');

			$block ->assign(['data' => $_clinicians]);

			$this->renderLayout();

		}






	}

}