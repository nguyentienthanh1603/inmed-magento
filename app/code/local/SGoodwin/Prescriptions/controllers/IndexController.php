<?php
class SGoodwin_Prescriptions_IndexController extends Mage_Core_Controller_Front_Action{

	protected $api_get_prescriptons_url = "http://localhost/pmt/public/api/v1/user/prescriptions/";
	protected $api_get_prescripton_url = "http://localhost/pmt/public/api/v1/user/prescription/ics/";

	protected $api_user;
	protected $api_password;

	protected $website_code;
	protected $website_id;


	protected function redirectDashboard()
	{
		// for alerts
//			Mage::getSingleton('customer/session')
//				->addError('No access');

		$url = Mage::getModel('core/url')
			->getUrl("customer/account");


		Mage::app()
			->getResponse()
			->setRedirect($url);

		Mage::app()
			->getResponse()
			->sendResponse();

		exit;
	}

	protected function checkAccess()
	{
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$getClinicianGroup = 'getClinicianGroup'.ucwords( Mage::app()->getWebsite()->getCode() );
		$getClinicianGroup = $customer->$getClinicianGroup();

		if ( is_null( $getClinicianGroup ) || $customer->getGroupId() > 1)
		{
			$this->redirectDashboard();
		}

	}

	public function IndexAction()
	{

		// check to make sure have access
		$this->checkAccess();

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


      
		$this->loadLayout();
		$this->getLayout()->getBlock("head")->setTitle($this->__("Prescriptions"));
			$breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");


		$breadcrumbs->addCrumb("prescriptions", array(
				"label" => $this->__("Prescriptions"),
				"title" => $this->__("Prescriptions")
		   ));

		$block = $this->getLayout()->getBlock('prescriptions_index');

		$block ->assign([
			'data' => $this->getPrescriptions()
		]);

		$this->renderLayout();
	  
    }


	private function getPrescriptions ()
	{

		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$client = new Zend_Http_Client($this->api_get_prescriptons_url.$customer->getId());

		// set some parameters
		$client->setAuth($this->api_user, $this->api_password, \Zend_Http_Client::AUTH_BASIC);

		// POST request
		$result = $client->request(Zend_Http_Client::POST);

		// decode response
		$jsonData = json_decode($result->getBody());

		if ( $jsonData->status == 'success' )
		{
			return $jsonData->data;
		}
		else
		{
			$this->redirectDashboard();
		}
	}

	public function icsAction ()
	{

		$this->api_user     = Mage::getModel('core/variable')
			->setStoreId(Mage::app()->getStore()->getId())
			->loadByCode('pmt_api_user')
			->getValue('text');
		$this->api_password = Mage::getModel('core/variable')
			->setStoreId(Mage::app()->getStore()->getId())
			->loadByCode('pmt_api_password')
			->getValue('text');


		// get prescription id
		$prescriptionRepeatId     = Mage::app()->getRequest()->getParam('id');

		$customer = Mage::getSingleton('customer/session')->getCustomer();
		$client = new Zend_Http_Client($this->api_get_prescripton_url.$prescriptionRepeatId);

		$params = [
			'pmt_user_id'             => $customer->getId(),
			'prescription_repeat_id'         => $prescriptionRepeatId
		];

		// set some parameters
		$client->setParameterPost($params);
		$client->setAuth($this->api_user, $this->api_password, \Zend_Http_Client::AUTH_BASIC);

		// POST request
		$result = $client->request(Zend_Http_Client::POST);

		// decode response
		$jsonData = json_decode($result->getBody());

//		var_dump($prescriptionRepeatId . $customer->getId());
//		var_dump($result);
//		exit();

		if ( !$jsonData->status == 'success' )
		{
			// create error message, before redirecting

			// redirect to prescriptions page
			$this->redirectDashboard();
		}



		$this->loadLayout();

		$block = $this->getLayout()->getBlock('prescriptions_ics');

		$block ->assign([
			'repeat' => $jsonData->data->repeats[0],
			'product' => $jsonData->data->prescription
		]);

		$this->renderLayout();

	}
}