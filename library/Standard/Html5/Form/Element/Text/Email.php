<?php
class Standard_Html5_Form_Element_Text_Email extends Standard_Html5_Form_Element_Text
{
    public function init()
    {
        if ($this->isAutoloadValidators())
        {
            $this->addValidator('EmailAddress');
        }
    }

}