<?php
class Admin_LoginController extends Zend_Controller_Action {
	public function init() {
	}
	public function indexAction() {
		// action body
		$request = $this->getRequest ();
		$form = new Admin_Form_Login ();
		if ($this->getRequest ()->isPost ()) {
			if ($form->isValid ( $request->getPost () )) {
				// set up the auth adapter
				// get the default db adapter
				$db = Zend_Db_Table::getDefaultAdapter ();
				// create the auth adapter
				$authAdapter = new Zend_Auth_Adapter_DbTable ( $db, 'system_user', 'email', 'password', 'MD5(CONCAT(?,email))' );
				
				$authAdapter->setIdentity ( $request->getPost ( 'email' ) );
				$authAdapter->setCredential ( $request->getPost ( 'password' ) );
				// authenticate
				$result = $authAdapter->authenticate (); // var_dump($result);exit();
				if ($result->isValid ()) {
					
					// store the username, first and last names of the user
					$auth = Zend_Auth::getInstance ();
					$identity = new stdClass ();
					$identity = $authAdapter->getResultRowObject ( array (
							'system_user_id',
							'email',
							'role' 
					) );
					
					// Setting is active Data
					$identity->role_id = $identity->role;
					$identity->role = ($identity->role_id == 1) ? "admin" : "user";
					$identity->active_language_id_admin = 1;
					
					$storage = $auth->getStorage ();
					$storage->write ( $identity );
					
					if ($request->getPost ( 'remember' ) == "1") {
						// Zend_Session::rememberMe();
						$url = $this->getRequest ()->getScheme () . '://' . $this->getRequest ()->getHttpHost () . str_replace ( "/login", "", $this->getRequest ()->getRequestUri () );
						
						setcookie ( "email", base64_encode ( $request->getPost ( 'appstart_email' ) ), time () + ((24 * 3600) * 7) );
						setcookie ( "password", base64_encode ( $request->getPost ( 'appstart_password' ) ), time () + ((24 * 3600) * 7) );
					} 					// }
					else {
						// Zend_Session::forgetMe();
						setcookie ( "email", "", time () + ((24 * 3600) * 7) );
						setcookie ( "password", "", time () + ((24 * 3600) * 7) );
					}
					$this->_redirect ( 'admin/index' );
					return;
				}
				$this->view->assign ( array (
						'loginMessage' => 'Invalid Username/Password' 
				) );
				$this->_helper->viewRenderer->setRender ( 'index' );
			} else {
				$this->view->assign ( array (
						'loginMessage' => 'Invalid Username/Password' 
				) );
				$this->_helper->viewRenderer->setRender ( 'index' );
			}
		}
		
		if (isset ( $_COOKIE ['appstart_email'] ) && isset ( $_COOKIE ['appstart_password'] ) && ! $this->getRequest ()->isPost ()) {
			$form->getElement ( "email" )->setValue ( base64_decode ( $_COOKIE ['appstart_email'] ) );
			$this->view->assign ( array (
					"password" => base64_decode ( $_COOKIE ['appstart_password'] ) 
			) );
		} else if ($this->getRequest ()->isPost ()) {
			$this->view->assign ( array (
					"password" => $this->getRequest ()->getParam ( 'appstart_password', "" ) 
			) );
		} else {
			$this->view->assign ( array (
					"password" => "" 
			) );
		}
		
		foreach ($form->getElements() as $element) {
			if($element->getDecorator('Label')) $element->getDecorator('Label')->setTag(null);
		}
		$this->view->form = $form;
	}
	public function logoutAction() {
		$authAdapter = Zend_Auth::getInstance ();
		$identity = $authAdapter->getIdentity ();
		$authAdapter->clearIdentity ();
		$this->_redirect ( '/admin/' );
	}
}

