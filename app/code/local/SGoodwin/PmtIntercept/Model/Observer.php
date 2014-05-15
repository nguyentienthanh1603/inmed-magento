<?php
class SGoodwin_PmtIntercept_Model_Observer //extends Varien_Object
{


	protected $api_create_url = "http://localhost/pmt/public/api/v1/user/create";
	protected $api_update_url = "http://localhost/pmt/public/api/v1/user/update";
	protected $api_login_url = "http://localhost/pmt/public/api/v1/user/login";

	protected $api_user;
	protected $api_password;

	protected $website_code;
	protected $website_id;

	function __construct()
	{
		// TODO: Implement __construct() method.
		$this->api_user     = Mage::getModel('core/variable')
								->setStoreId(Mage::app()->getStore()->getId())
								->loadByCode('pmt_api_user')
								->getValue('text');
		$this->api_password = Mage::getModel('core/variable')
								->setStoreId(Mage::app()->getStore()->getId())
								->loadByCode('pmt_api_password')
								->getValue('text');

		$this->website_code = Mage::app()->getWebsite()->getCode();
		$this->website_id   = Mage::app()->getWebsite()->getId();
		//Mage::app()->getWebsite($customer->website_id)->getCode();
	}


	/**
	 * only used when clinician or reseller is registering
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function customerSaveBefore(Varien_Event_Observer $observer)
	{
		$customer = $observer->getCustomer();

//		exit();
		// when registering
		if ( Mage::app()->getRequest()->getPost('register_catch') )
		{
			if ( Mage::app()->getRequest()->getPost('account_select') == "clinician" )
			{
				// create and add the clinician group
				$this->clinicianCreateClinicianGroup( $observer->getCustomer() );

//				$this->pmtRegister($customer);
//				exit();

			}
			// only for consumer register
			if ( Mage::app()->getRequest()->getPost('account_select') == "consumer" )
			{

			}
		}
	}

	/**
	 * only used for registration
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function customerRegisterSuccess(Varien_Event_Observer $observer)
	{
		try
		{
			$customer = $observer->getCustomer();

			if ( Mage::app()->getRequest()->getPost('account_select') == "consumer" )
			{
//				$this->consumerGroup($customer); // Set to the default customer group

				$customer->setData( 'group_id', 1 );
				$customer->setData( 'customer_activated', '1' );

				// assign to group
				$this->consumerAssignClinicianGroup( $customer );

				// send welcome email
				$this->sendRegistrationEmail($customer);

				// logout user
				Mage::getSingleton('customer/session')->logout();

			}
			elseif ( Mage::app()->getRequest()->getPost('account_select') == "clinician"
				|| Mage::app()->getRequest()->getPost('account_select') == "reseller" )
			{
				// either clinician or reseller
				$this->clinicianResellerSetup( $customer, Mage::app()->getRequest()->getPost('account_select') );
			}
			else
			{
				// not general, clinician or reseller
			}
		}
		catch ( Exception $e )
		{
			$this->adminEmail($customer, 'customer_save_before observer failed: No customer in observer', 1, $e->getMessage() );
		}
	}

	private function clinicianResellerSetup ( $customer,  $acctType = 'clinician')
	{

		// Note: create and assign own clinician_group_id happens on customer_save_before
		if ( $acctType == 'clinician')
		{
			$customer->setData( 'group_id', 4 );
		}
		else
		{
			$customer->setData( 'group_id', 5 );
		}

		// register to pmt
		$this->pmtRegister( $customer );

		// set not activated
		$customer->setData('customer_activated', '0' );

		// create cart price rule
		$this->createCartCoupon( $customer );

		// send email for account to notify @inmedtech
		if ( Mage::app()->getRequest()->getPost('account_apply_credit'))
		{
			$data = [
				'account_apply_credit'        => (Mage::app()->getRequest()->getPost('account_apply_credit') ? 'Yes' : 'No'),
				'account_request_limit'       => Mage::app()->getRequest()->getPost('account_request_limit'),
				'account_years_in_business'   => Mage::app()->getRequest()->getPost('account_years_in_business'),
				'account_social_security_ein' => Mage::app()->getRequest()->getPost('account_social_security_ein'),
				'account_bankruptcy'          => Mage::app()->getRequest()->getPost('account_bankruptcy')
			];
		}
		$this->adminEmail( $customer, null, 3, null, 'admin_new_account_template', $data );

		// if account credit
		if ( Mage::app()->getRequest()->getPost('account_apply_credit') )
		{

			$this->sendRegistrationEmail($customer, 'new_account_clinician_credit_template');
		}
		else
		{
			$this->sendRegistrationEmail($customer, 'new_account_clinician_template');
		}

		return;
	}

//-------------------------------------------------------------------
//------- After Customer save Observers -----------------------------------------------
//-------------------------------------------------------------------

	/**
	 * Customer edited, update PMT details
	 * @param Varien_Event_Observer $observer
	 */
	public function customerSaveAfter(Varien_Event_Observer $observer)
	{
		$customer = $observer->getCustomer();
		// editing
		if ( Mage::app()->getRequest()->getPost('edit_catch') )
		{
			if ( $customer->group_id != '1')
			{
				$this->pmtUpdate( $customer );
			}
		}
	}


//-------------------------------------------------------------------
//------- Clinician and Customer Groups -----------------------------
//-------------------------------------------------------------------

