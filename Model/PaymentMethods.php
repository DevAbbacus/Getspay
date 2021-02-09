<?php
namespace Magemonkeys\Gestpay\Model;

use Magemonkeys\Gestpay\Api\PaymentMethodsInterface;

class PaymentMethods implements PaymentMethodsInterface {

	/**
	 * @var \Magento\Framework\App\Request\Http
	 */
	private $http;

	/**
	 * @var \Magento\Integration\Model\Oauth\TokenFactory
	 */
	private $tokenFactory;

	/**
	 * @var \Magento\Customer\Model\Session
	 */
	private $customerSession;

	/**
	 * @var \EasyNolo\BancaSellaPro\Model\Config
	 */
	private $config;

	/**
	 * @var \EasyNolo\BancaSellaPro\Model\Token
	 */
	private $easynoloToken;

	/**
	 * @var \Magento\Quote\Api\CartRepositoryInterface
	 */
	private $cartRepositoryInterface;

	/**
	 * @var \Magento\Quote\Api\CartManagementInterface
	 */
	private $cartManagement;

	/**
	 * @var \Magento\Sales\Model\Order
	 */
	private $order;

	/**
	 * @var \EasyNolo\BancaSellaPro\Model\TokenFactory
	 */
	private $easynoloTokenFactory;

	/**
	 * @var \EasyNolo\BancaSellaPro\Helper\Data
	 */
	private $helper;

	/**
	 * @var \EasyNolo\BancaSellaPro\Model\WS\WS2S
	 */
	private $s2s;

	/**
	 * @var \EasyNolo\BancaSellaPro\Model\WS\CryptDecrypt
	 */
	private $cryptDecrypt;

	/**
	 * @var \Magento\Framework\App\Config\ScopeConfigInterface
	 */
	private $scopeConfig;

	/**
	 * @var \Magento\Store\Model\StoreManagerInterface
	 */
	private $_storeManager;

	/**
	 * @var \EasyNolo\BancaSellaPro\Model\Gestpay
	 */
	protected $_gestpay = null;

	protected $client = null;

	/**
	 * @param Magento\Framework\App\Request\Http $http
	 * @param Magento\Integration\Model\Oauth\TokenFactory $tokenFactory
	 * @param Magento\Customer\Model\Session $customerSession
	 * @param EasyNolo\BancaSellaPro\Model\Config $config
	 * @param EasyNolo\BancaSellaPro\Model\Token $easynoloToken
	 * @param Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface
	 * @param Magento\Quote\Api\CartManagementInterface $cartManagement
	 * @param Magento\Sales\Model\Order $order
	 * @param EasyNolo\BancaSellaPro\Model\TokenFactory $easynoloTokenFactory
	 * @param EasyNolo\BancaSellaPro\Helper\Data $helper
	 * @param EasyNolo\BancaSellaPro\Model\WS\WS2S $s2s
	 * @param EasyNolo\BancaSellaPro\Model\WS\CryptDecrypt $cryptDecrypt
	 * @param Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	 * @param Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param EasyNolo\BancaSellaPro\Model\Gestpay $gestpay
	 */
	public function __construct(
		\Magento\Framework\App\Request\Http $http,
		\Magento\Integration\Model\Oauth\TokenFactory $tokenFactory,
		\Magento\Customer\Model\Session $customerSession,
		\EasyNolo\BancaSellaPro\Model\Config $config,
		\EasyNolo\BancaSellaPro\Model\Token $easynoloToken,
		\Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
		\Magento\Quote\Api\CartManagementInterface $cartManagement,
		\Magento\Sales\Model\Order $order,
		\EasyNolo\BancaSellaPro\Model\TokenFactory $easynoloTokenFactory,
		\EasyNolo\BancaSellaPro\Helper\Data $helper,
		\EasyNolo\BancaSellaPro\Model\WS\WS2S $s2s,
		\EasyNolo\BancaSellaPro\Model\WS\CryptDecrypt $cryptDecrypt,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\EasyNolo\BancaSellaPro\Model\Gestpay $gestpay
	) {

		$this->http = $http;
		$this->tokenFactory = $tokenFactory;
		$this->customerSession = $customerSession;
		$this->config = $config;
		$this->easynoloToken = $easynoloToken;
		$this->CartRepositoryInterface = $cartRepositoryInterface;
		$this->cartManagement = $cartManagement;
		$this->order = $order;
		$this->easynoloTokenFactory = $easynoloTokenFactory;
		$this->s2s = $s2s;
		$this->helper = $helper;
		$this->cryptDecrypt = $cryptDecrypt;
		$this->scopeConfig = $scopeConfig;
		$this->_storeManager = $storeManager;
		$this->_gestpay = $gestpay;
		$this->_initClient();
	}

