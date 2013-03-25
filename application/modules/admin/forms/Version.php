<?php
class Admin_Form_Version extends Standard_Form{
	public function init(){
		$this->setMethod('post');
		$notEmpty = new Zend_Validate_NotEmpty();
		$notEmpty->setMessage('Enter A Password.');
		$this->addElement('hidden', 'version_id', array(
				'value'		 => '',
				'filters'    => array('StringTrim')
		));
		$this->addElement('hidden', 'version_detail_id', array(
				'value'		 => '',
				'filters'    => array('StringTrim')
		));
		$title = $this->createElement('text','title',array(
			'label' => 'Title',
			'required' => true,
			'validators' => array(
						array(
								$notEmpty,
								true 
						) 
			),
			'errorMessages' => array (
						'Invalid Version Name' 
			), 
			'filters' => array(
				'stringTrim')
		));
		$title->setAttrib("required", "required");
		$this->addElement($title);

		$version_number = $this->createElement('text','version_number',array(
			'label' => 'Version Number',
			'validators' => array(
					array(
						$notEmpty,
						true
					)
			),
			'filters' => array(
				'stringTrim')
		));
		$version_number->setAttrib("required","required");
		$this->addElement($version_number);

		$this->addElement('textarea','description',array(
			'label' => 'Description',
			'filters' => array(
				'stringTrim')
		));

		$categoryList = array(
			'Web App' => 'Web App',
			'iphone App' => 'iphone App',
			'ipad App' => 'ipad App',
			'Android App' => 'Android App'
		);

		$category = $this->createElement('select','category',array(
			'label' => 'Description',
			'id' => 'tagCategory',
			'multiple' => 'multiple',
			'MultiOptions' => $categoryList
		));
		$category->setIsArray(true);
		$this->addElement($category);

		//Created At
		$created_at = $this->createElement ( "text", "created_at", array (
				'label' => 'Created At:',
				'size' => '30',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $created_at);

		//Active
		$this->addElement('checkbox','status',array(
				'label' => 'Active',
				'value' => '1'
		));
		
		// Add the submit button
		$this->addElement('submit', 'submit', array(
				'ignore'   => true
		));
		// Add the reset button
		$this->addElement('reset', 'reset', array(
				'ignore'   => true
		));
	}
}