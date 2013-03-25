<?php
class SocialMedia_Form_SocialMedia extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
		
		// Module Events ID
		$module_events_id = $this->createElement ( "hidden", "module_social_media_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $module_events_id);
		
		// Module Events Detail ID
		$module_events_detail_id = $this->createElement ( "hidden", "module_social_media_detail_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $module_events_detail_id);
		
		// Language ID
		$language_id = $this->createElement ( "hidden", "language_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $language_id );
		
		// Social Media Type ID
		$social_media_type_id = $this->createElement ( 'select', 'social_media_type_id', array (
				'label' => 'Type:',
				'MultiOptions' => $this->_getSocialMediaType (),
				'validators' => array (
						'NotEmpty'
				),
				'Required' => true
		) );
		$social_media_type_id->setAttrib ( "required", "required" );
		$this->addElement ( $social_media_type_id );
		
		// Title
		$title = $this->createElement ( "text", "title", array (
				'label' => 'Title:',
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
						'Invalid Title' 
				) 
		) );
		$title->setAttrib("required", "required");
		$this->addElement ( $title);
		
		// URL
		$url = $this->createElement ( "text", "url", array (
				'label' => 'URL:',
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
						'Invalid Title'
				)
		) );
		$url->setAttrib("required", "required");
		$this->addElement ( $url);
		
		// image
		
		$image = $this->createElement('file','icon');
		$image->setLabel('Icon:')
			 ->setDestination(Standard_Functions::getResourcePath(). "social-media/images")
			 ->addValidator('Size', false, 102400)
			 ->addValidator('Extension', false, 'jpg,png,gif');
		$this->addElement($image);
		
		$this->addElement('checkbox', 'status', array(
				'label'      => 'Active',
				'value'      => '1'
		));
		
		// Submit button
		$submit = $this->addElement ( 'submit', 'submit', array (
				'ignore' => true,
				'class' => "button" 
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
	
	protected function _getSocialMediaType() {
		$options = array();
		$mapper = new SocialMedia_Model_Mapper_SocialMediaType();
		$model = $mapper->fetchAll();
		
		if($model) {
			foreach($model as $type) {
				$options [$type->getSocialMediaTypeId ()] = $type->getTitle();
			}
		}
		
		return $options;
	}
}