	/**
	 * Returns Gestpay Payment Methods(such as credit card,paypal,slimpay) to user
	 *
	 * @api
	 * @return mixed[] Gestpay Payment Methods to user.
	 */
	public function getPaymentMethods() {

		$dafultMethod = array('credit-card' => array('title' => 'Credit Card', 'type' => 'CREDIT CARD'));

		$methods = $this->config->getActiveAlternativeMethods();

		$methods = array_merge($dafultMethod, $methods);

		$json = array();

		foreach ($methods as $code => $method) {
			$json[$code] = array(
				'title' => $method['title'],
				'type' => $method['type'],
			);
		}

		return json_encode($json);

	}

	/**
	 * Returns Customer Easynolo Bancasellapro Tokens
	 *
	 * @api
	 * @return mixed[] Tokens
	 */
	public function getTokens() {

		$customerId = $this->getCustomerId();

		$easynoloTokens = $this->easynoloToken->getCollection();

		if ($customerId) {

			$easynoloTokens->addFieldToFilter('customer_id', $customerId);
		}

		return $easynoloTokens->toJson();

	}

	/**
	 * place order using token for a specified cart.
	 *
	 * @api
	 * @param int $cartId
	 * @param int $tokenId
	 * @return int Order ID.
	 */

	public function placeOrderUsingToken($cartId, $tokenId) {

		$quote = $this->CartRepositoryInterface->getActive($cartId);
		$quote->setPaymentMethod('easynolo_bancasellapro'); //payment method
		$quote->setInventoryProcessed(false); //not effetc inventory
		// Set Sales Order Payment
		$quote->getPayment()->importData(['method' => 'easynolo_bancasellapro']);
		$quote->save(); //Now Save quote and your quote is ready

		$orderId = $this->cartManagement->placeOrder($quote->getId());

		$tokenModel = $this->easynoloTokenFactory->create();

		$token = $tokenModel->load($tokenId);

		if ($token && $token->getId() && ($token->getCustomerId() == $this->getCustomerId())) {

			$order = $this->order->load($orderId);

			$_helper = $this->helper;

			$params = $_helper->getGestpayParams($order, ['tokenValue' => $token->getToken()]);
			$result = $this->s2s->executePaymentS2S($params);
			$method = $order->getPayment()->getMethodInstance();

			if (!$result->getTransactionResult() || $result->getTransactionResult() == 'KO') {
				return (string) $result->getErrorDescription();

			} else {
				$method->setStatusOrderByS2SRequest($order, $result);
				if ($order->getStatus() != \Magento\Sales\Model\Order::STATUS_FRAUD) {
					$this->helper->log('Invio email di conferma creazione ordine all\'utente');
					//$order->sendNewOrderEmail();
				}
				$order->save();
			}

		}

		return $orderId;
	}

