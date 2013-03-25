<?php
class HomeWallpaper_RestController extends Standard_Rest_Controller {
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::getAction()
	 */
	public function getAction() {
		// TODO Auto-generated method stub
		$service = $this->_request->getParam("service",null);
		if($service==null) {
			$this->_sendError("No service called");
		} else {
			if($service == "sync") {
				$this->_sync();
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
	
	protected function _sync() {
		$customer_id = $this->_request->getParam("customer_id",null);
		$resolution_id = $this->_request->getParam("resolution",null);
		if($customer_id===null) {
			$this->_sendError("Invalid request");
		} else {
			try{
				$mapper = new Admin_Model_Mapper_Customer();
				$customer = $mapper->find($customer_id);
				if($customer) {
					$response = array();
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
											$details["image_path"] = "resource/home-wallpaper/wallpapers/C" . $customer_id. "/R".$resolution_id."/".$homeWallpaperImageModels[0]->get("image_path");
										}
									}
									$wallaperDetails[] = $details;
								}
							}
							
							$response["data"][] = array("tbl_home_wallpaper" => $wallpaper->toArray(),"tbl_home_wallpaper_detail"=>$wallaperDetails);
						}
					}else{
						$response["data"][] = array("tbl_home_wallpaper" => array(),"tbl_home_wallpaper_detail"=>array());	
					}
					$data["status"] = "success";
					$data["data"] = $response;
					$this->_sendData($data);
				} else {
					$this->_sendError("Invalid customer ID");
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
}