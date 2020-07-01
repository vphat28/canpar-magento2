<?php
namespace CollinsHarper\MeasureUnit\Model\System;

class ProductAttributes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeFactory
     */
    protected $attributeFactory;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeFactory
    ) {
    
        $this->attributeFactory = $attributeFactory;
    }
    /**
     * To options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $returnArr = [];
        $collections = $this->attributeFactory->getCollection();

        foreach ($collections as $attribute) {
            $returnArr[] = [
                'label' => $attribute->getAttributeCode(),
                'value' => $attribute->getAttributeName()
            ];
        }

        return $returnArr;
    }
}