	/**
	 * place order using new credit card,paypal or other alternatives payment method for a specified cart.
	 * @param int $cartId
	 * @param null $type
	 * @return false|string
	 * @throws \Magento\Framework\Exception\CouldNotSaveException
	 * @throws \Magento\Framework\Exception\NoSuchEntityException
	 */
	public function placeOrderUsingCc($cartId, $type = null) {

		$quote = $this->CartRepositoryInterface->getActive($cartId);
		$quote->setPaymentMethod('easynolo_bancasellapro'); //payment method
		$quote->setInventoryProcessed(false); //not effetc inventory
		// Set Sales Order Payment
		$quote->getPayment()->importData(['method' => 'easynolo_bancasellapro']);

		if (!empty($type) && $type != 'credit-card') {

			$paymentData = array('alternative-payment' => $type);

			$method = $quote->getPayment()->getMethodInstance();

			$method->getInfoInstance()->setAdditionalInformation($paymentData);
		}

		$quote->save(); //Now Save quote and your quote is ready

		$orderId = $this->cartManagement->placeOrder($quote->getId());

		$order = $this->order->load($orderId);

		$params = $this->helper->getGestpayParams($order);

		$encryptedString = $this->cryptDecrypt->getEncryptString($params);

		$gestpay = $order->getPayment()->getMethodInstance();

		$url = $gestpay->getRedirectPagePaymentUrl();

		if (empty($type) || $type == 'credit-card') {
			$response = [
				'merchantCode' => $params['shopLogin'],
				'encryptedString' => $encryptedString,
			];
		}
		$response['fallbackUrl'] = $url . '?a=' . $params['shopLogin'] . '&b=' . $encryptedString;

		return json_encode($response);
		//return $url . '?a=' . $params['shopLogin'] . '&b=' . $encryptedString;

	}

	/**
	 * Calling this api, for the current user in session, magento must start a transaction on gestpay for 0,01 euro.
	 * The API must return the Encrypted String and the Merchat ID to permits the frontend to build the iframe page.
	 *
	 * @api
	 * @return mixed[]|null|string return the Encrypted String and the Merchat ID
	 */
	public function requestIframeCCValidation() {

		$customerId = $this->getCustomerId();

		if ($customerId) {

			$params = [];

			$storeId = $this->_storeManager->getStore()->getId();

			$params['shopLogin'] = $this->getConfigData('merchant_id', $storeId);

			$shopTransactionID = $customerId . '_iFrameCCValidation_' . date("H:i:s"); //your payment order identifier

			$params['shopTransactionId'] = $shopTransactionID;
			$params['uicCode'] = $this->getConfigData('currency', $storeId);

			if ($this->getConfigData('language', $storeId)) {
				$params['languageId'] = $this->getConfigData('language', $storeId);
			}

			$total = 0.01;

			$params['amount'] = round($total, 2);

			if ($this->getConfigData('tokenization', $storeId)) {
				$params['requestToken'] = 'MASKEDPAN';
			}

			$this->helper->log($params);

			try {

				$encryptedString = $this->cryptDecrypt->getEncryptString($params);

				$result = array('EncString' => $encryptedString, 'shopLogin' => $params['shopLogin']);

			} catch (\Exception $e) {
				$result = (string) $e->getMessage();
			}

			return json_encode($result);
		}
	}

