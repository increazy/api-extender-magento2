<?php

namespace Increazy\ApiExtender\Plugin;

use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule as RuleModel;
use Magento\Framework\Stdlib\DateTime\DateTime as DateTimeMagento;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroup;
use Magento\Store\Model\StoreManagerInterface;

class Rule
{
    private $_rule;
    private $_storeManager;
    private $_customerGroup;
    private $_productRepository;
    private $_dateTime;

    public function __construct(
        RuleModel $rule,
        StoreManagerInterface $storeManager,
        CustomerGroup $customerGroup,
        DateTimeMagento $dateTime,
        ProductRepository $productRepository
    ) {
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
                    $rules[$websiteID][$group['value']] = $this->_rule->getRulesFromProduct(
                        $this->_dateTime->gmtDate(),
                        $websiteID,
                        $group['value'],
                        $entity->getId()
                    );
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