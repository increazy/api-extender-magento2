<?php

namespace Increazy\ApiExtender\Plugin;

use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule as RuleModel;
use Magento\Framework\Stdlib\DateTime\DateTime as DateTimeMagento;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroup;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogInventory\Model\Stock\Item;

class Rule
{
    private $_rule;
    private $_storeManager;
    private $_customerGroup;
    private $_productRepository;
    private $_dateTime;
    private $_stock;

    public function __construct(
        Item $stock,
        RuleModel $rule,
        StoreManagerInterface $storeManager,
        CustomerGroup $customerGroup,
        DateTimeMagento $dateTime,
        ProductRepository $productRepository
    ) {
        $this->_stock = $stock;
        $this->_rule = $rule;
        $this->_storeManager = $storeManager;
        $this->_customerGroup = $customerGroup;
        $this->_dateTime = $dateTime;
        $this->_productRepository  = $productRepository;
    }

    public function afterGetList(ProductRepositoryInterface $subject, SearchResultsInterface $searchCriteria)
    {
        $products = [];
        $groups = $this->_customerGroup->toOptionArray();
        $websites = $this->_storeManager->getWebsites();

        foreach ($searchCriteria->getItems() as $entity) {
            $extensionAttributes = $entity->getExtensionAttributes();

            $rules = [];
            foreach ($websites as $websiteID => $websiteConfig) {
                $rules[$websiteID] = [];
                foreach ($groups as $group) {
                    if ($entity->getTypeId() === 'configurable') {
                        $children = $entity->getTypeInstance()->getUsedProducts($entity);

                        foreach ($children as $child){
                            $rules[$websiteID][$group['value']][$child->getSku()] = $this->_rule->getRulesFromProduct(
                                $this->_dateTime->gmtDate(),
                                $websiteID,
                                $group['value'],
                                $child->getId()
                            );

                            $rules[$websiteID][$group['value']][$child->getSku()]['special_price'] = $child->getSpecialPrice();
                            $rules[$websiteID][$group['value']][$child->getSku()]['price'] = $child->getPrice();
                            $rules[$websiteID][$group['value']][$child->getSku()]['special_date'] = $child->getSpecialToDate();

                            $stockItem = $this->_stock->load($child->getId(), 'product_id');
                            $rules[$websiteID][$group['value']][$child->getSku()]['stock'] = $stockItem->getData();
                        }
                    } else {
                        $rules[$websiteID][$group['value']][$entity->getSku()]= $this->_rule->getRulesFromProduct(
                            $this->_dateTime->gmtDate(),
                            $websiteID,
                            $group['value'],
                            $entity->getId()
                        );

                        $rules[$websiteID][$group['value']][$entity->getSku()]['special_price'] = $entity->getSpecialPrice();
                        $rules[$websiteID][$group['value']][$entity->getSku()]['price'] = $entity->getPrice();
                        $rules[$websiteID][$group['value']][$entity->getSku()]['special_date'] = $entity->getSpecialToDate();

                        $stockItem = $this->_stock->load($entity->getId(), 'product_id');
                        $rules[$websiteID][$group['value']][$entity->getSku()]['stock'] = $stockItem->getData();
                    }
                }
            }

            // by_percent aplica o percentual
            // by_fixed aplica o bruto
            // to_percent o preço final é igual a esse percentual
            // to_fixed o preço final é igual a esse valor

            $extensionAttributes->setRules(json_encode($rules));
            $entity->setExtensionAttributes($extensionAttributes);
            $products[] = $entity;
        }
        $searchCriteria->setItems($products);
        return $searchCriteria;
    }
}