<?php

/**
 * PriceMotion element renderer to display prices
 *
 * @package		Pricemotion
 * @copyright	Aim4Solutions s.r.l.
 * @author		Sebastian Pruteanu <sebastian@aim4solutions.com>
 */

class A4s_Pricemotion_Block_Adminhtml_Form_Element_Prices extends Varien_Data_Form_Element_Abstract {
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
		$html = '<div class="grid"><table cellspacing="0" class="data">
                 <thead>
				  <tr class="headings">';
		foreach($this->getTablehead() as $th) {
			$html .= "<th>{$th}</th>";
		}
		$html .= '<tbody>';
		foreach($this->getValue() as $tr) {
			$html .= "<tr>";
			$html .= "<td>{$tr['seller']}</td>";
			$html .= "<td>{$tr['price']}</td>";
			$html .= "</tr>";
		}
		$html .= '</tbody>';
		$html .= '</tr>
				</thead>';
		$html .= '</table></div>';
		return $html;
	}
}