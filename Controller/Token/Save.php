<?php

namespace Magemonkeys\Gestpay\Controller\Token;

use EasyNolo\BancaSellaPro\Model\Token;
use EasyNolo\BancaSellaPro\Model\TokenFactory;
use Magemonkeys\Gestpay\Controller\Token as TokenController;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class Save extends TokenController implements HttpPostActionInterface {

	public function __construct(
		Context $context,
		TokenFactory $tokenFactory,
		Session $customerSession,
		StoreManagerInterface $storeManager,
		CustomerUrl $customerUrl
	) {

		parent::__construct(
			$context,
			$tokenFactory,
			$customerSession,
			$storeManager,
			$customerUrl
		);
	}

	public function execute() {
		if ($this->getRequest()->isPost() && $this->getRequest()->getPost('is_default')) {

			$tokenId = $this->getRequest()->getPost('is_default');
			$customerId = $this->_customerSession->getCustomer()->getId();

			try {

				$collections = $this->_tokenFactory->create()->getCollection()
					->addFieldToFilter('customer_id', array('eq' => $customerId));

				foreach ($collections as $item) {

					if ($item->getId() == $tokenId) {
						$item->setIsDefault(1);
					} else {
						$item->setIsDefault(0);

					}

				}

				$collections->save();

				$this->messageManager->addSuccessMessage(__('Selecting default reccuring payment method successfully.'));
			} catch (LocalizedException $e) {
				$this->messageManager->addErrorMessage($e->getMessage());
			} catch (\Exception $e) {
				$this->messageManager->addExceptionMessage($e, __('Something went wrong with when you selecting default reccuring payment method.'));
			}
		}
		$this->getResponse()->setRedirect($this->_redirect->getRedirectUrl());
	}

}
