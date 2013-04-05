<?php
class Admin_Model_DbTable_Module extends Zend_Db_Table_Abstract {
	protected $_name = 'module';
	protected $_dependentTables = array (
			'Admin_Model_DbTable_TemplateModule',
			'Default_Model_DbTable_UserGroupModule',
			'Admin_Model_DbTable_CustomerModule'
	);
}