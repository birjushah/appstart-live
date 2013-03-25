<?php
class PushMessage_ConfigController extends Zend_Controller_Action {
	public function init() {
		/* Initialize action controller here */
	}
	public function installAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ( true );
		
		$db = Standard_Functions::getDefaultDbAdapter ();
		try {
			
			// Create Table Module Cms If Not Exsist
			$sql = "CREATE TABLE IF NOT EXISTS `module_cms` (
					  `module_cms_id` int(11) NOT NULL AUTO_INCREMENT,
					  `customer_id` int(11) DEFAULT NULL,
					  `parent_id` int(11) DEFAULT NULL,
					  `status` tinyint(4) DEFAULT NULL,
					  `order` int(11) DEFAULT NULL,
					  `last_updated_by` int(11) DEFAULT NULL,
					  `last_updated_at` datetime DEFAULT NULL,
					  `created_by` int(11) DEFAULT NULL,
					  `created_at` datetime DEFAULT NULL,
					  PRIMARY KEY (`module_cms_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
			
			$db->query ( $sql );
			
			// Create Table Module Cms detail If Not Exsist
			$sql = "CREATE TABLE IF NOT EXISTS `module_cms_detail` (
					  `module_cms_detail_id` int(11) NOT NULL AUTO_INCREMENT,
					  `module_cms_id` int(11) DEFAULT NULL,
					  `language_id` int(11) DEFAULT NULL,
					  `title` varchar(90) DEFAULT NULL,
					  `thumb` varchar(128) DEFAULT NULL,
					  `content` text,
					  PRIMARY KEY (`module_cms_detail_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
			
			$db->query ( $sql );
			
			// Create Resource Dir
			echo "Success";
		} catch ( Exception $ex ) {
			echo $ex->getMessage ();
		}
	}
	public function indexAction() {
		// action body
	}
}

