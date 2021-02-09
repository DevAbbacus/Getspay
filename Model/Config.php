<?php

namespace Magemonkeys\Gestpay\Model;

use Magemonkeys\Gestpay\Model\Config\Data as AlterNativeExtraConfig;

/**
 * Payment configuration model
 *
 * Used for retrieving configuration data by payment models
 */
class Config extends \EasyNolo\BancaSellaPro\Model\Config {
	/**
	 * @var \Magento\Framework\Config\DataInterface
	 */
	protected $_dataStorage;

	protected $_methods = null;

	/** @var AlterNativeExtraConfig wsConfig */
	protected $alterNativeExtraConfig;

	/**
	 * Construct
	 *
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	 * @param \Magento\Framework\Config\DataInterface $dataStorage
	 */
	public function __construct(
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Config\DataInterface $dataStorage,
		AlterNativeExtraConfig $alterNativeExtraConfig
	) {
		$this->_scopeConfig = $scopeConfig;
		$this->_dataStorage = $dataStorage;
		$this->alterNativeExtraConfig = $alterNativeExtraConfig;

	}

	/**
	 * Retrieve active system payments
	 *
	 * @return array
	 * @api
	 */
	public function getActiveAlternativeMethods() {
		if (is_null($this->_methods)) {

			$this->_methods = array();

			foreach ($this->_dataStorage->get('alternative_payments') as $code => $payment) {
				if ($this->_scopeConfig->getValue('payment/easynolo_bancasellapro_alternative/enable_' . $code)) {
					$this->_methods[$code] = (array) $payment;
				}
			}

			foreach ($this->alterNativeExtraConfig->get('alternative_payments') as $code => $payment) {
				if ($this->_scopeConfig->getValue('payment/easynolo_bancasellapro_alternative/enable_' . $code)) {
					$this->_methods[$code] = (array) $payment;
				}
			}

		}

		return $this->_methods;
	}
}
