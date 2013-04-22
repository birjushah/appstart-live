<?php
class Default_Plugin_Authentication extends Zend_Controller_Plugin_Abstract {
	private $_acl = null;
	private $_auth = null;
	public function __construct(Zend_Auth $auth) {
		$this->_auth = $auth;
	}
	public function preDispatch(Zend_Controller_Request_Abstract $request) {
		if($this->_acl == null) {
			$this->_acl = new Default_Plugin_Acl();
		}
		
		$resource = $request->getControllerName ();
		$action = $request->getActionName ();
		if (strtolower ( $request->getModuleName () ) != "admin") {
			
			if($resource != "forgot" && $resource != "error" && $action!="admin-login" && $resource!="rest" && $resource!="download") {
				if (! $this->_auth->hasIdentity () && $resource != "forgot" && $action != "check-login" ) {
					$request->setModuleName("default")->setControllerName ( 'login' )->setActionName ( 'index' );
				} else if ((! isset ( $this->_auth->getStorage ()->read ()->group_id ) || $this->_auth->getStorage ()->read ()->group_id == 0) && $resource != "forgot" && $action != "check-login") {
					$request->setModuleName("default")->setControllerName ( 'login' )->setActionName ( 'index' );
				}
				
				if ($this->_auth->hasIdentity () && $this->_acl->hasRole($this->_auth->getStorage ()->read ()->group_id) && $this->_auth->getStorage ()->read ()->group_id != "guest") {
					$this->_initLocale();
					if($request->getModuleName ()== "default" && 
							$this->_acl->hasRole($this->_auth->getStorage ()->read ()->group_id) && 
							$this->_acl->has($resource) &&
							$this->_acl->isAllowed($this->_auth->getStorage ()->read ()->group_id,$resource)) {
						// Access Allowed For Default Module
						$this->_initNavigation();
					} else if($request->getModuleName () != "default" &&
							$this->_acl->hasRole($this->_auth->getStorage ()->read ()->group_id) &&
							$this->_acl->has($request->getModuleName ()) &&
							$this->_acl->isAllowed($this->_auth->getStorage ()->read ()->group_id,$request->getModuleName ())) {
						// Access Allowed For Other Modules
						$this->_initNavigation();
					}
					else {
						//$request->setModuleName("default")->setControllerName ( 'login' )->setActionName ( 'logout' );
					}
				} 
				else {
					$request->setModuleName("default")->setControllerName ( 'login' )->setActionName ( 'index' );
				}
			}
		}
	}
	
	private function _initNavigation() {
		$view = Zend_Layout::getMvcInstance ()->getView ();
		$config = new Zend_Config( array(), true);
		
		$iterator = new DirectoryIterator(APPLICATION_PATH . '/modules/');
		foreach ($iterator as $fileinfo) {
			if($fileinfo->isDir() && strtolower($fileinfo->getFilename()) != "admin") {
				if(file_exists($fileinfo->getPath().'/'.$fileinfo->getFilename().'/configs/navigation.xml')) {
					$config->merge(new Zend_Config_Xml ( $fileinfo->getPath().'/'.$fileinfo->getFilename().'/configs/navigation.xml', "nav" ));
				}
			}
		}
		$configArray = $config->toArray();
		
		$modules = array();
		$icons = array();
		$moduleName = array();
		$mapper = new Admin_Model_Mapper_Module();
		$model = $mapper->fetchAll();
		foreach ($model as $module) {
			$modules[$module->getName()] = $module->getDescription();
			$moduleName[$module->getModuleId()] = $module->getName();
		}
		$mapper = new Admin_Model_Mapper_CustomerModule();
		$model = $mapper->fetchAll("status=1 AND customer_id=".Standard_Functions::getCurrentUser()->customer_id);
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		foreach ($model as $customerModule) {
			$icons[$moduleName[$customerModule->getModuleId()]] = $customerModule->getIcon();
			$order[$moduleName[$customerModule->getModuleId()]] = $customerModule->getOrderNumber();
			$mapperDetail = new Admin_Model_Mapper_CustomerModuleDetail();
			$modelDetail = $mapperDetail->fetchAll("customer_module_id = ".$customerModule->get("customer_module_id"). " AND language_id=".$active_lang_id);
			
			if(is_array($modelDetail) && is_object($modelDetail[0])) {
				$modules[$moduleName[$customerModule->getModuleId()]] = $modelDetail[0]->getScreenName();
			}
		}
		$modules = array_filter($modules,function($element){
			return ($element != "");
		});
		foreach($configArray["Modules"]["pages"] as $key=>$page) {		
			$configArray["Modules"]["pages"][$key]["MM_description"] = $configArray["Modules"]["pages"][$key]["label"];
			if(key_exists($page["module"], $modules))
				$configArray["Modules"]["pages"][$key]["label"] = $modules[$page["module"]];
			if(key_exists($page["module"], $icons))
				$configArray["Modules"]["pages"][$key]["MM_icon"] = $icons[$page["module"]];
			if(key_exists($page["module"], $order)){
				$configArray["Modules"]["pages"][$key]["order"] = $order[$page["module"]];
			}
		}
		//sorting for ordering in mega menu
		usort($configArray["Modules"]["pages"], function($array1,$array2){
			if ($array1["order"] == $array2["order"]) {
        		return 0;
    		}
    		return ($array1["order"] < $array2["order"]) ? -1 : 1;
		});
		
		$config = new Zend_Config( $configArray, true);

		$navigation = new Zend_Navigation ( $config );
		$view->navigation ( $navigation )->setAcl ( $this->_acl )->setRole ( $this->_auth->getStorage ()->read ()->group_id );
	}
	private function _initLocale() {
		$localeValue = 'en';
		
		$lang = Standard_Functions::getActiveLanguage();
		if($lang) {
			$localeValue = $lang->getLang();
		}
		
		$locale = new Zend_Locale($localeValue);
		Zend_Registry::set('Zend_Locale', $locale);
		
		$translate = Zend_Registry::get("app_translate");
		$translate->getAdapter()->setLocale($localeValue);
		
		Zend_Registry::set('Zend_Translate', $translate);
		Zend_Registry::set("app_translate", $translate);
	}
}