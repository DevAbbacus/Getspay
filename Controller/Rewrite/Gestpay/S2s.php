<?php

namespace Magemonkeys\Gestpay\Controller\Rewrite\Gestpay;
use \EasyNolo\BancaSellaPro\Helper\Data as GestpayData;
use \EasyNolo\BancaSellaPro\Model\TokenFactory;
use \EasyNolo\BancaSellaPro\Model\WS\CryptDecrypt;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\App\Request\Http;
use \Magento\Framework\Registry;
use \Magento\Sales\Model\Order;

class S2s extends \EasyNolo\BancaSellaPro\Controller\Gestpay\S2s {

	protected $_request, $_registry, $_dataHelper, $cryptDecrypt, $order, $_modelTokenFactory, $easynoloToken;

	public function __construct(
		Context $context,
		Http $request,
		Registry $registry,
		Order $order,
		GestpayData $dataHelper,
		CryptDecrypt $cryptDecrypt,
		TokenFactory $modelTokenFactory,
		\EasyNolo\BancaSellaPro\Model\Token $easynoloToken
	) {

		$this->_request = $request;
		$this->_registry = $registry;
		$this->_dataHelper = $dataHelper;
		$this->cryptDecrypt = $cryptDecrypt;
		$this->order = $order;
		$this->_modelTokenFactory = $modelTokenFactory;
		$this->easynoloToken = $easynoloToken;

		return parent::__construct($context, $request, $registry, $order, $dataHelper, $cryptDecrypt, $modelTokenFactory);
	}

	public function execute() {
		$a = $this->getRequest()->getParam('a', false);
		$b = $this->getRequest()->getParam('b', false);

		if (!$a || !$b) {
			$this->_dataHelper->log('Accesso alla pagina per il risultato del pagamento non consentito, mancano i parametri di input');
			$this->getRequest()->initForward();
			$this->getRequest()->setActionName('noroute');
			$this->getRequest()->setDispatched(false);
			return;
		}

		$this->_registry->register('bancasella_param_a', $a);
		$this->_registry->register('bancasella_param_b', $b);

		$params = $this->_dataHelper->_getDecryptParams($a, $b);
		$result = $this->cryptDecrypt->decryptRequest($params);

		$orderId = $result->getShopTransactionID();
		$order = $this->order->loadByIncrementId($orderId);

		if ($order->getId()) {

			$method = $order->getPayment()->getMethodInstance();
			$additionalData = $method->getInfoInstance()->getAdditionalInformation();

			$alternativePayment = isset($additionalData['alternative-payment']) ? $additionalData['alternative-payment'] : "";

			if (($result->getToken() && $order->getCustomerId()) || ($alternativePayment == 'slimpay' && $result->getAuthorizationCode() && $order->getCustomerId())) {
				$this->_dataHelper->log('Salvo il token');
				$token = $this->_modelTokenFactory->create();

				if ($alternativePayment == 'slimpay') {

					$token->setTokenInfo(
						$result->getAuthorizationCode(),
						$result->getTokenExpiryMonth(),
						$result->getTokenExpiryYear());

				} else {

					$token->setTokenInfo(
						$result->getToken(),
						$result->getTokenExpiryMonth(),
						$result->getTokenExpiryYear());
				}

				//save token type like(credit-card,paypal,slimpay etc)

				if ($alternativePayment == 'paypal') {

					$tokenType = 'Paypal';

				} elseif ($alternativePayment == 'slimpay') {

					$tokenType = 'Slimpay';

				} else {

					$tokenType = 'Credit Card';
				}

				$token->setTokenType($tokenType);

				//set token is default for reccuring payment if deafult token is not set

				$tokens = $this->easynoloToken->getCollection()->addFieldToSelect('id')->addFieldToFilter('customer_id', $order->getCustomerId())
					->addFieldToFilter('is_default', 1)->getFirstItem();

				if ($tokens->getId()) {

					$isDefault = 0;
				} else {
					$isDefault = 1;
				}

				$token->setIsDefault($isDefault);

				$token->setCustomerId($order->getCustomerId());

				$token->save();
			}

			$this->_dataHelper->log('Imposto lo stato dell\'ordine in base al decrypt');
			$method = $order->getPayment()->getMethodInstance();
			$method->setStatusOrderByS2SRequest($order, $result);

		} else {
			$this->_dataHelper->log('La richiesta effettuata non ha un corrispettivo ordine. Id ordine= ' . $result->getShopTransactionID());
		}

		//restiutisco una pagina vuota per notifica a GestPay
		$this->getResponse()->setBody('<html></html>');
		return;
	}
}