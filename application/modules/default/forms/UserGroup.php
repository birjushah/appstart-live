<?php
class Default_Form_UserGroup extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		
		$this->addElement ( 'hidden', 'user_group_id', array (
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( 'hidden', 'customer_id', array (
				'filters' => array (
						'StringTrim'
				)
		) );
		
		$this->addElement ( 'text', 'name', array (
				'label' => 'Group Name:',
				'size' => '30',
				'required' => true,
				'filters' => array (
						'StringTrim' 
				),
				'errorMessages' => array (
						'Invalid User Name' 
				) 
		) );
		$this->getElement("name")->setAttrib("required", "required");
		
		$this->addElement ( 'multiselect', 'modules', array (
				'label' => 'Modules:',
				'MultiOptions' => $this->_getModules (),
				'validators' => array (
						'NotEmpty' 
				),
				'Required' => true 
		) );
		
		//$this->getElement("modules")->setAttrib("required", "required");
		
		$this->addElement('checkbox','settings', array(
			'label' => 'Settings:',
			'value' => 1
		));


		$this->addElement ( 'submit', 'submit', array (
				'ignore' => true,
		) );
		// Add the reset button
		$this->addElement('reset', 'reset', array(
				'ignore'   => true
		));
	}
	public function _getModules() {
		$user_group_id = Standard_Functions::getCurrentUser ()->user_group_id;
		$moduleMapper = new Admin_Model_Mapper_Module();
		$select = $moduleMapper->getDbTable()->select('unique')
					->setIntegrityCheck(false)
					->from(array("m"=>"module"),array("m.module_id","m.name"))
					->join(array("ugm"=>"user_group_module"), "m.module_id = ugm.module_id AND ugm.user_group_id = '".$user_group_id."' AND ugm.status = 1");
		$modules = $moduleMapper->fetchAll($select);
		$options = array();
		foreach ($modules as $module)
		{
			$options[$module->getModuleId()] = $module->getName();
		}
		return $options;
	}
}	