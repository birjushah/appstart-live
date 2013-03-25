<?php
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {
		public function _initTranslate() {
		$localeValue = 'en';
		
		$locale = new Zend_Locale($localeValue);
		Zend_Registry::set('Zend_Locale', $locale);
		
		$translate = new Zend_Translate(
						    array(
						        'adapter' => 'array',
						        'disableNotices' => true,
						    )
						);
		
		$iterator = new DirectoryIterator(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR);
		foreach ($iterator as $fileinfo) {
			if(!$fileinfo->isDir()) {
				$translate->getAdapter()->addTranslation(array(
							'content' => $fileinfo->getPath(). DIRECTORY_SEPARATOR .$fileinfo->getFilename(),
							'locale' => str_replace(".php", "", $fileinfo->getFilename())
						));
			}
		}
		
		$iterator = new DirectoryIterator(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR);
		foreach ($iterator as $fileinfo) {
			if($fileinfo->isDir() && file_exists($fileinfo->getPath(). DIRECTORY_SEPARATOR . $fileinfo->getFilename() . DIRECTORY_SEPARATOR . 'lang')) {
				$langIterator = new DirectoryIterator($fileinfo->getPath() . DIRECTORY_SEPARATOR . $fileinfo->getFilename() . DIRECTORY_SEPARATOR .'lang'. DIRECTORY_SEPARATOR);
				foreach ($langIterator as $langFolder) {
					if(!$langFolder->isDir()) {
						$translate->getAdapter()->addTranslation(array(
								'content' => $langFolder->getPath(). DIRECTORY_SEPARATOR .$langFolder->getFilename(),
								'locale' => str_replace(".php", "", $langFolder->getFilename())
						));
					}	
				}
			}
		}
		$translate->getAdapter()->setLocale($localeValue);
		Zend_Registry::set('Zend_Translate', $translate);
		Zend_Registry::set("app_translate", $translate);
	}
	protected  function _initConfig(){
		$AppConfig= $this->getOption('AppConfig');
		Zend_Registry::set('AppConfig', $AppConfig);
	}
	protected function _initDebug() {
		// SoundCloud API
		// Client ID: e9d49c642a93447a3469437bfc92df02
		// Client Secret: e534ca88d9bf7378b0f0a28de4101e6c
		
		//$soundCloud = new Standard_Plugin_Music_Soundcloud("e9d49c642a93447a3469437bfc92df02","e534ca88d9bf7378b0f0a28de4101e6c");
		//$soundCloud->accessToken("baaea756baca2499d1c04035723bc024");
		//$response = $soundCloud->get("tracks",array("q"=>"life","limit"=>10,"offset"=>11));
		//var_dump($soundCloud->getAuthorizeUrl());
		//foreach ($response->tracks->track as $track)
		//	var_dump($track);
		//die;
		
		// Beatport
		/*$response = Standard_Plugin_Music_Beatport::search("lady gaga");
		foreach ($response->response->result as $result)
		{
			foreach($result as $track){
				var_dump($track->image);
				print "\n============================\n\n";
			}
		}
		die;*/
		//$response = Standard_Plugin_Music_iTunes::search("life",array("media"=>"music","country"=>"US","entity"=>"song","limit"=>20,"offset"=>5));
		//foreach ($response->response->searchResults->searchResult as $result) {
		//var_dump($response);
		//}
		
		//$response = Standard_Plugin_Music_SevenDigital::search("life","US");
		//foreach ($response->response->searchResults->searchResult as $result) {
			//var_dump($response->response->searchResults->searchResult[0]->track->{"@attributes"}->id);
		//}
		//die;
	}
}

