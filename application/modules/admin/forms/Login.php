<?php
class Admin_Form_Login extends Standard_Form
{
	public function init(){
		$this->setMethod('post');
		$notEmpty = new Zend_Validate_NotEmpty();
		$notEmpty->setMessage('Enter A Password.');
		/*$this->addElement('text', 'email', array(
			'label'      => 'User Email:',
			'size'		 => '35',
            'required'   => true,
            'filters'    => array('StringTrim'),
            'validators' => array(
                array($notEmpty,true),
            	array('EmailAddress',true),
            ),
		'errorMessages' => array('Invalid Email Address')
				
        ));*/
		$email = new Standard_Html5_Form_Element_Text_Email("email");
		$email->setLabel("User Email:");
		$email->setAttrib("size", 35);
		$email->setAttrib("required", "required");
		$email->setFilters(array('StringTrim'));
		$email->setValidators(array(
                array($notEmpty,true),
            	array('EmailAddress',true),
            ));
		$email->setErrorMessages(array('Invalid Email Address'));
		$this->addElement($email);
		
		$this->addElement('password', 'password', array(
            'label'      => 'Password:',
			'size'		 => '35',
			'required'   => true,
			'filters'    => array('StringTrim'),
            'validators' => array(
                array($notEmpty,true),
            )
        ));
		
		$this->addElement('checkbox', 'remember', array(        
			'label'      => 'Keep me logged in',
			'value'      => 'checked'
        ));
		
		// Add the submit button
        $this->addElement('submit', 'submit', array(
            'ignore'   => true,
        	'class'	   => "button"
        ));	
		
        $this->getElement ( "email" )->setAttrib ( "required", "required" );
        $this->getElement ( "password" )->setAttrib ( "required", "required" );
    }
}