	/**
	 * for new clinicians
	 * only executed on register
	 *
	 * @param $customer
	 */
	private function clinicianCreateClinicianGroup( $customer )
	{

		try
		{
			// make new group name
			$newGroup = $customer->getLastname() . '-' . $customer->getFirstname() . '-' . date('Ymd');

			// get attribute model
			$attribute_model = Mage::getModel('eav/entity_attribute');
			$attribute_options_model = Mage::getModel('eav/entity_attribute_source_table');

			// get group attribute, via website code.
			$attribute_code = $attribute_model->getIdByCode('customer', 'clinician_group_' . $this->website_code);
			$attribute = $attribute_model->load($attribute_code);

			// lets create the group
			$attribute_options_model->setAttribute($attribute);
			$options = $attribute_options_model->getAllOptions(false);

			// determine if this option exists
			$value_exists = false;
			$opt_id = '';
			foreach($options as $option) {
				if ($option['label'] == $newGroup) {
					$value_exists = true;
					$opt_id = $option['value'];
					break;
				}
			}

			// if this option does not exist, add it.
			if (!$value_exists) {
				$attribute->setData('option', array(
					'value' => array(
						'option' => array($newGroup,$newGroup)
					)
				));
				$attribute->save();
			}
			else
			{

				$customer->setData('clinician_group_' . $this->website_code, $opt_id );
			}
		}
		catch ( Exception $e )
		{
			$this->adminEmail($customer, 'Assign and Create clinician group error', 1, $e->getMessage() );
		}

	}

	/**
	 * Executed for consumer rego
	 *
	 * @param $customer
	 */
	private function consumerAssignClinicianGroup( $customer )
	{
		$getClinicianGroup = 'getClinicianGroup'.ucwords( $this->website_code );

		// get passed pract/customer id from autocomplete
		// $refId format clinician_group_id | clinician id
		//           64|12
		$refId = Mage::app()->getRequest()->getPost('clinician_group_id');

		$_clinician_id = explode('|', $refId);

		try
		{

			if ( count($_clinician_id) == 2 )
			{
				// lookup selected clinician details
				$clinician_data = Mage::getModel('customer/customer')->load($_clinician_id[1]);

				// check to make certain it is not a consumer
				if ( $clinician_data->getGroupId() != 1 )
				{
					// cross check to make sure group matches
					if ( $clinician_data->$getClinicianGroup() == $_clinician_id[0] )
					{
						$customer->setData('clinician_group_' . $this->website_code, $_clinician_id[0]);
					}
					else // not matching
					{
						$this->adminEmail($customer, 'Attempted to assign customer to selected clinician. No matching group found  - ' . $refId, 2 );
					}
				}
				else
				{
					$this->adminEmail($customer, 'Attempted to assign customer to selected clinician. Selected clinician not in group 4. Passed: ' . $refId . ". Found: ". $clinician_data->getClinicianGroup(), 2 );
				}
			}
			else
			{
				// does consumer want us to contact clinician
				$email_admin = Mage::app()->getRequest()->getPost('clinician_not_found');

				if ( !empty( $email_admin ) )
				{
					// lets email details
					$data = [
						'clinician_name' => Mage::app()->getRequest()->getPost('clinician_group_name'),
						'clinician_city' => Mage::app()->getRequest()->getPost('clinician_group_city'),
						'clinician_state' => Mage::app()->getRequest()->getPost('clinician_group_region_id'),
						'clinician_zip' => Mage::app()->getRequest()->getPost('clinician_group_zip'),
					];

					$this->adminEmail($customer, 'Patient clinician submission', 3, null, 'admin_customer_clinician_submission_template', $data);

				}
			}
		}
		catch ( Exception $e )
		{
			$this->adminEmail($customer, 'unable to assign consumer to clinician group ' . $refId, 2, $e->getMessage() );
		}
	}

//-------------------------------------------------------------------
//------- Coupons ---------------------------------------------------
//-------------------------------------------------------------------

