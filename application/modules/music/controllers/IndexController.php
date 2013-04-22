<?php
class Music_IndexController extends Zend_Controller_Action
{
	var $_module_id;
	var $_customer_module_id;
    public function init()
    {
		/* Initialize action controller here */
    	$modulesMapper = new Admin_Model_Mapper_Module();
    	$module = $modulesMapper->fetchAll("name ='music'");
    	if(is_array($module)) {
    		$this->_module_id = $module[0]->getModuleId();
    	}
    	$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
    	$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
    	$customermodule = $customermoduleMapper->fetchAll("customer_id=". $customer_id ." AND module_id=".$this->_module_id);
    	if(is_array($customermodule)) {
    	    $customermodule = $customermodule[0];
    	    $this->_customer_module_id = $customermodule->getCustomerModuleId();
    	}
    }

    public function indexAction()
    {
    	$this->view->addlink = $this->view->url ( array (
						    			"module" => "music",
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
						    			"module" => "music",
						    			"controller" => "index",
						    			"action" => "reorder"
						    	), "default", true );
    }
    
    public function addAction()
    {
    	$lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
    	$language = new Admin_Model_Mapper_Language();
    	$lang = $language->find($lang_id);
    	$this->view->language = $lang->getTitle();
    	
    	$form = new Music_Form_Music();
    	foreach ( $form->getElements () as $element ) {
    		if ($element->getDecorator ( 'Label' ))
    			$element->getDecorator ( 'Label' )->setTag ( null );
    	}
    	
    	$action = $this->view->url ( array (
    			"module" => "music",
    			"controller" => "index",
    			"action" => "save"
    	), "default", true );
    	$form->setAction($action);
    	
    	$this->view->preview = "";
    	$this->view->album_art_url= "";
    	
    	$this->view->form = $form;
    	$this->view->assign ( array (
    			"partial" => "index/partials/add.phtml"
    	) );
    	$this->render ( "add-edit" );
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
    	
    		$mapper = new Music_Model_Mapper_ModuleMusic();
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
    			if($model && $model->getModuleMusicId()!="") {
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
    	 
    	$mapper = new Music_Model_Mapper_ModuleMusic();
    	$select = $mapper->getDbTable ()->
				    	select ( false )->
				    	setIntegrityCheck ( false )->
				    	from ( array ("m" => "module_music"),
				    			array (
				    					"m.module_music_id" => "module_music_id",
				    					"m.status" => "status",
				    					"m.order" => "order"))->
    					joinLeft ( array ("md" => "module_music_detail"),
    							"md.module_music_id = m.module_music_id AND md.language_id = ".$active_lang_id,
    							array (
    									"md.module_music_detail_id" => "module_music_detail_id",
    									"md.title" => "title",
    									"md.album" => "album",
    									"md.artist" => "artist",
    					))->where("m.customer_id=".$customer_id)->order("m.order");
		$response = $mapper->getDbTable()->fetchAll($select)->toArray();
    	$this->view->data = $response;
    }
    
    public function editAction()
    {
    	$form = new Music_Form_Music();
    	$request = $this->getRequest ();
    	if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
    		$music_id = $request->getParam ( "id", "" );
    		$lang_id = $request->getParam ( "lang", "" );
    		$language = new Admin_Model_Mapper_Language();
    		$lang = $language->find($lang_id);
    		$this->view->language = $lang->getTitle();
    		 
    		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
    		 
    		$mapper = new Music_Model_Mapper_ModuleMusic();
    		$data = $mapper->find ( $music_id )->toArray ();
    		$form->populate ( $data );
    		 
    		$dataDetails = array();
    		$details = new Music_Model_Mapper_ModuleMusicDetail();
    		 
    		if($details->countAll("module_music_id = ".$music_id." AND language_id = ".$lang_id) > 0) {
    			// Record For Language Found
    			$dataDetails = $details->getDbTable()->fetchAll("module_music_id = ".$music_id." AND language_id = ".$lang_id)->toArray();
    		} else {
    			// Record For Language Not Found
    			$dataDetails = $details->getDbTable()->fetchAll("module_music_id = ".$music_id." AND language_id = ".$default_lang_id)->toArray();
    			$dataDetails[0]["module_music_detail_id"] = "";
    			$dataDetails[0]["language_id"] = $lang_id;
    		}
    		 
    		if(isset($dataDetails[0]) && is_array($dataDetails[0])) {
    			$form->populate ( $dataDetails[0] );
    			$this->view->preview = $dataDetails[0]["preview_url"];
    			$this->view->album_art = $dataDetails[0]["album_art_url"];
    		}
    	
    		foreach ( $form->getElements () as $element ) {
    			if ($element->getDecorator ( 'Label' ))
    				$element->getDecorator ( 'Label' )->setTag ( null );
    		}
    		 
    		$action = $this->view->url ( array (
    				"module" => "music",
    				"controller" => "index",
    				"action" => "save",
    				"id" => $request->getParam ( "id", "" )
    		), "default", true );
    		$form->setAction($action);
    	} else {
    		$this->_redirect ( '/' );
    	}
    	 
    	$this->view->form = $form;
    	$this->view->assign ( array (
    			"partial" => "index/partials/edit.phtml"
    	) );
    	$this->render ( "add-edit" );
    }
    
