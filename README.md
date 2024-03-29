# Shopgate Shopware Integration

[![GitHub license](http://dmlc.github.io/img/apache2.svg)](LICENSE.md)
[![Run CS fixer & deploy](https://github.com/shopgate/cart-integration-shopware/actions/workflows/check_and_deploy.yml/badge.svg)](https://github.com/shopgate/cart-integration-shopware/actions/workflows/check_and_deploy.yml)

The Shopgate Shopware integration allows you to connect your Shopware store with the Shopgate backend.

## Getting Started
Download and unzip the [latest release](https://github.com/shopgate/cart-integration-shopware/releases/latest) for your PHP version into the root folder of your Shopware installation.

Note: If you're on PHP 8 use the PHP 7 package.

## Installation and Documentation

You can find more information in our [support center](https://support.shopgate.com/hc/en-us/articles/202798446-Connecting-to-Shopware) and our [developer documentation](https://docs.shopgate.com/).

## Shop Multi Views and Translations

We need distinguishable URLs for the plugin location to enable Shopware loading the correct shop view data, locale and configuration.

Example:

If the shop is set up to have its virtual URL point to
- `/shop/de` for the German shop
- `/shop/en` for the English shop

then access to the plugin should be
- `/shop/de/shopgate/plugin` to get contents (catalog etc.) in German
- `/shop/en/shopgate/plugin` to get contents (catalog etc.) in English

## Changelog

See [CHANGELOG.md](CHANGELOG.md) file for more information.

## Contributing

See [CONTRIBUTING.md](docs/CONTRIBUTING.md) file for more information.

## About Shopgate

Shopgate is the leading mobile commerce platform. Online retailers use our software-as-a-service (SaaS) to provide their mobile customers with successful native shopping apps. Developers can enhance the Shopgate Cloud platform by building extensions that customize the user experience and add new functionality to our powerful ecommerce solutions.

## License

The Shopgate Shopware integration is available under the Apache License, Version 2.0.

See the [LICENSE.md](LICENSE.md) file for more information.
