<?php
class Standard_Html5_Form_Element_Hash extends Zend_Form_Element_Hash implements Standard_Html5_Form_Element_Hash_Interface
{

    /**
     * Adapter for hash elements
     *
     * @var Standard_Html5_Form_Element_Hash_Interface
     */
    private $_adapter;

    /**
     * Constructor that checks if an adapter isset
     *
     * @param $spec
     * @param $options
     */
    public function __construct($spec, $options = null)
    {
        if (isset($options['adapter']))
        {
            $this->setAdapter($options['adapter']);
            unset($options['adapter']);
        }
        parent::__construct($spec, $options);
    }

    /**
     * Returns the currently set adapter
     *
     * @return Standard_Html5_Form_Element_Hash_Interface
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    /**
     * Set an adapter for the hash element to base its storage on
     *
     * @param $adapter
     * @return Standard_Html5_Form_Element_Hash
     */
    public function setAdapter($adapter)
    {
        if (is_string($adapter))
        {
            $adapter = new $adapter($this);
        }
        if (!$adapter instanceof Standard_Html5_Form_Element_Hash_Interface)
        {
            throw new Standard_Html5_Exception('Adapter needs to be an instance of Standard_Html5_Form_Element_Hash_Interface!');
        }
        $this->_adapter = $adapter;
        return $this;
    }

    /**
     * Initialize CSRF validator
     *
     * Initializes CSRF token in given adapter storage or session if no adapter is set.
     * Additionally, adds validator for validating CSRF token.
     *
     * @return Standard_Html5_Form_Element_Hash
     */
    public function initCsrfValidator()
    {
        if (null !== $this->getAdapter())
        {
             $this->getAdapter()->initCsrfValidator();
        }
        else
        {
            parent::initCsrfValidator();
        }
        return $this;
    }

    /**
     * Initialize CSRF token in adapter or session if adapter is not set
     *
     * @return void
     */
    public function initCsrfToken()
    {
        if (null !== $this->getAdapter())
        {
            $this->_adapter->initCsrfToken();
        }
        else
        {
            parent::initCsrfToken();
        }
    }

    /**
     * Clear the hash data of the adapter
     *
     * @return Standard_Html5_Form_Element_Hash
     */
    public function clear()
    {
        //@todo: check if no adapter isset..
        if (null !== $this->getAdapter())
        {
            $this->getAdapter()->clear();
        }
        return $this;
    }

}