	private function createCartCoupon( $customer )
	{

		$code = strtolower(substr($customer->getFirstname(), 0, 2) . date('Y').substr($customer->getLastname(), 0, 2). date('md'));

		$rule = Mage::getModel('salesrule/rule');
		$customer_groups = [0, 1, 2, 3, 4, 5];
		$rule->setName( $customer->getFirstname() . ' ' . $customer->getLastname() . ' - Coupon' )
			->setDescription($customer->getFirstname() . ' ' . $customer->getLastname() . ' cart coupon, created on '.date('Ymd'))
			->setFromDate('')
			->setCouponType(2)
			->setCouponCode($code)
			->setUsesPerCustomer(100)
			->setCustomerGroupIds( $customer_groups ) //an array of customer grou pids
			->setIsActive(1)
			->setConditionsSerialized('')
			->setActionsSerialized('')
			->setStopRulesProcessing(0)
			->setIsAdvanced(1)
			->setProductIds('')
			->setSortOrder(0)
			->setSimpleAction('by_percent') // by percetnage of cart
			->setDiscountAmount(10)
			->setDiscountQty(null)
			->setDiscountStep(0)
			->setSimpleFreeShipping('0')
			->setApplyToShipping('0')
			->setIsRss(0)
			->setWebsiteIds([Mage::app()->getStore()->getId()]);

//		$item_found = Mage::getModel('salesrule/rule_condition_product_found')
//			->setType('salesrule/rule_condition_product_found')
//			->setValue(1) // 1 == FOUND
//			->setAggregator('all'); // match ALL conditions
//		$rule->getConditions()->addCondition($item_found);
//		$conditions = Mage::getModel('salesrule/rule_condition_product')
//			->setType('salesrule/rule_condition_product')
//			->setAttribute('sku')
//			->setOperator('==')
//			->setValue($sku);
//		$item_found->addCondition($conditions);

//		$actions = Mage::getModel('salesrule/rule_condition_product')
//			->setType('salesrule/rule_condition_product')
//			->setAttribute('sku')
//			->setOperator('==')
//			->setValue($sku);
//		$rule->getActions()->addCondition($actions);
		$rule->save();

		// save customers code
		$customer->setData('clinician_coupon_code', $code);

//		var_dump($customer);
//		exit();


	}


//-------------------------------------------------------------------
//------- Checkout Save Observers -----------------------------------------------
//-------------------------------------------------------------------

	public function checkoutOnepageControllerSuccessAction (Varien_Event_Observer $observer)
	{

		// group id holder for coupons
		$groupId = 0;

		// check for coupon for clinician
		// then add it to customer
		foreach ($observer['order_ids'] as $order)
		{
			$order = Mage::getModel('sales/order')->load($order);

			if ( $discount_code = $order->getDiscountDescription() )
			{
				$groupId = $this->couponCheck($discount_code, $order->getCustomerId());
			}
		}


		$customer = Mage::getSingleton('customer/session')->getCustomer();

		$getClinicianGroup = 'clinician_group_'.$this->website_code;

		$current_user = $customer->getData();

		// update only if patient belongs to clinician, or a coupon was added
		if ( isset( $current_user[$getClinicianGroup] ) || $groupId )
		{

			if (isset($observer['order_ids']))
			{

				foreach ($observer['order_ids'] as $order)
				{
					// get order
					$orderUpdate = Mage::getModel('sales/order')->load($order);

					// make sure group id is valid
					$groupId = $groupId ?: $current_user[$getClinicianGroup];

					$orderUpdate->setClinicianGroupId( $groupId );

					$orderUpdate->save();
				}
			} else {
				//Do something with the order
				$this->adminEmail($current_user, 'Single order did not update for the PMT. Observer checkout success', 1 );
			}
		}

	}

