<?php

namespace Magemonkeys\Gestpay\Controller;

use EasyNolo\BancaSellaPro\Model\TokenFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;

abstract class Token extends \Magento\Framework\App\Action\Action {
	/**
	 * Customer session
	 *
	 * @var Session
	 */
	protected $_customerSession;

	/**
	 * Token factory
	 *
	 * @var TokenFactory
	 */
	protected $_tokenFactory;

	/**
	 * @var \Magento\Store\Model\StoreManagerInterface
	 */
	protected $_storeManager;

	/**
	 * @var CustomerUrl
	 */
	protected $_customerUrl;

	/**
	 * @param Context $context
	 * @param TokenFactory $tokenFactory
	 * @param Session $customerSession
	 * @param StoreManagerInterface $storeManager
	 * @param CustomerUrl $customerUrl
	 */
	public function __construct(
		Context $context,
		TokenFactory $tokenFactory,
		Session $customerSession,
		StoreManagerInterface $storeManager,
		CustomerUrl $customerUrl
	) {
		parent::__construct($context);
		$this->_storeManager = $storeManager;
		$this->_tokenFactory = $tokenFactory;
		$this->_customerSession = $customerSession;
		$this->_customerUrl = $customerUrl;
	}
}
