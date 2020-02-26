<?php

/**
 * PriceMotion element renderer to display errors
 *
 * @package		Pricemotion
 * @copyright	Aim4Solutions s.r.l.
 * @author		Sebastian Pruteanu <sebastian@aim4solutions.com>
 */

class A4s_Pricemotion_Block_Adminhtml_Form_Element_Errormsg extends Varien_Data_Form_Element_Abstract {
	public function __construct($attributes=array())
	{
		parent::__construct($attributes);
	}
	
	/**
     * Generates element html
     *
     * @return string
     */
	public function getElementHtml()
	{
		$html = '<ul class="messages"><li class="error-msg"><ul><li><span>';
		$html .= $this->getMessage();
		$html .= '</span></li></ul></li></ul>';
		return $html;
	}
}