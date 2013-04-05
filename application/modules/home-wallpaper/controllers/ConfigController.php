<?php

class HomeWallpaper_ConfigController extends Zend_Controller_Action
{

    public function init()
    {
		/* Initialize action controller here */
    }

    public function installAction() {
    	$this->_helper->layout ()->disableLayout ();
    	$this->_helper->viewRenderer->setNoRender ( true );
    	
    	$db = Standard_Functions::getDefaultDbAdapter();
    	try {
    		// Create Table Home Wallpaper If Not Exsist
    		$sql = "CREATE TABLE IF NOT EXISTS `home_wallpaper` (
					  `home_wallpaper_id` int(11) NOT NULL AUTO_INCREMENT,
					  `customer_id` int(11) DEFAULT NULL,
					  `status` tinyint(4) DEFAULT NULL,
					  `order` int(11) DEFAULT NULL,
					  `last_updated_by` int(11) DEFAULT NULL,
					  `last_updated_at` datetime DEFAULT NULL,
					  `created_by` int(11) DEFAULT NULL,
					  `created_at` datetime DEFAULT NULL,
					  PRIMARY KEY (`home_wallpaper_id`),
					  KEY `fk_hw_customer` (`customer_id`),
					  KEY `fk_hw_updated` (`last_updated_by`),
					  KEY `fk_hw_created` (`created_by`),
					  CONSTRAINT `fk_hw_created` FOREIGN KEY (`created_by`) REFERENCES `user` (`user_id`),
					  CONSTRAINT `fk_hw_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`),
					  CONSTRAINT `fk_hw_updated` FOREIGN KEY (`last_updated_by`) REFERENCES `user` (`user_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    	
    		$db->query($sql);
    		
    		// Create Table Home Wallpaper Detail If Not Exsist
    		$sql = "CREATE TABLE `home_wallpaper_detail` (
					  `home_wallpaper_detail_id` int(11) NOT NULL AUTO_INCREMENT,
					  `home_wallpaper_id` int(11) NOT NULL,
					  `language_id` int(11) NOT NULL,
					  `image_title` varchar(60) DEFAULT NULL,
					  `image_ipad` varchar(256) DEFAULT NULL,
					  `image_iphone` varchar(256) DEFAULT NULL,
					  `image_android` varchar(256) DEFAULT NULL,
					  `image_ipad_3` varchar(256) DEFAULT NULL,
					  `link_to_module` varchar(60) DEFAULT NULL,
					  `last_updated_by` int(11) DEFAULT NULL,
					  `last_updated_at` datetime DEFAULT NULL,
					  `created_by` int(11) DEFAULT NULL,
					  `created_at` datetime DEFAULT NULL,
					  PRIMARY KEY (`home_wallpaper_detail_id`),
					  KEY `fk_hwd_wallpaper` (`home_wallpaper_id`),
					  KEY `fk_hwd_language` (`language_id`),
					  KEY `fk_hwd_updated` (`last_updated_by`),
					  KEY `fk_hwd_created` (`created_by`),
					  CONSTRAINT `fk_hwd_created` FOREIGN KEY (`created_by`) REFERENCES `user` (`user_id`),
					  CONSTRAINT `fk_hwd_language` FOREIGN KEY (`language_id`) REFERENCES `language` (`language_id`),
					  CONSTRAINT `fk_hwd_updated` FOREIGN KEY (`last_updated_by`) REFERENCES `user` (`user_id`),
					  CONSTRAINT `fk_hwd_wallpaper` FOREIGN KEY (`home_wallpaper_id`) REFERENCES `home_wallpaper` (`home_wallpaper_id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    		 
    		$db->query($sql);
    		
    		// Create Resource Dir
    		mkdir(Standard_Functions::getResourcePath()."home-wallpaper",0777);
    		mkdir(Standard_Functions::getResourcePath()."home-wallpaper/tmp/images",0777);
            mkdir(Standard_Functions::getResourcePath()."home-wallpaper/tmp/images/ipad",0777);
            mkdir(Standard_Functions::getResourcePath()."home-wallpaper/tmp/images/iphone",0777);
            mkdir(Standard_Functions::getResourcePath()."home-wallpaper/tmp/images/android",0777);
            mkdir(Standard_Functions::getResourcePath()."home-wallpaper/tmp/images/ipad3",0777);
            mkdir(Standard_Functions::getResourcePath()."home-wallpaper/wallpapers",0777);
            echo "Success";
    	}
    	catch (Exception $ex) {
    		echo $ex->getMessage();
    	}
    } 
    public function indexAction()
    {
        // action body
		
    }


}