	/**
	 * This API will be called from a javascript callback of the iframe, passing the encrypted string, if the transaction will be accepted,
	 * Magento should decrypt the string (through gestpay flow as already happen) and should extract the transaction_id and the token for
	 * the  credit card.
	 * Magento should delete this transaction calling the method CallDeleteS2S (as documented here https://api.gestpay.it/#calldeletes2s) .
	 * Just for reference how is already implement this method to void a payment, see method voidPayment in class
	 * EasyNolo\BancaSellaPro\Model\WS\WS2S .
	 * Magento should check if a token, for the current user in session, with the same 4 last digit returned as above already exists. If
	 * exists, the token must be replaced with new one (updating create and expire dates also).
	 * If the token not exists yet in the DB, magento save the new token.
	 *
	 * @api
	 * @param string $encryptedString encrypted string
	 * @return string|null The response from API should be "true" or an error in case of exceptions
	 */
	public function saveCCTokenFromEncryptedString($encryptedString) {
		$customerId = $this->getCustomerId();

		if ($customerId) {
			$storeId = $this->_storeManager->getStore()->getId();

			$shopLogin = $this->getConfigData('merchant_id', $storeId);

			$params = $this->helper->_getDecryptParams($shopLogin, $encryptedString);
			$result = $this->cryptDecrypt->decryptRequest($params);

			$shopTransactionID = $result->getShopTransactionID();

			if ($shopTransactionID) {

				if ($result->getToken()) {

					$this->helper->log('Salvo il token');

					$tokenCollections = $this->easynoloTokenFactory->create()->getCollection()->addFieldToFilter('customer_id', $customerId)->addFieldToFilter('token', $result->getToken());

					$tokenCollections->setPageSize(1)->setCurPage(1)->load();

					if (count($tokenCollections) > 0) {

						foreach ($tokenCollections as $tokenCollection) {

							$tokenCollection->setTokenInfo(
								$result->getToken(),
								$result->getTokenExpiryMonth(),
								$result->getTokenExpiryYear());
							$tokenCollection->setCustomerId($customerId);
							$tokenCollection->save();

						}

					} else {

						$token = $this->easynoloTokenFactory->create();
						$token->setTokenInfo(
							$result->getToken(),
							$result->getTokenExpiryMonth(),
							$result->getTokenExpiryYear());
						$token->setCustomerId($customerId);
						$token->save();
					}

					$params = [];
					$params['shopLogin'] = $this->getConfigData('merchant_id', $storeId);
					$params['shopTransactionId'] = $shopTransactionID;
					$params['bankTransactionId'] = $result->getBankTransactionID();
					$params['CancelReason'] = __('Delete order after recording of credit cards information.');

					try {

						$this->helper->log("Call S2S::callDeleteS2S Request: ");
						$this->helper->log($params);

						$result = $this->client->callDeleteS2S($params);
						$result = simplexml_load_string($result->callDeleteS2SResult->any);

						$this->helper->log("Call S2S::callDeleteS2S Response: ");
						$this->helper->log((array) $result);

						if ($result->TransactionResult == "KO") {

							return (string) $result->ErrorDescription;
						}

					} catch (\Exception $e) {
						return (string) $e->getMessage();
					}

					return true;
				}
			}

		}
	}

	/**
	 *
	 * We need an API to handle recurring payment. This API should be exposed as REST and as SOAP api and should also be possibile to call
	 * the method internally by other magento code for future development.
	 * The exposed API should be protected under admin token.
	 * This API will be called passing four parameters:
	 *
	 * customerId: magento customer id (or another attribute to identify the customer, for example the email address. This should be easy
	 * customizable in the source code)
	 *
	 * transactionId: a string rapresented the transaction identifier
	 *
	 * amount: the amount to be pay
	 *
	 * paymentMethod: if not defined will be used the first credit card's saved token for the user specified (in the future the user could
	 * choose which token using by default).
	 * if "paypal" is passed, a paypal token will be used.
	 * if "slimpay" a slimpay token will be used.
	 *
	 * The API should do a transaction for the amount and with payment method specified. Basically, the logic under this API could be the
	 * same used for a payment with token, using the transaction id and the amount passed as parameters.
	 *
	 * The api should responde "true" and with the bank transaction id when a correct transaction occur or with the relative error in other
	 * cases.
	 *
	 * Be careful: later, when ohter payment methods will be integrated as recurring (PayPal, Slimpay), the user should be able to choose which
	 * default method, and which token, use for recurring payments also.
	 *
	 *
	 * @api
	 * @param int $customerId
	 * @param string $transactionId
	 * @param float $amount
	 * @param string $paymentMethod
	 * @return mixed[]|string
	 */
	public function handlingRecurringPayments($customerId, $transactionId, $amount, $paymentMethod = null) {

		$params = [];

		$storeId = $this->_storeManager->getStore()->getId();

		$order = $this->order->loadByIncrementId($transactionId);

		$params['shopLogin'] = $this->getConfigData('merchant_id', $storeId);

		$params['uicCode'] = $this->getConfigData('currency', $storeId);

		if ($this->getConfigData('language', $storeId)) {
			$params['languageId'] = $this->getConfigData('language', $storeId);
		}

		$params['shopTransactionId'] = $transactionId;

		$token = $this->easynoloToken->getCollection()->addFieldToSelect('token')->addFieldToFilter('customer_id', $customerId)
			->addFieldToFilter('is_default', 1)
			->getFirstItem();

		$params['amount'] = round($amount, 2);

		if ($paymentMethod == 'paypal') {

			$params['shopTransactionId'] = $transactionId . "_" . date("H:i:s");

		}

		if ($paymentMethod == 'slimpay') {

			$params['OrderDetails']['BillingAddress']['CountryCode'] = $order->getBillingAddress()->getCountryId();

		}

		if ($token->getToken()) {

			$params['tokenValue'] = $token->getToken();

			$this->helper->log($params);

			$result = $this->s2s->executePaymentS2S($params);

			if (!$result->getTransactionResult() || $result->getTransactionResult() == 'KO') {

				return (string) $result->getErrorDescription();

			} else {

				if ($paymentMethod == 'slimpay') {

					$response = ['transactionResult' => (string) $result->getTransactionResult(), 'bankTransactionID' => (string) $result->getBankTransactionID()];

				} else {

					unset($params['tokenValue']);

					$params['bankTransID'] = $result->getBankTransactionID();

					$this->helper->log("Call S2S::callSettleS2S Request: ");
					$this->helper->log($params);

					$result = $this->client->callSettleS2S($params);
					$result = simplexml_load_string($result->callSettleS2SResult->any);

					$this->helper->log("Call S2S::callSettleS2S Response: ");
					$this->helper->log((array) $result);

					if ($result->TransactionResult == "KO") {

						$message = __('Capture amount of %1 online failed: %2', $order->formatPriceTxt($params['amount']), $result->ErrorDescription);
						$order->addStatusHistoryComment($message, false);
						$order->save();
						return (string) $message;
					}
					$message = __('Capture amount of %1 online done.', $order->formatPriceTxt($params['amount']));
					$order->addStatusHistoryComment($message, false);
					$order->save();

					$response = ['transactionResult' => (string) $result->TransactionResult, 'bankTransactionID' => (string) $result->BankTransactionID];
				}

				return json_encode($response);
			}

		} else {
			return "Customer token not found";
		}
	}

