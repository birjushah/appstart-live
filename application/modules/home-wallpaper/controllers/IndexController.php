<?php
class HomeWallpaper_IndexController extends Zend_Controller_Action {
	var $_module_id;
	var $_customer_module_id;
	var $_total_uploaded_wallpapers;
	var $_upload_wallpaper_limit;
	public function init() {
		/* Initialize action controller here */
		$modulesMapper = new Admin_Model_Mapper_Module();
		$module = $modulesMapper->fetchAll("name ='home-wallpaper'");
		if(is_array($module)) {
			HomeWallpaper_Form_HomeWallpaper::$_module_id = $this->_module_id = $module[0]->getModuleId();
		}
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
		$customermodule = $customermoduleMapper->fetchAll("customer_id=". $customer_id ." AND module_id=".$this->_module_id);
		if(is_array($customermodule)) {
		    $customermodule = $customermodule[0];
		    $this->_customer_module_id = $customermodule->getCustomerModuleId();
		}
		
		//getting the uploaded wallpapers
		$mapper = new HomeWallpaper_Model_Mapper_HomeWallpaper();
		$customer_id = Standard_Functions::getCurrentUser()->customer_id;
		$DBExpr = new Zend_Db_Expr("COUNT(home_wallpaper_id)");
		$select = $mapper->getDbTable()->select(false)
		->setIntegrityCheck(false)
		->from('module_home_wallpaper',array('count'=>$DBExpr ))
		->where("customer_id =".$customer_id);
		$stack = $mapper->getDbTable()->fetchRow($select)->toArray();
		$this->_total_uploaded_wallpapers = $stack['count'];
		
		//Getting Wallapaer Limit for customer
		$limit = Standard_Functions::getUploadLimits();
		$this->_upload_wallpaper_limit = $limit['home-wallpaper'];
	}
	public function indexAction() {
		// action body
		$this->view->addlink = $this->view->url ( array (
				"module" => "home-wallpaper",
				"controller" => "index",
				"action" => "add" 
		), "default", true );
		$this->view->publishlink = $this->view->url ( array (
		        "module" => "default",
		        "controller" => "configuration",
		        "action" => "publish",
		        "id" => $this->_customer_module_id
		), "default", true );
		$this->view->reorderlink = $this->view->url ( array (
				"module" => "home-wallpaper",
				"controller" => "index",
				"action" => "reorder"
		), "default", true );
		
		$this->view->imagesUploaded = $this->_total_uploaded_wallpapers;
		$this->view->imagesLimit = $this->_upload_wallpaper_limit;
	}
	public function reorderAction() {
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		 
		if ($this->_request->isPost ()) {
			$this->_helper->layout ()->disableLayout ();
			$this->_helper->viewRenderer->setNoRender ();
	
			$user_id = Standard_Functions::getCurrentUser ()->user_id;
			$date_time = Standard_Functions::getCurrentDateTime ();
			$response = array();
	
			$order = $this->_request->getParam ("order");
	
			$mapper = new HomeWallpaper_Model_Mapper_HomeWallpaper();
			$mapper->getDbTable()->getAdapter()->beginTransaction();
			try {
				foreach($order as $key=>$value) {
					$model = $mapper->find($value);
					$model->setOrder($key);
					$model->setLastUpdatedBy ( $user_id );
					$model->setLastUpdatedAt ( $date_time );
					$model->save();
				}
		   
				// set is pulish to false
				$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
				$customermodule = $customermoduleMapper->fetchAll("customer_id=". $customer_id ." AND module_id=".$this->_module_id);
				if(is_array($customermodule)) {
					$customermodule = $customermodule[0];
					$customermodule->setIsPublish("NO");
					$customermodule->save();
				}
		   
				$mapper->getDbTable()->getAdapter()->commit();
				if($model && $model->getHomeWallpaperId()!="") {
					$response = array (
							"success" => true
					);
				}
			}catch(Exception $e) {
				$mapper->getDbTable()->getAdapter()->rollBack();
				$response = array (
						"errors" => $e->getMessage()
				);
			}
			echo Zend_Json::encode($response);
			exit;
		}
		 
		$mapper = new HomeWallpaper_Model_Mapper_HomeWallpaper();
		$select = $mapper->getDbTable ()->
							select ( false )->
							setIntegrityCheck ( false )->
							from ( array ("h" => "module_home_wallpaper"), 
									array (
										"h.home_wallpaper_id" => "home_wallpaper_id",
										"h.status" => "status",
										"h.order" => "order") )->
							joinLeft ( array ("hd" => "module_home_wallpaper_detail"), 
										"hd.home_wallpaper_id = h.home_wallpaper_id AND hd.language_id=" . $active_lang_id, 
									array (
										"hd.image_title" => "image_title",
										"hd.home_wallpaper_detail_id" => "home_wallpaper_detail_id") )->
							where ( "h.customer_id=" . $customer_id )->order("h.order");
		$response = $mapper->getDbTable()->fetchAll($select)->toArray();
		$this->view->data = $response;
	}
	public function addAction() {
		// action body
		$form = new HomeWallpaper_Form_HomeWallpaper ();
		foreach ( $form->getElements () as $element ) {
			if ($element->getDecorator ( 'Label' ))
				$element->getDecorator ( 'Label' )->setTag ( null );
		}
		$action = $this->view->url ( array (
				"module" => "home-wallpaper",
				"controller" => "index",
				"action" => "save" 
		), "default", true );
		$form->setAction ( $action );
		
		$this->view->form = $form;
		
		$mapper = new Admin_Model_Mapper_Resolution();
		
		$this->view->assign ( array (
				"partial" => "index/partials/add.phtml",
		        "iphoneresolutions" => $mapper->fetchAll("category = 1"),
		        "resolutions" => $mapper->fetchAll("category = 0")
		) );
		$this->view->resourcedir = $this->view->baseUrl();
		$this->view->imagesUploaded = $this->_total_uploaded_wallpapers;
		$this->view->imagesLimit = $this->_upload_wallpaper_limit;
		$this->render ( "add-edit" );
	}
	public function editAction() {
		// CHANGE
		$form = new HomeWallpaper_Form_HomeWallpaper ();
		$request = $this->getRequest ();
		if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
			$homeWallpaperMapper = new HomeWallpaper_Model_Mapper_HomeWallpaper ();
			$home_wallpaper_id = $request->getParam ( "id", "" );
			$language_id = $request->getParam ( "lang", "" );
			$languageMapper = new Admin_Model_Mapper_Language ();
			$languageData = $languageMapper->find ( $language_id );
			$this->view->language = $languageData->getTitle ();
			
			$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
			$data = $homeWallpaperMapper->find ( $home_wallpaper_id )->toArray ();
			$form->populate ( $data );
			$datadetails = array ();
			$homeWallpaperDetailMapper = new HomeWallpaper_Model_Mapper_HomeWallpaperDetail ();
			if ($homeWallpaperDetailMapper->countAll ( "home_wallpaper_id = " . $home_wallpaper_id . " AND language_id = " . $language_id ) > 0) {
				// Record For Language Found
				$dataDetails = $homeWallpaperDetailMapper->getDbTable ()->fetchAll ( "home_wallpaper_id = " . $home_wallpaper_id . " AND language_id = " . $language_id )->toArray ();
			} else {
				// Record For Language Not Found
				$dataDetails = $homeWallpaperDetailMapper->getDbTable ()->fetchAll ( "home_wallpaper_id = " . $home_wallpaper_id . " AND language_id = " . $default_lang_id )->toArray ();
				$dataDetails [0] ["home_wallpaper_detail_id"] = "";
				$dataDetails [0] ["language_id"] = $language_id;
			}
			if (isset ( $dataDetails [0] ) && is_array ( $dataDetails [0] )) {
				$form->populate ( $dataDetails [0] );
				$customerId = Standard_Functions::getCurrentUser ()->customer_id;
				
				$image_path = array();
				$homeWallpaperImageMapper = new HomeWallpaper_Model_Mapper_HomeWallpaperImage();
				if($dataDetails [0]["home_wallpaper_detail_id"] != null){
				    $homeWallpaperImageModels = $homeWallpaperImageMapper->fetchAll("home_wallpaper_detail_id=".$dataDetails [0]["home_wallpaper_detail_id"]);
				}
				if(is_array($homeWallpaperImageModels)){
				    foreach($homeWallpaperImageModels as $homeWallpaperImage) {
				        $resolution_id = $homeWallpaperImage->getResolutionId();
				        $img_uri = "resource/home-wallpaper/wallpapers/C" . $customerId."/R".$resolution_id;
				        $filename = $homeWallpaperImage->get("image_path");
				        $ext = array_pop(explode(".",$filename));
				        $filename = str_replace(".".$ext, "_thumb.".$ext, $filename);
				        if($filename != ""){
				            $image_path[$resolution_id] = $this->view->baseUrl($img_uri ."/" . $filename);
				        }else{
				            $image_path[$resolution_id] = "";
				        }
				    }    
				}
				$this->view->image_path = $image_path;
			}
			foreach ( $form->getElements () as $element ) {
				if ($element->getDecorator ( 'Label' )) {
					$element->getDecorator ( 'Label' )->setTag ( null );
				}
				$action = $this->view->url ( array (
						"module" => "home-wallpaper",
						"controller" => "index",
						"action" => "save",
						"id" => $request->getParam ( "id", "" ) 
				), "default", true );
				$form->setAction ( $action );
			}
		} else {
			$this->_redirect ( 'index' );
		}
		$this->view->form = $form;
		$mapper = new Admin_Model_Mapper_Resolution();
		$this->view->assign ( array (
				"partial" => "index/partials/edit.phtml",
				"iphoneresolutions" => $mapper->fetchAll("category = 1"),
		        "resolutions" => $mapper->fetchAll("category = 0")
		) );
		$this->view->resourcedir = $this->view->baseUrl();
		$this->view->imagesUploaded = $this->_total_uploaded_wallpapers;
		$this->view->imagesLimit = $this->_upload_wallpaper_limit;
		$this->render ( "add-edit" );
	}
	public function saveAction() {
		// Change
		$form = new HomeWallpaper_Form_HomeWallpaper ();
		$request = $this->getRequest ();
		$response = array ();
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		if ($this->_request->isPost ()) {
			if ($request->getParam ( "upload", "" ) != "") {
				$response = $this->fileUplaod ();
				echo Zend_Json::encode ( $response );
				exit ();
			}
			$allFlag = $this->_request->getParam("all",false);
			if ($form->isValid ( $this->_request->getParams () )) {
				try {
					$allFormValues = $form->getValues ();
					$customerId = Standard_Functions::getCurrentUser ()->customer_id;
					$user_id = Standard_Functions::getCurrentUser ()->user_id;
					$date_time = Standard_Functions::getCurrentDateTime ();
					$homeWallpaperMapper = new HomeWallpaper_Model_Mapper_HomeWallpaper ();
					$homeWallpaperMapper->getDbTable ()->getAdapter ()->beginTransaction ();
					$homeWallpaperModel = new HomeWallpaper_Model_HomeWallpaper ( $allFormValues );
					if ($request->getParam ( "home_wallpaper_id", "" ) == "") {
						// Adding new record
					    $maxOrder = $homeWallpaperMapper->getNextOrder ( $customerId );
						$homeWallpaperModel->setOrder ( $maxOrder + 1 );
						$homeWallpaperModel->setCreatedBy ( Standard_Functions::getCurrentUser ()->user_id );
						$homeWallpaperModel->setCreatedAt ( Standard_Functions::getCurrentDateTime () );
						$homeWallpaperModel->setCustomerId ( $customerId );
						$homeWallpaperModel->setLastUpdatedBy ( Standard_Functions::getCurrentUser ()->user_id );
						$homeWallpaperModel->setLastUpdatedAt ( Standard_Functions::getCurrentDateTime () );
						$homeWallpaperModel = $homeWallpaperModel->save ();
						
						// saving homewallpaer details
						$homeWallpaperId = $homeWallpaperModel->get ( "home_wallpaper_id" );
						$customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
						$customerLanguageModel = $customerLanguageMapper->fetchAll ( "customer_id = " . $customerId );
						if (is_array ( $customerLanguageModel )) {
							$is_uploaded = false;
							if (! is_dir ( Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . $customerId )) {
								mkdir ( Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . $customerId, 755 );
							}
							foreach ( $customerLanguageModel as $languages ) {
								$homeWallpaperDetailModel = new HomeWallpaper_Model_HomeWallpaperDetail ( $allFormValues );
								$homeWallpaperDetailModel->setHomeWallpaperId ( $homeWallpaperId );
								$homeWallpaperDetailModel->setLanguageId ( $languages->getLanguageId () );
								$homeWallpaperDetailModel->setCreatedBy ( Standard_Functions::getCurrentUser ()->user_id );
								$homeWallpaperDetailModel->setCreatedAt ( Standard_Functions::getCurrentDateTime () );
								$homeWallpaperDetailModel->setLastUpdatedBy ( Standard_Functions::getCurrentUser ()->user_id );
								$homeWallpaperDetailModel->setLastUpdatedAt ( Standard_Functions::getCurrentDateTime () );
								$homeWallpaperDetailModel = $homeWallpaperDetailModel->save ();
								$homeWallpaperDetailId = $homeWallpaperDetailModel->getHomeWallpaperDetailId();
								
								$mapperResolution = new Admin_Model_Mapper_Resolution();
								$modelResolution = $mapperResolution->fetchAll();
								foreach($modelResolution as $resoulution) {
									$resolution_id = $resoulution->getResolutionId();
									$filename = $request->getParam ( "image_".$resolution_id."_path" );
									if(!$is_uploaded && $filename != "") {
										// Move Uploaded Files
										$source_dir = Standard_Functions::getResourcePath () . "home-wallpaper/tmp/images/R".$resolution_id."/";
										$upload_dir = Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . $customerId."/R".$resolution_id."/";
										$filename = $this->moveUploadFile ( $source_dir , $upload_dir , $filename );
										$storedfilename[$resolution_id] = $filename;
									}elseif ($storedfilename[$resolution_id]){
									    $filename = $storedfilename[$resolution_id];
									}
									$homeWallpaperImageModel = new HomeWallpaper_Model_HomeWallpaperImage();
									$homeWallpaperImageModel->setHomeWallpaperDetailId($homeWallpaperDetailId);
									$homeWallpaperImageModel->setResolutionId($resolution_id);
									$homeWallpaperImageModel->setImagePath($filename);
									$homeWallpaperImageModel->save();
								}
								$is_uploaded = true;
							}
						}
					}elseif($allFlag){
					    $homeWallpaperModel->setLastUpdatedBy ( $user_id );
					    $homeWallpaperModel->setLastUpdatedAt ( $date_time );
					    $homeWallpaperModel = $homeWallpaperModel->save ();
					    $homeWallpaperImageModel = new HomeWallpaper_Model_HomeWallpaperImage();
					    $customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
					    $languages = $customerLanguageMapper->fetchAll ( "customer_id = " . $customerId );
					    $homeWallpaperImageMapper = new HomeWallpaper_Model_Mapper_HomeWallpaperImage();
					    $homeWallpaperDetailMapper = new HomeWallpaper_Model_Mapper_HomeWallpaperDetail();
					    $homewallpaperdetails = $homeWallpaperDetailMapper->getDbTAble()->fetchAll("home_wallpaper_id =".$allFormValues['home_wallpaper_id'])->toArray();
					    $is_uploaded = false;
					    if (! is_dir ( Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . $customerId )) {
					        mkdir ( Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . $customerId, 755 );
					    }
					    if($allFormValues['home_wallpaper_detail_id'] != null){
					        $currentimagedetails = $homeWallpaperImageMapper->getDbTable()->fetchAll("home_wallpaper_detail_id =".$allFormValues['home_wallpaper_detail_id'])->toArray();
					    }else{
					        $currenthomewallpaperdetails = $homeWallpaperDetailMapper->getDbTable()->fetchAll("home_wallpaper_id ='".$allFormValues['home_wallpaper_id']."' AND language_id =".$default_lang_id)->toArray();
					        $currentimagedetails = $homeWallpaperImageMapper->getDbTable()->fetchAll("home_wallpaper_detail_id =".$currenthomewallpaperdetails[0]['home_wallpaper_detail_id'])->toArray();   
					    }
					    if(is_array($currentimagedetails)){
					        $allImages = array();
					        foreach ($currentimagedetails as $currentimagedetail) {
					            $allImages[$currentimagedetail['resolution_id']] = $currentimagedetail['image_path'];
					        }
					    }
					    unset($allFormValues['home_wallpaper_detail_id'],$allFormValues['language_id']);
					    if(count($languages) == count($homewallpaperdetails)){
					        foreach ($homewallpaperdetails as $homewallpaperdetail){
					            $homewallpaperdetail = array_intersect_key($allFormValues + $homewallpaperdetail, $homewallpaperdetail);
					            $homewallpaperdetailModel = new HomeWallpaper_Model_HomeWallpaperDetail($homewallpaperdetail);
					            $homewallpaperimageMapper = new HomeWallpaper_Model_Mapper_HomeWallpaperImage();
					            $imagedetails = $homewallpaperimageMapper->fetchAll("home_wallpaper_detail_id =".$homewallpaperdetail['home_wallpaper_detail_id']);
					            foreach ($imagedetails as $imagedetail){
					                $resolution_id = $imagedetail->get("resolution_id");
					                $filename = $request->getParam ( "image_".$resolution_id."_path" );
					                if(!$is_uploaded && $filename != "" && $filename != "deleted" ) {
					                    // Move Uploaded Files
                                        $source_dir = Standard_Functions::getResourcePath () . "home-wallpaper/tmp/images/R".$resolution_id."/";
                                        $upload_dir = Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . $customerId."/R".$resolution_id."/";
                                        $filename = $this->moveUploadFile ( $source_dir , $upload_dir , $filename );
                                        $storedname[$resolution_id] = $filename;
					                }elseif($storedname[$resolution_id] && $filename != "deleted"){
					                    $filename = $storedname[$resolution_id];
					                }elseif($filename == "deleted"){
					                    $filename = "";
					                }elseif($allImages[$resolution_id]){
					                    $filename = $allImages[$resolution_id];
					                }
					                $imagedetail->setImagePath($filename);
					                $imagedetail->save();
					            }
					            $is_uploaded = true;
					            $homewallpaperdetailModel = $homewallpaperdetailModel->save();
					        }
					    }else{
					        $homewallpaperdetails = $homeWallpaperDetailMapper->fetchAll("home_wallpaper_id =".$allFormValues['home_wallpaper_id']);
					        foreach ($homewallpaperdetails as $homewallpaperdetail){
					            $imagedetails = $homeWallpaperImageMapper->fetchAll("home_wallpaper_detail_id =".$homewallpaperdetail->getHomeWallpaperDetailId());
					            foreach ($imagedetails as $imagedetail){
					                $imagedetail->delete();
					            }
					            $homewallpaperdetail->delete();
					        }
					        if (is_array ( $languages )) {
					            $is_uploaded = false;
					            if (! is_dir ( Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . $customerId )) {
					                mkdir ( Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . $customerId, 755 );
					            }
    					        foreach ( $languages as $language ) {
    					            $homeWallpaperDetailModel = new HomeWallpaper_Model_HomeWallpaperDetail ( $allFormValues );
    					            $homeWallpaperDetailModel->setLanguageId ( $language->getLanguageId () );
    					            $homeWallpaperDetailModel->setCreatedBy ( Standard_Functions::getCurrentUser ()->user_id );
    					            $homeWallpaperDetailModel->setCreatedAt ( Standard_Functions::getCurrentDateTime () );
    					            $homeWallpaperDetailModel->setLastUpdatedBy ( Standard_Functions::getCurrentUser ()->user_id );
    					            $homeWallpaperDetailModel->setLastUpdatedAt ( Standard_Functions::getCurrentDateTime () );
    					            $homeWallpaperDetailModel = $homeWallpaperDetailModel->save ();
    					            $homeWallpaperDetailId = $homeWallpaperDetailModel->getHomeWallpaperDetailId();
    					            $mapperResolution = new Admin_Model_Mapper_Resolution();
    					            $modelResolutions = $mapperResolution->fetchAll();
    					            foreach ($modelResolutions as $modelResolution){
    					                $resolution_id = $modelResolution->getResolutionId();
    					                $homeWallpaperImageModel = new HomeWallpaper_Model_HomeWallpaperImage();
    					                $homeWallpaperImageModel->setHomeWallpaperDetailId($homeWallpaperDetailId);
    					                $homeWallpaperImageModel->setResolutionId($resolution_id);
    					                $filename = $request->getParam ( "image_".$resolution_id."_path" );
    					                if(!$is_uploaded && $filename != "" && $filename != "deleted" ) {
    					                    // Move Uploaded Files
					                        $source_dir = Standard_Functions::getResourcePath () . "home-wallpaper/tmp/images/R".$resolution_id."/";
					                        $upload_dir = Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . $customerId."/R".$resolution_id."/";
					                        $filename = $this->moveUploadFile ( $source_dir , $upload_dir , $filename );
					                        $storedname[$resolution_id] = $filename;
    					                }elseif($storedname[$resolution_id] && $filename != "deleted"){
					                        $filename = $storedname[$resolution_id];
					                    }elseif($filename == "deleted"){
    					                    $filename = "";
    					                }elseif($allImages[$resolution_id]){
    					                    $filename = $allImages[$resolution_id];
    					                }
    					                $homeWallpaperImageModel->setImagePath($filename);
    					                $homeWallpaperImageModel->save();
    					            }
    					            $is_uploaded = true;
    					        }
					        }  
					    }
					} else {
						// Update homewallpaper record
						$homeWallpaperModel->setLastUpdatedBy ( $user_id );
						$homeWallpaperModel->setLastUpdatedAt ( $date_time );
						$homeWallpaperModel = $homeWallpaperModel->save ();
						
						$homeWallpaperDetailModel = new HomeWallpaper_Model_HomeWallpaperDetail ( $allFormValues );
						$homeWallpaperDetailModel->setCreatedBy ( $user_id );
						$homeWallpaperDetailModel->setCreatedAt ( $date_time );
						$homeWallpaperDetailModel->setLastUpdatedBy ( $user_id );
						$homeWallpaperDetailModel->setLastUpdatedAt ( $date_time );
						$homeWallpaperDetailModel = $homeWallpaperDetailModel->save ();
						
						if (! is_dir ( Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . $customerId )) {
							mkdir ( Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . $customerId, 755 );
						}
						$homeWallpaperDetailId = $homeWallpaperDetailModel->getHomeWallpaperDetailId();

						$mapperResolution = new Admin_Model_Mapper_Resolution();
						$modelResolution = $mapperResolution->fetchAll();
						foreach($modelResolution as $resoulution) {
							$resolution_id = $resoulution->getResolutionId();
							
							$filename = $request->getParam ( "image_".$resolution_id."_path" ,"");

							if($filename != "" && $filename != "deleted") {
								$source_dir = Standard_Functions::getResourcePath () . "home-wallpaper/tmp/images/R".$resolution_id."/";
								$upload_dir = Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . $customerId."/R".$resolution_id."/";
								$filename = $this->moveUploadFile ( $source_dir , $upload_dir , $filename );
							}

							$homeWallpaperImageModel = new HomeWallpaper_Model_HomeWallpaperImage();

							$homeWallpaperImageMapper = new HomeWallpaper_Model_Mapper_HomeWallpaperImage();
							$homeWallpaperImageModels = $homeWallpaperImageMapper->fetchAll("home_wallpaper_detail_id=".$homeWallpaperDetailId.
								" AND resolution_id =".$resolution_id);
							if(is_array($homeWallpaperImageModels)){
								$homeWallpaperImageModel = $homeWallpaperImageModels[0];
								if($filename != "" && $filename != "deleted") {
									$homeWallpaperImageModel->setHomeWallpaperDetailId($homeWallpaperDetailId);
									$homeWallpaperImageModel->setResolutionId($resolution_id);
									$homeWallpaperImageModel->setImagePath($filename);
									$homeWallpaperImageModel->save();
								} else if ($filename == "deleted"){
									$homeWallpaperImageModel->setImagePath("");
									$homeWallpaperImageModel->save();
								} 
							} else {
								$homeWallpaperImageModel->setHomeWallpaperDetailId($homeWallpaperDetailId);
								$homeWallpaperImageModel->setResolutionId($resolution_id);
								$homeWallpaperImageModel->setImagePath($filename);
								$homeWallpaperImageModel->save();
							}
						}
					}
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
					$customermodule = $customermoduleMapper->fetchAll("customer_id=". $customerId ." AND module_id=".$this->_module_id);
					if(is_array($customermodule)) {
						$customermodule = $customermodule[0];
						$customermodule->setIsPublish("NO");
						$customermodule->save();
					}
					
					$homeWallpaperMapper->getDbTable ()->getAdapter ()->commit ();
					
					if ($homeWallpaperModel && $homeWallpaperModel->getHomeWallpaperId () != "") {
						$response = array (
								"success" => $homeWallpaperModel->toArray () 
						);
					}
				} catch ( Exception $ex ) {
					$homeWallpaperMapper->getDbTable ()->getAdapter ()->rollBack ();
					$response = array (
							"errors" => $ex->getMessage () 
					);
				}
			} else {
				$errors = $form->getMessages ();
				foreach ( $errors as $name => $error ) {
					$errors [$name] = $error [0];
				}
				$response = array (
						"errors" => $errors 
				);
			}
		}
		// Send error or success message accordingly
		$this->_helper->json ( $response );
	}
	private function moveUploadFile($source_dir, $dest_dir, $filename) {
		$source_file_name = $filename;
		$expension = array_pop ( explode ( ".", $filename ) );
		try {
			$i = 1;
			while ( file_exists ( $dest_dir . $filename ) ) {
				$filename = str_replace ( "." . $expension, "_" . $i ++ . "." . $expension, $source_file_name );
			}
			if (! is_dir ( $dest_dir )) {
				mkdir ( $dest_dir, 755 );
			}
			while(!file_exists($source_dir . $source_file_name)) {}
			if (copy ( $source_dir . $source_file_name, $dest_dir . $filename )) {
				unlink ( $source_dir . $source_file_name );
			}
			$thumbname = str_replace ( "." . $expension, "_thumb." . $expension, $filename );
			$this->generateThumb ( $dest_dir . $filename, $dest_dir . $thumbname, 0, 75 );
		} catch ( Exception $ex ) {
			
		}
		return $filename;
	}
	public function generateThumb($src, $dest, $destWidth = 0, $destHeight = 0) {
		/* read the source image */
		$stype = array_pop ( explode ( ".", $src ) );
		switch (strtolower($stype)) {
			case 'gif' :
				$source_image = imagecreatefromgif ( $src );
				break;
			case 'jpg' :
			case 'jpeg' :
				$source_image = imagecreatefromjpeg ( $src );
				break;
			case 'png' :
				$source_image = imagecreatefrompng ( $src );
				break;
		}
		$width = imagesx ( $source_image );
		$height = imagesy ( $source_image );
		
		$desired_height = 0;
		$desired_width = 0;
		if ($destWidth == 0) {
			$desired_height = $destHeight;
			$desired_width = floor ( $width * ($destHeight / $height) );
		} else {
			$desired_height = floor ( $destHeight * ($destWidth / $width) );
			$desired_width = $destWidth;
		}
		
		/* create a new, "virtual" image */
		$virtual_image = imagecreatetruecolor ( $desired_width, $desired_height );
		imagealphablending($virtual_image, false);
		imagesavealpha($virtual_image, true);
		
		imagealphablending($source_image, true);
		/* copy source image at a resized size */
		imagecopyresampled ( $virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height );
		
		/* create the physical thumbnail image to its destination */
		switch (strtolower($stype)) {
			case 'gif' :
				imagegif ( $virtual_image, $dest );
				break;
			case 'jpg' :
			case 'jpeg' :
				imagejpeg ( $virtual_image, $dest );
				break;
			case 'png' :
				imagepng ( $virtual_image, $dest,0 );
				break;
		}
	}
	public function fileUplaod() {
		$form = new HomeWallpaper_Form_HomeWallpaper ();
		$request = $this->getRequest ();
		$response = array ();
		if ($request->getParam ( "upload", "" ) != "") {
			$element = $request->getParam ( "upload" );
			$upload_dir = Standard_Functions::getResourcePath () . "home-wallpaper/tmp/images/R" . str_ireplace ( "image_", "", $element );
			if (! is_dir ( $upload_dir )) {
				mkdir ( $upload_dir, 755 );
			}
			$adapter = new Zend_File_Transfer_Adapter_Http ();
			$adapter->setDestination ( $upload_dir );
			$adapter->receive ();
			
			if ($adapter->getFileName ( $element ) != "") {
				$response = array (
						"success" => array_pop ( explode ( '/', str_ireplace("\\","/",$adapter->getFileName ( $element )) ) ) 
				);
			} else {
				$response = array (
						"errors" => "Error Occured" 
				);
			}
			return $response;
		}
		return "";
	}
	public function deleteAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$request = $this->getRequest ();
		
		if (($homeWallpaperId = $request->getParam ( "id", "" )) != "") {
			$homeWallpaper = new HomeWallpaper_Model_HomeWallpaper ();
			$homeWallpaper->populate ( $homeWallpaperId );
			if ($homeWallpaper) {
				try {
					$homeWallpaperDetailMapper = new HomeWallpaper_Model_Mapper_HomeWallpaperDetail ();
					$homeWallpaperDetailModel = new HomeWallpaper_Model_HomeWallpaperDetail ();
					$homeWallpaperDetailMapper->getDbTable ()->getAdapter ()->beginTransaction ();
					$dataDetails = $homeWallpaperDetailMapper->fetchAll ( "home_wallpaper_id =" . $homeWallpaperId );
					foreach ( $dataDetails as $dataDetail ) {
						$model = $dataDetail->toArray();
						$homeWallpaperImageMapper = new HomeWallpaper_Model_Mapper_HomeWallpaperImage();
						$homeWallpaperImageModels = $homeWallpaperImageMapper->fetchAll("home_wallpaper_detail_id=".$dataDetail->getHomeWallpaperDetailId());
						foreach($homeWallpaperImageModels as $homeWallpaperImage) {
							$resolution_id = $homeWallpaperImage->getResolutionId();
							$image_dir = Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . Standard_Functions::getCurrentUser ()->customer_id . "/R" . $resolution_id."/";
							$ext = array_pop ( explode ( ".", $homeWallpaperImage->get("image_path") ));
							if ( file_exists ( $image_dir . str_replace ( "." . $ext, "_thumb." . $ext, $homeWallpaperImage->get("image_path") ) )) {
								unlink ( $image_dir . str_replace ( "." . $ext, "_thumb." . $ext, $homeWallpaperImage->get("image_path") ) );
								unlink ( $image_dir . $homeWallpaperImage->get("image_path") );
							}
							$homeWallpaperImage->delete();
						}
						$dataDetail->delete ();
					}
					
					$deletedRows = $homeWallpaper->delete ();
					
					// set is pulish to false
					$customerId = Standard_Functions::getCurrentUser ()->customer_id;
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
					$customermodule = $customermoduleMapper->fetchAll("customer_id=".$customerId." AND module_id=".$this->_module_id);
					if(is_array($customermodule)) {
						$customermodule = $customermodule[0];
						$customermodule->setIsPublish("NO");
						$customermodule->save();
					}
					
					$homeWallpaperDetailMapper->getDbTable ()->getAdapter ()->commit ();
					$response = array (
							"success" => array (
									"deleted_rows" => $deletedRows 
							) 
					);
				} catch ( Exception $e ) {
					$response = array (
							"errors" => array (
									"message" => $e->getMessage () 
							) 
					);
				}
			} else {
				$response = array (
						"errors" => array (
								"message" => "No wallpaper to delete." 
						) 
				);
			}
		} else {
			$this->_redirect ( '/' );
		}
		
		$this->_helper->json ( $response );
	}
	public function gridAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$homeWallpaperMapper = new HomeWallpaper_Model_Mapper_HomeWallpaper ();
		
		$select = $homeWallpaperMapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
								"h" => "module_home_wallpaper" 
								), array (
										"h.home_wallpaper_id" => "home_wallpaper_id",
										"h.status" => "status",
										"h.order" => "order",
								        "h.default" => "default"
								) )->joinLeft ( array (
										"hd" => "module_home_wallpaper_detail" 
								), "hd.home_wallpaper_id = h.home_wallpaper_id AND hd.language_id=" . $active_lang_id, array (
										"hd.image_title" => "image_title",
										"hd.home_wallpaper_detail_id" => "home_wallpaper_detail_id"
						) )->where ( "h.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id );
		
		$response = $homeWallpaperMapper->getGridData ( array (
							'column' => array (
									'id' => array (
											'actions' 
									),
									'replace' => array (
											'h.status' => array (
													'1' => $this->view->translate ( 'Active' ),
													'0' => $this->view->translate ( 'Inactive' ) 
											),
									        'h.default' => array(
									                '1' => $this->view->translate ('Home Screen'),
									                '0' => $this->view->translate ('Normal')
								            ) 
									),
									'ignore' => array('thumbnail')
							) 
					), "h.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id, $select );
		$customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
		
		$select = $customerLanguageMapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
									"l" => 'language'
							), array (
									"l.language_id" => "language_id",
									"l.title" => "title",
									"logo" => "logo" 
							) )->joinLeft ( array (
									"cl" => "customer_language" 
							), "l.language_id = cl.language_id", array (
									"cl.customer_id" 
						) )->where ( "cl.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id );
		$languages = $customerLanguageMapper->getDbTable ()->fetchAll ( $select )->toArray ();
		
		$rows = $response ['aaData'];
		foreach ( $rows as $rowId => $row ) {
		    $edit = array ();
			if($row [5] ["hd.home_wallpaper_detail_id"]=="") {
				$mapper = new HomeWallpaper_Model_Mapper_HomeWallpaperDetail();
				$details = $mapper->fetchAll("home_wallpaper_id=".$row [5] ["h.home_wallpaper_id"]." AND language_id=".$default_lang_id);
				if(is_array($details)) {
					$details = $details[0];
					$row[5]["hd.image_title"] = $row[1] = $details->getImageTitle();
				}
			}
			$response ['aaData'] [$rowId] = $row;
			if ($languages) {
				foreach ( $languages as $lang ) {
					$editUrl = $this->view->url ( array (
							"module" => "home-wallpaper",
							"controller" => "index",
							"action" => "edit",
							"id" => $row [5] ["h.home_wallpaper_id"],
							"lang" => $lang ["l.language_id"] 
					), "default", true );
					$edit [] = '<a href="' . $editUrl . '" ><img src="images/lang/' . $lang ["logo"] . '" alt="' . $lang ["l.title"] . '" /></a>';
				}
			}
			$model = $row [5];
			$response ['aaData'] [$rowId] [0] = "No Image Found";
			
			$homeWallpaperImageMapper = new HomeWallpaper_Model_Mapper_HomeWallpaperImage();
			if($row [5] ["hd.home_wallpaper_detail_id"] != null){
				$homeWallpaperImageModels = $homeWallpaperImageMapper->fetchAll("home_wallpaper_detail_id=".$row [5] ["hd.home_wallpaper_detail_id"]);
				foreach($homeWallpaperImageModels as $homeWallpaperImage) {
				$resolution_id = $homeWallpaperImage->getResolutionId();
				$img_uri = "resource/home-wallpaper/wallpapers/C" . Standard_Functions::getCurrentUser ()->customer_id . "/R".$resolution_id."/";
				$filename = $homeWallpaperImage->get("image_path");
					if(file_exists(Standard_Functions::getResourcePath () . "home-wallpaper/wallpapers/C" . Standard_Functions::getCurrentUser ()->customer_id ."/R".$resolution_id."/".$filename) && $filename != "") {
						$ext = array_pop(explode(".",$filename));
						$filename = str_replace(".".$ext, "_thumb.".$ext, $filename);
						$image_path = $this->view->baseUrl($img_uri ."/" . $filename);
						$response ['aaData'] [$rowId] [0] = "<img src='" .$image_path . "' title='" . $model ["hd.image_title"] . "' />";
						break;
					}
				}
			}
			
			$deleteUrl = $this->view->url ( array (
					"module" => "home-wallpaper",
					"controller" => "index",
					"action" => "delete",
					"id" => $row [5] ["h.home_wallpaper_id"] 
			), "default", true );
			$defaultEdit = '<div id="editLanguage">&nbsp;<div class="flag-list">'.implode("",$edit).'</div></div>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
			$sap = '';
			$response ['aaData'] [$rowId] [5] = $defaultEdit . $sap . $delete;
		}
		
		$jsonGrid = Zend_Json::encode ( $response );
		$this->_response->appendBody ( $jsonGrid );
	}
}