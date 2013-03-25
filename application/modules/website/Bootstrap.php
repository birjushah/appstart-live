<?php

class Website_Bootstrap extends Zend_Application_Module_Bootstrap
{
	protected function _initRestRoutes() {
		$this->bootstrap ( 'FrontController' );
		$frontController = Zend_Controller_Front::getInstance ();
		$restRoute = new Zend_Rest_Route ( $frontController, array (), array (
				'website' => array (
						'rest'
				)
		) );
		$frontController->getRouter ()->addRoute ( 'websiterestroute', $restRoute );
	}
}	

