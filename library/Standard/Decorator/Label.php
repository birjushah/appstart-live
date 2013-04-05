<?php
class Standard_Decorator_Label extends Zend_Form_Decorator_Abstract
{
	protected $_format = '<label for="%s">%s</label>';
	 
	public function render($content)
	{
		$element = $this->getElement();
		$label   = $element->getLabel();
		$id      = htmlentities($element->getId());
		 
		$markup  = sprintf($this->_format, $id, $label);
		return $markup;
	}
	public function setTag() {
		
	}
}