<?php
class Admin_Model_DbTable_BusinessType extends Zend_Db_Table_Abstract {
	protected $_name = 'business_type';
	protected $_dependentTables = array (
			'Admin_Model_DbTable_Customer' 
	);
}