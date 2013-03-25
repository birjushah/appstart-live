<?php
class Default_RestController extends Standard_Rest_Controller {
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::getAction()
	 */
	public function getAction() {
		// TODO Auto-generated method stub
		$service = $this->_request->getParam("service",null);
		if($service==null) {
			$this->_sendError("No service called");
		} else {
			if($service == "authenticate") {
				$this->_authenticate();
			} else if($service == "sync") {
				$this->_sync();
			}else if($service == "gcm") {
				$this->_gcm();
			} else {
				$this->_sendError("Invalid service");
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
	
	protected function _authenticate() {
		$appAccessID = $this->_request->getParam("app_access_id",null);
		$password = $this->_request->getParam("password",null);
		$response = array();
		if($appAccessID===null || $password === null) {
			$this->_sendError("Invalid request");
		} else {
			try{
				$mapper = new Admin_Model_Mapper_Customer();
				$customer = $mapper->fetchAll("app_access_id='".$appAccessID."' and app_password='".$password."'");
				if($customer) {
					$customer = $customer[0];
					/*$userMapper = new Default_Model_Mapper_User();
					$user = $userMapper->fetchAll("user_id='".$customer->getUserId()."' AND password=MD5(concat('".$password."',username))");
					if($user) {*/
							
						$data["customer_id"] = $customer->getCustomerId();
						$data["app_access_id"] = $customer->getCustomerId();
						$data["password"] = $password;
						$data["status"] = $customer->getStatus();
						
						$response["status"] = "success";
						$response["data"] = $data;
						$this->_sendData($response);
							
					/*} else {
						$this->_sendError("Invalid password");
					}*/
				} else {
					$this->_sendError("Invalid App Access ID Or Password");
				}
			} catch (Exception $ex) {
				$this->_sendError($ex->getMessage());
			}
		}
	}
	
	protected function _sync() {
		$customer_id = $this->_request->getParam("customer_id",null);
		$resolution_id = $this->_request->getParam("resolution",null);
		if($customer_id===null) {
			$this->_sendError("Invalid request");
		} else {
			try{
				if($resolution_id==null) {
					$response = array();
					$resolutionMapper = new Admin_Model_Mapper_Resolution();
					$resolution = $resolutionMapper->fetchAll();
					if($resolution) {
						foreach ($resolution as $res) {
							$response["tbl_resolution"][] = $res->toArray();
						}
					}
					$data["status"] = "success";
					$data["data"] = $response;
					$this->_sendData($data);
				} else {
					$mapper = new Admin_Model_Mapper_Customer();
					$customer = $mapper->find($customer_id);
					if($customer) {
						$response = array();
						$moduleMapper = new Admin_Model_Mapper_CustomerModule();
						$customerModule = $moduleMapper->fetchAll("customer_id=".$customer_id);
						if($customerModule) {
							foreach($customerModule as $module) {
								$details = array();
								$customerModuleDetailsMapper = new Admin_Model_Mapper_CustomerModuleDetail();
								$customerModuleDetails = $customerModuleDetailsMapper->fetchAll("customer_module_id=".$module->getCustomerModuleId());
								if($customerModuleDetails) {
									foreach($customerModuleDetails as $moduleDetail) {
										$tempDetail = $moduleDetail->toArray();
										if($tempDetail['background_type'] == 1){
											if($resolution_id > 0 && is_numeric($tempDetail["background_image"])){
												$homeWallpaperImageMapper = new HomeWallpaper_Model_Mapper_HomeWallpaperImage();
												$homeWallpaperImageModels = $homeWallpaperImageMapper->fetchAll("home_wallpaper_detail_id=".$tempDetail["background_image"]." AND resolution_id=".$resolution_id);
												if($homeWallpaperImageModels){
													if($homeWallpaperImageModels[0]->get("image_path") != ""){
														$tempDetail["background_image"] = "resource/home-wallpaper/wallpapers/C".$customer_id."/R".$resolution_id."/".$homeWallpaperImageModels[0]->get("image_path");
													}else{
														$tempDetail["background_image"] = "";	
													}
												}else{
													$tempDetail["background_image"] = "";	
												}
											}
										}elseif($tempDetail['background_type'] == 0){
											if($tempDetail['background_color'] != null){
												$tempDetail["background_color"] = "#".$tempDetail['background_color'];
											}else{
												$tempDetail["background_color"] = "";
											} 
										}else{
											$tempDetail["background_type"] =	 "";	
										}
										$details[] = $tempDetail;
									}
								}
								$module = $module->toArray();
								if(isset($module["icon"]) && $module["icon"]!="") {
									$module["icon"] = "resource/default/images/icon/".$module["icon"];
								} else {
									$module["icon"]= "";
								}
								
								$response["customer_module"][] = array("tbl_customer_module" => $module,
																			"tbl_customer_module_detail" => $details);
							}
						}
											
						$configMapper = new Admin_Model_Mapper_CustomerConfiguration();
						$customerConfig = $configMapper->fetchAll("customer_id=".$customer_id);
						if($customerConfig) {
							foreach($customerConfig as $config) {
								$response["tbl_customer_configuration"][] = $config->toArray();
							}
						}
						
						$languageMapper = new Admin_Model_Mapper_CustomerLanguage();
						$customerLanguage = $languageMapper->fetchAll("customer_id=".$customer_id);
						if($customerLanguage) {
							foreach($customerLanguage as $language) {
								$response["tbl_customer_language"][] = $language->toArray();
							}
						}
						
						$allLanguages = new Admin_Model_Mapper_Language();
						$allLanguages = $allLanguages->fetchAll();
						if($allLanguages){
							foreach ($allLanguages as $language) {
								$response["tbl_language"][] = $language->toArray();
							}
						}

						$data["status"] = "success";
						$data["data"] = $response;
						$this->_sendData($data);
					} else {
						$this->_sendError("Invalid customer ID");
					}
				}
			} catch (Exception $ex) {
				$this->_sendError($ex->getMessage());
			}
		}
	}
	/* (non-PHPdoc)
	 * @see Zend_Rest_Controller::headAction()
	 */public function headAction() {
		// TODO Auto-generated method stub
	}

	protected function _gcm(){
		$customer_id = $this->_request->getParam("customer_id",null);
		$device_id = $this->_request->getParam("device_id",null);
		$reg_id = $this->_request->getParam("reg_id",null);
		if($customer_id===null) {
			$this->_sendError("Invalid request");
		}else{
			try{
				if($device_id != "" && $customer_id != "" && $device_id != "000000000000000"){
					$clouduserModel = new Default_Model_CloudUser();
					$clouduserMapper = new Default_Model_Mapper_CloudUser();
					$clouduserExists = $clouduserMapper->getDbTable()->fetchAll("device_id =".$device_id)->toArray();
					if($clouduserExists){
						$clouduserModel->setCloudUserId($clouduserExists[0]["cloud_user_id"]);
					}
					$clouduserModel->setCustomerId($customer_id);
					$clouduserModel->setDeviceId($device_id);
					$clouduserModel->setRegId($reg_id);
					$clouduserModel->save();
				}
			}catch(Exception $ex){
				$this->_sendError($ex->getMessage());
			}
		}
	}
}