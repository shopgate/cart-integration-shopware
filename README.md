# Shopgate Shopware Integration

[![GitHub license](http://dmlc.github.io/img/apache2.svg)](LICENSE.md)
[![Build Status](https://travis-ci.org/shopgate/cart-integration-shopware.svg?branch=master)](https://travis-ci.org/shopgate/cart-integration-shopware)

The Shopgate Shopware integration allows you to connect your Shopware store with the Shopgate backend.

## Getting Started
Download and unzip the [latest releases](https://github.com/shopgate/cart-integration-shopware/releases/latest) into the root folder of your Shopware installation.

## Installation and Documentation

You can find more information in our [support center](https://support.shopgate.com/hc/en-us/articles/202798446-Connecting-to-Shopware) and our [developer documentation](https://docs.shopgate.com/).

## Shop multi views and translations

We need distinguish url of plugin location to allow Shopware load correct shop view data, locale and configuration

Shop settings generally should be

- `virtual Url` field point to (example)
    - /shop/de for DE shop
    - /shop/en for EN shop

Plugin access uri should be accordingly:
- /shop/de/shopgate/plugin for DE shop view and translations
- /shop/en/shopgate/plugin for EN shop view and translations

## Changelog

See [CHANGELOG.md](CHANGELOG.md) file for more information.

## Contributing

See [CONTRIBUTING.md](docs/CONTRIBUTING.md) file for more information.

## About Shopgate

Shopgate is the leading mobile commerce platform. Online retailers use our software-as-a-service (SaaS) to provide their mobile customers with successful native shopping apps. Developers can enhance the Shopgate Cloud platform by building extensions that customize the user experience and add new functionality to our powerful ecommerce solutions.

## License

The Shopgate Shopware integration is available under the Apache License, Version 2.0.

See the [LICENSE.md](LICENSE.md) file for more information.
