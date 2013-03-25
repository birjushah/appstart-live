<?php
class Standard_Html5_View_Helper_Esi extends Zend_View_Helper_Abstract
{
    /**
     * Default ESI header name
     *
     * @var string
     */
    protected static $_varnishHeaderName = 'enable-esi';

    /**
     * Default ESI header value
     *
     * @var int
     */
    protected static $_varnishHeaderValue = 1;

    /**
     * Has the Varnish header been sent?
     *
     * @var boolean
     */
    protected static $_varnishHeaderSent = false;

    /**
     * Sets the ESI header settings (to match your Varnish VCL)
     *
     * @param string $name
     * @param string $value
     */
    public static function setHeader($name, $value)
    {
        self::$_varnishHeaderName = $name;
        self::$_varnishHeaderValue = $value;
    }

    /**
     * Create an ESI tag for a given SRC.
     *
     * @param  string $uri
     * @return string
     */
    public function esi($uri)
    {
        // If the ESI headers have not been sent yet do it now
        if(false === self::$_varnishHeaderSent)
        {
            $response = Zend_Controller_Front::getInstance()->getResponse();
            $response->setHeader(self::$_varnishHeaderName, self::$_varnishHeaderValue);
            self::$_varnishHeaderSent = true;
        }

        return '<esi:include src="' . $uri . '"/>';
    }
}