<?php
class Admin_BusinessTypeController extends Zend_Controller_Action {
	public static $EDIT_MODE = "edit";
	public static $ADD_MODE = "add";
	public function init() {
		/* Initialize action controller here */
	}
	public function indexAction() {
		// action body
		$this->view->addlink = $this->view->url ( array (
										"module" => "admin",
										"controller" => "business-type",
										"action" => "add"
								), "default", true );
	}
	
	public function gridAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ( true );
		$businessTypeMapper = new Admin_Model_Mapper_BusinessType ();
		
		/*$select = $businessTypeMapper->getDbTable ()
							->select ( false )
							->setIntegrityCheck ( false )
							->from ( array ("bt" => "business_type"), 
										array ("bt.business_type_id",
												"bt_name" => "bt.name") )
							->joinLeft ( array ("c" => "customer"), "c.business_type_id=bt.business_type_id", 
											array ("total_customer" => "count(c.customer_id)") )
							->joinLeft ( array ("t" => "template"), "t.business_type_id=bt.business_type_id AND t.template_id=c.template_id", 
											array ("total_template" => "count(t.template_id)") )
							->group ( "bt.business_type_id" );
		*/
		$select = $businessTypeMapper->getDbTable ()
							->select ( false )
							->setIntegrityCheck ( false )
							->from ( array ("bt" => "business_type"),
									array ("bt.business_type_id",
											"bt_name" => "bt.name") )
							->group ( "bt.business_type_id" );
		$response = $businessTypeMapper->getGridData ( array (
				'column' => array (
						'id' => array (
								'actions'
						),
						'ignore' => array (
								'total_customer',
								'total_template'
						)
				)
		),null,$select);
		$rows = $response ['aaData'];
		foreach ( $rows as $rowId => $row ) {
			$customer = new Admin_Model_Mapper_Customer();
			$totalCustomer = $customer->countAll("business_type_id=".$row [4] ["business_type_id"]);
			$template = new Admin_Model_Mapper_Template();
			$totalTemplate = $template->countAll("business_type_id=".$row [4] ["business_type_id"]);
			
			$response ['aaData'] [$rowId] [2] = $totalCustomer;
			$response ['aaData'] [$rowId] [3] = $totalTemplate;
			
			
			$editUrl = $this->view->url ( array (
					"module" => "admin",
					"controller" => "business-type",
					"action" => "edit",
					"id" => $row [4] ["business_type_id"]
			), "default", true );
			$deleteUrl = $this->view->url ( array (
					"module" => "admin",
					"controller" => "business-type",
					"action" => "delete",
					"id" => $row [4] ["business_type_id"]
			), "default", true );
			$edit = '<a href="' . $editUrl . '" class="button-grid greay grid_edit" >'.$this->view->translate('Edit').'</a>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
			$response ['aaData'] [$rowId] [4] = $edit . "&nbsp;|&nbsp;" . $delete;
		}
		echo $this->_helper->json ( $response );
	}
	
	/**
	 * Add Action for adding business type
	 */
	public function addAction() {
		$form = new Admin_Form_BusinessType ();
		
		$this->_save ( $form , self::$ADD_MODE );
		
		$this->view->form = $form;
		$this->view->assign ( array (
				"partial" => "business-type/partials/add.phtml"
		) );
		$this->render ( "add-edit" );
	}
	
	/**
	 * Edit mode of the business type
	 */
	public function editAction() {
		
		// Check for ID and permissions and thus proceed or redirect accordingly
		$redirect = false;
		$id = $this->_request->getParam ( "id", "" );
		if ($id != "") {
			$businessTypeModel = new Admin_Model_BusinessType ();
			$businessTypeModel->populate ( $id );
			if (! $businessTypeModel) {
				$redirect = true;
			}
		} else {
			$redirect = true;
		}
		if ($redirect) {
			$this->_redirect ( '/admin/business-type' );
		}
		
		$form = new Admin_Form_BusinessType ();
		$form->populate ( $businessTypeModel->toArray () );
		$this->_save ( $form, self::$EDIT_MODE );
		
		$this->view->form = $form;
		$this->view->assign ( array (
				"partial" => "business-type/partials/edit.phtml"
		) );
		$this->render ( "add-edit" );
	}
	
	/**
	 * Delete Business Type
	 */
	public function deleteAction() {
		// Check for ID and permissions and thus proceed or redirect accordingly
		$redirect = false;
		$id = $this->_request->getParam ( "id", "" );
		if ($id != "") {
			$businessTypeModel = new Admin_Model_BusinessType ();
			$businessTypeModel->populate ( $id );
			if (! $businessTypeModel) {
				$redirect = true;
			}
		} else {
			$redirect = true;
		}
		if ($redirect) {
			$this->_redirect ( '/admin/business-type' );
		}
		
		$response = array ();
		try {
			$deletedRows = $businessTypeModel->delete ();
			$response = array (
					"success" => array (
							"deleted_rows" => $deletedRows 
					) 
			);
		} catch ( Zend_Exception $ex ) {
			$message = $ex->getMessage ();
			if (strpos ( $message, "foreign key constraint fails" ) !== false) {
				$response = array (
						"errors" => array (
								"message" => "Business Type is already linked to one or more templates"
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
	private function _save(Zend_Form $form, $mode = null) {
		if ($mode == null) {
			$mode == self::$ADD_MODE;
		}
		
		if ($this->_request->isPost ()) {
			$response = array ();
			if ($form->isValid ( $this->_request->getParams () )) {
				$businessTypeModel = new Admin_Model_BusinessType ( $form->getValues () );
				
				// Set default values for created and updated
				$businessTypeModel->set ( "last_updated_at", Standard_Functions::getCurrentDateTime () );
				$businessTypeModel->set ( "last_updated_by", Standard_Functions::getCurrentUser ()->system_user_id );
				
				if ($mode == self::$ADD_MODE) {
					$businessTypeModel->set ( "created_at", Standard_Functions::getCurrentDateTime () );
					$businessTypeModel->set ( "created_by", Standard_Functions::getCurrentUser ()->system_user_id );
				}
				
				// Mapper class to save the data
				$businessTypeMapper = new Admin_Model_Mapper_BusinessType ();
				
				// Try to save the data
				try {
					$businessTypeModel = $businessTypeMapper->save ( $businessTypeModel );
				} catch ( Zend_Exception $ex ) {
					if (strpos ( $ex->getMessage (), "Duplicate" ) !== false) {
						$response = array (
								"errors" => array (
										"name" => "Business Type already exists." 
								) 
						);
					}
				}
				if ($businessTypeModel && $businessTypeModel->get ( "business_type_id" ) != "") {
					$response = array (
							"success" => $businessTypeModel->toArray () 
					);
				}
			} else {
				$errors = $form->getMessages ();
				foreach ( $errors as $name => $error ) {
					$errors [$name] = $error [0];
				}
				$response = array (
						"errors" => $errors 
				);
			}
			// Send error or success message accordingly
			$this->_helper->json ( $response );
		}
	}
}