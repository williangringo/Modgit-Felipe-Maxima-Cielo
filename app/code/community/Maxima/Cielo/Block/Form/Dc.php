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

class Maxima_Cielo_Block_Form_Dc extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('cielo/form/dc.phtml');
    }
	
	/**
     * 
     * Lista opcoes de meses
     * 
     */
    
    public function getMonths()
	{
    	$months = array();
		
		for($i = 1; $i <= 12; $i++)
		{
			$label = ($i < 10) ? ("0" . $i) : $i;
			
			$months[] = array("num" => $i, "label" => $this->htmlEscape($label));
		}
		
		return $months;
	}
	
	/**
     * 
     * Lista opcoes de anos
     * 
     */
    
    public function getYears()
	{
    	$years = array();
		
		$initYear = (int) date("Y");
		
		for($i = $initYear; $i <= $initYear + 10; $i++)
		{
			$years[] = array("num" => $i, "label" => $i);
		}
		
		return $years;
	}
    
    
	/**
     * 
     * Retorna vetor com os codigos dos cartoes habilitados
     * 
     */
    
    public function getAllowedCards()
	{
    	$allowedCards = explode(",", Mage::getStoreConfig('payment/Maxima_Cielo_Dc/card_types'));
    	$allCards = Mage::getModel('Maxima_Cielo/dc_types')->toOptionArray();
    	
    	$validCards = array();
    	
    	foreach($allCards as $card)
    	{
			if(in_array($card['value'], $allowedCards))
			{
				$validCards[] = $card;
			}
    	}
    	
    	return $validCards;
	}
	
	
	/**
     * 
     * Pega os valores da configuracao do modulo
     * 
     */
    
    public function getConfigData($config)
	{
    	return Mage::getStoreConfig('payment/Maxima_Cielo_Dc/' . $config);
	}
}
