<?php
namespace CollinsHarper\Canpar\Model\Source;

class DimUnits implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * To options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $returnArr = [];
        $returnArr[] = ['value' => "I", 'label' => 'IN'];
        $returnArr[] = ['value' => "C", 'label' => 'CM'];
        return $returnArr;
    }
}
