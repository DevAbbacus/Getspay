<?php
namespace Magemonkeys\Gestpay\Api;

interface PaymentMethodsInterface {
	/**
	 * Returns Gestpay Payment Methods(such as credit card,paypal,sepa) to user
	 *
	 * @api
	 * @return mixed[] Gestpay Payment Methods to users.
	 */
	public function getPaymentMethods();

	/**
	 * Returns Customer Easynolo Bancasellapro Tokens
	 *
	 * @api
	 * @return mixed[] Tokens
	 */
	public function getTokens();

	/**
	 * place order using token for a specified cart.
	 *
	 * @api
	 * @param int $cartId
	 * @param int $tokenId
	 * @return int Order ID.
	 */

	public function placeOrderUsingToken($cartId, $tokenId);

	/**
	 * place order using new credit card,paypal or other alternatives payment method for a specified cart.
	 *
	 * @api
	 * @param int $cartId
	 * @param string $type credit card,paypal or other alternatives payment
	 * @return string fallbackurl.
	 */

	public function placeOrderUsingCc($cartId, $type = null);

	/**
	 * Calling this api, for the current user in session, magento must start a transaction on gestpay for 0,01 euro.
	 * The API must return the Encrypted String and the Merchat ID to permits the frontend to build the iframe page.
	 *
	 * @api
	 * @return mixed[]|null|string return the Encrypted String and the Merchat ID
	 */
	public function requestIframeCCValidation();

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
	public function saveCCTokenFromEncryptedString($encryptedString);

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
	 * if "sepa" a sepa token will be used.
	 *
	 * The API should do a transaction for the amount and with payment method specified. Basically, the logic under this API could be the
	 * same used for a payment with token, using the transaction id and the amount passed as parameters.
	 *
	 * The api should responde "true" and with the bank transaction id when a correct transaction occur or with the relative error in other
	 * cases.
	 *
	 * Be careful: later, when ohter payment methods will be integrated as recurring (PayPal, SEPA), the user should be able to choose which
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
	public function handlingRecurringPayments($customerId, $transactionId, $amount, $paymentMethod = null);

	/**
	 *
	 * This api used for selecting default reccuring payment method for logged-in user like credit-card,paypal,slimpay
	 *
	 * @api
	 * @param string $tokenId
	 * @return string
	 */
	public function setDefaultRecurringPaymentMethod($tokenId);

}