<?php
class Default_Form_Settings extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		
		$notEmpty = new Zend_Validate_NotEmpty();
		$notEmpty->setMessage('Enter A Password.');
		$this->addElement ( 'hidden', 'user_id', array (
				'filters' => array (
						'StringTrim' 
				) 
		) );
		
		$this->addElement ( 'text', 'name', array (
				'label' => 'Name:',
				'size' => '30',
				'required' => true,
				'filters' => array (
						'StringTrim' 
				),
				'errorMessages' => array (
						'Invalid User Name' 
				) 
		)
		 );
		/*$this->addElement ( 'text', 'email', array (
				'label' => 'User Email:',
				'size' => '35',
				'required' => true,
				'filters' => array (
						'StringTrim' 
				),
				'errorMessages' => array (
						'Invalid Email Address' 
				) 
		)
		 );*/
		$email = new Standard_Html5_Form_Element_Text_Email("email");
		$email->setLabel("User Email:");
		$email->setAttrib("size", 35);
		$email->setAttrib("required", "required");
		$email->setFilters(array('StringTrim'));
		$email->setValidators(array(array('EmailAddress',true)));
		$email->setErrorMessages(array('Invalid Email Address'));
		$this->addElement($email);
		
		$this->getElement("password");
		$confValidator = new Zend_Validate_Identical('password');
		$confValidator->setMessage("Confirm password do not match");
		
		$this->addElement('password', 'password', array(
				'label'      => 'Password:',
				'size'		 => '35',
				'required'   => true,
				'filters'    => array('StringTrim'),
				'validators' => array(
						array($notEmpty,true),
						array('StringLength',true,
								array('min' => 6,
										'max' => 50,
										'messages' => array(
												Zend_Validate_StringLength::INVALID =>
												'Invalid Password',
												Zend_Validate_StringLength::TOO_LONG =>
												'Password too long',
												Zend_Validate_StringLength::TOO_SHORT =>
												'Password must be of minimum 6 character'))),
				)
		));
		$this->getElement("password")->setAttrib("required", "required");	
		$confValidator = new Zend_Validate_Identical('password');
		$confValidator->setMessage("Confirm password do not match");
		
		$this->addElement('password', 'confirm_password', array(
				'label'      => 'Confirm Password:',
				'size'		 => '35',
				'required'   => true,
				'filters'    => array('StringTrim'),
				'validators' => array(
						array($notEmpty,true),array($confValidator,true)
				)
		));
		
		$this->getElement("confirm_password")->setAttrib("required", "required");
		
		$this->addElement('submit', 'submit', array(
				'ignore'   => true
		));
		
		$this->addElement('reset', 'reset', array(
				'ignore'   => true
		));

	}
	/*
	 * public function defaults($values) { $records = array(); foreach ($values
	 * as $val) { $records[] = $val; } }
	 */
}
?>