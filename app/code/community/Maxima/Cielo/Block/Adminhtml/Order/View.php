<?php

/*
 * Maxima Cielo Module - payment method module for Magento, integrating
 * the billing forms with a Cielo's gateway Web Service.
 * Copyright (C) 2012  Fillipe Almeida Dutra
 * Belo Horizonte, Minas Gerais - Brazil
 * 
 * Contact: lawsann@gmail.com
 * Project link: http://code.google.com/p/magento-maxima-cielo/
 * Group discussion: http://groups.google.com/group/cielo-magento
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class Maxima_Cielo_Block_Adminhtml_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{
    public function  __construct()
    {
		parent::__construct();
		
		$payment = $this->getOrder()->getPayment();
		$method = $payment->getMethodInstance()->getCode();
		$tid = $payment->getAdditionalInformation('Cielo_tid');
		
		if(!$tid)
		{
			return;
		}
		
		if($method == "Maxima_Cielo_Cc")
		{
			if ($this->_isAllowedAction("cielo-capture"))
			{
				$this->_addButton('maxima_cielo_capture', array
				(
					'label'     => Mage::helper('Maxima_Cielo')->__('Capture'),
					'onclick'   => "captureCieloOrder('" . $tid . "', " . $this->getOrder()->getId() . ");",
					'class'     => 'go'
				));
			}
		}
		
		if($method == "Maxima_Cielo_Cc" || $method == "Maxima_Cielo_Dc")
		{
			if ($this->_isAllowedAction("cielo-consult"))
			{
				$this->_addButton('maxima_cielo_consult', array
				(
					'label'     => Mage::helper('Maxima_Cielo')->__('Consult WebService'),
					'onclick'   => "loadCieloWebServiceData('" . $tid . "', " . $this->getOrder()->getId() . ");",
					'class'     => 'go'
				));
			}
		}
		
		if($method == "Maxima_Cielo_Cc")
		{
			if ($this->_isAllowedAction("cielo-cancel"))
			{
				$this->_addButton('maxima_cielo_cancel', array
				(
					'label'     => Mage::helper('Maxima_Cielo')->__('Cancel on Cielo'),
					'onclick'   => "cancelCieloOrder('" . $tid . "', " . $this->getOrder()->getId() . ");",
					'class'     => 'go'
				));
			}
		}
	}
}
