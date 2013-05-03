<?php
class Default_DownloadController extends Standard_Rest_Controller {
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::getAction()
	 */
	public function getAction() {
		// TODO Auto-generated method stub
		$customer_id = $this->_request->getParam("customer_id",null);
		$resolution_id = $this->_request->getParam("resolution",null);
		$reqModuleName = $this->_request->getParam("module-name",null);
		
		if($customer_id===null) {
			$this->_sendError("Invalid request");
		} else {
			try{
				$zip = new ZipArchive();
				$zip->open("resource/images.zip",ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
				if($reqModuleName==null || $reqModuleName=="icon") {
					// Icons
					$zip->addEmptyDir("icon");
					$moduleMapper = new Admin_Model_Mapper_CustomerModule();
					$customerModule = $moduleMapper->fetchAll("customer_id=".$customer_id);
					if($customerModule) {
						foreach($customerModule as $module) {
							$module = $module->toArray();
							if(isset($module["icon"]) && $module["icon"]!="") {
								if(file_exists("resource/default/images/icon/".$module["icon"]) && is_file("resource/default/images/icon/".$module["icon"]))
									$zip->addFile("resource/default/images/icon/".$module["icon"],"icon/".$module["icon"]);
							}
						}
					}
				}
				
				if($reqModuleName==null || $reqModuleName=="contact") {
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
										if(file_exists("resource/contact/images/".$details["logo"]) && is_file("resource/contact/images/".$details["logo"]))
											$zip->addFile("resource/contact/images/".$details["logo"],"contact/".$details["logo"]);
									}
									if(isset($details["icon"])) {
									    if(count(explode('/', $details["icon"])) > 1){
									        if(file_exists("resource/contact/".$details["icon"]) && is_file("resource/contact/".$details["icon"])){
									            $zip->addFile("resource/contact/".$details["icon"],"contact/".$details["icon"]);
									        }
									    }else{
									        if(file_exists("resource/contact/preset-icons/".$details["icon"]) && is_file("resource/contact/preset-icons/".$details["icon"])){
									            $zip->addFile("resource/contact/preset-icons/".$details["icon"],"contact/preset-icons/".$details["icon"]);
									        }
									    }
									}
								}
							}
						}
					}				
				}
				
				if($reqModuleName==null || $reqModuleName=="events") {
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
										if(file_exists("resource/events/images/".$details["image"]) && is_file("resource/events/images/".$details["image"]))
											$zip->addFile("resource/events/images/".$details["image"],"events/".$details["image"]);
									}
									if(isset($details["icon"])) {
									    if(count(explode('/', $details["icon"])) > 1){
									        if(file_exists("resource/events/".$details["icon"]) && is_file("resource/events/".$details["icon"])){
									            $zip->addFile("resource/events/".$details["icon"],"events/".$details["icon"]);
									        }
									    }else{
									        if(file_exists("resource/events/preset-icons/".$details["icon"]) && is_file("resource/events/preset-icons/".$details["icon"])){
									            $zip->addFile("resource/events/preset-icons/".$details["icon"],"events/preset-icons/".$details["icon"]);
									        }
									    }
									}
								}
							}
						}
					}
				}
				if($reqModuleName==null || $reqModuleName=="home-wallpaper") {
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
											if(file_exists("resource/home-wallpaper/wallpapers/C" . $customer_id. "/R".$resolution_id."/".$homeWallpaperImageModels[0]->get("image_path")) && is_file("resource/home-wallpaper/wallpapers/C" . $customer_id. "/R".$resolution_id."/".$homeWallpaperImageModels[0]->get("image_path")))
												$zip->addFile("resource/home-wallpaper/wallpapers/C" . $customer_id. "/R".$resolution_id."/".$homeWallpaperImageModels[0]->get("image_path"),"home-wallpaper/".$homeWallpaperImageModels[0]->get("image_path"));
										}
									}
								}
							}
						}
					}
				}
				if($reqModuleName==null || $reqModuleName=="image-gallery") {
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
										if(file_exists($file_path .$details["image_path"]) && is_file($file_path .$details["image_path"]))
											$zip->addFile($file_path .$details["image_path"],"module-image-gallery/".$details["image_path"]);
										
										$ext = array_pop(explode(".", $details["image_path"]));
										$thumb = str_replace(".".$ext, "_thumb.".$ext, $details["image_path"]);
										if(file_exists($file_path .$thumb) && is_file($file_path .$thumb))
											$zip->addFile($file_path .$thumb,"module-image-gallery/".$thumb);
									}
								}
							}
						}
					}
					$imageCategoryMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategory();
					$imageCategoryModel = $imageCategoryMapper->fetchAll("customer_id =".$customer_id);
					if($imageCategoryModel){
					    foreach ($imageCategoryModel as $category){
					        $categoryDetailMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategoryDetail();
					        $categoryDetailModel = $categoryDetailMapper->fetchAll("module_image_gallery_category_id =".$category->getModuleImageGalleryCategoryId());
					        foreach ($categoryDetailModel as $categoryDetail){
					            $details = $categoryDetail->toArray();
					            if(isset($details["icon"])) {
					                if(count(explode('/', $details["icon"])) > 1){
					                    if(file_exists("resource/module-image-gallery/".$details["icon"]) && is_file("resource/module-image-gallery/".$details["icon"])){
					                        $zip->addFile("resource/module-image-gallery/".$details["icon"],"module-image-gallery/".$details["icon"]);
					                    }
					                }else{
					                    if(file_exists("resource/module-image-gallery/preset-icons/".$details["icon"]) && is_file("resource/module-image-gallery/preset-icons/".$details["icon"])){
					                        $zip->addFile("resource/module-image-gallery/preset-icons/".$details["icon"],"module-image-gallery/preset-icons/".$details["icon"]);
					                    }
					                }
					            }    
					        }
					    }
					}
				}
				if($reqModuleName==null || $reqModuleName=="image-gallery-1") {
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
										if(file_exists($file_path .$details["image_path"]) && is_file($file_path .$details["image_path"]))
											$zip->addFile($file_path .$details["image_path"],"module-image-gallery-1/".$details["image_path"]);
										
										$ext = array_pop(explode(".", $details["image_path"]));
										$thumb = str_replace(".".$ext, "_thumb.".$ext, $details["image_path"]);
										if(file_exists($file_path .$thumb) && is_file($file_path .$thumb))
											$zip->addFile($file_path .$thumb,"module-image-gallery-1/".$thumb);
									}
								}
							}
						}
					}
					$imageCategoryMapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryCategory1();
					$imageCategoryModel = $imageCategoryMapper->fetchAll("customer_id =".$customer_id);
					if($imageCategoryModel){
					    foreach ($imageCategoryModel as $category){
					        $categoryDetailMapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryCategoryDetail1();
					        $categoryDetailModel = $categoryDetailMapper->fetchAll("module_image_gallery_category_1_id =".$category->getModuleImageGalleryCategory1Id());
					        foreach ($categoryDetailModel as $categoryDetail){
					            $details = $categoryDetail->toArray();
					            if(isset($details["icon"])) {
					                if(count(explode('/', $details["icon"])) > 1){
					                    if(file_exists("resource/module-image-gallery-1/".$details["icon"]) && is_file("resource/module-image-gallery-1/".$details["icon"])){
					                        $zip->addFile("resource/module-image-gallery-1/".$details["icon"],"module-image-gallery-1/".$details["icon"]);
					                    }
					                }else{
					                    if(file_exists("resource/module-image-gallery-1/preset-icons/".$details["icon"]) && is_file("resource/module-image-gallery-1/preset-icons/".$details["icon"])){
					                        $zip->addFile("resource/module-image-gallery-1/preset-icons/".$details["icon"],"module-image-gallery-1/preset-icons/".$details["icon"]);
					                    }
					                }
					            }    
					        }
					    }
					}
				}
				if($reqModuleName==null || $reqModuleName=="social-media") {
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
										if(file_exists("resource/social-media/".$details["icon_path"]) && is_file("resource/social-media/".$details["icon_path"]))
											$zip->addFile("resource/social-media/".$details["icon_path"],"social-media/".$details["icon_path"]);
									}
								}
							}
						}
					}
				}
				
				if($reqModuleName==null || $reqModuleName=="push-message") {
				    // Push message
				    $zip->addEmptyDir("push-message");
				    $pushMessageMapper = new PushMessage_Model_Mapper_PushMessage();
				    $pushMessageModel = $pushMessageMapper->fetchAll("customer_id=".$customer_id);
				    if($pushMessageModel) {
				        foreach($pushMessageModel as $pushMessage) {
				            $pushMessageDetails = array();
				            $pushMessageDetailMapper = new PushMessage_Model_Mapper_PushMessageDetail();
				            $pushMessageDetailModel = $pushMessageDetailMapper->fetchAll("push_message_id=".$pushMessage->getPushMessageId());
				            if($pushMessageDetailModel) {
				                foreach($pushMessageDetailModel as $pushMessage_detail) {
				                    $details = $pushMessage_detail->toArray();
				                    if(isset($details["icon"])) {
    				                     if(count(explode('/', $details["icon"])) > 1){
                                             if(file_exists("resource/push-message/".$details["icon"]) && is_file("resource/push-message/".$details["icon"])){
                                                 $zip->addFile("resource/push-message/".$details["icon"],"push-message/".$details["icon"]);
    	                                     }    
			                             }else{
			                                 if(file_exists("resource/push-message/preset-icons/".$details["icon"]) && is_file("resource/push-message/preset-icons/".$details["icon"])){
				                                 $zip->addFile("resource/push-message/preset-icons/".$details["icon"],"push-message/preset-icons/".$details["icon"]);
				                             }    
			                            } 
				                    }
				                }
				            }
				        }
				    }
				}
				
				if($reqModuleName==null || $reqModuleName=="document") {
				    //Document and Category
				    $zip->addEmptyDir("document");
				    $documentMapper = new Document_Model_Mapper_ModuleDocument();
				    $documentModel = $documentMapper->fetchAll("customer_id=".$customer_id);
				    if($documentModel){
				        foreach ($documentModel as $document){
				            $documentDetails = array();
				            $documentDetailMapper = new Document_Model_Mapper_ModuleDocumentDetail();
				            $documentDetailModel = $documentDetailMapper->fetchAll("module_document_id=".$document->getModuleDocumentId());
				            if($documentDetailModel){
				                foreach ($documentDetailModel as $document_detail){
				                    $details = $document_detail->toArray();
				                    if($details["icon"] != null){
				                        if(count(explode('/', $details["icon"])) > 1){
				                            if(file_exists("resource/document/".$details["icon"]) && is_file("resource/document/".$details["icon"])){
				                                $zip->addFile("resource/document/".$details["icon"],"document/".$details["icon"]);
				                            }    
				                        }else{
				                            if(file_exists("resource/document/preset-icons/".$details["icon"]) && is_file("resource/document/preset-icons/".$details["icon"])){
				                                $zip->addFile("resource/document/preset-icons/".$details["icon"],"document/preset-icons/".$details["icon"]);
				                            }    
				                        }    
				                    }
				                }
				            }
				        }
				    }
				    $documentCategoryMapper = new Document_Model_Mapper_ModuleDocumentCategory();
				    $documentCategoryModel = $documentCategoryMapper->fetchAll("customer_id=".$customer_id);
				    if($documentCategoryModel){
				        foreach ($documentCategoryModel as $category){
				            $documentCategoryDetails = array();
				            $documentCategoryDetailMapper = new Document_Model_Mapper_ModuleDocumentCategoryDetail();
				            $documentCategoryDetailModel = $documentCategoryDetailMapper->fetchAll("module_document_category_id=".$category->getModuleDocumentCategoryId());
				            if($documentCategoryDetailModel){
				                foreach ($documentCategoryDetailModel as $category_detail){
				                    $details = $category_detail->toArray();
				                    if($details["icon"] != null){
				                        if(count(explode('/', $details["icon"])) > 1){
				                            if(file_exists("resource/document/".$details["icon"]) && is_file("resource/document/".$details["icon"])){
				                                $zip->addFile("resource/document/".$details["icon"],"document/".$details["icon"]);
				                            }
				                        }else{
				                            if(file_exists("resource/document/preset-icons/".$details["icon"]) && is_file("resource/document/preset-icons/".$details["icon"])){
				                                $zip->addFile("resource/document/preset-icons/".$details["icon"],"document/preset-icons/".$details["icon"]);
				                            }
				                        }
				                    }
				                }
				            }
				        }
				    }    
				}
				
				if($reqModuleName==null || $reqModuleName=="website") {
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
										if(file_exists("resource/website/logos/".$websiteDetail["website_logo"]) && is_file("resource/website/logos/".$websiteDetail["website_logo"]))
											$zip->addFile("resource/website/logos/".$websiteDetail["website_logo"],"website/".$websiteDetail["website_logo"]);
									}
								}
							}
						}
					}
				}
				if($reqModuleName==null || $reqModuleName=="website-1") {
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
										if(file_exists("resource/website-1/logos/".$websiteDetail["website_logo"]) && is_file("resource/website-1/logos/".$websiteDetail["website_logo"]))
											$zip->addFile("resource/website-1/logos/".$websiteDetail["website_logo"],"website-1/".$websiteDetail["website_logo"]);
									}
								}
							}
						}
					}
				}
				if($reqModuleName==null || $reqModuleName=="module-cms") {
					$zip->addEmptyDir("module-cms");
					$moduleCmsMapper = new ModuleCms_Model_Mapper_ModuleCms();
					$moduleCmsModel = $moduleCmsMapper->fetchAll("customer_id=".$customer_id);
					if($moduleCmsModel) {
						foreach($moduleCmsModel as $models) {
							$cmsDetails = array();
							$moduleCmsDetailMapper = new ModuleCms_Model_Mapper_ModuleCmsDetail();
							$moduleCmsDetailModel = $moduleCmsDetailMapper->fetchAll("module_cms_id=".$models->getModuleCmsId());
							if($moduleCmsDetailModel) {
								foreach($moduleCmsDetailModel as $detail_model) {
									$details = $detail_model->toArray();
									if(isset($details["thumb"])) {
										if(file_exists("resource/module-cms/thumb/".$details["thumb"]) && is_file("resource/module-cms/thumb/".$details["thumb"]))
											$zip->addFile("resource/module-cms/thumb/".$details["thumb"],"module-cms/".$details["thumb"]);
									}
								}
							}
						}
					}
				}
				if($reqModuleName==null || $reqModuleName=="module-cms-1") {
					$zip->addEmptyDir("module-cms-1");
					$moduleCmsMapper = new ModuleCms1_Model_Mapper_ModuleCms1();
					$moduleCmsModel = $moduleCmsMapper->fetchAll("customer_id=".$customer_id);
					if($moduleCmsModel) {
						foreach($moduleCmsModel as $models) {
							$cmsDetails = array();
							$moduleCmsDetailMapper = new ModuleCms1_Model_Mapper_ModuleCmsDetail1();
							$moduleCmsDetailModel = $moduleCmsDetailMapper->fetchAll("module_cms_1_id=".$models->getModuleCms1Id());
							if($moduleCmsDetailModel) {
								foreach($moduleCmsDetailModel as $detail_model) {
									$details = $detail_model->toArray();
									if(isset($details["thumb"])) {
										if(file_exists("resource/module-cms-1/thumb/".$details["thumb"]) && is_file("resource/module-cms-1/thumb/".$details["thumb"]))
											$zip->addFile("resource/module-cms-1/thumb/".$details["thumb"],"module-cms-1/".$details["thumb"]);
									}
								}
							}
						}
					}
				}
				if($reqModuleName==null || $reqModuleName=="module-cms-2") {
					// CMS 2
					$zip->addEmptyDir("module-cms-2");
					$moduleCmsMapper = new ModuleCms2_Model_Mapper_ModuleCms2();
					$moduleCmsModel = $moduleCmsMapper->fetchAll("customer_id=".$customer_id);
					if($moduleCmsModel) {
						foreach($moduleCmsModel as $models) {
							$cmsDetails = array();
							$moduleCmsDetailMapper = new ModuleCms2_Model_Mapper_ModuleCmsDetail2();
							$moduleCmsDetailModel = $moduleCmsDetailMapper->fetchAll("module_cms_2_id=".$models->getModuleCms2Id());
							if($moduleCmsDetailModel) {
								foreach($moduleCmsDetailModel as $detail_model) {
									$details = $detail_model->toArray();
									if(isset($details["thumb"])) {
										if(file_exists("resource/module-cms-2/thumb/".$details["thumb"]) && is_file("resource/module-cms-2/thumb/".$details["thumb"]))
											$zip->addFile("resource/module-cms-2/thumb/".$details["thumb"],"module-cms-2/".$details["thumb"]);
										
									}
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