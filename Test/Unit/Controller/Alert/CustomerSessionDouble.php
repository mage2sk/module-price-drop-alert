<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Test\Unit\Controller\Alert;

use Magento\Customer\Model\Session;

/**
 * Minimal in-memory customer session for controller tests.
 */
class CustomerSessionDouble extends Session
{
    public array $values = [];
    private ?int $loggedInId;
    private $customerModel;

    public function __construct(?int $loggedInId = null, $customer = null, array $values = [])
    {
        $this->loggedInId = $loggedInId;
        $this->customerModel = $customer;
        $this->values = $values;
    }

    public function isLoggedIn()
    {
        return $this->loggedInId !== null;
    }

    public function getCustomerId()
    {
        return $this->loggedInId;
    }

    public function getCustomer()
    {
        return $this->customerModel;
    }

    public function getData($key = '', $clear = false)
    {
        return $this->values[$key] ?? null;
    }

    public function setData($key, $value)
    {
        $this->values[$key] = $value;
        return $this;
    }
}
