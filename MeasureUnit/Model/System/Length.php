<?php
namespace CollinsHarper\MeasureUnit\Model\System;

class Length implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * To options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $returnArr = [];
        $returnArr[] = ['value' => "m", 'label' => 'Meter'];
        $returnArr[] = ['value' => "cm", 'label' => 'Centimeter'];
        $returnArr[] = ['value' => "mm", 'label' => 'Milimeter'];
        $returnArr[] = ['value' => "inch", 'label' => 'Inch'];
        $returnArr[] = ['value' => "feet", 'label' => 'Feet'];
        return $returnArr;
    }
}
