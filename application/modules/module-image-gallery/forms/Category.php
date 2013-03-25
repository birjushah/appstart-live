<?php 
class ModuleImageGallery_Form_Category extends Standard_Form{
	public function init(){
		$this->setMethod('POST');
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
	
		// Image Title
		$title = $this->createElement ( "text", "title", array (
				'label' => 'Category Title:',
				'size' => '90',
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
						'Invalid Category Title'
				)
		) );
		$title->setAttrib("required", "required");
		$this->addElement ($title);
		
		$this->addElement('checkbox', 'status', array(
				'label'      => 'Active',
				'value'      => '1'
		));
		
		// Submit button
		$submit = $this->addElement ( 'submit', 'submit', array (
				'ignore' => true,
				'class' => "button"
		) );
		
		//Image Gallery Category Id
		$image_gallery_category_id = $this->createElement ( "hidden", "module_image_gallery_category_id", array (
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $image_gallery_category_id );
		
		//Image Gallery Category Detail Id
		$image_gallery_category_detail_id = $this->createElement ( "hidden", "module_image_gallery_category_detail_id", array (
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $image_gallery_category_detail_id );
		
		// Language ID
		$language_id = $this->createElement ( "hidden", "language_id", array (
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement($language_id);
		
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