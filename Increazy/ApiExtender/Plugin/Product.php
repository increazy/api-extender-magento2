<?php

namespace Increazy\ApiExtender\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule as RuleModel;
use Magento\Framework\Stdlib\DateTime\DateTime as DateTimeMagento;

class Product
{
    private $_productRepository;
    private $_rule;
    private $_dateTime;

    public function __construct(DateTimeMagento $dateTime,RuleModel $rule, ProductRepository $productRepository) {
        $this->_dateTime  = $dateTime;
        $this->_rule  = $rule;
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
                    'min' => $this->caculateMinPrice($entity),
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
            $this->_rule->getRulesFromProduct(
                $this->_dateTime->gmtDate(),
                1,
                0,
                $child->getId()
            );

            $mins[] = $this->caculateMinPrice($child);
            $maxs[] = $child->getPrice();
        }

        return [
            'min' => min($mins),
            'max' => max($maxs),
        ];
    }

    private function caculateMinPrice($product)
    {
        $rules = $this->_rule->getRulesFromProduct(
            $this->_dateTime->gmtDate(),
            1,
            0,
            $product->getId()
        );

        $price = $product->getSpecialPrice() > 0 ? $product->getSpecialPrice() : $product->getPrice();
        foreach ($rules as $rule) {
            // var_dump($price, $rule['action_operator'], $rule['action_amount']);
            if ($rule['action_operator'] === 'by_percent') {
                $price = $price - ($price * ($rule['action_amount'] / 100));
            } elseif ($rule['action_operator'] === 'by_fixed') {
                $price = $price - $rule['action_amount'];
            } elseif ($rule['action_operator'] === 'to_percent') {
                $price = $price * ($rule['action_amount'] / 100);
            } elseif ($rule['action_operator'] === 'to_fixed') {
                $price = $rule['action_amount'];
            }
        }

        return $price;
        // by_percent aplica o percentual
            // by_fixed aplica o bruto
            // to_percent o preço final é igual a esse percentual
            // to_fixed o preço final é igual a esse valor
    }
}
