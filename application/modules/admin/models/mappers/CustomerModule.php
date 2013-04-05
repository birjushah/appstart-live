<?php
class Admin_Model_Mapper_CustomerModule extends Standard_ModelMapper
{
	protected $_dbTableClass = "Admin_Model_DbTable_CustomerModule";
	
	public function saveCustomerModule($template_id,$ignore_modules)
	{
		$ignore_modules = explode(",",$ignore_modules);
		$customerMapper = new Admin_Model_Mapper_Customer();
		$templateCustomer = $customerMapper->fetchAll("template_id=".$template_id);
		//first inactive all existing modules of customers having this template id
		if(is_array($templateCustomer)){
			foreach($templateCustomer as $customer){
				$customerModuleQuote = $this->getDbTable ()->getAdapter ()->quoteInto ( "customer_id = ?", $customer->getCustomerId() );
				//set status of all related modules to 0 and order to null
				$this->getDbTable ()->update ( array (
						"status" => 0,
						"order_number" => ""
				), $customerModuleQuote );
				$customerModuleOrder = 1;
				foreach($ignore_modules as $ignore_module){
					$moduleExists = $this->getDbTable()->fetchRow("module_id ='".$ignore_module."'AND customer_id =".$customer->getCustomerId());
					if(!$moduleExists){
						//place a new entry if module is new
						$module = new Admin_Model_DbTable_Module();
						$row = $module->find($ignore_module)->toArray();
						$customerModuleOptions = array (
								'customer_module_id' => "",
								'customer_id' => $customer->getCustomerId (),
								'module_id' => $row[0]['module_id'],
								'order_number' => $customerModuleOrder,
								'visibility' => 0,
								'status' => 1,
						);
						$model = new $this->_modelClass();
						$model->setOptions ( $customerModuleOptions );
						$model->save();
					}else{
						//set status of selected modules to 1 and auto increment the order
						$moduleExistsQuote = $this->getDbTable ()->getAdapter ()->quoteInto ( "AND module_id = ?", $moduleExists['module_id'] );
						$this->getDbTable ()->update ( array (
								"status" => 1,
								"order_number" => $customerModuleOrder
						), $customerModuleQuote.$moduleExistsQuote );
					}
					$customerModuleOrder ++;
				}		
			}
		}
	}
	
	public function getNextOrder($customer_id) {
		$select = $this->getDbTable()->select(false)
		->from("customer_module",array("max_order" => "max(`order`)"))
		->group("customer_id")
		->having("customer_id=".$customer_id);
	
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}