<?php
class Admin_CustomerController extends Zend_Controller_Action {
	public function init() {
		// $parentModule =
		// $templateModules->findParentRow('Admin_Model_DbTable_Module','Module')->toArray();
		
		/* Initialize action controller here */
	}
	public function indexAction() {
		// action body
		$this->view->addlink = $this->view->url ( array (
				"module" => "admin",
				"controller" => "customer",
				"action" => "add" 
		), "default", true );
	}
	public function addAction() {
		$this->view->heading = "Add Customer";
		$customerForm = $this->_createCustomerForm ( Admin_Model_Mapper_Customer::$ADD_MODE );
		$customerMapper = new Admin_Model_Mapper_Customer ();
		$model = $customerMapper->fetchAll("1=1","customer_id desc",1);
		if(is_array($model)) {
			$app_id = $model[0]->getAppAccessId();
			$app_id = $app_id + 1;
			$customerForm->getElement ( 'app_access_id' )->setValue ($app_id);
		}else{
			$app_id = "121321";
			$customerForm->getElement ( 'app_access_id' )->setValue ($app_id);
		}
		$this->view->hasData = true;
		
		// Configure the Customer Configuration Form
		$customerConfigurationForm = new Admin_Form_CustomerConfiguration ();
		$customerConfigurationForm->getElement ( 'customer_id' )->setAttrib ( "id", "configuration_customer_id" );
		// Set form for view
		$this->view->customerForm = $customerForm;
		$this->view->customerConfigurationForm = $customerConfigurationForm;
		
		$mapper = new Admin_Model_Mapper_BusinessType ();
		$models = $mapper->countAll();
		$template = new Admin_Model_Mapper_Template();
		$template = $template->countAll();
		if($models == 0 || $template == 0) {
			$this->view->hasData = false;
		}
		$this->render ( "add-edit" );
	}
	public function editAction() {
		
		// Redirect on no customer_id found
		$this->view->hasData = true;
		$customer_id = $this->_request->getParam ( "id", "" );
		if ($customer_id == "") {
			$this->_redirect ( 'index' );
		}
		$customerMapper = new Admin_Model_Mapper_Customer ();
		
		// Find and populate the customer information in customer model
		$customer = $customerMapper->find ( $customer_id );
		if (! $customer) {
			$this->_redirect ( '/admin/customer' );
		}
		
		// Populate the User data with username and password
		$user = new Default_Model_User ();
		$user->populate ( $customer->getUserId () );
		
		$formPopulateData = $customer->toArray ();
		$formPopulateData ["username"] = $user->getUsername ();
		$formPopulateData ["password"] = $user->getPassword ();
		$formPopulateData ["phone"] = $user->getPhone ();
		$formPopulateData ["email"] = $user->getEmail ();
		if($formPopulateData["start_date_time"] != ""){
			$start_date = DateTime::createFromFormat ( "Y-m-d H:i:s", $formPopulateData["start_date_time"]);
			$formPopulateData["start_date_time"]=$start_date->format ( "d/m/Y H:i" ) ;
		}
		$this->view->heading = "Edit Customer";
		
		$customerForm = $this->_createCustomerForm ( Admin_Model_Mapper_Customer::$EDIT_MODE );
		
		// Populate customer langauages
		$languageMapper = new Admin_Model_Mapper_CustomerLanguage();
		$languages = $languageMapper->fetchAll("customer_id=".$customer_id);
		if(is_array($languages)) {
			$selected = array();
			$default = "";
			foreach($languages as $lang) {
				$selected[] = $lang->getLanguageId();
				if($lang->getIsDefault()==1) {
					$default = $lang->getLanguageId();
				}
			}
			$customerForm->getElement ( 'language_id' )->setValue($selected);
			$customerForm->getElement ( 'default_language_id' )->setValue($default);
		}
		
		// Remove Password Validation and required attribute
		$password = $customerForm->getElement ( 'password' );
		$password->removeValidator ( 'NotEmpty' );
		$password->setRequired ( false );
		unset ( $password->required );
		$customerForm->addElement ( $password );
		
		// Populate the form with available data
		$customerForm->populate ( $formPopulateData );
		$customerForm->getElement("app_access_id")->setAttrib("readonly", "readonly");
		//
		// Configure the Customer Configuration Form
		//
		$customerConfigurationForm = new Admin_Form_CustomerConfiguration ();
		$customerConfigurationMapper = new Admin_Model_Mapper_CustomerConfiguration ();
		$customerConfigurationData = $customerConfigurationMapper->getConfigurationByCustomerId ( $customer_id );
		if (empty ( $customerConfigurationData ))
			$customerConfigurationForm->getElement ( 'customer_id' )->setValue ( $customer_id );
		else
			$customerConfigurationForm->populate ( $customerConfigurationData );
		
		$customerConfigurationForm->getElement ( 'customer_id' )->setAttrib ( "id", "configuration_customer_id" );
		
		// Populate Source
		$sourceMapper = new Parking_Model_Mapper_ModuleParkingCustomerSource();
		$sources = $sourceMapper->fetchAll("customer_id=".$customer_id);
		if(is_array($sources)) {
		    $selected = array();
		    foreach($sources as $source) {
		        $selected[] = $source->getModuleParkingSourceId();
		    }
		    $customerForm->getElement ( 'source_id' )->setValue($selected);
		}
		
		// Set forms for view
		$this->view->customerForm = $customerForm;
		$this->view->customerConfigurationForm = $customerConfigurationForm;
		
		$this->render ( "add-edit" );
	}
	public function deleteAction() {
		// Check for ID and permissions and thus proceed or redirect accordingly
		$redirect = false;
		$id = $this->_request->getParam ( "id", "" );
		$response = array ();
		$usergroupMapper = new Default_Model_Mapper_UserGroup ();
		try {
			$usergroupMapper->getDbTable()->getAdapter()->beginTransaction();
			$usergroups = $usergroupMapper->fetchAll ( "customer_id=" . $id );
			if(is_array($usergroups)){
				foreach ( $usergroups as $group ) {
					$groupmoduleMaper = new Default_Model_Mapper_UserGroupModule ();
					$groupmodule = $groupmoduleMaper->fetchAll ( "user_group_id=" . $group->getUserGroupId () );
					foreach ( $groupmodule as $module ) {
						$module->delete ();
					}
					$userMaper = new Default_Model_Mapper_User ();
					$users = $userMaper->fetchAll ( "user_group_id=" . $group->getUserGroupId () );
					if(is_array($users)){
						foreach ( $users as $user ) {
							$user->delete ();
						}
					}
					$group->delete ();
				}
			}
			$customermoduleMapper = new Admin_Model_Mapper_CustomerModule ();
			$customermodules = $customermoduleMapper->fetchAll ( "customer_id=" . $id );
			if(is_array($customermodules)) {
				foreach ( $customermodules as $module ) {
					$customermoduledetailMapper = new Admin_Model_Mapper_CustomerModuleDetail();
					$customermoduledetail = $customermoduledetailMapper->fetchAll("customer_module_id=".$module->getCustomerModuleId());
					if(is_array($customermoduledetail)) {
						foreach($customermoduledetail as $detail) {
							$detail->delete();
						}
					}
					$module->delete ();
				}
			}
			$customerconfigMapper = new Admin_Model_Mapper_CustomerConfiguration ();
			$customerconfigs = $customerconfigMapper->fetchAll ( "customer_id=" . $id );
			if(is_array($customerconfigs)) {
				foreach ( $customerconfigs as $config ) {
					$config->delete ("customer_id=" . $id);
				}
			}
			// Delete Customer Languages
			$customerLangMapper = new Admin_Model_Mapper_CustomerLanguage();
			$deletedRows = $customerLangMapper->delete("customer_id=" . $id);
			
			$customerMapper = new Admin_Model_Mapper_Customer();
			$deletedRows = $customerMapper->delete("customer_id=" . $id);
			
			$response = array (
					"success" => array (
							"deleted_rows" => $deletedRows 
					) 
			);
			$usergroupMapper->getDbTable()->getAdapter()->commit();
		} catch ( Zend_Exception $ex ) {
			$usergroupMapper->getDbTable()->getAdapter()->rollBack();
			$message = $ex->getMessage ();
			if (strpos ( $message, "foreign key constraint fails" ) !== false) {
				$response = array (
						"errors" => array (
								"message" => $ex->getMessage() 
						) 
				);
			} else {
				$response = array (
						"errors" => array (
								"message" => $ex->getMessage () 
						) 
				);
			}
		}
		
		$this->_helper->json ( $response );
	}
	
