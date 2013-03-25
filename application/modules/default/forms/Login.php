<?php
class Default_Form_Login extends Zend_Form
{
	public function init(){
		$this->setMethod('post');
		
		$notEmpty = new Zend_Validate_NotEmpty();
		$notEmpty->setMessage('Enter A Password.');
		$this->addElement('text', 'username', array(
			'label'      => 'User Name:',
			'size'		 => '35',
            'required'   => true,
            'filters'    => array('StringTrim'),
            'validators' => array(
                array($notEmpty,true),
            	//array('EmailAddress',true),
            ),
		'errorMessages' => array('Invalid User Name')
				
        ));
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
		
        $this->getElement ( "username" )->setAttrib ( "required", "required" );
        $this->getElement ( "password" )->setAttrib ( "required", "required" );
    }
}

