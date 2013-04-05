<?php

class Admin_IndexController extends Zend_Controller_Action
{

    public function init()
    {
		/* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
		
    }

    public function changeLocaleAction() {
    	$this->_helper->layout ()->disableLayout ();
    	$this->_helper->viewRenderer->setNoRender ( true );
    	try {
    		$language_id = $this->_request->getParam("language_id");
    		$authAdapter = Zend_Auth::getInstance ();
    		$identity = $authAdapter->getIdentity ();
    		$identity->active_language_id_admin = $language_id;
    		$storage = $authAdapter->getStorage ();
    		$storage->write ( $identity );
    		$response = array (
    				"success" => array (
    						"message" => "Success"
    				)
    		);
    	}catch(Exception $ex) {
    		$response = array (
    				"errors" => array (
    						"deleted_rows" => $ex->getMessage()
    				)
    		);
    	}
    	$this->_helper->json ( $response );
    }
}

