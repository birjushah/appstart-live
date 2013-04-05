<?php
class Default_Bootstrap extends Zend_Application_Module_Bootstrap {
	public function _initAuloload() {
		$this->_auth = Zend_Auth::getInstance ();
		
		if (! $this->_auth->hasIdentity ()) {
			$this->_auth->getStorage ()->write ( ( object ) array (
					'role' => 'guest',
					'group_id' => 0 
			) );
		}
		
		$fc = Zend_Controller_Front::getInstance ();
		$fc->registerPlugin ( new Default_Plugin_Authentication ( $this->_auth ) );
	}
	protected function _initRestRoutes() {
		$this->bootstrap ( 'FrontController' );
		$frontController = Zend_Controller_Front::getInstance ();
		$restRoute = new Zend_Rest_Route ( $frontController, array (), array (
				'default' => array (
						'rest' 
				) 
		) );
		$frontController->getRouter ()->addRoute ( 'defaultrestroute', $restRoute );
	}
}

