# Increazy api extender Magento 2

Module to add extra API in Magento 2.3.X, follow the installation steps:

1. Copy the Increazy folder to app/code.
2. Execute:

```bash
php bin/magento module:enable Increazy_ApiExtender
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
php bin/magento cache:clean
```