	/**
	 * Save customer data (without customer configuration)
	 */
	public function saveCustomerAction() {
		$customer_id = $this->_request->getParam ( "customer_id", "" );
		$user_id = $this->_request->getParam ( "user_id", "" );
		
		// if isset customer_id and user_id in option then set the mode to edit
		// mode
		$mode = Admin_Model_Mapper_Customer::$ADD_MODE;
		if ($customer_id != "" && $customer_id != null && $user_id != "" && $user_id != null)
			$mode = Admin_Model_Mapper_Customer::$EDIT_MODE;
		
		$customerForm = $this->_createCustomerForm ( $mode );
		
		$response = array ();
		$params = $this->_request->getParams ();
		if ($customerForm->isValid ( $params )) {
			
			$refinedParams = $customerForm->getValues ();
			$customerMapper = new Admin_Model_Mapper_Customer ();
			try {
				$customerUserData = $customerMapper->saveCustomer ( $refinedParams, $mode );
				//sending email to customer
				if($mode == "add"){
					$email = $customerUserData['user']['email'];
					$username = $customerUserData['user']['username'];
					$parseVariable = array();
					$parseVariable["{DATETIME}"] = Standard_Functions::getCurrentDateTime(null,"m-d-Y H:i:s");
					$parseVariable["{USERNAME}"] = $username;
					$emailObj = new Standard_Email();
					$emailObj->sendEmail("templates/welcome_customer.phtml",
							"Welcome To Appstart!", $parseVariable,
							array($email => "Customer"));
				}
			} catch ( Exception $ex ) {
				if (strpos ( $ex->getMessage (), "Duplicate entry" ) !== false && strpos ( $ex->getMessage (), "username" ) !== false) {
					$response = array (
							'errors' => array (
									'username' => "Customer username already exists." 
							) 
					);
				} else if (strpos ( $ex->getMessage (), "Duplicate entry" ) !== false && strpos ( $ex->getMessage (), "app_access_id" ) !== false) {
					$response = array (
							'errors' => array (
									'app_access_id' => "App Access ID already exists." 
							) 
					);
				} else {
					$response = array (
							'errors' => array (
									'message' => $ex->getMessage () . $ex->getTraceAsString () 
							) 
					);
				}
			}
			if (empty ( $response )) {
				$response = array (
						'success' => array (
								'message' => $customerUserData 
						) 
				);
			}
		} else {
			$errors = $customerForm->getMessages ();
			
			foreach ( $errors as $name => $error ) {
				$errors [$name] = array_pop ( $error );
			}
			$response = array (
					"errors" => $errors 
			);
		}
		$this->_helper->json ( $response );
	}
	public function saveCustomerConfigurationAction() {
		// Check for customer -- If configuration we are saving is having a
		// customer_id
		$customer_id = $this->_request->getParam ( "customer_id", "" );
		// if isset customer_id and user_id in option then set the mode to edit
		// mode
		$response = array ();
		
		if ($customer_id == "" && $customer_id == null) {
			$response = array (
					'errors' => array (
							'message' => "Please save customer before saving customer configurations" 
					) 
			);
			$this->_helper->json ( $response );
			return;
		}
		// Select the mode of saving the configuration (add/edit)
		$customer_configuration_id = $this->_request->getParam ( "customer_configuration_id", "" );
		$mode = Admin_Model_Mapper_CustomerConfiguration::$ADD_MODE;
		if ($customer_configuration_id != "")
			$mode = Admin_Model_Mapper_CustomerConfiguration::$EDIT_MODE;
		
		$customerConfigurationForm = new Admin_Form_CustomerConfiguration ();
		
		$params = $this->_request->getParams ();
		if ($customerConfigurationForm->isValid ( $params )) {
			
			$refinedParams = $customerConfigurationForm->getValues ();
			$customerConfigurationMapper = new Admin_Model_Mapper_CustomerConfiguration ();
			try {
				$customerConfigurationData = $customerConfigurationMapper->saveCustomerConfiguration ( $refinedParams, $mode );
			} catch ( Exception $ex ) {
				$response = array (
						'errors' => array (
								'message' => $ex->getMessage () . $ex->getTraceAsString () 
						) 
				);
			}
			if (empty ( $response )) {
				$response = array (
						'success' => array (
								'message' => $customerConfigurationData 
						) 
				);
			}
		} else {
			$errors = $customerConfigurationForm->getMessages ();
			
			foreach ( $errors as $name => $error ) {
				$errors [$name] = array_pop ( $error );
			}
			$response = array (
					"errors" => $errors 
			);
		}
		$this->_helper->json ( $response );
	}
	public function gridAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ( true );
		$customerMapper = new Admin_Model_Mapper_Customer ();
		
