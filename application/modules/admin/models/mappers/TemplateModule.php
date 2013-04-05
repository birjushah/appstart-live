<?php
class Admin_Model_Mapper_TemplateModule extends Standard_ModelMapper {
	protected $_dbTableClass = "Admin_Model_DbTable_TemplateModule";
	
	public function findByTemplateAndModuleId($template_id,$module_id) {
		return $this->getDbTable()->fetchRow("template_id='".$template_id."' AND module_id=".$module_id);
	}
	
	public function addModules($template_id,$arrModules) {
		foreach($arrModules as $module_id) {
		 $model = $this->findByTemplateAndModuleId($template_id, $module_id);
			if($model != null) {
				$model = new $this->_modelClass($model->toArray());
				$model->setLastUpdatedBy(Standard_Functions::getCurrentUser ()->system_user_id);
				$model->setLastUpdatedAt(Standard_Functions::getCurrentDateTime ());
				$model->setStatus(1);
				$model->save();
			} else {
				$model = new $this->_modelClass();
				$model->setModuleId($module_id);
				$model->setTemplateId($template_id);
				$model->setStatus(1);
				$model->setLastUpdatedBy(Standard_Functions::getCurrentUser ()->system_user_id);
				$model->setLastUpdatedAt(Standard_Functions::getCurrentDateTime ());
				$model->setCreatedBy(Standard_Functions::getCurrentUser ()->system_user_id);
				$model->setCreatedAt(Standard_Functions::getCurrentDateTime ());
				
				$model->save();
			}
				
		}
	}	
}