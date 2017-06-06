<?php

class MOIP_Onestepcheckout_Block_Adminhtml_System_Config_Header
    extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Render element html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $useContainerId = $element->getData('use_container_id');
        return sprintf(
            '<tr class="system-fieldset-sub-head" id="row_%s"><td colspan="5" style="max-width:580px;"><h4 id="%s">%s</h4><p class="subheading-note" style="font-size:11px;font-style:italic;color:#666;"><span>%s</span></p></td></tr>',
            $element->getHtmlId(), $element->getHtmlId(), $element->getLabel(), $element->getComment()
        );

    
    }
}
