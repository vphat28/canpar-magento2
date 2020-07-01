<?php
namespace CollinsHarper\Canpar\Model\Source;

class Handling implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * To options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $returnArr = [];
        $returnArr[] = ['value' => "%", 'label' => '% from total'];
        $returnArr[] = ['value' => "fixed", 'label' => 'Fixed'];
        return $returnArr;
    }
}
