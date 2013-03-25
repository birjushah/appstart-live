<?php
class Default_LoginController extends Zend_Controller_Action {
	public function init() {
		
	}
	public function indexAction() {
		// action body
		$request = $this->getRequest ();
		$form = new Default_Form_Login ();
		
		if ($this->getRequest ()->isPost ()) {
			if ($form->isValid ( $request->getPost () )) {
				// set up the auth adapter
				// get the default db adapter
				$db = Zend_Db_Table::getDefaultAdapter ();
				// create the auth adapter
				$authAdapter = new Zend_Auth_Adapter_DbTable ( $db, 'user', 'username', 'password', 'MD5(CONCAT(?,username))' );
				
				$authAdapter->setIdentity ( $request->getPost ( 'username' ) );
				$authAdapter->setCredential ( $request->getPost ( 'password' ) );
				// authenticate
				$result = $authAdapter->authenticate (); // var_dump($result);exit();
				if ($result->isValid ()) {
					
					// store the username, first and last names of the user
					$auth = Zend_Auth::getInstance ();
					$identity = new stdClass ();
					$identity = $authAdapter->getResultRowObject ( array (
							'user_id',
							'username',
                            'name',
                            'user_group_id',
					) );
					
					$groupMapper = new Default_Model_Mapper_UserGroup();
					$group = $groupMapper->find($identity->user_group_id);
					$customerMapper = new Admin_Model_Mapper_Customer();
					$customer = $customerMapper->find($group->getCustomerId());
					if($customer->getStatus()==1) {
						if($group) {
							$identity->customer_id = $group->getCustomerId();
						} else {
							$identity->customer_id = 0;
						}
						$identity->group_id = $identity->user_group_id;
						
						$languageMapper = new Admin_Model_Mapper_CustomerLanguage();
						$language = $languageMapper->fetchAll("customer_id = ".$group->getCustomerId()." AND is_default=1");
						if(is_array($language)) {
							$identity->default_language_id = $language[0]->getLanguageId();
							$identity->active_language_id = $language[0]->getLanguageId();
						} else {
							$identity->default_language_id = 1;
							$identity->active_language_id = 1;
						}
						$storage = $auth->getStorage ();
						$storage->write ( $identity );
						
						if ($request->getPost ( 'remember' ) == "1") {
							// Zend_Session::rememberMe();
							$url = $this->getRequest ()->getScheme () . '://' . $this->getRequest ()->getHttpHost () . str_replace ( "/login", "", $this->getRequest ()->getRequestUri () );
							
							setcookie ( "username", base64_encode ( $request->getPost ( 'username' ) ), time () + ((24 * 3600) * 7) );
							setcookie ( "password", base64_encode ( $request->getPost ( 'password' ) ), time () + ((24 * 3600) * 7) );
						} 					// }
						else {
							// Zend_Session::forgetMe();
							setcookie ( "username", "", time () + ((24 * 3600) * 7) );
							setcookie ( "password", "", time () + ((24 * 3600) * 7) );
						}
						//$this->setLocale($identity->active_language_id);
						$this->_redirect ( '/' );
						return;
					} else {
						$this->view->assign ( array (
								'loginMessage' => 'Your Account is Inactivated. Please Contact The Administrator.'
						) );
					}
				} else {
					$this->view->assign ( array (
							'loginMessage' => 'Invalid Username/Password' 
					) );
				}
				$this->_helper->viewRenderer->setRender ( 'index' );
			} else {
				$this->view->assign ( array (
						'loginMessage' => 'Invalid Username/Password' 
				) );
				$this->_helper->viewRenderer->setRender ( 'index' );
			}
		}
		
		if (isset ( $_COOKIE ['username'] ) && isset ( $_COOKIE ['password'] ) && ! $this->getRequest ()->isPost ()) {
			$form->getElement ( "username" )->setValue ( base64_decode ( $_COOKIE ['username'] ) );
			$this->view->assign ( array (
					"password" => base64_decode ( $_COOKIE ['password'] ) 
			) );
		} else if ($this->getRequest ()->isPost ()) {
			$this->view->assign ( array (
					"password" => $this->getRequest ()->getParam ( 'password', "" ) 
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
	public function adminLoginAction() {
		$authAdapter = Zend_Auth::getInstance ();
		if($authAdapter->hasIdentity() && 
				isset($authAdapter->getStorage ()->read ()->role) && 
				$authAdapter->getStorage ()->read ()->role != "guest"){
			$customer_id = $this->_request->getParam ( "customer_id", "" );
			if($customer_id!="") {
				$customerMapper = new Admin_Model_Mapper_Customer();
				$customer = $customerMapper->find($customer_id);
				if($customer)
				{
					$userMapper = new Default_Model_Mapper_User();
					$user = $userMapper->find($customer->getUserId());
					
					$auth = Zend_Auth::getInstance ();
					$identity = $auth->getIdentity();
					$identity->user_id = $user->getUserId();
					$identity->username = $user->getUsername();
					$identity->name = $user->getName();
					$identity->group_id = $identity->user_group_id = $user->getUserGroupId();
					$identity->customer_id = $customer->getCustomerId();
					
					$languageMapper = new Admin_Model_Mapper_CustomerLanguage();
					$language = $languageMapper->fetchAll("customer_id = ".$customer->getCustomerId()." AND is_default=1");
					if(is_array($language)) {
						$identity->default_language_id = $language[0]->getLanguageId();
						$identity->active_language_id = $language[0]->getLanguageId();
					} else {
						$identity->default_language_id = 1;
						$identity->active_language_id = 1;
					}
					
					$storage = $auth->getStorage ();
					$storage->write ( $identity );
				}
			}
		}
		//$this->setLocale($identity->active_language_id);
		$this->_redirect ( '/' );
	}
	public function logoutAction() {
		$authAdapter = Zend_Auth::getInstance ();
		if($authAdapter->hasIdentity() &&
			isset($authAdapter->getStorage ()->read ()->role) &&
			$authAdapter->getStorage ()->read ()->role != "guest") {
				$identity = $authAdapter->getIdentity ();
				unset($identity->user_id);
				unset($identity->username);
				unset($identity->name);
				$identity->group_id=0;
				unset($identity->user_group_id);
				unset($identity->customer_id);
				unset($identity->default_language_id);
				unset($identity->active_language_id);
				$storage = $authAdapter->getStorage ();
				$storage->write ( $identity );
				$this->_redirect ( '/' );
		} else {
			$identity = $authAdapter->getIdentity ();
			$authAdapter->clearIdentity ();
			$this->_redirect ( '/' );
		}
	}
}