		$select = $customerMapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"c" => "customer" 
		), array (
				"c.app_access_id",
				"c.customer_name",
				"c.contact_person_name",
				"c.status" => "c.status",
				"c.customer_id" 
		) )->joinLeft ( array (
				"bt" => "business_type" 
		), "bt.business_type_id = c.business_type_id", array (
				"bt.name" => "bt.name" 
		) )->joinLeft ( array (
				"t" => "template" 
		), "t.template_id = c.template_id", array (
				"t.name" => "t.name" 
		) )->joinLeft ( array (
				"u" => "user" 
		), "u.user_id = c.user_id ", array (
				"u.username" => "u.username" 
		) );
		
		$response = $customerMapper->getGridData ( array (
				'column' => array (
						'id' => array (
								'actions' 
						),
						'replace' => array (
								'c.status' => array (
										'0' => 'Inactive',
										'1' => 'Active' 
								) 
						) 
				) 
		), null, $select );
		$rows = $response ['aaData'];
		foreach ( $rows as $rowId => $row ) {
			$editUrl = $this->view->url ( array (
					"module" => "admin",
					"controller" => "customer",
					"action" => "edit",
					"id" => $row [6] ["customer_id"] 
			), "default", true );
			$deleteUrl = $this->view->url ( array (
					"module" => "admin",
					"controller" => "customer",
					"action" => "delete",
					"id" => $row [6] ["customer_id"] 
			), "default", true );
			$viewUrl = $this->view->url ( array (
					"module" => "default",
					"controller" => "login",
					"action" => "admin-login",
					"customer_id" => $row [6] ["customer_id"],
					"offset" => "",
			), "default", true );
			$edit = '<a href="' . $editUrl . '" class="button-grid greay grid_edit" >'.$this->view->translate('Edit').'</a>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
			$view = '<a href="' . $viewUrl . '"class="button-grid greay grid_dashboard" target="_blank">Dashboard</a>';
			$response ['aaData'] [$rowId] [6] = $edit . "&nbsp;|&nbsp;" . $delete . "&nbsp;|&nbsp;" .$view;
		}
		echo $this->_helper->json ( $response );
	}
	private function _createCustomerForm($mode = null) {
		$mode = $mode == null ? Admin_Model_Mapper_Customer::$ADD_MODE : $mode;
		$customerForm = new Admin_Form_Customer ();
		
		Default_Form_User::$IS_ADMIN_ADD = true;
		
		$userForm = new Default_Form_User ();
		
		// Add Username
		$customerForm->addElement ( $userForm->getElement ( "username" )->setLabel ( 'Customer Username' ) );
		// Add Password
		$customerForm->addElement ( $userForm->getElement ( "password" )->setLabel ( 'Customer Password' ) );
		// Add Phone
		$customerForm->addElement ( $userForm->getElement ( "phone" )->setLabel ( 'Customer Phone' ) );
		// Add Email
		$customerForm->addElement ( $userForm->getElement ( "email" )->setLabel ( 'Customer Email' )->setAttrib('required', 'required') );
		
		if ($mode == Admin_Model_Mapper_Customer::$EDIT_MODE) {
			$password = $customerForm->getElement ( 'password' );
			$password->removeValidator ( 'NotEmpty' );
			$password->setRequired ( false );
			unset ( $password->required );
			$customerForm->addElement ( $password );
		}
		
		return $customerForm;
	}
	
	public function getTemplateAction() {
		sleep(2);
		$business_type_id = $this->_request->getParam ( "business_type_id", "" );
		$options = array ();
		$options [0]["key"] = "";
		$options [0]["value"] = "Select Template";
		$mapper = new Admin_Model_Mapper_Template ();
		
		// Generate Quote
		//$templateQuote = $mapper->getDbTable()->getAdapter()->quoteInto('status = ? AND business_type_id = ?', 1,$business_type_id);
		$i=1;
		$models = $mapper->fetchAll ("status = 1 AND business_type_id = ".$business_type_id);
		if(is_array($models)) {
			foreach ( $models as $template ) {
				$options [$i]["key"] = $template->getTemplateId ();
				$options [$i++]["value"] = $template->getName ();
			}
		}
		echo $this->_helper->json ( $options );
	}
}

