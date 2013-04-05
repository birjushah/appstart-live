<?php
class Admin_Model_DbTable_Template extends Zend_Db_Table_Abstract {
	protected $_name = 'template';
	protected $_dependentTables = array (
			'Admin_Model_DbTable_TemplateModule',
			'Admin_Model_DbTable_Customer',
	);
}