<?php

class Admin_Form_Template extends Standard_Form
{
	public function init(){
		$this->setMethod('post');
		$this->addElement('hidden', 'template_id', array(
				'value'		 => '',
				'filters'    => array('StringTrim')
		));
		
		$notEmpty = new Zend_Validate_NotEmpty();
		$notEmpty->setMessage('Enter A Password.');
		$this->addElement('text', 'name', array(
			'label'      => 'Name:',
			'size'		 => '35',
            'required'   => true,
            'filters'    => array('StringTrim'),
            'validators' => array(
                array($notEmpty,true),
            ),
		'errorMessages' => array('Invalid Template Name')
				
        ));
		
		$this->addElement('select','business_type_id',array(
				'label'		 => 'Business Type:',
				'MultiOptions' => $this->_getBusinessType(),
				'validators'	=>	array(
						'NotEmpty'
				),
				'Required'	=>	true
		));
		
		$this->addElement('multiselect','modules',array(
				'label'		 => 'Modules:',
				'MultiOptions' => $this->_getModules(),
				'validators'	=>	array(
						'NotEmpty'
				),
				'Required'	=>	true
		));
		
		$this->addElement('checkbox', 'status', array(
				'label'      => 'Active',
				'value'      => '1'
		));
		
		//Add Apply to all checkbox
		$this->addElement('checkbox','applytoall',array(
				'label' => 'Apply To All',
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
    
    public function _getBusinessType()
    {
    	$options = array("" => 'Select business type');
    	
    	$mapper = new Admin_Model_Mapper_BusinessType();
    	$models = $mapper->fetchAll();
    	if($models) {
	    	foreach($models as $businessType) {
	    		$options[$businessType->getBusinessTypeId()] = $businessType->getName();
	    	}
    	}
    	return $options;
    }
    
    public function _getModules()
    {
    	$options = array();
    	 
    	$mapper = new Admin_Model_Mapper_Module();
    	$models = $mapper->fetchAll();
    	if($models) {
	    	foreach($models as $module) {
	    		$options[$module->getModuleId()] = $module->getName();
	    	}
    	}
    	return $options;
    }
}