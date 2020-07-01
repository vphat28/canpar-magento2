<?php
namespace CollinsHarper\Canpar\Model\Source;

class WeightUnits implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * To options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $returnArr = [];
        $returnArr[] = ['value' => "LB", 'label' => 'LBs'];
        $returnArr[] = ['value' => "KG", 'label' => 'KGs'];
        return $returnArr;
    }
}
