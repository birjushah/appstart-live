<?php
class Admin_Model_DbTable_TemplateModule extends Zend_Db_Table_Abstract {
	protected $_name = 'template_module';
	protected $_referenceMap = array (
			'Module' => array (
					'columns' => array (
							'module_id'
					),
					'refTableClass' => 'Admin_Model_DbTable_Module',
					'refColumns' => array (
							'module_id'
					),
			),
			'Template' => array (
					'columns' => array (
							'template_id'
					),
					'refTableClass' => 'Admin_Model_DbTable_Template',
					'refColums' => array (
							'template_id'
					)
			)
	);

}