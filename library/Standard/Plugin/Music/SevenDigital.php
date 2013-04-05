<?php
class Standard_Plugin_Music_SevenDigital {
	const API_SEARCH = 'http://api.7digital.com/1.2/track/search?';
	const API_PREVIEW = 'http://api.7digital.com/1.2/track/preview?';
    const CUSTOMER_KEY = '7dpzkzganrc5';
     /**
     * Search track inside SevenDigital
     * 
     * @access public
     * @static
     * @param mixed $term
     * @param mixed $country (default: GB)
     * @return array
     */
    public static function search($term,$country = 'GB')
    {
    	$config = array("q" => $term,
						"oauth_consumer_key" => self::CUSTOMER_KEY,
						"country" => $country,
    					"pagesize"=>50);
    
        $content = self::_get_content($config);
        
        return $content;
    }
    
    /**
     * Get track preview inside SevenDigital
     *
     * @access public
     * @static
     * @param mixed $track_id
     * @return array
     */
    public static function preview($track_id)
    {
    	$config = array("trackid" => $track_id,
    			"oauth_consumer_key" => self::CUSTOMER_KEY,
    			"redirect"=>"false");
    	
    	$url = self::API_PREVIEW;
    	$url .= http_build_query($config);
    	
    	$content = file_get_contents($url);
    	
    	return json_decode(Zend_Json::fromXml($content,false));
    }
    
    /**
     * Get the content from the SevenDigital servers
     * 
     * @access protected
     * @static
     * @param array $config
     * @return array
     */
    protected static function _get_content($config)
    {
        $url = self::API_SEARCH;
        $url .= http_build_query($config);
        
        $content = file_get_contents($url);
        $array = json_decode(Zend_Json::fromXml($content,false));
        
        return $array;
    }
}