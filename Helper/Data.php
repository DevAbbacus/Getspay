<?php

namespace Magemonkeys\Gestpay\Helper;

class Data extends \EasyNolo\BancaSellaPro\Helper\Data {

	protected function setPaymentParams(&$params, $order) {
		$method = $order->getPayment()->getMethodInstance();
		$additionalData = $method->getInfoInstance()->getAdditionalInformation();

		$allowLowRiskProfile = true;

		if (!empty($additionalData) && !empty($additionalData['alternative-payment'])) {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$alternativeHelper = $objectManager->create('EasyNolo\BancaSellaPro\Helper\AlternativePayments');
			$alternatives = $alternativeHelper->getAlternativePayments();
			if (!empty($alternatives) && !empty($alternatives[$additionalData['alternative-payment']])) {
				$method = $alternatives[$additionalData['alternative-payment']];

				if (isset($params['requestToken'])) {
					unset($params['requestToken']);
				}

				$allowLowRiskProfile = false;

				$params['paymentTypes'] = array();
				$params['paymentTypes']['paymentType'] = array();
				$params['paymentTypes']['paymentType'][] = $method['type'];
				if (!empty($method['encrypt_helper'])) {
					$helperPayment = $objectManager->create($method['encrypt_helper']);
					if ($helperPayment) {
						$additional = $helperPayment->getEncryptParams($order);
						if ($additional && is_array($additional)) {
							$params = array_merge_recursive($params, $additional);
						}
					}
				}

				if (in_array('PAYPAL', $params['paymentTypes']['paymentType']) && $this->scopeConfig->getValue('payment/easynolo_bancasellapro_alternative_recurring/enable_paypal_recurring')) {
					$params['payPalBillingAgreementDescription'] = $this->scopeConfig->getValue('payment/easynolo_bancasellapro_alternative_recurring/paypal_billing_agreement_description');
				}
			}
		}

		// Low Risk Profile
		if (empty($params['requestToken']) && $allowLowRiskProfile) {
			if ($method->isLowRiskProfiledEnabled($order)) {
				$params['shopLogin'] = $method->getLowRiskProfileShopLogin();
			}
		}

		// Shop Login (for transactions with Token)
		if (!empty($params['tokenValue'])) {
			if ($tk_merchant_id = $method->getConfigData('tk_merchant_id')) {
				$params['shopLogin'] = $tk_merchant_id;
			}
		}
	}

}