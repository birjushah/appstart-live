<?php
class Standard_Plugin_Music_Beatport {
	const API_SEARCH = 'http://api.beatport.com/catalog/search?';
	
     /**
     * Search track inside BeatPort
     * 
     * @access public
     * @static
     * @param mixed $term
     * @return array
     */
    public static function search($term)
    {
    	$config = array("query" => $term,
    					"facets"=>"fieldType:track",
    					"page"=>1,
    					"perPage"=>50);
    
        $content = self::_get_content($config);
        
        return $content;
    }
    
    /**
     * Get the content from the BeatPort servers
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