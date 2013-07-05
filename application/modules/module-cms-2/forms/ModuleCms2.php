<?php 
class ModuleCms2_Form_ModuleCms2 extends Standard_Form{
	public function init(){
		$this->setMethod('POST');
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );

		//  Cms ID
		$module_cms_id = $this->createElement ( "hidden", "module_cms_2_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $module_cms_id);
		
		//  Cms Detail Id
		$module_cms_detail_id = $this->createElement ( "hidden", "module_cms_detail_2_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $module_cms_detail_id);
		
		// Language ID
		$language_id = $this->createElement ( "hidden", "language_id", array (
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $language_id );
		
		//Parent ID
		$parent_id = $this->createElement ( "hidden", "parent_id", array (
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $parent_id );
		
		// Cms Title
		$title = $this->createElement ( "text", "title", array (
				'label' => 'Cms Title:',
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
						'Invalid Cms Title'
				)
		) );
		$title->setAttrib("required", "required");
		$this->addElement ($title);
		
		//Cms Thumb 
		$thumb = $this->createElement('file','thumb');
		$thumb->setLabel(false)
			 ->setDestination(Standard_Functions::getResourcePath(). "module-cms-2/images/")
			 ->addValidator('Size', false, 10485760)
			 ->addValidator('Extension', false, 'jpeg,jpg,png,gif');
		$this->addElement($thumb);
		
		//Cms Content
		$content = $this->createElement("textarea","content",array(
				'label' => 'Cms Content:',
				'id' => 'content_textarea',
				'size' => '255',
				'class'=>"mceEditor",
				'filters' => array(
						'StringTrim'
				),
				'errorMessages' => array(
						'Invalid Cms content Description'
				)
		));
		$this->addElement($content);
		
		// Label 1
		$label_1 = $this->createElement ( "text", "label_1", array (
		        'label' => 'Label 1 (Text):',
		        'size' => '90',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ($label_1);
		
		// Label 1 Color
		$label_1_color = $this->createElement ( "text", "label_1_color", array (
		        'label' => 'Label 1 (Color):',
		        'size' => '15',
		        'maxlength' => '6',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ( $label_1_color );
		
		// LAbel 2
		$label_2 = $this->createElement ( "text", "label_2", array (
		        'label' => 'Label 2:',
		        'size' => '90',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ($label_2);

		$this->addElement('checkbox', 'status', array(
				'label'      => 'Active',
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