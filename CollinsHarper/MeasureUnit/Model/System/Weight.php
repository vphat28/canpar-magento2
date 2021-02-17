<?php
namespace CollinsHarper\MeasureUnit\Model\System;

class Weight implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * To options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $returnArr = [];
        $returnArr[] = ['value' => "kg", 'label' => 'kg'];
        $returnArr[] = ['value' => "gram", 'label' => 'gram'];
        $returnArr[] = ['value' => "lb", 'label' => 'lb'];
        $returnArr[] = ['value' => "oz", 'label' => 'oz'];
        return $returnArr;
    }
}
