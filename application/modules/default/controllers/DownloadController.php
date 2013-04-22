<?php
class Default_DownloadController extends Standard_Rest_Controller {
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::getAction()
	 */
	public function getAction() {
		// TODO Auto-generated method stub
		$customer_id = $this->_request->getParam("customer_id",null);
		$resolution_id = $this->_request->getParam("resolution",null);
		if($customer_id===null) {
			$this->_sendError("Invalid request");
		} else {
			try{
				$zip = new ZipArchive();
				$zip->open("resource/images.zip",ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
				// Icons
				$zip->addEmptyDir("icon");
				$moduleMapper = new Admin_Model_Mapper_CustomerModule();
				$customerModule = $moduleMapper->fetchAll("customer_id=".$customer_id);
				if($customerModule) {
					foreach($customerModule as $module) {
						$module = $module->toArray();
						if(isset($module["icon"]) && $module["icon"]!="") {
							$zip->addFile("resource/default/images/icon/".$module["icon"],"icon/".$module["icon"]);
						}
					}
				}
				// Contact
				$zip->addEmptyDir("contact");
				$contactMapper = new Contact_Model_Mapper_Contact();
				$contactModel = $contactMapper->fetchAll("customer_id=".$customer_id);
				if($contactModel) {
					foreach($contactModel as $contact) {
						$contactDetails = array();
						$contactDetailMapper = new Contact_Model_Mapper_ContactDetail();
						$contactDetailModel = $contactDetailMapper->fetchAll("contact_id=".$contact->getContactId());
						if($contactDetailModel) {
							foreach($contactDetailModel as $contact_detail) {
								$details = $contact_detail->toArray();
								if(isset($details["logo"])) {
									$zip->addFile("resource/contact/images/".$details["logo"],"contact/".$details["logo"]);
								}
							}
						}
					}
				}				
				
				// Events
				$zip->addEmptyDir("events");
				$eventMapper = new Events_Model_Mapper_ModuleEvents();
				$eventModel = $eventMapper->fetchAll("customer_id=".$customer_id);
				if($eventModel) {
					foreach($eventModel as $event) {
						$eventDetails = array();
						$eventDetailMapper = new Events_Model_Mapper_ModuleEventsDetail();
						$eventDetailModel = $eventDetailMapper->fetchAll("module_events_id=".$event->getModuleEventsId());
						if($eventDetailModel) {
							$eventLocationMapper = new Events_Model_Mapper_ModuleEventsLocation();
							foreach($eventDetailModel as $event_detail) {
								$details = $event_detail->toArray();
								if(isset($details["image"])) {
									$zip->addFile("resource/events/images/".$details["image"],"events/".$details["image"]);
								}
							}
						}
					}
				}
				
				// Home Wallpaper
				$zip->addEmptyDir("home-wallpaper");
				$wallpaperMapper = new HomeWallpaper_Model_Mapper_HomeWallpaper();
				$wallpaperModel = $wallpaperMapper->fetchAll("customer_id=".$customer_id);
				if($wallpaperModel) {
					foreach($wallpaperModel as $wallpaper) {
						$wallaperDetails = array();
						$wallpaperDetailMapper = new HomeWallpaper_Model_Mapper_HomeWallpaperDetail();
						$wallpaperDetailModel = $wallpaperDetailMapper->fetchAll("home_wallpaper_id=".$wallpaper->getHomeWallpaperId());
						if($wallpaperDetailModel) {
							foreach($wallpaperDetailModel as $wallpaper_detail) {
								$details = $wallpaper_detail->toArray();
								if($resolution_id > 0) {
									$homeWallpaperImageMapper = new HomeWallpaper_Model_Mapper_HomeWallpaperImage();
									$homeWallpaperImageModels = $homeWallpaperImageMapper->fetchAll("home_wallpaper_detail_id=".$details["home_wallpaper_detail_id"]." AND resolution_id=".$resolution_id);
									if($homeWallpaperImageModels) {
										$zip->addFile("resource/home-wallpaper/wallpapers/C" . $customer_id. "/R".$resolution_id."/".$homeWallpaperImageModels[0]->get("image_path"),"home-wallpaper/".$homeWallpaperImageModels[0]->get("image_path"));
									}
								}
							}
						}
					}
				}
				
				// Image Gallery
				$zip->addEmptyDir("module-image-gallery");
				$file_path = "resource/module-image-gallery/thumb/".$customer_id."/";
				$imageMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGallery();
				$imageModel = $imageMapper->fetchAll("customer_id=".$customer_id);
				if($imageModel) {
					foreach($imageModel as $image) {
						$imageDetails = array();
						$imageDetailMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryDetail();
						$imageDetailModel = $imageDetailMapper->fetchAll("module_image_gallery_id=".$image->getModuleImageGalleryId());
						if($imageDetailModel) {
							foreach($imageDetailModel as $image_detail) {
								$details = $image_detail->toArray();
								if(isset($details["image_path"])) {
									$zip->addFile($file_path .$details["image_path"],"module-image-gallery/".$details["image_path"]);
								}
							}
						}
					}
				}
				
				// Image Gallery 1
				$zip->addEmptyDir("module-image-gallery-1");
				$file_path = "resource/module-image-gallery-1/thumb/".$customer_id."/";
				$imageMapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGallery1();
				$imageModel = $imageMapper->fetchAll("customer_id=".$customer_id);
				if($imageModel) {
					foreach($imageModel as $image) {
						$imageDetails = array();
						$imageDetailMapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryDetail1();
						$imageDetailModel = $imageDetailMapper->fetchAll("module_image_gallery_1_id=".$image->getModuleImageGallery1Id());
						if($imageDetailModel) {
							foreach($imageDetailModel as $image_detail) {
								$details = $image_detail->toArray();
								if(isset($details["image_path"])) {
									$zip->addFile($file_path .$details["image_path"],"module-image-gallery-1/".$details["image_path"]);
								}
							}
						}
					}
				}
				
				// Social Media
				$zip->addEmptyDir("social-media");
				$socialMediaMapper = new SocialMedia_Model_Mapper_ModuleSocialMedia();
				$socialMediaModel = $socialMediaMapper->fetchAll("customer_id=".$customer_id);
				if($socialMediaModel) {
					foreach($socialMediaModel as $socialMedia) {
						$socialMediaDetails = array();
						$socialMediaDetailMapper = new SocialMedia_Model_Mapper_ModuleSocialMediaDetail();
						$socialMediaDetailModel = $socialMediaDetailMapper->fetchAll("module_social_media_id=".$socialMedia->getModuleSocialMediaId());
						if($socialMediaDetailModel) {
							foreach($socialMediaDetailModel as $socialMedia_detail) {
								$details = $socialMedia_detail->toArray();
								if(isset($details["icon_path"])) {
									$zip->addFile("resource/social-media/".$details["icon_path"],"social-media/".$details["icon_path"]);
								}
							}
						}
					}
				}
				
				// Website
				$zip->addEmptyDir("website");
				$websiteMapper = new Website_Model_Mapper_ModuleWebsite();
				$websiteModel = $websiteMapper->fetchAll("customer_id=".$customer_id);
				if($websiteModel) {
					foreach($websiteModel as $website) {
						$websiteDetail = array();
						$websiteDetailMapper = new Website_Model_Mapper_ModuleWebsiteDetail();
						$websiteDetailModel = $websiteDetailMapper->fetchAll("module_website_id=".$website->getModuleWebsiteId());
						if($websiteDetailModel) {
							foreach($websiteDetailModel as $website_detail) {
								$websiteDetail = $website_detail->toArray();
								if($websiteDetail["website_logo"] != null){
									$zip->addFile("resource/website/logos/".$websiteDetail["website_logo"],"website/".$websiteDetail["website_logo"]);
								}
							}
						}
					}
				}
				
				// Website 1
				$zip->addEmptyDir("website-1");
				$websiteMapper = new Website1_Model_Mapper_ModuleWebsite1();
				$websiteModel = $websiteMapper->fetchAll("customer_id=".$customer_id);
				if($websiteModel) {
					foreach($websiteModel as $website) {
						$websiteDetail = array();
						$websiteDetailMapper = new Website1_Model_Mapper_ModuleWebsiteDetail1();
						$websiteDetailModel = $websiteDetailMapper->fetchAll("module_website_1_id=".$website->getModuleWebsite1Id());
						if($websiteDetailModel) {
							foreach($websiteDetailModel as $website_detail) {
								$websiteDetail = $website_detail->toArray();
								if(isset($websiteDetail["website_logo"])) {
									$zip->addFile("resource/website-1/logos/".$websiteDetail["website_logo"],"website-1/".$websiteDetail["website_logo"]);
								}
							}
						}
					}
				}
				
				if(($zip->close()===true)) {
					header('Content-Type: application/zip');
					header('Content-disposition: attachment; filename=images.zip');
					header('Content-Length: ' . filesize("resource/images.zip"));
					readfile("resource/images.zip");
					unlink("resource/images.zip");
					die;
				}
			} catch (Exception $ex) {
				$this->_sendError($ex->getMessage());
			}
		}
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::postAction()
	 */
	public function postAction() {
		// TODO Auto-generated method stub
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::putAction()
	 */
	public function putAction() {
		// TODO Auto-generated method stub
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::deleteAction()
	 */
	public function deleteAction() {
		// TODO Auto-generated method stub
	}
	
	/* (non-PHPdoc)
	 * @see Zend_Rest_Controller::headAction()
	 */public function headAction() {
		// TODO Auto-generated method stub
	}
}