	/**
	 * Checks the coupon code to see if is owned by a reseller/clinician
	 * if so, then adds the customer to that group
	 *
	 * @param $discount_code
	 * @param $customer_id
	 *
	 * @return mixed
	 */
	private function couponCheck ( $discount_code, $customer_id )
	{
		// get the customer
		$customer = Mage::getSingleton('customer/session')->getCustomer();

		$getClinicianGroup = 'getClinicianGroup'.ucwords( $this->website_code );

		$resource = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');

		// get the clinician owner
		$query = 'SELECT entity_id FROM customer_entity_varchar WHERE value = "'. $discount_code .'"';

		$results = $readConnection->fetchAll($query);

		// check if belong to clinician
		if ( count($results) )
		{
			foreach ( $results AS $entity_id )
			{
				$clinician = Mage::getModel('customer/customer')->load($entity_id);
				// get clincian group id
				$clinician_group_id = $clinician->$getClinicianGroup();

				// load customer and update its clincian_group_ud/au
				$customer = Mage::getModel('customer/customer')->load($customer_id);
				$customer->setData('clinician_group_' . $this->website_code, $clinician_group_id);
				$customer->save();

				return $clinician_group_id;
			}
		}

	}


	// before submitting order
	public function checkoutTypeOnepageSaveOrder (Varien_Event_Observer $observer)
	{

	}

//-------------------------------------------------------------------
//------- Login Observers -----------------------------------------------
//-------------------------------------------------------------------

	public function customerLogin (Varien_Event_Observer $observer)
	{

		$getClinicianGroup = 'getClinicianGroup'.ucwords($this->website_code);

		$customer = $observer->getCustomer();

		// activate and is a clinician group
		if ( $customer->customer_activated && $customer->$getClinicianGroup() )
		{
			$this->pmtLoginUpdate( $customer );
		}
	}

//-------------------------------------------------------------------
//------- PMT Observers -----------------------------------------------
//-------------------------------------------------------------------

	// @todo need to fix pmt_login
	private function pmtLoginUpdate( $data = [] )
	{
		$getClinicianGroup = 'getClinicianGroup'.ucwords($this->website_code);

		$client = new Zend_Http_Client($this->api_login_url);

		$params = [
			'pmt_user_id'             => ($data->getPmtUserId()?: 0),
			'email'                   => $data->getEmail(),
			'password'                => ($data->getPassword()?:''),
			'username'                => $data->getFirstname() . $data->getLastname(),
			'mage_entity_id'          => $data->getEntityId(),
			'mage_group_id'           => $data->getGroupId(),
			'website_id'           	  => $this->website_id,
			'mage_clinician_group_id' => ($data->$getClinicianGroup()?: 0)
		];
		// set some parameters
		$client->setParameterPost($params);

		$client->setAuth($this->api_user, $this->api_password, \Zend_Http_Client::AUTH_BASIC);


		// POST request
		$result = $client->request(Zend_Http_Client::POST);

		// decode response
		$jsonData = json_decode($result->getBody());

		if ( $jsonData->status != 'success' )
		{
//			$params['api_user'] = $this->api_user;
//			$params['api_password'] = $this->api_password;
			$this->adminEmail($data, 'PMT API Login error ', 2, $jsonData->error->message . '<br />Generated from pmtLoginUpdate. No success.','admin_pmt_error_template', $params );
		}

		return true;
	}

	/**
	 * Registers clinician | reseller to pmt
	 * @param array $data
	 */
	private function pmtRegister ( $data = [] )
	{

		$getClinicianGroup = 'getClinicianGroup'.ucwords($this->website_code);

		// get clinician group_id
		$customer = Mage::getModel('customer/customer')->load($data->getEntityId());

		$group_id = $customer->$getClinicianGroup();

		// new HTTP request to some HTTP address
		$client = new Zend_Http_Client($this->api_create_url);

		$params = [
			'email'                   => $data->getEmail(),
			'password'                => $data->getPassword(),
			'username'                => $data->getFirstname() . $data->getLastname(),
			'first_name'              => $data->getFirstname(),
			'last_name'               => $data->getLastname(),
			'mage_entity_id'          => $data->getEntityId()?:time(),
			'mage_group_id'           => $data->getGroupId(),
			'mage_clinician_group_id' => $group_id ?: ($data->getEntityId()?:time())
		];

		// set some parameters
		$client->setParameterPost($params);


		$client->setAuth($this->api_user, $this->api_password, \Zend_Http_Client::AUTH_BASIC);
		// POST request
		$result = $client->request(Zend_Http_Client::POST);

//		echo $result;

		// decode response
		$jsonData = json_decode($result->getBody());



		if ( $jsonData->status == 'success' )
		{
			$customer->setPmtUserId( $jsonData->data->id );
			try
			{
				$customer->save();
			}
			catch (Exception $e)
			{
				$this->adminEmail($customer, 'Unable to save pmt user id to clinician ', 1, $e->getMessage(), 'admin_pmt_error_template', $params  );
			}
			return $jsonData->data->id;
		}
		else
		{
			$this->adminEmail($customer, 'PMT API Register error ', 1, $jsonData->error->message, 'admin_pmt_error_template', $params  );
		}

		return 0;
	}