	/**
	 *
	 * This api used for selecting default reccuring payment method for logged-in user like credit-card,paypal,slimpay
	 *
	 * @api
	 * @param string $tokenId
	 * @return string
	 */
	public function setDefaultRecurringPaymentMethod($tokenId) {

		$customerId = $this->getCustomerId();

		if ($customerId) {

			try {

				$collections = $this->easynoloTokenFactory->create()->getCollection()
					->addFieldToFilter('customer_id', array('eq' => $customerId));

				foreach ($collections as $item) {

					if ($item->getId() == $tokenId) {
						$item->setIsDefault(1);
					} else {
						$item->setIsDefault(0);

					}

				}

				$collections->save();

				return (string) 'Selecting default reccuring payment method successfully.';
			} catch (\Exception $e) {
				return (string) $e->getMessage();
			}

		}
	}

	private function _initClient() {
		if (!extension_loaded('soap')) {
			$this->log('ERROR! Unable to create WebService client - PHP SOAP extension is required.');
			throw new \Exception('PHP SOAP extension is required.');
			return false;
		}

		$url = $this->getWSUrl();
		$this->client = new \Zend_Soap_Client(
			$url, [
				'compression' => SOAP_COMPRESSION_ACCEPT,
				'soap_version' => SOAP_1_2,
			]
		);
	}

	public function getWSUrl() {
		return $this->_gestpay->getBaseWSDLUrlSella() . $this->s2s::PATH_WS_CRYPT_DECRIPT;
	}

	/**
	 * Retrieve information from payment configuration
	 *
	 * @param string $field
	 * @param int|string|null|\Magento\Store\Model\Store $storeId
	 *
	 * @return mixed
	 */
	protected function getConfigData($field, $storeId) {
		if (strpos($field, '/') === false) {
			$path = 'payment/easynolo_bancasellapro/' . $field;
		} else {
			$path = $field;
		}
		return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
	}

	/**
	 * Returns Customer Id
	 * @return int customerId
	 */
	private function getCustomerId() {

		$customerId = $this->customerSession->getCustomerId();

		if (!$customerId) {
			$authorizationHeader = $this->http->getHeader('Authorization');

			$tokenParts = explode('Bearer', $authorizationHeader);
			$tokenPayload = trim(array_pop($tokenParts));

			/** @var Token $token */
			$token = $this->tokenFactory->create();
			$token->loadByToken($tokenPayload);

			$customerId = $token->getCustomerId();
		}

		return $customerId;

	}

}