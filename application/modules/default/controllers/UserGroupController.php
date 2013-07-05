<?php
class Default_UserGroupController extends Zend_Controller_Action {
	public static $EDIT_MODE = "edit";
	public static $ADD_MODE = "add";
	public function indexAction() {
		$this->view->addlink = $this->view->url(array(
				'module' => 'default',
				'controller' => 'user-group',
				'action' => 'add'
		));
	}
	public function gridAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ( true );
		$userGroupMapper = new Default_Model_Mapper_UserGroup ();
		$userId = Standard_Functions::getCurrentUser()->user_id;
		$select = $userGroupMapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"ug" => "user_group"
		), array (
				"ug.user_group_id",
				"ug_name" => "ug.name" 
		) )
		->where("ug.created_by = ".$userId . " AND ug.name <> 'Administrator'" )
		->group ( "ug.user_group_id" );
		$response = $userGroupMapper->getGridData ( array (
				'column' => array (
						'id' => array (
								'actions' 
						),
						'ignore' => array(
								'total_modules',
								'total_users',
						)
				) 
		), null, $select );
		$rows = $response ['aaData'];
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
    	$languages = $mapper->getDbTable ()->fetchAll($select)->toArray();
		foreach ( $rows as $rowId => $row ) {
			$edit = array();
			$userMapper = new Default_Model_Mapper_User ();
			$totalUsers = $userMapper->countAll ( "user_group_id=" . $row [4] ["user_group_id"] );
			$userGroupModuleMapper = new Default_Model_Mapper_UserGroupModule ();
			$totalModules = $userGroupModuleMapper->countAll ( "user_group_id=" . $row [4] ["user_group_id"] );
			
			$response ['aaData'] [$rowId] [2] = $totalModules;
			$response ['aaData'] [$rowId] [3] = $totalUsers;
			$editUrl = $this->view->url ( array (
    			"module" => "default",
    			"controller" => "user-group",
    			"action" => "edit",
    			"id" => $row [4] ["user_group_id"] 
    			), "default", true );
			$deleteUrl = $this->view->url ( array (
					"module" => "default",
					"controller" => "user-group",
					"action" => "delete",
					"id" => $row [4] ["user_group_id"] 
			), "default", true );
			$edit = '<a href="' . $editUrl . '" class="button-grid greay grid_edit" >'.$this->view->translate('Edit').'</a>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
			$sap = '&nbsp;';
			$response ['aaData'] [$rowId] [4] = $edit.$sap.$delete;
		}
		echo $this->_helper->json ( $response );
	}
	
	/**
	 * User Group Add Action to save new UserGroup
	 */
	public function addAction() {
		$form = new Default_Form_UserGroup ();
		$userGroup = new Default_Model_UserGroup ();
		$userGroupMapper = new Default_Model_Mapper_UserGroup ();
		$this->_save ( $form, self::$ADD_MODE );
		unset($form->getElement('modules')->required);
		$this->view->form = $form;
		$this->render ( "add-edit" );
	}
	
	/**
	 * Delete Business Type
	 */
	public function deleteAction() {
		// Check for ID and permissions and thus proceed or redirect accordingly
		$redirect = false;
		$groupId = $this->_request->getParam ( "id", "" );
		$userGroupMapper = new Default_Model_Mapper_UserGroup ();
		if ($groupId != "") {
			$userGroupModel = $userGroupMapper->find ( $groupId );
			if (! $userGroupModel) {
				$redirect = true;
			}
		} else {
			$redirect = true;
		}
		if ($redirect) {
			$this->_redirect ( 'index' );
		}
		$response = array ();
		try {
			$userGroupMapper->getDbTable()->getAdapter()->beginTransaction();
			//delete the user group modules first
			$userGroupModuleMapper = new Default_Model_Mapper_UserGroupModule();
			$userGroupModuleMapper->getDbTable()->delete("user_group_id = ".$groupId);
			
			// delete users related to user group
			$userMapper = new Default_Model_Mapper_User();
			$userMapper->getDbTable()->delete("user_group_id = ".$groupId);
			
			$deletedRows = $userGroupModel->delete();
			$response = array (
					"success" => array (
							"deleted_rows" => $deletedRows 
					) 
			);
			$userGroupMapper->getDbTable()->getAdapter()->commit();
		} catch ( Zend_Exception $ex ) {
			$userGroupMapper->getDbTable()->getAdapter()->rollBack();			
			$response = array (
				"errors" => $ex->getMessage()
			);
		}
		$this->_helper->json ( $response );
	}
	
	/**
	 * Edit action for the User-Group
	 */
	public function editAction() {
		$groupId = $this->_request->getParam ( "id" );
		if ($groupId == "") {
			$this->_redirect ( 'index' );
		}
		$userGroupMapper = new Default_Model_Mapper_UserGroup ();
		$userGroup = $userGroupMapper->find ( $groupId );
		if (! $userGroup) {
			$this->_redirect ( 'index' );
		}
		$form = new Default_Form_UserGroup ();
		$this->_save ( $form, self::$EDIT_MODE );
		$formData = $userGroup->toArray ();
		$userGroupModuleMapper = new Default_Model_Mapper_UserGroupModule ();
		$userGroupModules = $userGroupModuleMapper->fetchAll ( "user_group_id = " . $groupId );
		$modules = array ();
		foreach ( $userGroupModules as $userGroupModule ) {
			$modules [] = $userGroupModule->getModuleId ();
		}
		$formData ['modules'] = $modules;
		$form->populate ( $formData );
		$form->setAction ( $this->view->url ( array (
				"module" => "default",
				"controller" => 'user-group',
				"action" => 'edit',
				'id' => $groupId 
		) ) );
		unset($form->getElement('modules')->required);
		$this->view->form = $form;
		$this->render ( "add-edit" );
	}
	private function _save(Zend_Form $form, $mode = null) {
		if ($mode == null) {
			$mode == self::$ADD_MODE;
		}
		
		if ($this->_request->isPost ()) {
			$response = array ();
			if ($form->isValid ( $this->_request->getParams () )) {
				$userGroupMapper = new Default_Model_Mapper_UserGroup ();
				$userGroup = new Default_Model_UserGroup ();
				try {
					$userGroupMapper->getDbTable ()->getAdapter ()->beginTransaction ();
					$modules = $form->getValue ( "modules" );
					$userGroup->setOptions ( $form->getValues () );
					
					// Setting options for user group add and edit mode
					$userGroup->setOptions ( array (
							'last_updated_at' => Standard_Functions::getCurrentDateTime (),
							'last_updated_by' => Standard_Functions::getCurrentUser ()->user_id 
					) );
					if ($mode == self::$ADD_MODE) {
						$userGroup->setOptions ( array (
								'user_group_id' => "",
								'customer_id' => Standard_Functions::getCurrentUser ()->customer_id,
								'created_at' => Standard_Functions::getCurrentDateTime (),
								'created_by' => Standard_Functions::getCurrentUser ()->user_id 
						) );
					}
					
					if ($userGroup->save ()) {
						
						// User Group Module Save Login
						// 1) If the user group is newly created then add the
						// modules without hesitation
						// 2) Id the user group already exists then do as
						// following
						// a) Delete previous user_group_moulde_id which are now
						// not needed
						// this is based on critera that the module id now
						// received only that needs to be kept in database
						//
						$userGroupModuleMapper = new Default_Model_Mapper_UserGroupModule ();
						$userGroupModule = new Default_Model_UserGroupModule ();
						$deletedRows = $userGroupModuleMapper->getDbTable ()->delete ( "user_group_id = " . $userGroup->getUserGroupId () . " AND module_id NOT IN (" . implode ( ",", $modules ) . ") " );
						// Check for module existance and then add accordingly
						$userGroupModules = $userGroupModuleMapper->fetchAll ( " user_group_id = " . $userGroup->getUserGroupId () );
						$existingModules = array ();
						if ($userGroupModules) {
							foreach ( $userGroupModules as $userGroupModule ) {
								$existingModules [] = $userGroupModule->getModuleId ();
							}
						}
						$newModules = array_diff ( $modules, $existingModules );
						
						foreach ( $newModules as $module_id ) {
							$userGroupModule->setOptions ( array (
									'user_group_module_id' => "",
									'user_group_id' => $userGroup->getUserGroupId (),
									'module_id' => $module_id,
									'status' => 1,
									'last_updated_at' => Standard_Functions::getCurrentDateTime (),
									'created_at' => Standard_Functions::getCurrentDateTime (),
									'last_updated_by' => Standard_Functions::getCurrentUser ()->user_id,
									'created_by' => Standard_Functions::getCurrentUser ()->user_id 
							) );
							$userGroupModule->save ();
						}
						$response = array (
								'success' => $userGroup->toArray () 
						);
					}
					$userGroupMapper->getDbTable ()->getAdapter ()->commit ();
				} catch ( Exception $ex ) {
					$userGroupMapper->getDbTable ()->getAdapter ()->rollBack ();
					if (strpos ( $ex->getMessage (), "Duplicate entry" ) !== false && strpos ( $ex->getMessage (), "customer_user_group" ) !== false) {
						$response = array (
								'errors' => array (
										'name' => "Group name already exists." 
								) 
						);
					} else {
						$response = array (
								'errors' => $ex->getMessage () 
						);
					}
				}
			} else {
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
