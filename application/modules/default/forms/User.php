<?php
class Default_Form_User extends Standard_Form {
	public static $IS_ADMIN_ADD = false;
	public function init() {
		$this->setMethod ( 'POST' );
		
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
		
		// User ID
		$user_id = $this->createElement ( "hidden", "user_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement($user_id);
		// Username
		// Check with front_controller
		$request = Zend_Controller_Front::getInstance()->getRequest();
		$uniqueUsernameValidator = new Zend_Validate_Db_NoRecordExists ( array (
				'table' => 'user',
				'field' => 'username',
				'exclude' => array (
						'field' => 'user_id',
						'value' => $request->getParam ( "user_id", null ) 
				) 
		) );
		$uniqueUsernameValidator->setMessage ( "Username already exits" );
		$username = $this->createElement ( "text", "username", array (
				'label' => 'Username:',
				'size' => '50',
				'required' => true,
				'filters' => array (
						'StringTrim' 
				),
				'validators' => array (
						array (
								$notEmptyValidator,
								true 
						),
						array (
								$uniqueUsernameValidator,
								true 
						) 
				) 
		) );
		$username->setAttrib ( "required", "required" );
		$this->addElement ( $username );
		
		// Password
		$password = $this->createElement ( "password", "password", array (
				'label' => 'Password:',
				'size' => '50',
				'validators' => array (
						array (
								$notEmptyValidator,
								true 
						) 
				),
				'errorMessages' => array (
						'Invalid Password' 
				) 
		) );
		$password->setRequired(true);
		$password->setAttrib ( "required", "required" );
		$this->addElement ( $password );
		
		// Name
		$name = $this->createElement ( "text", "name", array (
				'label' => 'Name:',
				'size' => '35',
				'required' => true,
				'filters' => array (
						'StringTrim' 
				),
				'validators' => array (
						array (
								$notEmptyValidator,
								true 
						) 
				),
				'errorMessages' => array (
						'Invalid Customer Name' 
				) 
		) );
		$name->setAttrib("required", "required");
		$this->addElement ( $name );
		
		// Phone
		$phone = $this->createElement ( "text", "phone", array (
				'label' => 'Phone:',
				'size' => '20',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $phone );
		
		// Email
		/*$email = $this->createElement ( "text", "email", array (
				'label' => 'Email:',
				'size' => '60',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $email );*/
		
		$email = new Standard_Html5_Form_Element_Text_Email("email");
		$email->setLabel("User Email:");
		$email->setAttrib("size", 35);
		$email->setFilters(array('StringTrim'));
		$email->setValidators(array(array('EmailAddress',true)));
		$email->setErrorMessages(array('Invalid Email Address'));
		$this->addElement($email);
		
		// Status
		$status = $this->createElement ( 'select', 'status', array (
				'label' => 'Status:',
				'MultiOptions' => array (
						'1' => 'Active',
						'2' => 'InActive' 
				),
				'validators' => array (
						'NotEmpty' 
				),
				'Required' => true 
		) );
		$this->addElement ( $status );
		
		// User Group ID
		$user_group_id = $this->createElement ( 'select', 'user_group_id', array (
				'label' => 'User Group:',
				'MultiOptions' => $this->_getUserGroups (),
				'validators' => array (
						'NotEmpty' 
				),
				'required' => true 
		) );
		$user_group_id->setAttrib("required", "requried");
		$this->addElement($user_group_id);
		
		// Submit Button
		$submit = $this->createElement ( 'submit', 'submit', array (
				'ignore' => true 
		) );
		$this->addElement ( $submit );
		
		// Reset Button
		$reset = $this->createElement ( 'reset', 'reset', array (
				'ignore' => true 
		) );
		$this->addElement ( $reset );
	}
	public function _getUserGroups() {
		$options = array (
				"" => 'Select User Groups' 
		);
		if(!self::$IS_ADMIN_ADD){
			$where = "created_by = " . Standard_Functions::getCurrentUser()->user_id . " AND name <> 'Administrator'";
		} else {
			$where = null;
		}
		$mapper = new Default_Model_Mapper_UserGroup ();
		$models = $mapper->fetchAll ($where);
		if ($models) {
			foreach ( $models as $userGroup ) {
				$options [$userGroup->getUserGroupId ()] = $userGroup->getName ();
			}
		}
		return $options;
	}
}