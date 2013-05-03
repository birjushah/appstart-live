<?php
class Events_IndexController extends Zend_Controller_Action
{
	var $_module_id;
	var $_customer_module_id;
    public function init()
    {
		/* Initialize action controller here */
    	$modulesMapper = new Admin_Model_Mapper_Module();
    	$module = $modulesMapper->fetchAll("name ='events'");
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
    	$image_dir = Standard_Functions::getResourcePath(). "events/preset-icons";
    	if(is_dir($image_dir)){
    	    $direc = opendir($image_dir);
    	    $iconpack = array();
    	    while($icon = readdir($direc)){
    	        if(is_file($image_dir."/".$icon) && getimagesize($image_dir."/".$icon)){
    	            $iconpack[] = $icon;
    	        }
    	    }
    	}
    	$this->_iconpack = $iconpack;
    }

    public function indexAction()
    {
        // action body
    	$this->view->addlink = $this->view->url ( array (
					    			"module" => "events",
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
    			"module" => "events",
    			"controller" => "index",
    			"action" => "reorder"
    	), "default", true );
    }
    
    public function addAction()
    {
    	// action body
    	$lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
    	$language = new Admin_Model_Mapper_Language();
    	$lang = $language->find($lang_id);
    	$this->view->language = $lang->getTitle();
    	 
    	$form = new Events_Form_Events();
    	foreach ( $form->getElements () as $element ) {
    		if ($element->getDecorator ( 'Label' ))
    			$element->getDecorator ( 'Label' )->setTag ( null );
    	}
    	$action = $this->view->url ( array (
    			"module" => "events",
    			"controller" => "index",
    			"action" => "save"
    	), "default", true );
    	$form->setAction($action);
    	$this->view->form = $form;
    	$this->view->image_path="";
    	$this->view->assign ( array (
    			"partial" => "index/partials/add.phtml"
    	) );
    	$this->view->iconpack = $this->_iconpack;
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
    		
    		$mapper = new Events_Model_Mapper_ModuleEvents();
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
	    		if($model && $model->getModuleEventsId()!="") {
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
    	
    	$mapper = new Events_Model_Mapper_ModuleEvents();
    	$select = $mapper->getDbTable ()->
					    	select ( false )->
					    	setIntegrityCheck ( false )->
					    	from ( array ("e" => "module_events"),
					    			array (
					    					"e.module_events_id" => "module_events_id",
					    					"e.status" => "status",
					    					"e.order" => "order"))->
    						joinLeft ( array ("ed" => "module_events_detail"),
    									"ed.module_events_id = e.module_events_id AND ed.language_id = ".$active_lang_id,
		    							array (
		    									"ed.module_events_detail_id" => "module_events_detail_id",
		    									"ed.title" => "title",
		    									"ed.description" => "description",
		    									"ed.start_date_time" => "start_date_time",
		    									"ed.end_date_time" => "end_date_time",
		    							))->
		    				where("e.customer_id=".$customer_id)->order("e.order");
    	$response = $mapper->getDbTable()->fetchAll($select)->toArray();
    	$rows = $response;
    	foreach ( $rows as $rowId => $row ) {
    		$dateTime = new DateTime($row ["ed.start_date_time"]);
    		$row["ed.start_date_time"] = $dateTime->format("d/m/Y H:i");
    		
    		$dateTime = new DateTime($row ["ed.end_date_time"]);
    		$row["ed.end_date_time"] = $dateTime->format("d/m/Y H:i");
    		
    		$response [$rowId] = $row;
    	}
    	$this->view->data = $response;
    }
    
    public function editAction()
    {
    	$form = new Events_Form_Events();
    	$request = $this->getRequest ();
    	if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
    		$events_id = $request->getParam ( "id", "" );
    		$lang_id = $request->getParam ( "lang", "" );
    		$language = new Admin_Model_Mapper_Language();
    		$lang = $language->find($lang_id);
    		$this->view->language = $lang->getTitle();
    	
    		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
    	
    		$mapper = new Events_Model_Mapper_ModuleEvents();
    		$data = $mapper->find ( $events_id )->toArray ();
    		$form->populate ( $data );
    	
    		$dataDetails = array();
    		$details = new Events_Model_Mapper_ModuleEventsDetail();
    	
    		if($details->countAll("module_events_id = ".$events_id." AND language_id = ".$lang_id) > 0) {
    			// Record For Language Found
    			$dataDetails = $details->getDbTable()->fetchAll("module_events_id = ".$events_id." AND language_id = ".$lang_id)->toArray();
    		} else {
    			// Record For Language Not Found
    			$dataDetails = $details->getDbTable()->fetchAll("module_events_id = ".$events_id." AND language_id = ".$default_lang_id)->toArray();
    			$default_event_detail_id = $dataDetails[0]["module_events_detail_id"];
    			$dataDetails[0]["module_events_detail_id"] = "";
    			$dataDetails[0]["language_id"] = $lang_id;
    		}
    		
    		if(isset($dataDetails[0]) && is_array($dataDetails[0])) {
    		    if($dataDetails[0]['icon'] != null){
    		        if(count(explode('/',$dataDetails[0]['icon'])) > 1){
    		            $this->view->icon_src = $dataDetails[0]['icon'];
    		        }else{
    		            $this->view->icon_src = "preset-icons/".$dataDetails[0]['icon'];
    		        }
    		    }
                $locationDetails = new Events_Model_Mapper_ModuleEventsLocation();
                if($dataDetails[0]["module_events_detail_id"] != ""){
                    $locations = $locationDetails->getDbTable()->fetchAll("module_events_detail_id =". $dataDetails[0]["module_events_detail_id"])->toArray();
                }else{
                    $locations = $locationDetails->getDbTable()->fetchAll("module_events_detail_id =". $default_event_detail_id)->toArray();
                }
                $location = array();
                if(is_array($locations)){
                    foreach($locations as $locations){
                        $temp = array(
                                'address' => $locations['address'],
                                'plz' => $locations['plz'],
                                'city' => $locations['city'],
                                'country' => $locations['country'],
                                'location' => $locations['location'],
                                'latitude' => $locations['latitude'],
                                'longitude' => $locations['longitude']
                        );
                        array_push($location, $temp);
                    }    
                }
                //print_r($dataDetails[0]);
                //die();
    			$form->populate ( $dataDetails[0] );
    			$this->view->location = json_encode($location);
                $this->view->image_path=$dataDetails[0]["image"];
    			$this->view->image_src=$this->view->baseUrl("resource/events/images/" . $dataDetails[0]["image"]);
    		}
    		foreach ( $form->getElements () as $element ) {
    			if ($element->getDecorator ( 'Label' ))
    				$element->getDecorator ( 'Label' )->setTag ( null );
    		}
    	
    		$action = $this->view->url ( array (
    				"module" => "events",
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
    	$this->view->iconpack = $this->_iconpack;
    	$this->render ( "add-edit" );
    }
    
    public function saveAction()
    {
    	// action body
    	$form = new Events_Form_Events();
    	$request = $this->getRequest ();
    	$response = array (); 
    	$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
    	if ($this->_request->isPost ()) {
    		if($request->getParam ( "upload", "" ) != "") {
    			$adapter = new Zend_File_Transfer_Adapter_Http();
    			$adapter->setDestination(Standard_Functions::getResourcePath(). "events/images");
    			$adapter->receive();
    			if($adapter->getFileName("image")!="")
    			{
    				$response = array (
    						"success" => array_pop(explode('/',$adapter->getFileName("image")))
    				);
    			} else {
    				$response = array (
    						"errors" => "Error Occured"
    				);
    			}
    			 
    			echo Zend_Json::encode($response);
    			exit;
    		}
    		if($request->getParam ( "iconupload", "" ) != "") {
    		    $adapter = new Zend_File_Transfer_Adapter_Http();
    		    $adapter->setDestination(Standard_Functions::getResourcePath(). "events/uploaded-icons");
    		    $adapter->receive();
    		    if($adapter->getFileName("icon")!="")
    		    {
    		        $response = array (
    		                "success" => array_pop(explode('/',$adapter->getFileName("icon")))
    		        );
    		    } else {
    		        $response = array (
    		                "errors" => "Error Occured"
    		        );
    		    }
    		
    		    echo Zend_Json::encode($response);
    		    exit;
    		}
    		$form->removeElement("image");
    		$form->removeElement("icon");
    		$allFlag = $this->_request->getParam("all",false);
    		if ($form->isValid ( $this->_request->getParams () )) {
    			
    			$mapper = new Events_Model_Mapper_ModuleEvents();
    			$mapper->getDbTable()->getAdapter()->beginTransaction();
    			
    			try {
    				$arrFormValues = $form->getValues();
                    if($arrFormValues["start_date_time"] != ""){
                        $start_date = DateTime::createFromFormat ( "d/m/Y H:i", $arrFormValues["start_date_time"] );
                        if($start_date){
                            $arrFormValues["start_date_time"] = $start_date->format ( "Y-m-d H:i:s" );
                        }
                    }else{
                        unset($arrFormValues["start_date_time"]);
                    }
                    if($arrFormValues["end_date_time"] != ""){    
                        $end_date = DateTime::createFromFormat ( "d/m/Y H:i", $arrFormValues["end_date_time"] );
                        if($end_date){
                            $arrFormValues["end_date_time"] = $end_date->format ( "Y-m-d H:i:s" ) ;
                        }
                    }else{ 
                        unset($arrFormValues["end_date_time"]);
                    }   
                    $customer_id = Standard_Functions::getCurrentUser ()->customer_id;
    				$user_id = Standard_Functions::getCurrentUser ()->user_id;
    				$date_time = Standard_Functions::getCurrentDateTime ();
    				$image_path = $request->getParam ("image_path", "");
    				$model = new Events_Model_ModuleEvents($arrFormValues);
    				if($request->getParam("selLogo","0")){
    				    $selIcon = $request->getParam("selLogo","0");
    				}
    				$icon_path = $request->getParam("icon_path","");
    				if($selIcon != 0){
    				    $arrFormValues["icon"] = $selIcon;
    				}elseif ($icon_path != ""){
    				    $arrFormValues["icon"] = "uploaded-icons/".$icon_path;
    				}
    				if($request->getParam ( "module_events_id", "" ) == "") {
    					// Add New Event
    					$maxOrder = $mapper->getNextOrder($customer_id);
    					
    					$model->setOrder($maxOrder+1);
    					$model->setCustomerId ( $customer_id );
    					$model->setCreatedBy ( $user_id );
    					$model->setCreatedAt ( $date_time );
    					$model->setLastUpdatedBy ( $user_id );
    					$model->setLastUpdatedAt ( $date_time );
    					$model = $model->save ();
    					
    					// Save Events Details
    					
    					$moduleEventsId = $model->getModuleEventsId();
    					$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage();
    					$modelLanguages = $mapperLanguage->fetchAll("customer_id = ".$customer_id);
    					if(is_array($modelLanguages)) {
    						foreach($modelLanguages as $languages) {
    							$modelDetails = new Events_Model_ModuleEventsDetail($arrFormValues);
    							$modelDetails->setModuleEventsId($moduleEventsId);
    							$modelDetails->setLanguageId($languages->getLanguageId());
    							
    							if($image_path != "")
    							{
    								$modelDetails->setImage($image_path);
    							}
    							 
    							$modelDetails->setCreatedBy ( $user_id );
    							$modelDetails->setCreatedAt ( $date_time );
    							$modelDetails->setLastUpdatedBy ( $user_id );
    							$modelDetails->setLastUpdatedAt ( $date_time );
    							$modelDetails = $modelDetails->save();
                                //Adding locations
                                $detail_id = $modelDetails->get("module_events_detail_id");
                                $location = array();
                                for($i=0;$i<count($arrFormValues[address]);$i++){
                                    $tempLocationArray = array(
                                            'location' => $arrFormValues[location][$i],
                                            'address' => $arrFormValues[address][$i],
                                            'plz' => $arrFormValues[plz][$i],
                                            'city' => $arrFormValues[city][$i],
                                            'country' => $arrFormValues[country][$i],
                                            'latitude' => $arrFormValues[latitude][$i],
                                            'longitude' => $arrFormValues[longitude][$i]
                                        );
                                        array_push($location,$tempLocationArray);
                                }
                                foreach($location as $location){
                                    $modelLocation = new Events_Model_ModuleEventsLocation();
                                    $modelLocation->setModuleEventsDetailId($detail_id);
                                    $modelLocation->setAddress($location['address']);
                                    $modelLocation->setPlz($location['plz']);
                                    $modelLocation->setCity($location['city']);
                                    $modelLocation->setCountry($location['country']);
                                    $modelLocation->setLocation($location['location']);
                                    $modelLocation->setLatitude($location['latitude']);
                                    $modelLocation->setLongitude($location['longitude']);   
                                    $modelLocation->save();
                                }
    						}
    					}
    				}elseif($allFlag){
    				    $locationMapper = new Events_Model_Mapper_ModuleEventsLocation();
    				    $eventDetailMapper = new Events_Model_Mapper_ModuleEventsDetail();
    				    $eventDetails = $eventDetailMapper->fetchAll("module_events_id =".$arrFormValues['module_events_id']);
    				    foreach ($eventDetails as $eventDetail){
    				        $locations = $locationMapper->fetchAll("module_events_detail_id =".$eventDetail->getModuleEventsDetailId());
    				        foreach ($locations as $location){
    				            $location->delete();
    				        }
    				    }
    				    $model->setLastUpdatedBy ( $user_id );
    				    $model->setLastUpdatedAt ( $date_time);
    				    $model = $model->save ();
    				    $customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
    				    $customerLanguageModel = $customerLanguageMapper->fetchAll ( "customer_id = " . $customer_id );
    				    $eventdetailMapper = new Events_Model_Mapper_ModuleEventsDetail();
    				    $eventdetails = $eventdetailMapper->getDbTable()->fetchAll("module_events_id =".$arrFormValues["module_events_id"])->toArray();
    				    if($arrFormValues["module_events_detail_id"] != null){
    				        $currentevents = $eventdetailMapper->getDbTable()->fetchAll("module_events_detail_id =".$arrFormValues["module_events_detail_id"])->toArray();
    				    }else{
    				        $currentevents = $eventdetailMapper->getDbTable()->fetchAll("module_events_id ='".$arrFormValues["module_events_id"]."' AND language_id =".$default_lang_id)->toArray();
    				    }
    				    if(is_array($currentevents)){
    				        $arrFormValues['image'] = $currentevents[0]["image"];
    				        if(!$arrFormValues['icon']){
    				            $arrFormValues['icon'] = $currentevents[0]['icon'];
    				        } 
    				    }
    				    unset($arrFormValues['module_events_detail_id'],$arrFormValues['language_id']);
    				    if(count($eventdetails) == count($customerLanguageModel)){
    				        foreach ($eventdetails as $eventdetail) {
    				            $eventdetail = array_intersect_key($arrFormValues + $eventdetail, $eventdetail);
    				            $eventDetailModel = new Events_Model_ModuleEventsDetail($eventdetail);
    				            if($image_path != ""){
    								$eventDetailModel->setImage($image_path);
    							}
    				            $eventDetailModel = $eventDetailModel->save();
    				            //Updating locations
    				            $detail_id = $eventDetailModel->get("module_events_detail_id");
    				            $location = array();
    				            for($i=0;$i<count($arrFormValues[address]);$i++){
    				                $tempLocationArray = array(
    				                        'location' => $arrFormValues[location][$i],
    				                        'address' => $arrFormValues[address][$i],
    				                        'plz' => $arrFormValues[plz][$i],
    				                        'city' => $arrFormValues[city][$i],
    				                        'country' => $arrFormValues[country][$i],
    				                        'latitude' => $arrFormValues[latitude][$i],
    				                        'longitude' => $arrFormValues[longitude][$i]
    				                );
    				                array_push($location,$tempLocationArray);
    				            }
    				            $modelLocation = new Events_Model_ModuleEventsLocation();
    				            foreach($location as $location){
    				                $modelLocation = new Events_Model_ModuleEventsLocation();
    				                $modelLocation->setModuleEventsDetailId($detail_id);
    				                $modelLocation->setAddress($location['address']);
    				                $modelLocation->setPlz($location['plz']);
    				                $modelLocation->setCity($location['city']);
    				                $modelLocation->setCountry($location['country']);
    				                $modelLocation->setLocation($location['location']);
    				                $modelLocation->setLatitude($location['latitude']);
    				                $modelLocation->setLongitude($location['longitude']);
    				                $modelLocation->save();
    				            }
    				        }
    				    }else{
    				        $eventDetailMapper = new Events_Model_Mapper_ModuleEventsDetail();
    				        $eventDetails = $eventDetailMapper->fetchAll("module_events_id =".$arrFormValues['module_events_id']);
    				        foreach ($eventDetails as $eventDetail){
    				            $eventDetail->delete();
    				        }
    				        if (is_array ( $customerLanguageModel )) {
    				            $is_uploaded_image = false;
    				            foreach ( $customerLanguageModel as $languages ) {
    				                $eventDetailModel = new Events_Model_ModuleEventsDetail($arrFormValues);
    				                $eventDetailModel->setLanguageId ( $languages->getLanguageId () );
    				                if($image_path != ""){
    								    $eventDetailModel->setImage($image_path);
    							    }
    				                $eventDetailModel->setCreatedBy ( $user_id );
    				                $eventDetailModel->setCreatedAt ( $date_time );
    				                $eventDetailModel->setLastUpdatedBy ( $user_id );
    				                $eventDetailModel->setLastUpdatedAt ( $date_time );
    				                $eventDetailModel = $eventDetailModel->save ();
    				                $detail_id = $eventDetailModel->get("module_events_detail_id");
    				                $location = array();
    				                for($i=0;$i<count($arrFormValues[address]);$i++){
    				                    $tempLocationArray = array(
    				                            'location' => $arrFormValues[location][$i],
    				                            'address' => $arrFormValues[address][$i],
    				                            'plz' => $arrFormValues[plz][$i],
    				                            'city' => $arrFormValues[city][$i],
    				                            'country' => $arrFormValues[country][$i],
    				                            'latitude' => $arrFormValues[latitude][$i],
    				                            'longitude' => $arrFormValues[longitude][$i]
    				                    );
    				                    array_push($location,$tempLocationArray);
    				                }
    				                foreach($location as $location){
    				                    $modelLocation = new Events_Model_ModuleEventsLocation();
    				                    $modelLocation->setModuleEventsDetailId($detail_id);
    				                    $modelLocation->setAddress($location['address']);
    				                    $modelLocation->setPlz($location['plz']);
    				                    $modelLocation->setCity($location['city']);
    				                    $modelLocation->setCountry($location['country']);
    				                    $modelLocation->setLocation($location['location']);
    				                    $modelLocation->setLatitude($location['latitude']);
    				                    $modelLocation->setLongitude($location['longitude']);
    				                    $modelLocation->save();
    				                }
    				            }
    				        }
    				    }
    				} else {
    					// Edit Event
    					$model->setLastUpdatedBy ( $user_id );
    					$model->setLastUpdatedAt ( $date_time);
    					$model = $model->save ();
    					
    					// Save Events Details
    					
    					$modelDetails = new Events_Model_ModuleEventsDetail($arrFormValues);
                        $detail_id = $modelDetails->get('module_events_detail_id');
                        $mapperLocation = new Events_Model_Mapper_ModuleEventsLocation();
                        $existingLocations = $mapperLocation->fetchAll("module_events_detail_id =".$detail_id);
                        if(is_array($existingLocations)){
                            foreach ($existingLocations as $existingLocation) {
                                $existingLocation->delete();
                            }
                        }
    					if($image_path != "")
    					{
    						$modelDetails->setImage($image_path);
    					}
    					if(!$modelDetails || $modelDetails->getModuleEventsDetailId()=="") {
    						$modelDetails->setCreatedBy ( $user_id );
    						$modelDetails->setCreatedAt ( $date_time );
    					}
    					$modelDetails->setLastUpdatedBy ( $user_id );
    					$modelDetails->setLastUpdatedAt ( $date_time );
    					$modelDetails = $modelDetails->save();
                        $modelLocation = new Events_Model_ModuleEventsLocation();
                        $location = array();
                        for($i=0;$i<count($arrFormValues[address]);$i++){
                            $tempLocationArray = array(
                                    'location' => $arrFormValues[location][$i],
                                    'address' => $arrFormValues[address][$i],
                                    'plz' => $arrFormValues[plz][$i],
                                    'city' => $arrFormValues[city][$i],
                                    'country' => $arrFormValues[country][$i],
                                    'latitude' => $arrFormValues[latitude][$i],
                                    'longitude' => $arrFormValues[longitude][$i]
                                );
                                array_push($location,$tempLocationArray);
                        }
                        foreach($location as $location){
                            $modelLocation = new Events_Model_ModuleEventsLocation();
                            $modelLocation->setModuleEventsDetailId($detail_id);
                            $modelLocation->setAddress($location['address']);
                            $modelLocation->setPlz($location['plz']);
                            $modelLocation->setCity($location['city']);
                            $modelLocation->setCountry($location['country']);
                            $modelLocation->setLocation($location['location']);
                            $modelLocation->setLatitude($location['latitude']);
                            $modelLocation->setLongitude($location['longitude']);   
                            $modelLocation->save();
                        }
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
    				if($model && $model->getModuleEventsId()!="") {
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
    
    	if (($eventsId = $request->getParam ( "id", "" )) != "") {
    		$events = new Events_Model_ModuleEvents();
    		$events->populate($eventsId);
    		if($events) {
    			$mapper = new Events_Model_Mapper_ModuleEvents();
    			$mapper->getDbTable()->getAdapter()->beginTransaction();
    			try {
                    $detailsMapper = new Events_Model_Mapper_ModuleEventsDetail();
    				$locationMapper = new Events_Model_Mapper_ModuleEventsLocation();
                    $details = $detailsMapper->fetchAll("module_events_id=".$events->getModuleEventsId());
                    if(is_array($details)) {
    					foreach($details as $eventDetail) {
                            $detail_id = $eventDetail->get("module_events_detail_id");
    						$locations = $locationMapper->fetchAll("module_events_detail_id =".$detail_id);
                            if(is_array($locations)){
                                foreach ($locations as $location) {
                                $location->delete();
                                }
                            }
                            $eventDetail->delete();
    					}
    				}
    				
    				$deletedRows = $events->delete ();
    				
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
    							"message" => "No events to delete."
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
    	$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
    	
    	$mapper = new Events_Model_Mapper_ModuleEvents();
    	 
    	$select = $mapper->getDbTable ()->
					    	select ( false )->
					    	setIntegrityCheck ( false )->
					    	from ( array ("e" => "module_events"),
					    			array (
					    					"e.module_events_id" => "module_events_id",
					    					"e.status" => "status",
					    					"e.order" => "order"))->
    						joinLeft ( array ("ed" => "module_events_detail"),
    									"ed.module_events_id = e.module_events_id AND ed.language_id = ".$active_lang_id,
		    							array (
		    									"ed.module_events_detail_id" => "module_events_detail_id",
		    									"ed.title" => "title",
		    									"ed.start_date_time" => "start_date_time",
		    									"ed.end_date_time" => "end_date_time",
		    							))->
		    				where("e.customer_id=".$customer_id)->order("e.order");
    	$response = $mapper->getGridData ( array (
    									'column' => array (
    											'id' => array (
    													'actions'
    											),
    											'replace' => array (
    													'e.status' => array (
    															'1' => $this->view->translate('Active'),
    															'0' => $this->view->translate('Inactive')
    													)
    											)
    									)
    							),"customer_id=".Standard_Functions::getCurrentUser ()->customer_id, $select );
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
    									 
    	$rows = $response ['aaData'];
    	foreach ( $rows as $rowId => $row ) {
    		$edit = array();
    		if($row [5] ["ed.module_events_detail_id"]=="") {
    			$mapper = new Events_Model_Mapper_ModuleEventsDetail();
    			$details = $mapper->fetchAll("module_events_id=".$row [5] ["e.module_events_id"]." AND language_id=".$default_lang_id);
    			if(is_array($details)) {
    				$details = $details[0];
    				$row [5] ["ed.title"] = $row[0] = $details->getTitle();
    				$row [5] ["ed.start_date_time"] = $row[1] = $details->getStartDateTime();
    				$row [5] ["ed.end_date_time"] = $row[2] = $details->getEndDateTime();
    			}
    		}
            if($row [5] ["ed.start_date_time"] != ""){
                $dateTime = new DateTime($row [5] ["ed.start_date_time"]);
                $row [5] ["ed.start_date_time"] = $row[1] = $dateTime->format("d/m/Y H:i");
            }
            if($row [5] ["ed.end_date_time"] != ""){
                $dateTime = new DateTime($row [5] ["ed.end_date_time"]);
                $row [5] ["ed.end_date_time"] = $row[2] = $dateTime->format("d/m/Y H:i");
            }   		
    		$response ['aaData'] [$rowId] = $row;
    		if($languages) {
    			foreach ($languages as $lang) {
    				$editUrl = $this->view->url ( array (
    												"module" => "events",
    												"controller" => "index",
    												"action" => "edit",
    												"id" => $row [5] ["e.module_events_id"],
    												"lang" => $lang["l.language_id"]
    										), "default", true );
    				$edit[] = '<a href="'. $editUrl .'"><img src="images/lang/'.$lang["logo"].'" alt="'.$lang["l.title"].'" /></a>';
    			}
    		}
    		$deleteUrl = $this->view->url ( array (
    										"module" => "events",
    										"controller" => "index",
    										"action" => "delete",
    										"id" => $row [5] ["e.module_events_id"]
    									), "default", true );
    		$defaultEdit = '<div id="editLanguage">&nbsp;<div class="flag-list">'.implode("",$edit).'</div></div>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
       		$sap = '';
    							 
    		$response ['aaData'] [$rowId] [5] = $defaultEdit. $sap .$delete;
    	}
    	$jsonGrid = Zend_Json::encode ( $response );
    	$this->_response->appendBody ( $jsonGrid );
    }
    public function loadContactsAction(){
        $active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
        $customer_id = Standard_Functions::getCurrentUser ()->customer_id;
        $contactMapper = new Contact_Model_Mapper_Contact();
        $data = $contactMapper->fetchAll("customer_id =" .$customer_id);
        $contactDetailMapper = new Contact_Model_Mapper_ContactDetail();
        $output = '<input class="button importLoadedContacts" type="submit" value="Import" name="submit" style="float:right">';
        $output .= '<table style="border-spacing:0;border-collapse:collapse;width:100%" class="pattern-style-b" id="dataGridReorder">';
        $output .= '<tr>';
        $output .= '<th></th>';
        $output .= '<th>Select All<br/><input type="checkbox" id="chkSelectAll" onclick="selectAll();" /></th>';
        $output .= '<th>Location</th>';
        $output .= '<th>Address</th>';
        $output .= '<th>City</th>';
        $output .= '<th>Country</th>';
        $output .= '<th>Plz</th>';
        $output .= '<th>Latitude</th>';
        $output .= '<th>Longitude</th>';
        $output .= '</tr>';
        if ($data) {
            $i = 0;
            foreach ( $data as $contact) {
                $dataDetails = $contactDetailMapper->fetchAll("language_id ='" .$active_lang_id. "'AND contact_id =" .$contact->getContactId());
                foreach($dataDetails as $key=>$value){
                    $fetchedContacts[$key] = $value;
                    $record[$i]['address'] = $fetchedContacts[$key]->getAddress();
                    $record[$i]['city'] = $fetchedContacts[$key]->getCity();
                    $record[$i]['country'] = $fetchedContacts[$key]->getCountry();
                    $record[$i]['plz'] = $fetchedContacts[$key]->getPlz();
                    $record[$i]['location'] = $fetchedContacts[$key]->getLocation();
                    $record[$i]['latitude'] = $fetchedContacts[$key]->getLatitude();
                    $record[$i]['longitude'] = $fetchedContacts[$key]->getLongitude();
                }
                $i++;
            }
            $i = 1;
            foreach ($record as $record) {
                $output .= '<tr>';
                $output .= '<td>'.$i.'</td>';
                $output .= '<td><input type="checkbox" name="contact[]" class="contact" value="1"/></td>';
                $output .= '<td>';
                $output .= $record['location'];
                $output .= '</td>';
                $output .= '<td>';
                $output .= $record['address'];
                $output .= '</td>';
                $output .= '<td>';
                $output .= $record['city'];
                $output .= '</td>';
                $output .= '<td>';
                $output .= $record['country'];
                $output .= '</td>';
                $output .= '<td>';
                $output .= $record['plz'];
                $output .= '</td>';
                $output .= '<td>';
                $output .= $record['latitude'];
                $output .= '</td>';
                $output .= '<td>';
                $output .= $record['longitude'];
                $output .= '</td>';
                $output .= '</tr>';
                $i++;
            }
            $output .= '</table>';
            echo $output;
            //echo json_encode($record);
            die();
        }else{
            $output .= '<tr>';
            $output .= '<td collspan="5">No locations found</td>';
            $output .= '</tr>';
            $output .= '</table>';
            echo $output;
            //echo json_encode($record);
            die();
        }
    }
}