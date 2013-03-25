<?php
class Admin_Model_Mapper_Customer extends Standard_ModelMapper {
	protected $_dbTableClass = "Admin_Model_DbTable_Customer";
	public static $ADD_MODE = "add";
	public static $EDIT_MODE = "edit";
	/**
	 * Save customer and user information with the help of options provided
	 *
	 * @param array $options        	
	 * @throws Zend_Exception
	 * @throws Exception
	 * @return multitype:Array
	 */
	public function saveCustomer(array $options = array(), $mode = null) {
		// Define the save mode of customer
		$mode = $mode == null ? self::$ADD_MODE : $mode;
		if ($options ["start_date_time"] != "") {
			$start_date = DateTime::createFromFormat ( "d/m/Y H:i", $options ["start_date_time"] );
			if ($start_date) {
				$options ["start_date_time"] = $start_date->format ( "Y-m-d H:i:s" );
			}
		} else {
			unset ( $options ["start_date_time"] );
		}
		$returnData = array ();
		if (empty ( $options ))
			throw new Zend_Exception ( "Invalid Options provided to save customer. Please provide options for Customer and User tables" );
			
			// Begin the transaction
		$db = $this->getDbTable ()->getAdapter ();
		$db->beginTransaction ();
		try {
			// Initialize Current Date Time
			$currentDateTime = Standard_Functions::getCurrentDateTime ();
			// Initialize Current System User
			$currentUserId = Standard_Functions::getCurrentUser ()->system_user_id;
			
			$customer = new Admin_Model_Customer ();
			// Set the options user Customer
			$customer->setOptions ( $options );
			$customer->setLastUpdatedBy ( $currentUserId );
			$customer->setLastUpdatedAt ( $currentDateTime );
			if ($mode == self::$ADD_MODE) {
				$customer->setCreatedBy ( $currentUserId );
				$customer->setCreatedAt ( $currentDateTime );
			}
			// Save the customer and update the customer model
			$customer = $customer->save ();
			// Setting the customer languages
			$languages = $options ["language_id"];
			$default_language_id = $options ["default_language_id"];
			$customer_id = $customer->getCustomerId ();
			if ($mode == self::$ADD_MODE) {
				foreach ( $languages as $lang ) {
					$modelLang = new Admin_Model_CustomerLanguage ();
					$modelLang->setCustomerId ( $customer_id );
					$modelLang->setLanguageId ( $lang );
					$modelLang->setIsDefault ( ( int ) ($default_language_id == $lang) );
					$modelLang->save ();
				}
			} else {
				$db->delete ( "customer_language", "customer_id=" . $customer_id . " AND language_id NOT IN(" . (implode ( ",", $languages )) . ")" );
				foreach ( $languages as $lang ) {
					$modelLang = new Admin_Model_CustomerLanguage ();
					
					$mapperLang = new Admin_Model_Mapper_CustomerLanguage ();
					$result = $mapperLang->fetchAll ( "customer_id=" . $customer_id . " AND language_id = " . $lang );
					if ($result) {
						$modelLang = $result [0];
					}
					
					$modelLang->setCustomerId ( $customer_id );
					$modelLang->setLanguageId ( $lang );
					$modelLang->setIsDefault ( ( int ) ($default_language_id == $lang) );
					$modelLang->save ();
				}
			}
			
			// Setting the user group
			$userGroup = new Default_Model_UserGroup ();
			
			// Setting the User Model
			$user = new Default_Model_User ();
			// Initializing User Options
			$userOptions = $options;
			
			if ($mode == self::$ADD_MODE) {
				// If adding new customer then add the user group options
				// Setting the UserGroup Model
				$userGroup->setOptions ( array (
						'customer_id' => $customer->getCustomerId (),
						'name' => 'Administrator',
						'created_at' => $currentDateTime 
				) );
				$userGroup = $userGroup->save ();
				// Save the user Group and update the usergroup model
				
				$userOptions ["user_group_id"] = $userGroup->getUserGroupId ();
				$userOptions ["customer_id"] = $customer->getCustomerId ();
				$userOptions ["created"] = $currentDateTime;
			}
			$userOptions ["name"] = $options ["customer_name"];
			$userOptions ["last_updated_at"] = $currentDateTime;
			if ($mode == self::$EDIT_MODE) {
				if ($userOptions ["password"] == "" || $userOptions ["password"] == null)
					unset ( $userOptions ["password"] );
			}
			if (isset ( $userOptions ["password"] )) {
				$userOptions ["password"] = md5 ( $userOptions ["password"] . $userOptions ["username"] );
			}
			// Set the options for user
			$user->setOptions ( $userOptions );
			$user = $user->save ();
			// Save the userGroup lastupdatedby and created by when new customer
			// is added
			if ($mode == self::$ADD_MODE) {
				
				// Set created and updated by for user
				$user->setLastUpdatedBy ( $user->getUserId () );
				$user->setCreatedBy ( $user->getUserId () );
				$user = $user->save ();
				
				// Set created and updated by for user group
				$userGroup->setLastUpdatedBy ( $user->getUserId () );
				$userGroup->setCreatedBy ( $user->getUserId () );
				$userGroup->save ();
				
				// Set the user id in customer table only if in add mode
				$customer->setUserId ( $user->getUserId () );
				$customer->save ();
			} else if ($mode == self::$EDIT_MODE) {
				$userGroupMapper = new Default_Model_Mapper_UserGroup ();
				$userGroup = $userGroupMapper->fetchAll ( " customer_id = " . $customer->getCustomerId () );
				if ($userGroup) {
					$userGroup = $userGroup [0];
				} else {
					throw new Zend_Exception ( "User Group Not Found" );
				}
			}
			
			// For Customer_Module
			$customerModule = new Admin_Model_CustomerModule ();
			
			$templateModuleMapper = new Admin_Model_Mapper_TemplateModule ();
			$templateModulesQuote = $templateModuleMapper->getDbTable ()->getAdapter ()->quoteInto ( " template_id = ? ", $options ["template_id"] );
			$templateModules = $this->_getTemplateModules ( $templateModulesQuote, true );
			
			// For User Group Module
			$db->getProfiler()->setEnabled(true);
			$userGroupModuleMapper = new Default_Model_Mapper_UserGroupModule ();
			$userGroupModuleQuote = $userGroupModuleMapper->getDbTable ()->getAdapter ()->quoteInto ( "user_group_id = ?", $userGroup->getUserGroupId () );
			$userGroupModuleMapper->getDbTable ()->update ( array (
					"status" => 0 
			), $userGroupModuleQuote );
	
			// For Customer Module
			$customerModuleMapper = new Admin_Model_Mapper_CustomerModule ();
			$customerModuleQuote = $customerModuleMapper->getDbTable ()->getAdapter ()->quoteInto ( "customer_id = ?", $customer->getCustomerId () );
			$customerModuleMapper->getDbTable ()->update ( array (
					"status" => 0 
			), $customerModuleQuote );

			$userId = $user->getUserId ();
			$customerModuleOrder = 1;
			if ($templateModules) {		
				foreach ( $templateModules as $templateModuleRow ) {
					$userGroupModule = new Default_Model_UserGroupModule ();
					$rowData = $templateModuleRow->toArray ();
					
					$userGroupModels = $userGroupModuleMapper->fetchAll("user_group_id =".$userGroup->getUserGroupId ()."  AND module_id=".$rowData ['module_id']);
					$userGroupModuleOptions = array (
							'user_group_module_id' => "",
							'user_group_id' => $userGroup->getUserGroupId (),
							'module_id' => $rowData ['module_id'],
							'last_updated_by' => $userId,
							'created_by' => $userId,
							'last_updated_at' => $currentDateTime,
							'status' => 1,
							'created_at' => $currentDateTime
					);
					if($userGroupModels){
						$userGroupModuleOptions["user_group_module_id"] = $userGroupModels[0]->getUserGroupModuleId();
					}
					
					$userGroupModule->setOptions ( $userGroupModuleOptions );
					$userGroupModule->save ();
					
					// Configuring for Customer_Module
					
					$module = $templateModuleRow->findParentRow ( 'Admin_Model_DbTable_Module', 'Module' )->toArray ();
					$customerModuleMapper = new Admin_Model_Mapper_CustomerModule();
					$customerModuleModels = $customerModuleMapper->fetchAll("customer_id =".$customer->getCustomerId ()."  AND module_id=".$rowData ['module_id']);
					
					$customerModuleOptions = array (
							'customer_module_id' => "",
							'customer_id' => $customer->getCustomerId (),
							'module_id' => $rowData ['module_id'],
							'order_number' => $customerModuleOrder,
							'visibility' => 0,
							'screen_name' => $module ['name'],
							'last_updated_by' => $currentUserId,
							'created_by' => $currentUserId,
							'last_updated_at' => $currentDateTime,
							'status' => 1,
							'created_at' => $currentDateTime
					);
					if($customerModuleModels){
						$customerModuleOptions["customer_module_id"] = $customerModuleModels[0]->getCustomerModuleId();
						$customerModuleOptions["icon"] = $customerModuleModels[0]->getIcon();
						$customerModuleOptions["visibility"] = $customerModuleModels[0]->getVisibility();
						$customerModuleOptions["is_publish"] = $customerModuleModels[0]->getIsPublish();
					}
					$customerModule->setOptions ( $customerModuleOptions );
					$customerModule = $customerModule->save ();
					$customerModuleOrder ++;
					if(!$customerModuleModels){
						foreach ( $languages as $language_id ) {
							$customerModuleDetail = new Admin_Model_CustomerModuleDetail ( $customerModule->toArray () );
							$moduleMapper = new Admin_Model_Mapper_Module ();
							$moduleModel = $moduleMapper->find ( $customerModule->getModuleId () );
							$customerModuleDetail->setScreenName ( $moduleModel->getDescription () );
							$customerModuleDetail->setLanguageId ( $language_id );
							$customerModuleDetail->save ();
						}
					}
				}
			}
			$db->commit ();
			$returnData ['customer'] = $customer->toArray ();
			$returnData ['user'] = $user->toArray ();
		} catch ( Exception $ex ) {
			$db->rollBack ();
			throw $ex;
		}
		return $returnData;
	}
	/**
	 *
	 * @param
	 *        	string | select | quote $where
	 * @param boolean $return_row        	
	 * @return Zend_Db_Table_Rowset_Abstract Ambigous boolean>
	 */
	private function _getTemplateModules($where = " 1 = 1 ", $return_row = false) {
		$templateModuleMapper = new Admin_Model_Mapper_TemplateModule ();
		
		// Search for active modules and active template_module
		$templateModuleSql = $templateModuleMapper->getDbTable ()->select ()->setIntegrityCheck ( false )->from ( array (
				'tm' => 'template_module' 
		), '*' )->join ( array (
				"m" => "module" 
		), " m.module_id = tm.module_id " )->where ( ' m.status = 1 AND tm.status = 1 ' )->where ( $where );
		if ($return_row)
			return $templateModuleMapper->getDbTable ()->fetchAll ( $templateModuleSql );
		else
			return $templateModuleMapper->fetchAll ( $templateModuleSql );
	}
}