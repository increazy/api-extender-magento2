<?php

namespace Increazy\ApiExtender\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Stock
{
    private $_stockItem;
    private $_productRepository;

    public function __construct(Item $stockItem, ProductRepository $productRepository) {
        $this->_stockItem = $stockItem;
        $this->_productRepository  = $productRepository;
    }

    public function afterGetList(ProductRepositoryInterface $subject, SearchResultsInterface $searchCriteria)
    {
        $products = [];
        foreach ($searchCriteria->getItems() as $entity) {
            $extensionAttributes = $entity->getExtensionAttributes();

            $stockItem = $this->_stockItem->load($entity->getId(), 'product_id');
            $extensionAttributes->setStock(json_encode($stockItem->getData()));
            $entity->setExtensionAttributes($extensionAttributes);
            $products[] = $entity;
        }
        $searchCriteria->setItems($products);
        return $searchCriteria;
    }
}