<?php

namespace Increazy\ApiExtender\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchResultsInterface;

class Product
{
    private $_productRepository;

    public function __construct(ProductRepository $productRepository) {
        $this->_productRepository  = $productRepository;
    }

    public function afterGetList(ProductRepositoryInterface $subject, SearchResultsInterface $searchCriteria)
    {
        $products = [];
        foreach ($searchCriteria->getItems() as $entity) {
            $extensionAttributes = $entity->getExtensionAttributes();

            if ($entity->getTypeId() === 'configurable') {
                
                $extensionAttributes->setPriceRange(json_encode($this->getMaxAndMin($entity)));
            } else {
                $extensionAttributes->setPriceRange(json_encode([
                    'min' => $entity->getSpecialPrice() > 0 ? $entity->getSpecialPrice() : $entity->getPrice(),
                    'max' => $entity->getPrice(),
                ]));
            }
            

            $entity->setExtensionAttributes($extensionAttributes);
            $products[] = $entity;
        }
        $searchCriteria->setItems($products);
        return $searchCriteria;
    }

    private function getMaxAndMin($product)
    {
        $children = $product->getTypeInstance()->getUsedProducts($product);
        $maxs = [];
        $mins = [];
        foreach ($children as $child){
            $mins[] = $child->getSpecialPrice() > 0 ? $child->getSpecialPrice() : $child->getPrice();
            $maxs[] = $child->getPrice();
        }

        return [
            'min' => min($mins),
            'max' => max($maxs),
        ];
    }
}
