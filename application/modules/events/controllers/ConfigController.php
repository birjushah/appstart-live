<?php
class Events_ConfigController extends Zend_Controller_Action {
	public function init() {
		/* Initialize action controller here */
	}
	public function installAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ( true );
		
		$db = Standard_Functions::getDefaultDbAdapter ();
		try {
			
			// Create Table Contact If Not Exsist
			$sql = "CREATE TABLE IF NOT EXISTS `contact` (
						`contact_id` int(11) NOT NULL AUTO_INCREMENT,
	  					`customer_id` int(11) DEFAULT NULL,
	  					`status` tinyint(4) DEFAULT NULL,
						`order` int(11) DEFAULT NULL,
						`last_updated_by` int(11) DEFAULT NULL,
						`last_updated_at` datetime DEFAULT NULL,
						`created_by` int(11) DEFAULT NULL,
						`created_at` datetime DEFAULT NULL,
						PRIMARY KEY (`contact_id`),
						KEY `fk_customer` (`customer_id`),
						KEY `fk_contact_update` (`last_updated_by`),
						KEY `fk_contact_created` (`created_by`),
						CONSTRAINT `fk_contact_created` FOREIGN KEY (`created_by`) REFERENCES `user` (`user_id`),
						CONSTRAINT `fk_contact_update` FOREIGN KEY (`last_updated_by`) REFERENCES `user` (`user_id`),
						CONSTRAINT `fk_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			
			$db->query ( $sql );
			
			// Create Table Contact If Not Exsist
			$sql = "CREATE TABLE IF NOT EXISTS `contact_detail` (
					  `contact_detail_id` int(11) NOT NULL AUTO_INCREMENT,
					  `contact_id` int(11) DEFAULT NULL,
					  `language_id` int(11) DEFAULT NULL,
					  `location` varchar(60) DEFAULT NULL,
					  `address` varchar(180) DEFAULT NULL,
					  `phone_1` varchar(20) DEFAULT NULL,
					  `phone_2` varchar(20) DEFAULT NULL,
					  `phone_3` varchar(20) DEFAULT NULL,
					  `fax` varchar(20) DEFAULT NULL,
					  `latitude` varchar(20) DEFAULT NULL,
					  `longitude` varchar(20) DEFAULT NULL,
					  `email_1` varchar(60) DEFAULT NULL,
					  `email_2` varchar(60) DEFAULT NULL,
					  `email_3` varchar(60) DEFAULT NULL,
					  `website` varchar(180) DEFAULT NULL,
					  `timings` varchar(50) DEFAULT NULL,
					  `logo` varchar(256) DEFAULT NULL,
					  `last_updated_by` int(11) DEFAULT NULL,
					  `last_updated_at` datetime DEFAULT NULL,
					  `created_by` int(11) DEFAULT NULL,
					  `created_at` datetime DEFAULT NULL,
					  PRIMARY KEY (`contact_detail_id`),
					  KEY `fk_contact` (`contact_id`),
					  KEY `fk_cd_language` (`language_id`),
					  KEY `fk_cd_updated` (`last_updated_by`),
					  KEY `fk_cd_created` (`created_by`),
					  CONSTRAINT `fk_cd_created` FOREIGN KEY (`created_by`) REFERENCES `user` (`user_id`),
					  CONSTRAINT `fk_cd_language` FOREIGN KEY (`language_id`) REFERENCES `language` (`language_id`),
					  CONSTRAINT `fk_cd_updated` FOREIGN KEY (`last_updated_by`) REFERENCES `user` (`user_id`),
					  CONSTRAINT `fk_contact` FOREIGN KEY (`contact_id`) REFERENCES `contact` (`contact_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
				
			$db->query ( $sql );
			
			// Create Resource Dir
			mkdir ( Standard_Functions::getResourcePath () . "contact", 0755 );
			mkdir ( Standard_Functions::getResourcePath () . "contact/images", 0755 );
			echo "Success";
		} catch ( Exception $ex ) {
			echo $ex->getMessage ();
		}
	}
	public function indexAction() {
		// action body
	}
}

