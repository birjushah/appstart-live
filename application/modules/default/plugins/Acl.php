<?php
class Default_Plugin_Acl extends Zend_Acl {
	protected static $_instance;
	public static function getInstance()
	{
		if (null === self::$_instance) {
			self::$_instance = new self();
		}
	
		return self::$_instance;
	}
	public function __construct() {
		//Zend_Loader_Autoloader::autoload('Admin_Model_Mapper_Module');
		$arrResources = array();
		
		// Add static resources
		$this->addResource(new Zend_Acl_Resource("index"));
		$this->addResource(new Zend_Acl_Resource("settings"));
		$this->addResource(new Zend_Acl_Resource("user-group"));
		$this->addResource(new Zend_Acl_Resource("profile"));
		$this->addResource(new Zend_Acl_Resource("user"));
		$this->addResource(new Zend_Acl_Resource("error"));
		$this->addResource(new Zend_Acl_Resource("configuration"));
		$moduleMapper = new Admin_Model_Mapper_Module();
		$modules = $moduleMapper->fetchAll("status=1");
		if(is_array($modules)) {
			foreach($modules as $module) {
				$this->addResource(new Zend_Acl_Resource($module->getName()));
				$arrResources[$module->getModuleId()] = $module->getName();
			}
		}
		// Setup Roles
		$rolesMapper = new Default_Model_Mapper_UserGroup();
		$roles = $rolesMapper->fetchAll();
		if(is_array($roles)) {
			foreach($roles as $role) {
				$this->addRole(new Zend_Acl_Role($role->getUserGroupId()));
				
				// Add static permissins
				$this->allow($role->getUserGroupId(),"index");
				$this->allow($role->getUserGroupId(),"profile");
				$this->allow($role->getUserGroupId(),"settings");
				if($role->getSettings()==1) {
					$this->allow($role->getUserGroupId(),"user-group");
					$this->allow($role->getUserGroupId(),"user");
					$this->allow($role->getUserGroupId(),"configuration");
				}			
				
				$this->allow($role->getUserGroupId(),"error");
				
				// Set Permissions
				/*$groupmodulesMapper = new Default_Model_Mapper_UserGroupModule();
				$groupmodules = $groupmodulesMapper->fetchAll("status=1 AND user_group_id=".$role->getUserGroupId()." module_id in (".implode(",", $arrCustomerModule).")");
				if(is_array($groupmodules)) {
					foreach($groupmodules as $module) {
						$this->allow($role->getUserGroupId(),$arrResources[$module->getModuleId()]);
					}
				}*/
				$customerModulesMapper = new Admin_Model_Mapper_CustomerModule();
				$customerModules = $customerModulesMapper->fetchAll("status=1 AND customer_id=".$role->getCustomerId());
				$arrCustomerModule = array();
				if(is_array($customerModules) && count($customerModules)>0) {
					foreach($customerModules as $cm) {
						$arrCustomerModule[] = $cm->getModuleId();
					}
					$groupmodulesMapper = new Default_Model_Mapper_UserGroupModule();
					$groupmodules = $groupmodulesMapper->fetchAll("status=1 AND user_group_id=".$role->getUserGroupId()." AND module_id in (".implode(",", $arrCustomerModule).")");
					if(is_array($groupmodules)) {
						foreach($groupmodules as $module) {
							$this->allow($role->getUserGroupId(),$arrResources[$module->getModuleId()]);
						}
					}
				}
			}
		}
	}
}