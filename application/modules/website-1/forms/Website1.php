<?php
class Website1_Form_Website1 extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
		
		// Website Id
		$module_website_id = $this->createElement ( "hidden", "module_website_1_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $module_website_id );
		
		// Website Detail Id
		$module_website_detail_id = $this->createElement ( "hidden", "module_website_detail_1_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $module_website_detail_id );
		
		// Language ID
		$language_id = $this->createElement ( "hidden", "language_id", array (
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $language_id );
		
		// Website Title
		$title = $this->createElement ( "text", "title", array (
				'label' => 'Image Title:',
				'size' => '90',
				'required' => true,
				'filters' => array (
						'StringTrim' 
				),
				'errorMessages' => array (
						'Invalid Image Title' 
				) 
		) );
		$title->setAttrib ( "required", "required" );
		$this->addElement ( $title );
		
		// Website Url
		$url = $this->createElement ( "text", "url" );
		$url->setOptions ( array (
				'label' => 'Url',
				'filters' => array (
						'StringTrim',
						'StripTags' 
				),
				'required' => true,
				'errorMessages' => array (
						'Invalid Website Url' 
				) 
		) );
		$url->addValidator('regex', true, 
                       array(
                           'pattern'=>'_^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$_iuS', 
                           'messages'=>array(
                               'regexNotMatch'=>'Your own custom error message'
                           )
                       ));
		$url->setAttrib("required", "required");
		$this->addElement ( $url );
		// Website Description
		$description = $this->createElement ( "textarea", "description", array (
				'label' => 'Image Description:',
				'size' => '255',
				'class' => "mceEditor",
				'filters' => array (
						'StringTrim' 
				),
				'errorMessages' => array (
						'Invalid Cms content Description' 
				) 
		) );
		$this->addElement ( $description );
		
		//Website Thumb 
		$thumb = $this->createElement('file','website_logo');
		$thumb->setLabel(false)
			 ->setDestination(Standard_Functions::getResourcePath(). "website/logos/")
			 ->addValidator('Size', false, 10485760)
			 ->addValidator('Extension', false, 'jpeg,jpg,png,gif');
		$this->addElement($thumb);

		$this->addElement ( 'checkbox', 'status', array (
				'label' => 'Active',
				'value' => '1' 
		) );
		
		// Submit button
		$submit = $this->addElement ( 'submit', 'submit', array (
				'ignore' => true,
				'class' => "button" 
		) );
		
		// Reset button
		$reset = $this->addElement ( 'reset', 'reset', array (
				'ignore' => true,
				'class' => "button" 
		) );
		$this->addElements ( array (
				$submit,
				$reset 
		) );
	}
}