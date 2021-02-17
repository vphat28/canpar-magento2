<?php

namespace CollinsHarper\Canpar\Plugin;

/**
 * This plugin is needed to fix a bug in Magento core. Read more about it here: https://github.com/magento/magento2/issues/3789
 *
 * Class ShippingMethodManagement
 */
class ShippingMethodManagement
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    private $customerAddressRepository;
    /**
     * @var \Magento\Quote\Model\Quote\AddressFactory
     */
    private $quoteAddressFactory;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $customerAddressRepository,
        \Magento\Quote\Model\Quote\AddressFactory $quoteAddressFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->quoteAddressFactory = $quoteAddressFactory;
    }

    /**
     * @param \Magento\Quote\Model\ShippingMethodManagement\Interceptor $interceptor
     * @param \Closure $originalMethod - closure that encapsulates the original "estimateByAddressId" method
     * @param int $cartId
     * @param int $addressId
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[]
     */
    public function aroundEstimateByAddressId(
        \Magento\Quote\Model\ShippingMethodManagement\Interceptor $interceptor,
        $originalMethod,
        $cartId,
        $addressId
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return [];
        }

        //load customer address
        $customerAddress = $this->customerAddressRepository->getById($addressId);

        //convert customer address to quote address
        $quoteAddress = $this->quoteAddressFactory->create();
        $quoteAddress->importCustomerAddressData($customerAddress);

        //get all shipping methods using the full ("extended") address
        $shippingMethods = $interceptor->estimateByExtendedAddress($cartId, $quoteAddress);

        return $shippingMethods;
    }
}
