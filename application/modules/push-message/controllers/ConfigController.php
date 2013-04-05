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
			
			// Create Table Push Message If Not Exsist
			$sql = "CREATE TABLE IF NOT EXISTS `push_message` (
					  `push_message_id` int(11) NOT NULL AUTO_INCREMENT,
					  `customer_id` int(11) DEFAULT NULL,
					  `status` tinyint(4) DEFAULT NULL,
					  `order` int(11) DEFAULT NULL,
					  `last_updated_by` int(11) DEFAULT NULL,
					  `last_updated_at` datetime DEFAULT NULL,
					  `created_by` int(11) DEFAULT NULL,
					  `created_at` datetime DEFAULT NULL,
					  PRIMARY KEY (`push_message_id`),
					  KEY `fk_pm_customer` (`customer_id`),
					  CONSTRAINT `fk_pm_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`)
					  )ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
			
			$db->query ( $sql );
			
			// Create Table Push message detail If Not Exsist
			$sql = "CREATE TABLE IF NOT EXISTS `push_message_detail` (
					  `push_message_detail_id` int(11) NOT NULL AUTO_INCREMENT,
					  `push_message_id` int(11) DEFAULT NULL,
					  `language_id` int(11) DEFAULT NULL,
					  `title` varchar(64) DEFAULT NULL,
					  `description` varchar(255) DEFAULT NULL,
					  `message_date` datetime DEFAULT NULL,
					  PRIMARY KEY (`push_message_detail_id`),
					  KEY `fk_pmd_message` (`push_message_id`),
					  CONSTRAINT `fk_pmd_message` FOREIGN KEY (`push_message_id`) REFERENCES `push_message` (`push_message_id`)
					  )ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
			
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

