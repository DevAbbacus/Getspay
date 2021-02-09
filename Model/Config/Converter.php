<?php

namespace Magemonkeys\Gestpay\Model\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface {
	/**
	 * {@inheritdoc}
	 */
	public function convert($source) {
		$xpath = new \DOMXPath($source);
		return [
			'alternative_payments' => $this->convertAlternativePayments($xpath),
		];
	}

	/**
	 * Convert groups xml tree to array
	 *
	 * @param \DOMXPath $xpath
	 * @return array
	 */
	protected function convertAlternativePayments(\DOMXPath $xpath) {
		$config = [];
		/** @var \DOMNode $group */
		foreach ($xpath->query('/config/alternative_payments/payment') as $payment) {
			$paymentInfo = [];
			$id = null;
			/** @var $groupSubNode \DOMNode */
			foreach ($payment->childNodes as $groupSubNode) {
				switch ($groupSubNode->nodeName) {
				case 'code':
					$id = $groupSubNode->nodeValue;
				default:
					$paymentInfo[$groupSubNode->nodeName] = $groupSubNode->nodeValue;
					break;
				}
			}
			if ($id) {
				$config[$id] = $paymentInfo;
			}
		}

		return $config;
	}
}
