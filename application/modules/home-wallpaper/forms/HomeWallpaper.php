<?php
class HomeWallpaper_Form_HomeWallpaper extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
		
		// home_wallpaper ID
		$home_wallpaper_id = $this->createElement ( "hidden", "home_wallpaper_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $home_wallpaper_id);
				
		// Home wallpaper Detail ID
		$home_wallpaper_detail_id = $this->createElement ( "hidden", "home_wallpaper_detail_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $home_wallpaper_detail_id);
		
		// Language ID
		$language_id = $this->createElement ( "hidden", "language_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $language_id );
		
		
		// Iamge Title
		$image_title = $this->createElement ( "text", "image_title", array (
				'label' => 'Image Title:',
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
						'Invalid Image Title' 
				) 
		) );
		$image_title->setAttrib("required", "required");
		$this->addElement ( $image_title);

		// link_to_module
		$link_to_module = $this->createElement ( "text", "link_to_module", array (
				'label' => 'Link to module:',
				'size' => '90',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $link_to_module );
		
		$this->addElement('checkbox', 'status', array(
				'label'      => 'Active',
				'value'      => '1'
		));
		
		//Set as Default button
		$this->addElement('checkbox', 'default', array(
				'label'      => 'Use as home screen',
				'value'      => '1'
		));
		
		// Submit button
		$submit = $this->addElement ( 'submit', 'submit', array (
				'ignore' => true,
				'class' => "button" 
		) );
		
		// Submit For ALL button
		$allpyall = $this->addElement ( 'button', 'applyall', array (
		        'ignore' => true,
		        'class' => "button",
		        'label' => 'submit to all'
		) );
		
		// REset button
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