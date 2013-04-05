<?php
class Document_ConfigController extends Zend_Controller_Action {
	public function init() {
		/* Initialize action controller here */
	}
	public function installAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ( true );
		
		$db = Standard_Functions::getDefaultDbAdapter ();
		try {
			
			// Create Table Contact If Not Exsist
			$sql = "CREATE TABLE `module_document` (
  						`module_document_id` int(11) NOT NULL AUTO_INCREMENT,
						`customer_id` int(11) DEFAULT NULL,
						`status` tinyint(4) DEFAULT NULL,
						`order` int(11) DEFAULT NULL,
						`last_updated_by` int(11) DEFAULT NULL,
						`last_updated_at` datetime DEFAULT NULL,
						`created_by` int(11) DEFAULT NULL,
						`created_at` datetime DEFAULT NULL,
						PRIMARY KEY (`module_document_id`),
						KEY `fk_md_customer` (`customer_id`),
						CONSTRAINT `fk_md_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
			
			$db->query ( $sql );
			
			// Create Table Contact If Not Exsist
			$sql = "CREATE TABLE `module_document_detail` (
						`module_document_detail_id` int(11) NOT NULL AUTO_INCREMENT,
						`module_document_id` int(11) DEFAULT NULL,
						`language_id` int(11) DEFAULT NULL,
						`title` varchar(60) DEFAULT NULL,
						`description` varchar(255) DEFAULT NULL,
						`document_path` varchar(255) DEFAULT NULL,
						`keywords` varchar(128) DEFAULT NULL,
						`type` varchar(30) DEFAULT NULL,
						`size` varchar(20) DEFAULT NULL,
						`last_updated_by` int(11) DEFAULT NULL,
						`last_updated_at` datetime DEFAULT NULL,
						`created_by` int(11) DEFAULT NULL,
						`created_at` datetime DEFAULT NULL,
						PRIMARY KEY (`module_document_detail_id`),
						KEY `fk_md_id` (`module_document_id`),
						KEY `fk_md_lang` (`language_id`),
						CONSTRAINT `fk_md_id` FOREIGN KEY (`module_document_id`) REFERENCES `module_document` (`module_document_id`),
						CONSTRAINT `fk_md_lang` FOREIGN KEY (`language_id`) REFERENCES `language` (`language_id`)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
				
			$db->query ( $sql );
			
			// Create Resource Dir
			mkdir ( Standard_Functions::getResourcePath () . "document", 0755 );
			mkdir ( Standard_Functions::getResourcePath () . "document/uploads", 0755 );
			echo "Success";
		} catch ( Exception $ex ) {
			echo $ex->getMessage ();
		}
	}
	public function indexAction() {
		// action body
	}
}

