<?php
class Default_Model_DbTable_UserGroupModule extends Zend_Db_Table_Abstract {
	protected $_name = 'user_group_module';
	protected $_referenceMap = array (
			'UserGroup' => array (
					'columns' => array (
							'user_group_id' 
					),
					'refTableClass' => 'Default_Model_DbTable_UserGroupModule',
					'refColumns' => array (
							'module_id' 
					) 
			),
			'UserGroupModule' => array(
					'columns' => array (
							'module_id'
					),
					'refTableClass' => 'Admin_Model_DbTable_CustomerModule',
					'refColumns' => array (
							'module_id'
					)
			)
	);
	
	
}