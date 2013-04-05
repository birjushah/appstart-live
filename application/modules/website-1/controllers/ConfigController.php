<?php
class Website1_ConfigController extends Zend_Controller_Action {
	public function init() {
		/* Initialize action controller here */
	}
	public function installAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ( true );
		
		$db = Standard_Functions::getDefaultDbAdapter ();
		try {
			
			// Create Table Website-1 If Not Exsist
			$sql = "CREATE TABLE IF NOT EXISTS `module_website_1` (
				  `module_website_1_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `customer_id` int(11) DEFAULT NULL,
				  `status` tinyint(4) DEFAULT NULL,
				  `order` int(11) DEFAULT NULL,
				  `last_updated_by` int(11) DEFAULT NULL,
				  `last_updated_at` datetime DEFAULT NULL,
				  `created_by` int(11) DEFAULT NULL,
				  `created_at` datetime DEFAULT NULL,
				  PRIMARY KEY (`module_website_1_id`),
				  KEY `fk_mw_customer` (`customer_id`),
				  KEY `last_updated_by` (`last_updated_by`),
				  KEY `created_by` (`created_by`)
				) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			
			$db->query ( $sql );
			
			// Create Table Website-1 detail If Not Exsist
			$sql = "CREATE TABLE IF NOT EXISTS `module_website_detail_1` (
				  `module_website_detail_1_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `module_website_1_id` int(11) unsigned DEFAULT NULL,
				  `language_id` int(11) DEFAULT NULL,
				  `title` varchar(45) DEFAULT NULL,
				  `url` varchar(65) DEFAULT NULL,
				  `description` varchar(255) DEFAULT NULL,
				  `last_updated_by` int(11) DEFAULT NULL,
				  `last_updated_at` datetime DEFAULT NULL,
				  `created_by` int(11) DEFAULT NULL,
				  `created_at` datetime DEFAULT NULL,
				  PRIMARY KEY (`module_website_detail_1_id`),
				  KEY `module_website_1_id` (`module_website_1_id`),
				  KEY `language_id` (`language_id`),
				  KEY `last_updated_by` (`last_updated_by`),
				  KEY `created_by` (`created_by`)
				) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
			
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

