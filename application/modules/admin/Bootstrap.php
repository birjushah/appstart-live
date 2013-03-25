<?php

class Admin_Bootstrap extends Zend_Application_Module_Bootstrap
{
	protected $_acl;
	protected $_auth;
	
	public function _initAuloload() {
	
		$this->_acl = Admin_Plugin_Acl::getInstance();
		
		$this->_auth = Zend_Auth::getInstance();
		$temp = $this->_auth->getStorage();
		$fc = Zend_Controller_Front::getInstance();
		if(!$this->_auth->hasIdentity()) {
			$this->_auth->getStorage()->write((object)array('role'=>'guest','group_id'=>0));
		}
		
		$fc->registerPlugin(new Admin_Plugin_Authentication($this->_acl, $this->_auth));
		$fc->registerPlugin(new Admin_Plugin_Layout());
	}
	
	private function _getTemplateModules($where = " 1 = 1 ") {
		$templateModuleMapper = new Admin_Model_Mapper_TemplateModule ();
	
		// Search for active modules and active template_module
		$templateModuleSql = $this->getDbTable ()->select ()->setIntegrityCheck ( false )->from ( array (
				'tm' => 'template_module'
		), '*' )->join ( array (
				"m" => "module"
		), " m.module_id = tm.module_id " )->where ( ' m.status = 1 AND tm.status = 1 ' )->where ( $where );
		return $templateModuleMapper->fetchAll ( $templateModuleSql );
	}
	
	public function _initTranslate() {
		$translate = Zend_Registry::get("app_translate");
		$langPath = APPLICATION_PATH . DIRECTORY_SEPARATOR .'admin'. DIRECTORY_SEPARATOR .'lang'. DIRECTORY_SEPARATOR;
		if(file_exists($langPath)) {
			$iterator = new DirectoryIterator($langPath);
			foreach ($iterator as $fileinfo) {
				if(!$fileinfo->isDir()) {
					$translate->getAdapter()->addTranslation(array(
							'content' => $fileinfo->getPath(). DIRECTORY_SEPARATOR .$fileinfo->getFilename(),
							'locale' => str_replace(".php", "", $fileinfo->getFilename())
					));
				}
			}
			
			Zend_Registry::set('Zend_Translate', $translate);
			Zend_Registry::set("app_translate", $translate);
		}
	}
}


