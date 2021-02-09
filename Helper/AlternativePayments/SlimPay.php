<?php
namespace Magemonkeys\Gestpay\Helper\AlternativePayments;

class SlimPay extends \Magento\Framework\App\Helper\AbstractHelper {

	public function getEncryptParams(\Magento\Sales\Model\Order $order) {

		$params = array('OrderDetails' => array('CustomerDetail' => array(), 'BillingAddress' => array()));

		$params['OrderDetails']['CustomerDetail']['FirstName'] = $order->getBillingAddress()->getFirstname();
		$params['OrderDetails']['CustomerDetail']['Lastname'] = $order->getBillingAddress()->getLastname();
		$params['OrderDetails']['CustomerDetail']['PrimaryEmail'] = $order->getCustomerEmail();
		$params['OrderDetails']['CustomerDetail']['PrimaryPhone'] = $order->getBillingAddress()->getTelephone();

		$params['OrderDetails']['BillingAddress']['StreetNumber'] = 8;
		$params['OrderDetails']['BillingAddress']['StreetName'] = $order->getBillingAddress()->getStreetLine(1);
		$params['OrderDetails']['BillingAddress']['City'] = $order->getBillingAddress()->getCity();
		$params['OrderDetails']['BillingAddress']['ZipCode'] = $order->getBillingAddress()->getPostcode();
		$params['OrderDetails']['BillingAddress']['CountryCode'] = $order->getBillingAddress()->getCountryId();

		return $params;
	}

}