	// working 2014/4/2
	private function pmtUpdate( $data = [] )
	{

//		$website_code = Mage::app()->getWebsite($data->getWebsiteId())->getCode();
		$getClinicianGroup = 'getClinicianGroup'.ucwords($this->website_code);

		$client = new Zend_Http_Client($this->api_update_url);

		// determine if password update
		$password = '';
		if ( Mage::app()->getRequest()->getPost('change_password') )
		{
			$password = Mage::app()->getRequest()->getPost('password');
		}

		// determine if change email
		if ( $data->email != $data->getOrigData('email'))
		{
			$client->setParameterPost([
				'new_email'                   => $data->getEmail(),
			]);
		}

		$params = [
			'pmt_user_id'             => $data->getPmtUserId(),
			'email'                   => $data->getEmail(),
			'password'                => $password,
			'username'                => $data->getFirstname() . $data->getLastname(),
			'first_name'              => $data->getFirstname(),
			'last_name'               => $data->getLastname(),
			'mage_entity_id'          => $data->getEntityId(),
			'mage_group_id'           => $data->getGroupId(),
			'mage_clinician_group_id' => ($data->$getClinicianGroup()?: 0)
		];
		// set some parameters
		$client->setParameterPost($params);

		$client->setAuth($this->api_user, $this->api_password, \Zend_Http_Client::AUTH_BASIC);
		// POST request
		$result = $client->request(Zend_Http_Client::POST);

		// decode response
		$jsonData = json_decode($result->getBody());

		if ( $jsonData->status == 'success' )
		{
			try
			{

			}
			catch (Exception $e)
			{
				$this->adminEmail($data, 'PMT API update error ', 1, $e->getMessage(), 'admin_pmt_error_template', $params );
			}
		}
		else
		{
			$this->adminEmail($data, 'PMT API update error ', 1, $jsonData->error->message, 'admin_pmt_error_template', $params );
		}
	}

//-------------------------------------------------------------------
//------- Admin Observers -----------------------------------------------
//-------------------------------------------------------------------

	public function admin_customer_delete_before (Varien_Event_Observer $observer)
	{
//		Mage::log( "admin_customer_delete_before");
		echo 'admin_customer_delete_before';
		var_dump($observer->getCustomer());
		exit();
	}

	/**
	 * Used ALWAYS on the save in admin and front end
	 *
	 * @param Varien_Event_Observer $observer
	 */

	public function adminhtml_customer_prepare_save (Varien_Event_Observer $observer)
	{
		$customer = $observer->getCustomer();

		// check if new
		if ( $customer->entity_id )
		{ // update customer
			if ( $customer->group_id != '1')
			{
				$this->pmtUpdate( $customer );
			}
		}
		else
		{ // new customer

			try
			{

				if ( $customer->group_id == "1" )
				{

				}
				else
				{
					// Note: create and assign own clinician_group_id happens on customer_save_before
					$this->clinicianCreateClinicianGroup( $customer );

					// register to pmt
					$pmt_user_id = $this->pmtRegister( $customer );
					// pmt user id
					$customer->setPmtUserId( $pmt_user_id );

					// send email for account to notify @inmedtech
					if ( Mage::app()->getRequest()->getPost('account_apply_credit'))
					{
						$data = [
							'account_apply_credit'        => (Mage::app()->getRequest()->getPost('account_apply_credit') ? 'Yes' : 'No'),
							'account_request_limit'       => Mage::app()->getRequest()->getPost('account_request_limit'),
							'account_years_in_business'   => Mage::app()->getRequest()->getPost('account_years_in_business'),
							'account_social_security_ein' => Mage::app()->getRequest()->getPost('account_social_security_ein'),
							'account_bankruptcy'          => Mage::app()->getRequest()->getPost('account_bankruptcy')
						];
					}
					$this->adminEmail( $customer, null, 3, null, 'admin_new_account_template', $data );

					// if account credit
					if ( Mage::app()->getRequest()->getPost('account_apply_credit') )
					{
						$this->sendRegistrationEmail($customer, 'new_account_clinician_credit_template');
					}
					else
					{
						$this->sendRegistrationEmail($customer, 'new_account_clinician_template');
					}
				}
			}
			catch ( Exception $e )
			{
				$this->sendErrorEmail($customer, 'No customer in observer', 1, $e->getMessage() );
			}
		}
	}

