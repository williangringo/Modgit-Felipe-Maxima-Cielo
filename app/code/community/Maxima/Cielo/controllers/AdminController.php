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

class Maxima_Cielo_AdminController extends Mage_Adminhtml_Controller_Action
{
	/**
	 * 
	 * Funcao responsavel por consultar o status de uma transacao no WebService da 
	 * Cielo
	 * 
	 */

	public function consultAction()
	{
		// verifica se o usuario estah logado na administracao do magento
		Mage::getSingleton('core/session', array('name' => 'adminhtml'));
		$session = Mage::getSingleton('admin/session');
		
		if (!$session->isLoggedIn())
		{
			return;
		}
		
		// pega pedido correspondente
		$orderId = $this->getRequest()->getParam('order');
		$order = Mage::getModel('sales/order')->load($orderId);
		
		// pega os dados para requisicao e realiza a consulta
		$methodCode = $order->getPayment()->getMethodInstance()->getCode();
		$cieloNumber 		= Mage::getStoreConfig('payment/' . $methodCode . '/cielo_number');
		$cieloKey 			= Mage::getStoreConfig('payment/' . $methodCode . '/cielo_key');
		$environment		= Mage::getStoreConfig('payment/' . $methodCode . '/environment');
		$sslFile			= Mage::getStoreConfig('payment/' . $methodCode . '/ssl_file');
		
		$model = Mage::getModel('Maxima_Cielo/webServiceOrder', array('enderecoBase' => $environment, 'caminhoCertificado' => $sslFile));
		
		$model->tid = $this->getRequest()->getParam('tid');
		$model->cieloNumber = $cieloNumber;
		$model->cieloKey = $cieloKey;
		
		$model->requestConsultation();
		$xml = $model->getXmlResponse();
		
		$this->getResponse()->setBody(Mage::helper('Maxima_Cielo')->xmlToHtml($xml));
	}
	
	
	/**
	 * 
	 * Funcao responsavel por enviar o pedido de captura para o WebService da Cielo
	 * 
	 */

	public function captureAction()
	{
		// verifica se o usuario estah logado na administracao do magento
		Mage::getSingleton('core/session', array('name' => 'adminhtml'));
		$session = Mage::getSingleton('admin/session');
		
		if (!$session->isLoggedIn())
		{
			return;
		}
		
		// pega pedido correspondente
		$orderId = $this->getRequest()->getParam('order');
		$order = Mage::getModel('sales/order')->load($orderId);
		$value = Mage::helper('Maxima_Cielo')->formatValueForCielo($order->getGrandTotal());
		
		// pega os dados para requisicao e realiza a consulta
		$methodCode = $order->getPayment()->getMethodInstance()->getCode();
		$cieloNumber 		= Mage::getStoreConfig('payment/Maxima_Cielo_Cc/cielo_number');
		$cieloKey 			= Mage::getStoreConfig('payment/Maxima_Cielo_Cc/cielo_key');
		$environment		= Mage::getStoreConfig('payment/' . $methodCode . '/environment');
		$sslFile			= Mage::getStoreConfig('payment/' . $methodCode . '/ssl_file');
		
		$model = Mage::getModel('Maxima_Cielo/webServiceOrder', array('enderecoBase' => $environment, 'caminhoCertificado' => $sslFile));
		
		$model->tid = $this->getRequest()->getParam('tid');
		$model->cieloNumber = $cieloNumber;
		$model->cieloKey = $cieloKey;
		
		// requisita captura
		$model->requestCapture($value);
		$xml = $model->getXmlResponse();
		$status = (string) $xml->status;
		
		// tudo ok, transacao aprovada, cria fatura
		if($status == 6)
		{
			$html = "<b>Pedido capturado com sucesso!</b> &nbsp; &nbsp; 
					<button type=\"button\" title=\"Atualizar Informações\" onclick=\"document.location.reload(true)\">
						<span>Recarregar Página</span>
					</button><br /><br />";
			
			// atualiza os dados da compra
			$payment = $order->getPayment();
			$payment->setAdditionalInformation('Cielo_status', $status);
			$payment->save();
			
			if($order->canInvoice() && !$order->hasInvoices())
			{
				$invoiceId = Mage::getModel('sales/order_invoice_api')->create($order->getIncrementId(), array());
				$invoice = Mage::getModel('sales/order_invoice')->loadByIncrementId($invoiceId);
				
				// envia email de confirmacao de fatura
				$invoice->sendEmail(true);
				$invoice->setEmailSent(true);
				$invoice->save();
			}
		}
		else
		{
			$html = "";
		}
		
		$this->getResponse()->setBody($html . Mage::helper('Maxima_Cielo')->xmlToHtml($xml));
	}
	
	
	/**
	 * 
	 * Funcao responsavel por enviar o pedido de cancelamento para o WebService da Cielo
	 * 
	 */

	public function cancelAction()
	{
		// verifica se o usuario estah logado na administracao do magento
		Mage::getSingleton('core/session', array('name' => 'adminhtml'));
		$session = Mage::getSingleton('admin/session');
		
		if (!$session->isLoggedIn())
		{
			return;
		}
		
		// pega pedido correspondente
		$orderId = $this->getRequest()->getParam('order');
		$order = Mage::getModel('sales/order')->load($orderId);
		
		// pega os dados para requisicao e realiza a consulta
		$methodCode = $order->getPayment()->getMethodInstance()->getCode();
		$cieloNumber 		= Mage::getStoreConfig('payment/Maxima_Cielo_Cc/cielo_number');
		$cieloKey 			= Mage::getStoreConfig('payment/Maxima_Cielo_Cc/cielo_key');
		$environment		= Mage::getStoreConfig('payment/' . $methodCode . '/environment');
		$sslFile			= Mage::getStoreConfig('payment/' . $methodCode . '/ssl_file');
		
		$model = Mage::getModel('Maxima_Cielo/webServiceOrder', array('enderecoBase' => $environment, 'caminhoCertificado' => $sslFile));
		
		$model->tid = $this->getRequest()->getParam('tid');
		$model->cieloNumber = $cieloNumber;
		$model->cieloKey = $cieloKey;
		
		// requisita cancelamento
		$model->requestCancellation();
		$xml = $model->getXmlResponse();
		$status = (string) $xml->status;
		
		// tudo ok, transacao cancelada
		if($status == 9)
		{
			$html = "<b>Pedido cancelado com sucesso!</b> &nbsp; &nbsp; 
					<button type=\"button\" title=\"Atualizar Informações\" onclick=\"document.location.reload(true)\">
						<span>Recarregar Página</span>
					</button><br /><br />";
			
			// atualiza os dados da compra
			$payment = $order->getPayment();
			$payment->setAdditionalInformation('Cielo_status', $status);
			$payment->save();
		}
		else
		{
			$html = "";
		}
		
		$this->getResponse()->setBody($html . Mage::helper('Maxima_Cielo')->xmlToHtml($xml));
	}
	
	
	/**
	 * 
	 * Funcao responsavel por conferir se usuario pode realizar a acao
	 * 
	 */
	
	protected function _isAllowed()
	{
		$action = 'sales/order/actions/cielo-' . $this->getRequest()->getActionName();
		
		return Mage::getSingleton('admin/session')->isAllowed($action);
	}
} 
