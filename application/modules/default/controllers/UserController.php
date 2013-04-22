<?php
class Default_UserController extends Zend_Controller_Action {
	public static $EDIT_MODE = "edit";
	public static $ADD_MODE = "add";
	public function indexAction() {
		$this->view->addlink = $this->view->url ( array (
				'module' => 'default',
				'controller' => 'user',
				'action' => 'add' 
		) );
	}
	public function gridAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ( true );
		$userMapper = new Default_Model_Mapper_User ();
		$userId = Standard_Functions::getCurrentUser ()->user_id;
		$select = $userMapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"u" => "user" 
		), array (
				"c.customer_name" => "c.customer_name",
				"u.username",
				"u.name" => "u.name",
				"u.status",
				"g.name" => "g.name",
				"u.email",
				"u.user_id" => "u.user_id" 
		) )->joinLeft ( array (
				"g" => "user_group" 
		), "g.user_group_id = u.user_group_id" )->joinLeft ( array (
				"c" => "customer" 
		), "g.customer_id = c.customer_id" )->where ( " u.created_by = " . $userId . " AND g.name <> 'Administrator'" );
		$response = $userMapper->getGridData ( array (
				'column' => array (
						'id' => array (
								'actions' 
						),
						'replace' => array (
								'status' => array (
										'0' => 'Inactive',
										'1' => 'Active' 
								) 
						) 
				) 
		), null, $select );
		$rows = $response ['aaData'];
		$customerId = Standard_Functions::getCurrentUser ()->customer_id;
		$mapper = new Admin_Model_Mapper_CustomerLanguage();
		$select = $mapper->getDbTable ()->
    							select ( false )->
    							setIntegrityCheck ( false )->
    							from ( array ("l" => "language"), array (
    									"l.language_id" => "language_id",
    									"l.title" => "title",
    									"logo" => "logo") )->
    							joinLeft ( array ("cl" => "customer_language"), "l.language_id = cl.language_id",
    											array ("cl.customer_id") )->
    											where("cl.customer_id=".Standard_Functions::getCurrentUser ()->customer_id);
		foreach ( $rows as $rowId => $row ) {
		    $editUrl = $this->view->url ( array (
		            "module" => "default",
		            "controller" => "user",
		            "action" => "edit",
		            "id" => $row [6] ["u.user_id"]
		    ), "default", true );
			$deleteUrl = $this->view->url ( array (
					"module" => "default",
					"controller" => "user",
					"action" => "delete",
					"id" => $row [6] ["u.user_id"] 
			), "default", true );
			$edit = '<a href="' . $editUrl . '" class="button-grid greay grid_edit" >'.$this->view->translate('Edit').'</a>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
			$sap = '&nbsp;';
			$response ['aaData'] [$rowId] [6] = $edit.$sap.$delete;
		}
		$this->_helper->json ( $response );
	}
	public function addAction() {
		$form = new Default_Form_User ();
		$user = new Default_Model_User ();
		$userMapper = new Default_Model_Mapper_User ();
		$this->_save ( $form, self::$ADD_MODE );
		$this->view->form = $form;
		$this->render ( "add-edit" );
	}
	public function editAction() {
		$userId = $this->_request->getParam ( "id" );
		if ($userId == "") {
			$this->_redirect ( "index" );
		}
		$userMapper = new Default_Model_Mapper_User ();
		$user = $userMapper->find ( $userId );
		if ($user == "") {
			$this->_redirect ( "index" );
		}
		$form = new Default_Form_User ();
		$this->_save ( $form, self::$EDIT_MODE );
		$formData = $user->toArray ();
		$form->populate ( $formData );
		$form->setAction ( $this->view->url ( array (
				"module" => "default",
				"controller" => 'user',
				"action" => 'edit',
				'id' => $userId 
		) ) );
		$this->view->form = $form;
		$this->render ( "add-edit" );
	}
	public function deleteAction() {
		$redirect = false;
		$userId = $this->_request->getParam ( "id", "" );
		$userMapper = new Default_Model_Mapper_User ();
		if ($userId != "") {
			$userModel = $userMapper->find ( $userId );
			if (! $userModel) {
				$redirect = true;
			}
		} else {
			$redirect = true;
		}
		if ($redirect) {
			$this->_redirect ( 'index' );
		}
		$response = array ();
		$userMapper->getDbTable ()->delete ( "user_id =" . $userId );
		$deletedRows = $userModel->delete ();
		$response = array (
				"success" => array (
						"deleted_rows" => $deletedRows 
				) 
		);
		$this->_helper->json ( $response );
	}
	private function _save(Zend_Form $form, $mode = null) {
		if ($mode == null) {
			$mode == self::$ADD_MODE;
		}
		
		if ($this->_request->isPost ()) {
			$response = array ();
			if ($form->isValid ( $this->_request->getParams () )) {
				$userMapper = new Default_Model_Mapper_User ();
				$user = new Default_Model_User ();
				try {
					$userMapper->getDbTable ()->getAdapter ()->beginTransaction ();
					$user->setOptions ( $form->getValues () );
					// Setting options for user group add and edit mode
					$user->setOptions ( array (
							'last_updated_at' => Standard_Functions::getCurrentDateTime (),
							'last_updated_by' => Standard_Functions::getCurrentUser ()->user_id 
					) );
					$user->setPassword ( md5 ( $this->_request->getParam ( "password" ) . $user->getUsername () ) );
					if ($mode == self::$ADD_MODE) {
						$user->setOptions ( array (
								'user_id' => "",
								'created_at' => Standard_Functions::getCurrentDateTime (),
								'created_by' => Standard_Functions::getCurrentUser ()->user_id 
						) );
					}
					$user->save ();
					$userMapper->getDbTable ()->getAdapter ()->commit ();
				} catch ( Exception $ex ) {
					$userMapper->getDbTable ()->getAdapter ()->rollBack ();
					if (strpos ( $ex->getMessage (), "Duplicate entry" ) !== false) {
						$response = array (
								'errors' => array (
										'name' => "User name already exists." 
								) 
						);
					} else {
						$response = array (
								'errors' => $ex->getMessage () 
						);
					}
				}
			}else{
				$errors = $form->getMessages ();
			
				foreach ( $errors as $name => $error ) {
					$errors [$name] = array_pop ( $error );
				}
				$response = array (
						"errors" => $errors 
				);
			}
			$this->_helper->json ( $response );
		}
	}
}