	/**
	 * Only is called on the save and continue edit
	 *
	 * @param Varien_Event_Observer $observer
	 */
	public function adminhtml_customer_save_after (Varien_Event_Observer $observer)
	{

	}


//-------------------------------------------------------------------
//------- Emails -----------------------------------------------
//-------------------------------------------------------------------

	private function adminEmail( $customer, $message = null, $level = 3, $exception = null, $template = 'admin_error_template', $data )
	{
		$emailTemplate  = Mage::getModel('core/email_template')
			->loadDefault($template);

		$status = "Notice";
		if ( $level == 1 )
		{
			$status = "URGENT"; // send to webmaster
		}
		elseif ( $level == 2 )
		{
			$status = "Semi-Urgent"; // send to webmaster & hello
		}

		$email_title = $status .': InMedTech.co - '.$message;




		// logging
		if ( Mage::getStoreConfig('system/log/enabled') )
		{
			Mage::log($message . ' - ' . $exception);
		}

		//Create an array of variables to assign to template
		$emailTemplateVariables = array();
		$emailTemplateVariables['customer']    = $customer;
		$emailTemplateVariables['date_time']        = date('Y/m/d');

		$emailTemplateVariables['message']          = $message;
		$emailTemplateVariables['level']            = $status;
		$emailTemplateVariables['exception']        = $exception;

		$passed_data = '';
		foreach ( $data AS $code => $value)
		{
			$passed_data .= '<br />'.$code . ': '.$value;
			$emailTemplateVariables[$code]   = $value;
		}
		$emailTemplateVariables['data']        = $passed_data;

		$emailTemplate->getProcessedTemplate($emailTemplateVariables);
		/*
		 * Or you can send the email directly,
		 * note getProcessedTemplate is called inside send()
		 */
		$emailTemplate->setSenderName( Mage::getStoreConfig('trans_email/ident_custom2/name') );
		$emailTemplate->setSenderEmail( Mage::getStoreConfig('trans_email/ident_custom2/email') );
		$emailTemplate->setTemplateSubject($email_title);

		if ( $level > 1 )
		{

			$result = $emailTemplate->send(
				Mage::getStoreConfig('trans_email/ident_custom2/email'),
				Mage::getStoreConfig('trans_email/ident_custom2/name'),
				$emailTemplateVariables
			);
		}
		else
		{
			$emailTemplate->send(
				Mage::getStoreConfig('trans_email/ident_sales/email'),
				Mage::getStoreConfig('trans_email/ident_sales/name'),
				$emailTemplateVariables
			);
		}
	}

	private function sendRegistrationEmail( $customer, $template = 'new_account_general_template', $subject = 'New Registration', $data = [], $sendAdmin = false )
	{
		$emailTemplate  = Mage::getModel('core/email_template')
			->loadDefault($template);
		//Create an array of variables to assign to template
		$emailTemplateVariables = array();
		$emailTemplateVariables['date_time']   = date('Y/m/d');
		$emailTemplateVariables['customer']    = $customer;
		$emailTemplateVariables['store_name']  = Mage::getStoreConfig('general/store_information/name');
		$emailTemplateVariables['store_url']   = Mage::app()->getStore()->getHomeUrl();
		$emailTemplateVariables['store_phone'] = Mage::getStoreConfig('general/store_information/phone');
		$emailTemplateVariables['store_address'] = Mage::getStoreConfig('general/store_information/address');

		foreach ( $data AS $code => $value)
		{
			$emailTemplateVariables[$code]   = $value;
		}

		$emailTemplate->getProcessedTemplate($emailTemplateVariables);

		$emailTemplate->setSenderName( Mage::getStoreConfig('trans_email/ident_general/name') );
		$emailTemplate->setSenderEmail( Mage::getStoreConfig('trans_email/ident_general/email') );
		$emailTemplate->setTemplateSubject('InMedTech.co: ' . $subject);

		if ( !$sendAdmin )
		{
			$emailTemplate->send(
				$customer->getEmail(),
				$customer->getFirstname() . ' ' . $customer->getLastname(),
				$emailTemplateVariables
			);
		}
		else
		{
			$emailTemplate->send(
				Mage::getStoreConfig('trans_email/ident_support/email'),
				Mage::getStoreConfig('trans_email/ident_support/name'),
				$emailTemplateVariables
			);
		}
	}
}