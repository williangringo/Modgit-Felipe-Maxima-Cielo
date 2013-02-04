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

class Maxima_Cielo_Model_Cc extends Mage_Payment_Model_Method_Abstract
{

    protected $_code  = 'Maxima_Cielo_Cc';
    protected $_formBlockType = 'Maxima_Cielo/form_cc';
    protected $_infoBlockType = 'Maxima_Cielo/info_cc';
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    
    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object))
		{
            $data = new Varien_Object($data);
        }
        
        // salva a bandeira e o numero de parcelas
		$info = $this->getInfoInstance();
        $additionaldata = array
        (
			'parcels_number' => $data->getParcelsNumber()
		);
		
		$info->setCcType($data->getCcType())
			 ->setCcNumber(Mage::helper('core')->encrypt($data->getCcNumber()))
			 ->setCcOwner($data->getCcOwner())
			 ->setCcExpMonth($data->getCcExpMonth())
			 ->setCcExpYear($data->getCcExpYear())
			 ->setCcCid(Mage::helper('core')->encrypt($data->getCcCid()))
			 ->setAdditionalData(serialize($additionaldata));
		
		
		// pega dados de juros
		$withoutInterest = intval($this->getConfigData('installment_without_interest', $this->getStoreId()));
		$interestValue = floatval($this->getConfigData('installment_interest_value', $this->getStoreId()));
		
		
		// verifica se há juros
		if($data->getParcelsNumber() > $withoutInterest)
		{
			$installmentValue = Mage::helper('Maxima_Cielo')->calcInstallmentValue
								(
									$info->getQuote()->getGrandTotal(), 
									$interestValue / 100, 
									$data->getParcelsNumber()
								);
			$installmentValue = round($installmentValue, 2);
			$interest = ($installmentValue * $data->getParcelsNumber()) - $info->getQuote()->getGrandTotal();
			
			$info->getQuote()->setInterest($info->getQuote()->getStore()->convertPrice($interest, false));
			$info->getQuote()->setBaseInterest($interest);
			
			$info->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
			$info->getQuote()->save();
		}
		else
		{
			$info->getQuote()->setInterest(0.0);
			$info->getQuote()->setBaseInterest(0.0);
			
			$info->getQuote()->setTotalsCollectedFlag(false)->collectTotals();
			$info->getQuote()->save();
		}
		
		
        return $this;
    }
	
	
	/**
	 * Valida dados
	 *
	 * @param   Mage_Payment_Model_Info $info
	 * @return  Mage_Payment_Model_Abstract
	 */
	public function validate()
	{
		/*
		 * chama validacao do metodo abstrato
		 */
		parent::validate();
		
		$info = $this->getInfoInstance();
		$errorMsg = false;
		
		if($this->getConfigData('buypage', $this->getStoreId()) != "loja")
			return $this;
		
		$availableTypes = Mage::getModel('Maxima_Cielo/cc_types')->getCodes();
		$ccNumber = Mage::helper('core')->decrypt($info->getCcNumber());

		// remove delimitadores do cartao, como "-" e espaco
		$ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
		$info->setCcNumber(Mage::helper('core')->encrypt($ccNumber));

		$ccType = '';
		
		// valida o numero do cartao de credito
		if(in_array($info->getCcType(), $availableTypes))
		{
			if ($this->validateCcNum($ccNumber))
			{
				$ccType = 'OT';
				$ccTypeRegExpList = array
				(
					//Solo, Switch or Maestro. International safe
					/*
					// Maestro / Solo
					'SS'  => '/^((6759[0-9]{12})|(6334|6767[0-9]{12})|(6334|6767[0-9]{14,15})'
							. '|(5018|5020|5038|6304|6759|6761|6763[0-9]{12,19})|(49[013][1356][0-9]{12})'
							. '|(633[34][0-9]{12})|(633110[0-9]{10})|(564182[0-9]{10}))([0-9]{2,3})?$/',
					*/
					// Solo only
					'SO' => '/(^(6334)[5-9](\d{11}$|\d{13,14}$))|(^(6767)(\d{12}$|\d{14,15}$))/',
					'SM' => '/(^(5[0678])\d{11,18}$)|(^(6[^05])\d{11,18}$)|(^(601)[^1]\d{9,16}$)|(^(6011)\d{9,11}$)'
							. '|(^(6011)\d{13,16}$)|(^(65)\d{11,13}$)|(^(65)\d{15,18}$)'
							. '|(^(49030)[2-9](\d{10}$|\d{12,13}$))|(^(49033)[5-9](\d{10}$|\d{12,13}$))'
							. '|(^(49110)[1-2](\d{10}$|\d{12,13}$))|(^(49117)[4-9](\d{10}$|\d{12,13}$))'
							. '|(^(49118)[0-2](\d{10}$|\d{12,13}$))|(^(4936)(\d{12}$|\d{14,15}$))/',
					// Visa
					'visa'  => '/^4[0-9]{12}([0-9]{3})?$/',
					// Master Card
					'mastercard'  => '/^5[1-5][0-9]{14}$/',
					// American Express
					'amex'  => '/^3[47][0-9]{13}$/',
					// Discovery
					'discover'  => '/^6011[0-9]{12}$/',
					// JCB
					'JCB' => '/^(3[0-9]{15}|(2131|1800)[0-9]{11})$/',
					// Diners Club
					'diners' => '/^3[0,6,8]\d{12}$/'
				);

				foreach ($ccTypeRegExpList as $ccTypeMatch => $ccTypeRegExp)
				{
					if (preg_match($ccTypeRegExp, $ccNumber))
					{
						$ccType = $ccTypeMatch;
						break;
					}
				}

				if ($info->getCcType() != 'elo' && ($ccType != $info->getCcType()))
				{
					$errorMsg = Mage::helper('Maxima_Cielo')->__('Credit card number mismatch with credit card type.');
				}
			}
			else
			{
				$errorMsg = Mage::helper('Maxima_Cielo')->__('Invalid Credit Card Number');
			}

		}
		else
		{
			$errorMsg = Mage::helper('Maxima_Cielo')->__('Credit card type is not allowed for this payment method.');
		}

		// valida o numero de verificacao
		if ($errorMsg === false)
		{
			$verificationRegEx = $this->getVerificationRegEx();
			$regExp = isset($verificationRegEx[$info->getCcType()]) ? $verificationRegEx[$info->getCcType()] : '';
			
			if ($regExp != '' && (!$info->getCcCid() || !preg_match($regExp, Mage::helper('core')->decrypt($info->getCcCid()))))
			{
				$errorMsg = Mage::helper('Maxima_Cielo')->__('Please enter a valid credit card verification number.');
			}
		}

		if (!$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth()))
		{
			$errorMsg = Mage::helper('Maxima_Cielo')->__('Incorrect credit card expiration date.');
		}

		if($errorMsg)
		{
			Mage::throwException($errorMsg);
		}

		//This must be after all validation conditions
		//if ($this->getIsCentinelValidationEnabled())
		//{
		//	$this->getCentinelValidator()->validate($this->getCentinelValidationData());
		//}

		return $this;
	}
	
	
	/**
     * Validacao retirada do modelo cc da versao 1.7 do Magento
     *
     * @param   string $cc_number
     * @return  bool
     */
    public function validateCcNum($ccNumber)
    {
        $cardNumber = strrev($ccNumber);
        $numSum = 0;

        for ($i=0; $i<strlen($cardNumber); $i++)
        {
            $currentNum = substr($cardNumber, $i, 1);

            /**
             * Double every second digit
             */
            if ($i % 2 == 1)
            {
                $currentNum *= 2;
            }

            /**
             * Add digits of 2-digit numbers together
             */
            if ($currentNum > 9)
            {
                $firstNum = $currentNum % 10;
                $secondNum = ($currentNum - $firstNum) / 10;
                $currentNum = $firstNum + $secondNum;
            }

            $numSum += $currentNum;
        }

        /**
         * If the total has no remainder it's OK
         */
        
        return ($numSum % 10 == 0);
    }
    
    
    /**
     * Expressao regular retirada do modelo cc da versao 1.7 do Magento
     *
     * @return  strig regExp
     */
     
    public function getVerificationRegEx()
    {
        $verificationExpList = array
        (
            'visa' 			=> '/^[0-9]{3}$/', 			// Visa
            'mastercard' 	=> '/^[0-9]{3}$/',       	// Master Card
            'amex' 			=> '/^[0-9]{4}$/',        	// American Express
            'discover' 		=> '/^[0-9]{3}$/',         	// Discovery
            'SS' 			=> '/^[0-9]{3,4}$/',
            'SM' 			=> '/^[0-9]{3,4}$/', 		// Switch or Maestro
            'SO' 			=> '/^[0-9]{3,4}$/', 		// Solo
            'OT' 			=> '/^[0-9]{3,4}$/',
            'JCB' 			=> '/^[0-9]{3,4}$/' 		//JCB
        );
        return $verificationExpList;
    }
    
    
    /**
     * Validacao retirada do modelo cc da versao 1.7 do Magento
     *
     * @return  strig regExp
     */
    
    protected function _validateExpDate($expYear, $expMonth)
    {
        $date = Mage::app()->getLocale()->date();
        
        if (!$expYear || !$expMonth || ($date->compareYear($expYear) == 1)
            || ($date->compareYear($expYear) == 0 && ($date->compareMonth($expMonth) == 1)))
        {
            return false;
        }
        
        return true;
    }
    
    
    
    
    /**
     *  Getter da instancia do pedido
     *
     *  @return	  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->_order == null) {}
		
        return $this->_order;
    }

    /**
     *  Setter do pedido
     *
     *  @param Mage_Sales_Model_Order $order
     */
    public function setOrder($order)
    {
        if ($order instanceof Mage_Sales_Model_Order)
		{
            $this->_order = $order;
        }
		elseif (is_numeric($order))
		{
            $this->_order = Mage::getModel('sales/order')->load($order);
        }
		else
		{
            $this->_order = null;
        }
        return $this;
    }
    
    
    
	
	
	/**
     * Abre transacao com a Cielo para uma compra e redirectiona para a 
     * pagina de pagamento na Cielo. Em caso de erro, redireciona para pagina
     * de erro.
     *
     * @return  string
     */
	public function getOrderPlaceRedirectUrl()
	{
		$info = $this->getInfoInstance();
		$order = $info->getQuote();
		$storeId = $this->getStoreId();
		$payment = $order->getPayment();
		$additionaldata = unserialize($payment->getData('additional_data'));
		
		// coleta os dados necessarios
		$value 				= Mage::helper('Maxima_Cielo')->formatValueForCielo($order->getGrandTotal());
		$paymentType 		= $additionaldata["parcels_number"];
		$ccType 			= $payment->getCcType();
		$paymentParcels 	= $this->getConfigData('installments_type', $storeId);
		$cieloNumber 		= $this->getConfigData('cielo_number', $storeId);
		$cieloKey 			= $this->getConfigData('cielo_key', $storeId);
		$autoCapture		= $this->getConfigData('auto_capture', $storeId);
		$environment 		= $this->getConfigData('environment', $storeId);
		$sslFile	 		= $this->getConfigData('ssl_file', $storeId);
		
		// cria instancia do pedido
		$webServiceOrder = Mage::getModel('Maxima_Cielo/webServiceOrder', array('enderecoBase' => $environment, 'caminhoCertificado' => $sslFile));
		
		// preenche dados coletados
		$webServiceOrderData = array
		(
			'ccType'			=> $ccType,
			'cieloNumber'		=> $cieloNumber,
			'cieloKey'			=> $cieloKey,
			'capture'			=> ($autoCapture == 1) ? 'true' : 'false',
			'autorize'			=> '1',
			'clientOrderNumber'	=> $payment->getId(),
			'clientOrderValue'	=> $value,
			'postbackURL'		=> Mage::getUrl('cielo/pay/verify'),
			'clientSoftDesc'	=> $this->getConfigData('softdescriptor', $storeId)
		);
		
		// conforme mostrado no manual versao 2.0, pagina 11,
		// caso o cartao seja Dinners, Discover, Elo ou Amex
		// o valor do flag autorizar deve ser 3
		if($ccType == "diners" 		|| 
		   $ccType == "discover" 	|| 
		   $ccType == "elo" 		|| 
		   $ccType == "amex" 		||
		   !$this->getConfigData('autenticate', $storeId))
		{
			$webServiceOrderData['autorize'] = '3';
		}
		
		if($paymentType == "1")
		{
			$webServiceOrderData['paymentType'] = $paymentType;
			$webServiceOrderData['paymentParcels'] = 1;
		}
		else
		{
			$webServiceOrderData['paymentType'] = $paymentParcels;
			$webServiceOrderData['paymentParcels'] = $paymentType;
		}
		
		$webServiceOrder->setData($webServiceOrderData);
		
		
		// caso seja buy page loja, passa dados do cliente
		if($this->getConfigData('buypage', $storeId) == "loja")
		{
			$ccExpMonth = $info->getCcExpMonth();
			$ccExpMonth = ($ccExpMonth < 10) ? ("0" . $ccExpMonth) : $ccExpMonth;
			
			$ownerData = array
			(
				'number' 	=> Mage::helper('core')->decrypt($info->getCcNumber()),
				'exp_date' 	=> $info->getCcExpYear() . $ccExpMonth,
				'sec_code' 	=> Mage::helper('core')->decrypt($info->getCcCid()),
				'name' 		=> $info->getCcOwner()
			);
		}
		else
		{
			$ownerData = false;
		}
		
		$redirectUrl = $webServiceOrder->requestTransaction($ownerData);
		Mage::getSingleton('core/session')->setData('cielo-transaction', $webServiceOrder);
		
		if($redirectUrl == false)
		{
			// caso nao haja autenticacao, enviar para o tratamento final do pedido
			if(($this->getConfigData('buypage', $storeId) == "loja") && ($webServiceOrderData['autorize'] == '3'))
			{
				return Mage::getUrl('cielo/pay/verify');
			}
			// erro nao indentificado
			else
			{
				return Mage::getUrl('cielo/pay/failure');
			}
		}
		else
		{
			return $redirectUrl;
		}
    }
}
