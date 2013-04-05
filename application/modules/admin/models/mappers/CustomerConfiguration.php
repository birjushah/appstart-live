<?php
class Admin_Model_Mapper_CustomerConfiguration extends Standard_ModelMapper {
	protected $_dbTableClass = "Admin_Model_DbTable_CustomerConfiguration";
	public static $ADD_MODE = "add";
	public static $EDIT_MODE = "edit";
	
	/**
	 * Save Customer Configuration
	 * 
	 * @param array $options
	 * @param string $mode
	 * @throws Zend_Exception
	 * @return multitype:
	 */
	public function saveCustomerConfiguration(array $options = array(), $mode = null) {
		// Define the save mode of customer
		$mode = $mode == null ? self::$ADD_MODE : $mode;
		$returnData = array ();
		if (empty ( $options ))
			throw new Zend_Exception ( "Invalid Options provided to save customer. Please provide options for Customer and User tables" );
		
		$currentDateTime = Standard_Functions::getCurrentDateTime();
		$currentUser = Standard_Functions::getCurrentUser()->system_user_id;
		$options['last_updated_at'] = $currentDateTime;
		$options['last_updated_by'] = $currentUser;
		if($mode == self::$ADD_MODE){
			$options['created_at'] = $currentDateTime;
			$options['created_by'] = $currentUser;
		}
		
		$customerConfiguration = new Admin_Model_CustomerConfiguration ();
		$customerConfiguration->setOptions ( $options );
		$customerConfiguration = $customerConfiguration->save ();
		$returnData['customer_configuration'] = $customerConfiguration->toArray(); 
		return $returnData;
	}
	
	public function getConfigurationByCustomerId($customer_id){
		$configurationData = $this->fetchAll(" customer_id = ".$customer_id);
		if(!$configurationData)
			return array();
		else
			$configurationData = $configurationData[0];
		return $configurationData->toArray();	
	}
}