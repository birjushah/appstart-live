<?php
class Admin_Plugin_Authentication extends Zend_Controller_Plugin_Abstract {
	private $_acl = null;
	private $_auth = null;
	public function __construct(Zend_Acl $acl, Zend_Auth $auth) {
		$this->_acl = $acl;
		$this->_auth = $auth;
	}
	public function preDispatch(Zend_Controller_Request_Abstract $request) {
		$resource = $request->getControllerName ();
		$action = $request->getActionName ();
		Standard_Functions::$action = strtolower($action);
		if (strtolower ( $request->getModuleName () ) == "admin") {
			
			if (! $this->_auth->hasIdentity () && $resource != "forgot" && $action != "check-login") {
				$request->setControllerName ( 'login' )->setActionName ( 'index' );
			} else if ((! isset ( $this->_auth->getStorage ()->read ()->role ) || $this->_auth->getStorage ()->read ()->role == "guest") && $resource != "forgot" && $action != "check-login") {
				$request->setControllerName ( 'login' )->setActionName ( 'index' );
			}
			
			if ($this->_auth->hasIdentity () && $this->_auth->getStorage ()->read ()->role != "guest") {
				//if($this->_acl->isAllowed($this->_auth->getStorage ()->read ()->role,$resource)) {
					$this->_initLocale();
					
					$view = Zend_Layout::getMvcInstance ()->getView ();
					$config = new Zend_Config_Xml ( APPLICATION_PATH . '/modules/admin/configs/navigation.xml', "nav" );
					$navigation = new Zend_Navigation ( $config );
					$view->navigation ( $navigation )->setAcl ( $this->_acl )->setRole ( $this->_auth->getStorage ()->read ()->role );
				//} else {
				//	$request->setControllerName ( 'login' )->setActionName ( 'logout' );
				//}
			} else if($resource != "forgot" && $resource != "error"){
				$request->setControllerName ( 'login' )->setActionName ( 'index' );
			}
		}
	}
	private function _initLocale() {
		$localeValue = 'en';
	
		$lang = Standard_Functions::getAdminActiveLanguage();
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