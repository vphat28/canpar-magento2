<?php
namespace CollinsHarper\Canpar\Model\Source;

/**
 * Class Method
 */
class Method implements \Magento\Framework\Option\ArrayInterface
{

    protected $shippingMethod;
    
    public function __construct(
        \CollinsHarper\Canpar\Model\Carrier\ShippingMethod $shippingMethod
    ) {
        $this->shippingMethod  = $shippingMethod;
    }
    
    public function toOptionArray()
    {
        $arr = [];
        $model = $this->shippingMethod;
        foreach ($model->getCode('method') as $v => $l) {
            $arr[] = ['value' => $v, 'label' => $l];
        }
        return $arr;
    }
}