    public function saveAction()
    {
    	$form = new Music_Form_Music();
    	$request = $this->getRequest ();
    	$response = array ();
    	$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
    	if ($this->_request->isPost ()) {
    		if($request->getParam ( "upload", "" ) == "preview") {
    			$adapter = new Zend_File_Transfer_Adapter_Http();
    			$adapter->setDestination(Standard_Functions::getResourcePath(). "music/tracks");
    			
    			if($adapter->receive() && $adapter->getFileName("preview")!="")
    			{
    				$response = array (
    						"success" => array_pop(explode('/',$adapter->getFileName("preview")))
    				);
    			} else {
    				$response = array (
    						"errors" => "Error Occured"
    				);
    			}
    			
    			echo Zend_Json::encode($response);
    			exit;
    		}
    		if($request->getParam ( "upload", "" ) == "album_art") {
    			$adapter = new Zend_File_Transfer_Adapter_Http();
    			$adapter->setDestination(Standard_Functions::getResourcePath(). "music/images");
    			$adapter->receive();
    			if($adapter->getFileName("album_art")!="")
    			{
    				$response = array (
    						"success" => array_pop(explode('/',$adapter->getFileName("album_art")))
    				);
    			} else {
    				$response = array (
    						"errors" => "Error Occured"
    				);
    			}
    			
    			echo Zend_Json::encode($response);
    			exit;
    		}
    		$form->removeElement("preview");
    		$form->removeElement("album_art");
    		$allFlag = $this->_request->getParam("all",false);
    		if ($form->isValid ( $this->_request->getParams () )) {
    			$mapper = new Music_Model_Mapper_ModuleMusic();
    			$mapper->getDbTable()->getAdapter()->beginTransaction();
    			try {
    				$arrFormValues = $form->getValues();
    				$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
    				$user_id = Standard_Functions::getCurrentUser ()->user_id;
    				$date_time = Standard_Functions::getCurrentDateTime ();
    				$preview_url = $request->getParam ("preview_url", "");
    				$album_art_url = $request->getParam ("album_art_url", "");
    				
    				$model = new Music_Model_ModuleMusic($arrFormValues);
    				if($request->getParam ( "module_music_id", "" ) == "") {
    					$maxOrder = $mapper->getNextOrder($customer_id);
    						
    					$model->setOrder($maxOrder+1);
    					$model->setCustomerId ( $customer_id );
    					$model->setCreatedBy ( $user_id );
    					$model->setCreatedAt ( $date_time );
    					$model->setLastUpdatedBy ( $user_id );
    					$model->setLastUpdatedAt ( $date_time );
    					$model = $model->save ();
    					
    					$moduleMusicId = $model->getModuleMusicId();
    					$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage();
    					$modelLanguages = $mapperLanguage->fetchAll("customer_id = ".$customer_id);
    					if(is_array($modelLanguages)) {
    						foreach($modelLanguages as $languages) {
    							$modelDetails = new Music_Model_ModuleMusicDetail($arrFormValues);
    							$modelDetails->setModuleMusicId($moduleMusicId);
    							$modelDetails->setLanguageId($languages->getLanguageId());
    							
    							if($preview_url != "")
    							{
    								$modelDetails->setPreviewUrl($preview_url);
    							}
    							
    							if($album_art_url != "")
    							{
    								$modelDetails->setAlbumArtUrl($album_art_url);
    							}
    							
    							$modelDetails->setCreatedBy ( $user_id );
    							$modelDetails->setCreatedAt ( $date_time );
    							$modelDetails->setLastUpdatedBy ( $user_id );
    							$modelDetails->setLastUpdatedAt ( $date_time );
    							$modelDetails = $modelDetails->save();
    						}
    					}
    				}elseif($allFlag){
    				    $model->setLastUpdatedBy ( $user_id );
    				    $model->setLastUpdatedAt ( $date_time);
    				    $model = $model->save ();
    				    $customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
    				    $customerLanguageModel = $customerLanguageMapper->fetchAll ( "customer_id = " . $customer_id );
    				    $musicDetailMapper = new Music_Model_Mapper_ModuleMusicDetail();
    				    if($arrFormValues['module_music_detail_id'] != null){
    				        $currentMusicDetails = $musicDetailMapper->getDbTable()->fetchAll("module_music_detail_id =".$arrFormValues['module_music_detail_id'])->toArray();
    				    }else{
    				        $currentMusicDetails = $musicDetailMapper->getDbTable()->fetchAll("module_music_id ='".$arrFormValues['module_music_id']."' AND language_id =".$default_lang_id)->toArray();
    				    }
    				    if(is_array($currentMusicDetails)){
    				        $allFormValues['preview_url'] = $currentMusicDetails[0]['preview_url'];
    				        $allFormValues['album_art_url'] = $currentMusicDetails[0]['album_art_url'];
    				    }
    				    $musicDetails = $musicDetailMapper->getDbTable()->fetchAll("module_music_id =".$arrFormValues['module_music_id'])->toArray();
    				    unset($arrFormValues['module_music_detail_id'],$arrFormValues['language_id']);
    				    if(count($musicDetails) == count($customerLanguageModel)){
    				        foreach ($musicDetails as $musicDetail) {
    				            $musicDetail = array_intersect_key($arrFormValues + $musicDetail, $musicDetail);
    				            $musicDetailModel = new Music_Model_ModuleMusicDetail($musicDetail);
        				        if($preview_url != "")
            					{
            						$musicDetailModel->setPreviewUrl($preview_url);
            					}
            					
            					if($album_art_url != "")
            					{
            						$musicDetailModel->setAlbumArtUrl($album_art_url);
            					}
        				        $musicDetailModel = $musicDetailModel->save();
        				    }
    				   }else{
    				       $musicDetailMapper = new Music_Model_Mapper_ModuleMusicDetail();
    				       $musicDetails = $musicDetailMapper->fetchAll("module_music_id =".$arrFormValues['module_music_id']);
    				       foreach ($musicDetails as $musicDetail){
    				           $musicDetail->delete();
    				       }
    				       if (is_array ( $customerLanguageModel )) {
    				           foreach ( $customerLanguageModel as $languages ) {
    				               $musicDetailModel = new Music_Model_ModuleMusicDetail($arrFormValues);
    				               $musicDetailModel->setLanguageId ( $languages->getLanguageId () );
        				           if($preview_url != ""){
                						$musicDetailModel->setPreviewUrl($preview_url);
                				   }
                				   if($album_art_url != ""){
                						$musicDetailModel->setAlbumArtUrl($album_art_url);
                				   }
    				               $musicDetailModel->setCreatedBy ( $user_id );
    				               $musicDetailModel->setCreatedAt ( $date_time );
    				               $musicDetailModel->setLastUpdatedBy ( $user_id );
    				               $musicDetailModel->setLastUpdatedAt ( $date_time );
    				               $musicDetailModel = $musicDetailModel->save ();
    				           }
    				       }
    				   }
    				} else {
    					$model->setLastUpdatedBy ( $user_id );
    					$model->setLastUpdatedAt ( $date_time);
    					$model = $model->save ();
    					
    					// Save Music Details
    					$modelDetails = new Music_Model_ModuleMusicDetail($arrFormValues);
    					if($preview_url != "")
    					{
    						$modelDetails->setPreviewUrl($preview_url);
    					}
    					
    					if($album_art_url != "")
    					{
    						$modelDetails->setAlbumArtUrl($album_art_url);
    					}
    					
    					if(!$modelDetails || $modelDetails->getModuleMusicDetailId()=="") {
    						$modelDetails->setCreatedBy ( $user_id );
    						$modelDetails->setCreatedAt ( $date_time );
    					}
    					$modelDetails->setLastUpdatedBy ( $user_id );
    					$modelDetails->setLastUpdatedAt ( $date_time );
    					$modelDetails = $modelDetails->save();
    					
    				}
                    $customermoduleMapper = new Admin_Model_Mapper_CustomerModule ();
                    $customermodule = $customermoduleMapper->fetchAll ( "customer_id=" . $customer_id . " AND module_id=" . $this->_module_id );
                    if (is_array ( $customermodule )) {
                        $customermodule = $customermodule [0];
                        $customermodule->setIsPublish ( "NO" );
                        $customermodule->save ();
                    }
    				$mapper->getDbTable()->getAdapter()->commit();
    				if($model && $model->getModuleMusicId()!="") {
    					$response = array (
    							"success" => $model->toArray ()
    					);
    				}
    			} catch (Exception $ex) {
    				$response = array (
    						"errors" => $ex->getMessage()
    				);
    				try {
    					$mapper->getDbTable()->getAdapter()->rollBack();
    				} catch (Exception $e) {
    					
    				}
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
    	$this->_helper->json ( $response );
    }
    
    public function deleteAction() {
    	$this->_helper->layout ()->disableLayout ();
    	$this->_helper->viewRenderer->setNoRender ();
    	$request = $this->getRequest ();
    	
    	if (($musicId = $request->getParam ( "id", "" )) != "") {
    		$id = str_ireplace("track_id=", "", $musicId);
    		$id = explode("&",$id);
    		foreach($id  as $musicId)
    		{
	    		$music = new Music_Model_ModuleMusic();
	    		$music->populate($musicId);
	    		if($music) {
	    			$mapper = new Music_Model_Mapper_ModuleMusic();
	    			$mapper->getDbTable()->getAdapter()->beginTransaction();
	    			try {
	    				$detailsMapper = new Music_Model_Mapper_ModuleMusicDetail();
	    				$details = $detailsMapper->fetchAll("module_music_id=".$music->getModuleMusicId());
	    				if(is_array($details)) {
	    					foreach($details as $eventDetail) {
	    						$eventDetail->delete();
	    					}
	    				}
	    	
	    				$deletedRows = $music->delete ();
	    	
	    				// set is pulish to false
	    				$customerId = Standard_Functions::getCurrentUser ()->customer_id;
	    				$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
	    				$customermodule = $customermoduleMapper->fetchAll("customer_id=".$customerId." AND module_id=".$this->_module_id);
	    				if(is_array($customermodule)) {
	    					$customermodule = $customermodule[0];
	    					$customermodule->setIsPublish("NO");
	    					$customermodule->save();
	    				}
	    	
	    				$mapper->getDbTable()->getAdapter()->commit();
	    	
	    				$response = array (
	    						"success" => array (
	    								"deleted_rows" => $deletedRows
	    						)
	    				);
	    	
	    			} catch (Exception $ex) {
	    				$mapper->getDbTable()->getAdapter()->rollBack();
	    				$response = array (
	    						"errors" => array (
	    								"message" => $ex->getMessage ()
	    						)
	    				);
	    			}
	    		} else {
	    			$response = array (
	    					"errors" => array (
	    							"message" => "No track to delete."
	    					)
	    			);
	    		}
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
    	$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
    	 
    	$mapper = new Music_Model_Mapper_ModuleMusic();
    	
    	$select = $mapper->getDbTable ()->
				    	select ( false )->
				    	setIntegrityCheck ( false )->
				    	from ( array ("m" => "module_music"),
				    			array (
				    					"m.module_music_id" => "module_music_id",
				    					"m.status" => "status",
				    					"m.order" => "order"))->
    					joinLeft ( array ("md" => "module_music_detail"),
    							"md.module_music_id = m.module_music_id AND md.language_id = ".$active_lang_id,
    							array (
    									"md.module_music_detail_id" => "module_music_detail_id",
    									"md.title" => "title",
    									"md.album" => "album",
    									"md.artist" => "artist",
    									"md.track_url" => "track_url",
    									"md.preview_url" => "preview_url"
    					))->
    					where("m.customer_id=".$customer_id)->order("m.order");
		$response = $mapper->getGridData ( array (
    					'column' => array (
    						'id' => array (
    						'actions'
    					),
    					'replace' => array (
    						'm.status' => array (
    							'1' => $this->view->translate('Active'),
    							'0' => $this->view->translate('Inactive')
    						)
    					)
    				)),"customer_id=".Standard_Functions::getCurrentUser ()->customer_id, $select );
		$records = $response['aaData'];
    	$mapper = new Admin_Model_Mapper_CustomerLanguage();
    	
    	$select = $mapper->getDbTable ()->
    							select ( false )->
    							setIntegrityCheck ( false )->
    							from ( array ("l" => "language"), array (
    									"l.language_id" => "language_id",
    									"l.title" => "title",
    									"logo" => "logo") )->
    							joinLeft ( array ("cl" => "customer_language"), "l.language_id = cl.language_id",
    											array ("cl.customer_id") )->
    							where("cl.customer_id=".Standard_Functions::getCurrentUser ()->customer_id);
    	$languages = $mapper->getDbTable ()->fetchAll($select)->toArray();
    	$i=1;
    	$rows = $response ['aaData'];
    	foreach ( $rows as $rowId => $row ) {
    		$edit = array();
    		if($row [8] ["md.module_music_detail_id"]=="") {
    			$mapper = new Music_Model_Mapper_ModuleMusicDetail();
    			$details = $mapper->fetchAll("module_music_id=".$row [8] ["m.module_music_id"]." AND language_id=".$default_lang_id);
    			if(is_array($details)) {
    				$details = $details[0];
    				$row [8] ["md.title"] = $row[3] = $details->getTitle();
    				$row [8] ["md.album"] = $row[4] = $details->getAlbum();
    				$row [8] ["md.artist"] = $row[5] = $details->getArtist();
    			}
    		}
    		/* <img src="<?php echo $this->baseUrl('images/beatport.png'); ?>" alt="" height="25" />*/
    		
    		$response ['aaData'] [$rowId] = $row;
    		if($languages) {
    			foreach ($languages as $lang) {
    				$editUrl = $this->view->url ( array (
    					"module" => "music",
    					"controller" => "index",
    					"action" => "edit",
    					"id" => $row [8] ["m.module_music_id"],
    					"lang" => $lang["l.language_id"]
    				), "default", true );
    				$edit[] = '<a href="'. $editUrl .'"><img src="images/lang/'.$lang["logo"].'" alt="'.$lang["l.title"].'" /></a>';
    			}
    		}
    		$deleteUrl = $this->view->url ( array (
    						"module" => "music",
    						"controller" => "index",
    						"action" => "delete",
    						"id" => $row [8] ["m.module_music_id"]
    					), "default", true );
    		$defaultEdit = '<div id="editLanguage">&nbsp;<div class="flag-list">'.implode("",$edit).'</div></div>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
       		$sap = '';
    		$logo = "";
    		$ext = "mp3";
    		if(stripos($row [8] ["md.track_url"], "itunes") !== false ) {
    			$logo = '<img src="images/itunes.png" alt="" height="25" />';
    			$ext = "m4a";
    		} else if(stripos($row [8] ["md.preview_url"], "7digital") !== false ) {
    			$logo = '<img src="images/7digital.png" alt="" height="25" />';
    		} else if(stripos($row [8] ["md.track_url"], "soundcloud") !== false ) {
    			$logo = '<img src="images/soundcloud.png" alt="" height="25" />';
    			$row [8] ["md.preview_url"] = $row [8] ["md.preview_url"]."";
    		} else if(stripos($row [8] ["md.track_url"], "beatport") !== false ) {
    			$logo = '<img src="images/beatport.png" alt="" height="25" />';
    		} else {
    			$row [8] ["md.preview_url"] = "resource/music/tracks/".$row [8] ["md.preview_url"];
    		}
    		
    		$option = "<div class='ext'>".$ext."</div><div class='path'>".$row [8] ["md.preview_url"]."</div>";
    		$player = $option.'<div id="jquery_jplayer_'.($i).'" class="cp-jplayer"></div>'.
    					'<div id="cp_container_'.($i++).'" class="cp-container">
							<div class="cp-buffer-holder">
								<div class="cp-buffer-1"></div>
								<div class="cp-buffer-2"></div>
							</div>
							<div class="cp-progress-holder"> 
								<div class="cp-progress-1"></div>
								<div class="cp-progress-2"></div>
							</div>
							<div class="cp-circle-control"></div>
							<ul class="cp-controls">
								<li><a class="cp-play">play</a></li>
								<li><a class="cp-pause" style="display:none;">pause</a></li>
							</ul>
						</div>';
    		 
    		$response ['aaData'] [$rowId] [0] = "<input type='checkbox' name='track_id' class='track_id' value='".$row [8] ["m.module_music_id"]."' />";
    		$response ['aaData'] [$rowId] [1] = $player;
    		$response ['aaData'] [$rowId] [2] = $logo;
    		$response ['aaData'] [$rowId] [8] = $defaultEdit. $sap .$delete;
    	}
    	$jsonGrid = Zend_Json::encode ( $response );
    	$this->_response->appendBody ( $jsonGrid );
    }

	public function searchItunesAction() {
		$method = $this->_request->getParam("method",null);
		
		$output = '<table style="border-spacing:0;border-collapse:collapse;width:100%" class="pattern-style-b" id="dataGridReorder">';
		$output .= '<tr>';
		$output .= '<th></th>';
		$output .= '<th>Select All<br/><input type="checkbox" id="chkSelectAll" onclick="selectAll();" /></th>';
		$output .= '<th>Title</th>';
		$output .= '<th>Artist</th>';
		$output .= '<th>Album</th>';
		$output .= '</tr>';
		$response = array();
		if($method=="search") {
			$keyword = $this->_request->getParam("keyword",null);
			$country = $this->_request->getParam("country",null);
			
			$response = Standard_Plugin_Music_iTunes::search($keyword,array("media"=>"music","country"=>$country,"entity"=>"song"));
			
		} else {
			$keyword = $this->_request->getParam("url",null);
			$parts = explode("/id", $keyword);
			if(count($parts)>1) {
				$parts = explode("?", $parts[1]);
				if(count($parts)>0 && $parts[0] > 0) {
					$response = Standard_Plugin_Music_iTunes::lookup($parts[0],'id',array("media"=>"music","entity"=>"song"));
				}
			}
		}
		if(count($response)==0) {
			$output .= '<tr>';
			$output .= '<td collspan="5">No track found</td>';
			$output .= '</tr>';
		} else {
			$i=0;
			$namespace = new Zend_Session_Namespace("itunes");
			$namespace->response = $response;
			
			foreach ($response->results as $result) {
				if($result->wrapperType == "track") {
					$class = ($i%2 == 1)? "even" : "odd";
					$output .= '<tr class="'.$class.'">';
					$output .= '<td>'.($i+1).'</td>';
					$output .= '<td><input type="checkbox" name="track[]" class="track" value="'.$i++.'" /></td>';
					$output .= '<td>'.$result->trackCensoredName.'</td>';
					$output .= '<td>'.$result->artistName.'</td>';
					$output .= '<td>'. ((in_array("collectionCensoredName", get_class_vars(get_class($result))))? $result->collectionCensoredName : $result->trackCensoredName) .'</td>';
					$output .= '</tr>';
				}
			}
		}
		
		$output .= '</table>';
		echo $output;die;
	}
	
	public function searchSevenDigitalAction() {
		$method = $this->_request->getParam("method",null);
	
		$output = '<table style="border-spacing:0;border-collapse:collapse;width:100%" class="pattern-style-b" id="dataGridReorder">';
		$output .= '<tr>';
		$output .= '<th></th>';
		$output .= '<th>Select All<br/><input type="checkbox" id="chkSelectAll" onclick="selectAll();" /></th>';
		$output .= '<th>Title</th>';
		$output .= '<th>Artist</th>';
		$output .= '<th>Album</th>';
		$output .= '</tr>';
		$response = array();
		
		$keyword = $this->_request->getParam("keyword",null);
		$country = $this->_request->getParam("country",null);

		$response = Standard_Plugin_Music_SevenDigital::search($keyword,$country);
		
		$namespace = new Zend_Session_Namespace("seven_digital");
		$namespace->response = $response;
		
		if(count($response)==0) {
			$output .= '<tr>';
			$output .= '<td collspan="5">No track found</td>';
			$output .= '</tr>';
		} else {
			$i=0;
			foreach ($response->response->searchResults->searchResult as $result) {
				if($result->type == "track") {
					$class = ($i%2 == 1)? "even" : "odd";
					$output .= '<tr class="'.$class.'">';
					$output .= '<td>'.($i+1).'</td>';
					$output .= '<td><input type="checkbox" name="track[]" class="track" value="'.$i++.'" /></td>';
					$output .= '<td>'.$result->track->title.'</td>';
					$output .= '<td>'.$result->track->artist->name.'</td>';
					$output .= '<td>'. $result->track->release->title .'</td>';
					$output .= '</tr>';
				}
			}
		}
	
		$output .= '</table>';
		echo $output;die;
	}
	
	public function searchBeatportAction() {
		$method = $this->_request->getParam("method",null);
	
		$output = '<table style="border-spacing:0;border-collapse:collapse;width:100%" class="pattern-style-b" id="dataGridReorder">';
		$output .= '<tr>';
		$output .= '<th></th>';
		$output .= '<th>Select All<br/><input type="checkbox" id="chkSelectAll" onclick="selectAll();" /></th>';
		$output .= '<th>Title</th>';
		$output .= '<th>Artist</th>';
		$output .= '<th>Album</th>';
		$output .= '</tr>';
		$response = array();
	
		$keyword = $this->_request->getParam("keyword",null);
	
		$response = Standard_Plugin_Music_Beatport::search($keyword);
	
		
	
		if(count($response)==0) {
			$output .= '<tr>';
			$output .= '<td collspan="5">No track found</td>';
			$output .= '</tr>';
		} else {
			$i=0;
			$finalResponse = array();
			foreach ($response->response->result as $result)
			{
				foreach($result as $track)
				{
					if(isset($track->name) && isset($track->urlName)) {
						$finalResponse[$i] = $track;
						$class = ($i%2 == 1)? "even" : "odd";
						$output .= '<tr class="'.$class.'">';
						$output .= '<td>'.($i+1).'</td>';
						$output .= '<td><input type="checkbox" name="track[]" class="track" value="'.$i++.'" /></td>';
						$output .= '<td>'.$track->name.'</td>';
						$release = "";
						if(isset($track->release)) {
							if(is_array($track->release)) {
								foreach($track->release as $album) {
									$release .= (($release=="")?"":",").$album->name;
								}
							} else {
								$release = $track->release->name;
							}
						} else {
							$release = $track->name;
						}
						$artist = "";
						if(isset($track->performer)) {
							if(is_array($track->performer)) {
								foreach($track->performer as $performer) {
									$artist .= (($artist=="")?"":",").$performer->name;
								}
							} else {
								$artist = $track->performer->name;
							}
						}
						$output .= '<td>'.$artist.'</td>';
						$output .= '<td>'. $release .'</td>';
						$output .= '</tr>';
					}
				}
			}
			$namespace = new Zend_Session_Namespace("beatport");
			$namespace->response = $finalResponse;
		}
		
		$output .= '</table>';
		echo $output;die;
	}
	
	public function searchSoundcloudAction() {
		$method = $this->_request->getParam("method",null);
    
        $output = '<table style="border-spacing:0;border-collapse:collapse;width:100%" class="pattern-style-b" id="dataGridReorder">';
        $output .= '<tr>';
        $output .= '<th></th>';
        $output .= '<th>Select All<br/><input type="checkbox" id="chkSelectAll" onclick="selectAll();" /></th>';
        $output .= '<th>Title</th>';
        $output .= '<th>User</th>';
        $output .= '<th>Track Type</th>';
        $output .= '</tr>';
        $response = array();
    
        $keyword = $this->_request->getParam("keyword",null);
        
        $soundCloud = new Standard_Plugin_Music_Soundcloud();
        $response = $soundCloud->get("tracks",array("q" => $keyword,"limit" => 50));
        
        if(count($response)==0) {
            $output .= '<tr>';
            $output .= '<td collspan="5">No track found</td>';
            $output .= '</tr>';
        } else {
            $i=0;
            $finalResponse = array();
            foreach ($response->tracks->track as $track)
            {
                $finalResponse[$i] = $track;
                $class = ($i%2 == 1)? "even" : "odd";
                if(strlen($track->{"stream-url"})==0) continue;
                $output .= '<tr class="'.$class.'">';
                $output .= '<td>'.($i+1).'</td>';
                $output .= '<td><input type="checkbox" name="track[]" class="track" value="'.$i++.'" /></td>';
                $output .= '<td>'.$track->title.'</td>';
                $output .= '<td>'.$track->user->username.'</td>';
                $output .= '<td>'. $track->{"track-type"} .'</td>';
                $output .= '</tr>';
            }
            $namespace = new Zend_Session_Namespace("soundcloud");
            $namespace->response = $response;
        }
    
        $output .= '</table>';
        echo $output;die;
	}
	
	public function importItunesAction() {
		$namespace = new Zend_Session_Namespace("itunes");
		$responseItunes = $namespace->response;
		
		$track = $this->_request->getParam("track");
		$response = array ();
		
		$mapper = new Music_Model_Mapper_ModuleMusic();
		$mapper->getDbTable()->getAdapter()->beginTransaction();
		
		try {
			$customerId = Standard_Functions::getCurrentUser ()->customer_id;
			$user_id = Standard_Functions::getCurrentUser ()->user_id;
			$date_time = Standard_Functions::getCurrentDateTime ();
			
			foreach($track as $index) {
				$modelMusic = new Music_Model_ModuleMusic();
				$modelMusic->setCustomerId($customerId);
				$maxOrder = $mapper->getNextOrder ( $customerId );
				$modelMusic->setStatus ( 1 );
				$modelMusic->setOrder ( $maxOrder + 1 );
				$modelMusic->setCreatedBy ( Standard_Functions::getCurrentUser ()->user_id );
				$modelMusic->setCreatedAt ( Standard_Functions::getCurrentDateTime () );
				$modelMusic->setCustomerId ( $customerId );
				$modelMusic->setLastUpdatedBy ( Standard_Functions::getCurrentUser ()->user_id );
				$modelMusic->setLastUpdatedAt ( Standard_Functions::getCurrentDateTime () );
				$modelMusic = $modelMusic->save ();
				$music_id = $modelMusic->getModuleMusicId();
				
				$customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
				$customerLanguageModel = $customerLanguageMapper->fetchAll ( "customer_id = " . $customerId );
				if (is_array ( $customerLanguageModel )) {
					$trackDetails = $responseItunes->results[$index];
					
					$deatils = array();
					$deatils["title"] = $trackDetails->trackCensoredName;
					$deatils["artist"] = $trackDetails->artistName;
					$deatils["album"] = ((in_array("collectionCensoredName", get_class_vars(get_class($trackDetails))))? $trackDetails->collectionCensoredName : $trackDetails->trackCensoredName);
					$deatils["track_url"] = $trackDetails->trackViewUrl;
					$deatils["preview_url"] = $trackDetails->previewUrl;
					$deatils["album_art_url"] = $trackDetails->artworkUrl60;
					
					foreach ( $customerLanguageModel as $languages ) {
						$modelDetails = new Music_Model_ModuleMusicDetail($deatils);
						$modelDetails->setModuleMusicId ( $music_id );
						$modelDetails->setLanguageId ( $languages->getLanguageId () );
						$modelDetails->setCreatedBy ( Standard_Functions::getCurrentUser ()->user_id );
						$modelDetails->setCreatedAt ( Standard_Functions::getCurrentDateTime () );
						$modelDetails->setLastUpdatedBy ( Standard_Functions::getCurrentUser ()->user_id );
						$modelDetails->setLastUpdatedAt ( Standard_Functions::getCurrentDateTime () );
						$modelDetails = $modelDetails->save ();
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
				
			$mapper->getDbTable ()->getAdapter ()->commit ();
				
			$response = array (
					"success" => "true"
			);
		} catch(Exception $ex) {
			$mapper->getDbTable()->getAdapter()->rollBack();
			$response = array (
					"errors" => $ex->getMessage ()
			);
		}
		$this->_helper->json ( $response );
	}
	
	public function importSevenDigitalAction() {
		$namespace = new Zend_Session_Namespace("seven_digital");
		$responseSevenDigital = $namespace->response;
		$track = $this->_request->getParam("track");
		$response = array ();
		
		$mapper = new Music_Model_Mapper_ModuleMusic();
		$mapper->getDbTable()->getAdapter()->beginTransaction();
		
		try {
			$customerId = Standard_Functions::getCurrentUser ()->customer_id;
			$user_id = Standard_Functions::getCurrentUser ()->user_id;
			$date_time = Standard_Functions::getCurrentDateTime ();
			
			foreach($track as $index) {
				$modelMusic = new Music_Model_ModuleMusic();
				$modelMusic->setCustomerId($customerId);
				$maxOrder = $mapper->getNextOrder ( $customerId );
				$modelMusic->setStatus ( 1 );
				$modelMusic->setOrder ( $maxOrder + 1 );
				$modelMusic->setCreatedBy ( Standard_Functions::getCurrentUser ()->user_id );
				$modelMusic->setCreatedAt ( Standard_Functions::getCurrentDateTime () );
				$modelMusic->setCustomerId ( $customerId );
				$modelMusic->setLastUpdatedBy ( Standard_Functions::getCurrentUser ()->user_id );
				$modelMusic->setLastUpdatedAt ( Standard_Functions::getCurrentDateTime () );
				$modelMusic = $modelMusic->save ();
				$music_id = $modelMusic->getModuleMusicId();
				
				$customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
				$customerLanguageModel = $customerLanguageMapper->fetchAll ( "customer_id = " . $customerId );
				if (is_array ( $customerLanguageModel )) {
					$trackDetails = $responseSevenDigital->response->searchResults->searchResult[$index];
					$deatils = array();
					$deatils["title"] = $trackDetails->track->title;
					$deatils["artist"] = $trackDetails->track->artist->name;
					$deatils["album"] = $trackDetails->track->release->title;
					$deatils["track_url"] = $trackDetails->track->url;
					$preview = Standard_Plugin_Music_SevenDigital::preview($trackDetails->track->{"@attributes"}->id);
					$deatils["preview_url"] = $preview->response->url;
					$deatils["album_art_url"] = $trackDetails->track->release->image;
					
					foreach ( $customerLanguageModel as $languages ) {
						$modelDetails = new Music_Model_ModuleMusicDetail($deatils);
						$modelDetails->setModuleMusicId ( $music_id );
						$modelDetails->setLanguageId ( $languages->getLanguageId () );
						$modelDetails->setCreatedBy ( Standard_Functions::getCurrentUser ()->user_id );
						$modelDetails->setCreatedAt ( Standard_Functions::getCurrentDateTime () );
						$modelDetails->setLastUpdatedBy ( Standard_Functions::getCurrentUser ()->user_id );
						$modelDetails->setLastUpdatedAt ( Standard_Functions::getCurrentDateTime () );
						$modelDetails = $modelDetails->save ();
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
				
			$mapper->getDbTable ()->getAdapter ()->commit ();
				
			$response = array (
					"success" => "true"
			);
		} catch(Exception $ex) {
			$mapper->getDbTable()->getAdapter()->rollBack();
			$response = array (
					"errors" => $ex->getMessage ()
			);
		}
		$this->_helper->json ( $response );
	}
	
	public function importBeatportAction() {
		$namespace = new Zend_Session_Namespace("beatport");
		$responseBeatport = $namespace->response;
		$track = $this->_request->getParam("track");
		$response = array ();
	
		$mapper = new Music_Model_Mapper_ModuleMusic();
		$mapper->getDbTable()->getAdapter()->beginTransaction();
	
		try {
			$customerId = Standard_Functions::getCurrentUser ()->customer_id;
			$user_id = Standard_Functions::getCurrentUser ()->user_id;
			$date_time = Standard_Functions::getCurrentDateTime ();
				
			foreach($track as $index) {
				$modelMusic = new Music_Model_ModuleMusic();
				$modelMusic->setCustomerId($customerId);
				$maxOrder = $mapper->getNextOrder ( $customerId );
				$modelMusic->setStatus ( 1 );
				$modelMusic->setOrder ( $maxOrder + 1 );
				$modelMusic->setCreatedBy ( Standard_Functions::getCurrentUser ()->user_id );
				$modelMusic->setCreatedAt ( Standard_Functions::getCurrentDateTime () );
				$modelMusic->setCustomerId ( $customerId );
				$modelMusic->setLastUpdatedBy ( Standard_Functions::getCurrentUser ()->user_id );
				$modelMusic->setLastUpdatedAt ( Standard_Functions::getCurrentDateTime () );
				$modelMusic = $modelMusic->save ();
				$music_id = $modelMusic->getModuleMusicId();
	
				$customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
				$customerLanguageModel = $customerLanguageMapper->fetchAll ( "customer_id = " . $customerId );
				if (is_array ( $customerLanguageModel )) {
					$trackDetails = $responseBeatport[$index];
					$deatils = array();
					$deatils["title"] = $trackDetails->name;
					$artist = "";
					if(isset($trackDetails->performer)) {
						if(is_array($trackDetails->performer)) {
							foreach($trackDetails->performer as $performer) {
								$artist .= (($artist=="")?"":",").$performer->name;
							}
						} else {
							$artist = $trackDetails->performer->name;
						}
					}
					$deatils["artist"] = $artist;
					$release = "";
					if(isset($trackDetails->release)) {
						if(is_array($trackDetails->release)) {
							foreach($trackDetails->release as $album) {
								$release .= (($release=="")?"":",").$album->name;
							}
						} else {
							$release = $trackDetails->release->name;
						}
					} else {
						$release = $trackDetails->name;
					}
					$deatils["album"] = $release;
					$deatils["track_url"] = "http://www.beatport.com/track/".$trackDetails->urlName."/".$trackDetails->{"@attributes"}->id;
					$deatils["preview_url"] = $trackDetails->{"@attributes"}->url;
					$image = "";
					if(isset($trackDetails->image) && is_array($trackDetails->image)) {
						foreach($trackDetails->image as $image) {
							if($image->{"@attributes"}->ref == "default" && $image->{"@attributes"}->height == 60) {
								$image = $image->{"@attributes"}->url;
								break;
							}
						}
					}
					$deatils["album_art_url"] = $image;
						
					foreach ( $customerLanguageModel as $languages ) {
						$modelDetails = new Music_Model_ModuleMusicDetail($deatils);
						$modelDetails->setModuleMusicId ( $music_id );
						$modelDetails->setLanguageId ( $languages->getLanguageId () );
						$modelDetails->setCreatedBy ( Standard_Functions::getCurrentUser ()->user_id );
						$modelDetails->setCreatedAt ( Standard_Functions::getCurrentDateTime () );
						$modelDetails->setLastUpdatedBy ( Standard_Functions::getCurrentUser ()->user_id );
						$modelDetails->setLastUpdatedAt ( Standard_Functions::getCurrentDateTime () );
						$modelDetails = $modelDetails->save ();
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
	
			$mapper->getDbTable ()->getAdapter ()->commit ();
	
			$response = array (
					"success" => "true"
			);
		} catch(Exception $ex) {
			$mapper->getDbTable()->getAdapter()->rollBack();
			$response = array (
					"errors" => $ex->getMessage ()
			);
		}
		$this->_helper->json ( $response );
	}
	
	public function importSoundcloudAction() {
		$namespace = new Zend_Session_Namespace("soundcloud");
		$responseSoundcloud = $namespace->response;
		$track = $this->_request->getParam("track");
		$response = array ();
	
		$mapper = new Music_Model_Mapper_ModuleMusic();
		$mapper->getDbTable()->getAdapter()->beginTransaction();
	
		try {
			$customerId = Standard_Functions::getCurrentUser ()->customer_id;
			$user_id = Standard_Functions::getCurrentUser ()->user_id;
			$date_time = Standard_Functions::getCurrentDateTime ();
	
			foreach($track as $index) {
				$modelMusic = new Music_Model_ModuleMusic();
				$modelMusic->setCustomerId($customerId);
				$maxOrder = $mapper->getNextOrder ( $customerId );
				$modelMusic->setStatus ( 1 );
				$modelMusic->setOrder ( $maxOrder + 1 );
				$modelMusic->setCreatedBy ( Standard_Functions::getCurrentUser ()->user_id );
				$modelMusic->setCreatedAt ( Standard_Functions::getCurrentDateTime () );
				$modelMusic->setCustomerId ( $customerId );
				$modelMusic->setLastUpdatedBy ( Standard_Functions::getCurrentUser ()->user_id );
				$modelMusic->setLastUpdatedAt ( Standard_Functions::getCurrentDateTime () );
				$modelMusic = $modelMusic->save ();
				$music_id = $modelMusic->getModuleMusicId();
	
				$customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
				$customerLanguageModel = $customerLanguageMapper->fetchAll ( "customer_id = " . $customerId );
				if (is_array ( $customerLanguageModel )) {
					$trackDetails = $responseSoundcloud->tracks->track[$index];
					$deatils = array();
					$deatils["title"] = $trackDetails->title;
					$deatils["artist"] = $trackDetails->user->username;
					$deatils["album"] = "";
					$deatils["track_url"] = $trackDetails->{"permalink-url"};
					$deatils["preview_url"] = $trackDetails->{"stream-url"}."?client_id=e9d49c642a93447a3469437bfc92df02";
					$deatils["album_art_url"] = $trackDetails->{"artwork-url"};
	
					foreach ( $customerLanguageModel as $languages ) {
						$modelDetails = new Music_Model_ModuleMusicDetail($deatils);
						$modelDetails->setModuleMusicId ( $music_id );
						$modelDetails->setLanguageId ( $languages->getLanguageId () );
						$modelDetails->setCreatedBy ( Standard_Functions::getCurrentUser ()->user_id );
						$modelDetails->setCreatedAt ( Standard_Functions::getCurrentDateTime () );
						$modelDetails->setLastUpdatedBy ( Standard_Functions::getCurrentUser ()->user_id );
						$modelDetails->setLastUpdatedAt ( Standard_Functions::getCurrentDateTime () );
						$modelDetails = $modelDetails->save ();
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
	
			$mapper->getDbTable ()->getAdapter ()->commit ();
	
			$response = array (
					"success" => "true"
			);
		} catch(Exception $ex) {
			$mapper->getDbTable()->getAdapter()->rollBack();
			$response = array (
					"errors" => $ex->getMessage ()
			);
		}
		$this->_helper->json ( $response );
	}
}