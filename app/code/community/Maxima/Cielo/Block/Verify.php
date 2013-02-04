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

class Maxima_Cielo_Block_Verify extends Mage_Checkout_Block_Onepage_Success
{
    private $_cieloStatus = -1;
    private $_cieloTid = -1;
    
    
    /**
	 * 
	 * Define mensagem mostrada ao fim da compra
	 * 
	 * @return string
	 * 
	 */
    
    public function getCieloDataHtml()
    {
		$html = "";
		
		if($this->_cieloStatus == 6 || $this->_cieloStatus == 4)
		{
			$html .= "Sua compra foi faturada com êxito.<br />O ID da sua transação na Cielo é <b>" . $this->_cieloTid . "</b>.";
		}
		else if($this->_cieloStatus == 1 || $this->_cieloStatus == 2 || $this->_cieloStatus == 10)
		{
			$html .= "Sua compra foi faturada com êxito.<br />O ID da sua transação na Cielo é <b>" . $this->_cieloTid . "</b>.";
		}
		else
		{
			$statusMsg = Mage::helper('Maxima_Cielo')->getStatusMessage($this->_cieloStatus);
			
			$html .= "Seu pagamento não foi realizado com sucesso.<br />
					  O TID da sua transação na Cielo é <b>" . $this->_cieloTid . "</b>.<br />
					  Mensagem de retorno da Cielo: <b>" . $statusMsg . "</b>.<br />
					  Para maiores informações, por favor acesse o link do pedido acima ou entre em contato conosco.";
		}
		
		return $html;
    }
    
    
    
    /**
     * 
     * Getters and Setters
     * 
     */
    
    public function setCieloStatus($st)
    {
		$this->_cieloStatus = $st;
    }
    
    public function getCieloStatus()
    {
		return $this->_cieloStatus;
    }
    
    public function setCieloTid($tid)
    {
		$this->_cieloTid = $tid;
    }
    
    public function getCieloTid()
    {
		return $this->_cieloTid;
    }
    
}
 
