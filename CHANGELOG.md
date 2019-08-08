# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [2.9.88] - 2019-07-02
### Added
- login action for web checkout
- logout action for web checkout
- getter for user data
- creator of guest carts
- getter for customer carts
- merging guest to user cart on login
- synchronization of cart changes (item add, delete & quantity)
- validation and removal of coupons in cart
- getter of checkout URL for web checkout
- synchronization of favorite list items
- synchronization of user data and addresses
- persist import cache

### Fixed
- export right item number for order items
- order debit payment method for SW >= 5.0
- error during customer import with invalid countries

## [2.9.87] - 2019-05-21
### Fixed
- export correct item number for order items

## [2.9.86] - 2019-04-23
### Fixed
- export of related and similar items
- version handling in product export

## [2.9.85] - 2019-01-11
### Added
- support for the new Shopware PayPal Plugin

### Fixed
- potential security issue in redirect

## [2.9.84] - 2018-11-20
### Changed
- uses Shopgate Cart Integration SDK 2.9.78

### Fixed
- loading the mobile redirect script failed due to wrong URL
- error during order import, in case the customer was not found

## [2.9.83] - 2018-11-08
### Added
- support for custom attributes based on new database columns
- support for the new Shopware PayPal Plus Plugin

### Changed
- Uses Shopgate Cart Integration SDK 2.9.77

### Fixed
- catch Shopware basket errors when validating items during check_stock

## [2.9.82] - 2018-09-21
### Added
- compatibility with Shopware 5.5.x

## [2.9.81] - 2018-08-22
### Fixed
- order import error with customer was not found
- test for CDN images in product export

## [2.9.80] - 2018-08-02
### Fixed
- state validation for tax rules
- export of externally hosted product images

## [2.9.79] - 2018-05-17
### Fixed
- line item totals in order confirmation mails
- tax calculation for international orders
- add order error with missing currency factor

## [2.9.78] - 2018-04-10
### Fixed
- compatibility with the Payone plugin
- adjustment for exporting attributes as part of the product description when attributes returned with empty elements

## [2.9.77] - 2018-03-12
### Fixed
- missing shipping methods in cart validation
- compatibility with the Payone plugin
- acquisition of availability text
- cart rule validation
- shipping method mapping
- exporting selected attributes as part of the product description
- how payment surcharge is deducted from shipping costs

## [2.9.76] - 2018-01-29
### Fixed
- error in cart validation

## [2.9.75] - 2018-01-25
### Fixed
- identification of the main image for parent products in export
- base price in the product export
- encoding issue in the product export
- add price group in product export
- handling of shopgate special offers

## [2.9.74] - 2018-01-09
### Fixed
- payment surcharges are not exported as shipping costs anymore in case of free shipping
- default birthday value for Shopware 5.2.0 and higher versions
- export of adresses in get_customer
- export of shipping methods in check_cart
- export of payment methods in check_cart
- payment mapping for paymorrow
- review item uid assignment
- amazon payment mapping

## [2.9.73] - 2017-11-29
### Added
- support for smarty variables within the available text during item export

### Fixed
- fixed missing attributes for item export in plugin configuration
- fixed wrong payment status when importing Paymorrow orders
- fixed wrong shipping costs in check_cart

## [2.9.72] - 2017-11-20
### Fixed
- fixed URL in product redirect 

## [2.9.71] - 2017-11-08
### Added
- when using batch processing to change order statuses to shipped or cancelled those changes will now also be
  synchronized to Shopgate for orders placed via Shopgate (props to LexXxurio)

### Changed
- changed redirect so it is done for the parent product rather than the first variant

### Fixed
- a bug that would cause import failures for orders that contained Shopgate coupons

## 2.9.70 - 2017-10-25
### Changed
- migrated Shopgate integration for Shopware to GitHub
- article sorting by category in the export for Shopware 5.3.0 and higher versions

### Fixed
- a bug that caused the plugin to fail when custom implementations of Shopware interfaces were used instead of the
  default ones
- added check to prevent attributes that do not have group ids from being added to products

## 2.9.69
### Fixed
- attribute translation issue
- redirect for variants
- a bug in the export of orders to the app that caused issues with images and links to ordered products

## 2.9.68
- added check for category streams so they can be applied correctly
- fixed omitting net-only customer groups in tax export
- modified method of getting Shopware order number for mobile orders. It now uses native Shopware method.

## 2.9.67
- fixed an incompatibility with PHP versions below 5.4

## 2.9.66
- fixed typos in the plugin settings
- fixed translation issue with option group names and option names
- fixed export of customer group id in cart validation
- no longer exports reviews of excluded or removed items
- fixed compatibility issue with Shopware 5.3.0
- fixed usage of risk management within the cart validation

## 2.9.65
- add support for custom field "customer_comment" in order addresses

## 2.9.64
- fixed product redirect
- uses Shopgate Library 2.9.65

## 2.9.63
- add support for custom field "customer_comment"
- enabled embedding iOS smartbanner and native app deeplinks if enabled in Shopgate Merchant Admin
- add new event to manipulate coupon information in cart "Shopgate_CheckCart_Voucher_FilterInfo"
- add new event to add or filter coupons in cart/order "Shopgate_LoadVoucher_FilterResult"

## 2.9.62
- improved logging
- fixed issue while loading configuration
- fixed missing prices for products from deactivated categories
- fixed missing tax rates

## 2.9.61
- improved export of stream categories
- add support for selection based stream categories
- default values for attributes of customers, billing addresses and shipping addresses are now saved upon importing orders
- fixed missing payment method in check cart response

## 2.9.60
- improved handling of product stream categories in Shopware 5.1
- fixed issue preventing redirect of start page when it is a blog page and plugin configuration allows blogs to be redirected
- fixed item export issue in case only one root category exists
- added support of payment method BestitAmazonPay
- fixed an issue where the customers birthday was not correctly transmitted to shopware

## 2.9.59
- added support for product stream categories and Shopware 5.1
- fixed issue in fetching customer in Shopware below 5.0
- fixed missing tax rates

## 2.9.58
- fixed problem with missing address parts in order import
- improved performance of product export, in case product streams are used
- fixed configuration of the attribute export for Shopware 5.2

## 2.9.57
- excluded rejected and incomplete/cancelled orders from being exported to shopgate via get_orders

## 2.9.56
- improved default value for order status
- added additional payment information in order attributes for amazon payment orders

## 2.9.55
- fixed free shipping for products in the item export
- uses Shopgate Library 2.9.60

## 2.9.54
- fixed orders always being imported into the default shop for Shopware versions 5.2 or higher
- implemented exclusion of specific products from the item export
- fixed performance issues caused by the plugin if activated
- uses Shopgate Library 2.9.59

## 2.9.53
- fixed issues with php7
- removed php5.3 incompatible array syntax
- uses Shopgate Library 2.9.58
- fixed saving shopgate configuration values via plugin api method set_settings
- improved base price calculation
- improved cart validation

## 2.9.52
- implemented support of categories with product streams for Shopware 5.2
- improved compatibility with the Shopware License Manager
- fixed issue in installation process of the module itself

## 2.9.51
- fixed tax export
- improved coupon validation
- fixed issue in installation process of the module itself

## 2.9.50
- fixed shipping addresses in order import

## 2.9.49
- improved state code recognition in addresses
- improved synchronization of canceled orders
- fixed error during order import for registered customers
- fixed billing addresses in order import

## 2.9.48
- plugin is now compatible with Shopware 5.2

## 2.9.47
- fixed bug in order import

## 2.9.46
- fixed bug in order synchronisation
- fixed bug in tax export

## 2.9.45
- added supplier number to product export

## 2.9.44
- fixed export of inactive tax rules
- fixed export in case of multi shops
- fixed category sort order in product export
- now mapping SEPA debit payment method

## 2.9.43
- fixed order import issue related to SwagDHL
- now adding EAN to items in order import
- improved available text in product export

## 2.9.42
- fixed issue with the export of child products without tierprices
- fixed issue with product taxes in order import
- fixed bug in cart validation: missing payment method
- fixed bug in cart product export: missing products
- improved mapping for PayPal+ Invoice orders

## 2.9.41
- fixed error when redirecting deactivated products
- fixed a bug in exporting product images when the plugin is not located in the "Community" folder

## 2.9.40
- improved pseudoprice detection

## 2.9.39
- file downloads can now be exported in the product description
- improved export of payment methods for registered customers in check_cart

## 2.9.38
- improved add_order: now setting the customer number in the billing address

## 2.9.37
- fixed a bug in product export: inactive details were exported as saleable

## 2.9.36
- fixed created_time in get_orders
- fixed return of wrong error codes in check_cart
- fixed a bug that caused inconsistent invoice PDF generation for net orders
- improved handling of corrupted variants in product export

## 2.9.35
- fixed price export for article details
- payment methods are now synched to shopgate via get_settings
- check_cart now returns all valid payment methods for current cart
- fixed error during addOrder concerning missing stateID
- fixed error get_customer concerning missing street numbers

## 2.9.34
- fixed product not found error in check_cart in case simple product was configured as variant article
- fixed bug in shopgate plugin settings: attributes drop-down was empty in Shopware 5.x
- fixed bug add_order: parent SKU was not always used
- fixed issue with missing Amazon button when AmazonPaymentsAdvanced is also installed

## 2.9.33
- improved handling of custom fields
- fixed missing shipping methods in cart validation method

## 2.9.32
- extended support of purchase steps
- added possibility to redirect index blog pages
- fixed a bug with child products without tier price data
- uses shopgate Library 2.9.36
- fix address items for shopware 5.x.x

## 2.9.31
- fixed bug with missing images for children
- fixed bug with missing sale_prices for children in sub-shops
- set default position to 450 for event listener "Shopware_Modules_Order_SendMail_Send"
- removed logic to prevent exporting tier prices with an amount of zero
- order import: payment info wasn't always displayed correctly

## 2.9.30
- fixed category export for shopware versions below 4.3.3
- updated Shopgate Library to 2.9.33
- native support of PayPal Plus payments
- fixed bug in exporting products that have only one child product
- fixed bug in image export for simple products
- fixed bug in ping function

## 2.9.29
- fixed bug in xml export for parent products without any children
- fixed bug in add_order with a voucher
- fixed product review export
- fixed issue in category export
- fixed issue in product image export
- fixed an incompatibility with PHP versions below 5.4

## 2.9.28
- fixed setting addition order information country for mail templates
- plugin is now compatible with Shopware 5.1
- fixed incomplete uids for related products in product export
- added search and brand redirect type
- fixed wrong tierprices in product export in case of deleted customer groups
- fixing issue with add_order, overwriting address state

## 2.9.27
- fixed issue in Shopgate configuration
- fixed issue in product export concerning stacked products
- fixed tax class in product export

## 2.9.26
- live check for shopping cart supports customer groups now
- reworked tax export

## 2.9.25
- fixed issue with missing street number
- return stock quantity for products in cart validation functionality
- fixed a bug where coupons were not substracted from an order's total net amount
- fixed prodcut not found bug if csv export is still used

## 2.9.24
- updated Shopgate Library to 2.9.22
- native support of Payolution payments

## 2.9.23
- fix child tierprices

## 2.9.22
- updated Shopgate Library to 2.9.21
- fixed bug in get_orders
- tracking_number not exported anymore to avoid errors in backend
- item uids do not collide anymore
- fixed a bug in redirecting from main products to the mobile website
- removed German changelog

## 2.9.21
- updated Shopgate Library to 2.9.19

## 2.9.20
- fixed a bug in exporting products for Shopware versions below 5.0

## 2.9.19
- payment related data like bank account information are saved from now on before the complete order is saved into database
- fixed problem with wrong discount prices and artcile variants
- implemented transferring the GET parameter "sPartner" on mobile redirect and using it on importing orders
- fixed issue in export of tax rules of tax free countries
- improved performance of the article export

## 2.9.18
- fixed a display error for payment costs in the order confirmation email
- fixed a bug that prevented using the REST API in some configurations
- changed date format in reviews export to prevent errors when importing to shopgate

## 2.9.17
- updated Shopgate Library to 2.9.13
- add_order: fixed a bug that could cause line items to be missing in the order
- export (XML): fixed a bug that could cause the export to crash
- removed usage of function array_column() which requires PHP 5.5

## 2.9.16
- get_settings: tax rates/rules were not exported correctly
- fixed error with minimum quantity in product export
- export (XML): products that have been deactivated in Shopware are not exported anymore

## 2.9.15
- get_customer: tax class wasn't exported
- add_order: fixed a bug concerning the payment method
- export (XML/CSV): products that have been deactivated in Shopware are now exported inactive/not saleable as well

## 2.9.14
- variations and properties in sub shops using translations from other sub shops are correctly translated now as well
- The root category can now be selected in the plugin configuration
- Minimum order quantity will now also be exported in case of deactivates stock handling
- Fixed a bug in order import

## 2.9.13
- product export (XML): products are now also linked to parent categories
- for sub shops the translated properties and variations are now exported if translations are available

## 2.9.12
- improvement in export of product measures

## 2.9.11
- bug in setting the active status for products set fixed

## 2.9.10

## 2.9.9
- plugin is now compatible with Shopware 5
- various minor bugfixes

## 2.9.8
- Product export: measures are now also being exported (as properties)
- implemented mapping for Sofortüberweisung
- implemented mapping for Amazon Payments
- implemented mapping for Paymorrow

## 2.9.7
- fixed a bug in the calculation of basic prices for child products
- in the plugin settings a list of article custom fields that should be attached to the description can be defined now

## 2.9.6
- additional pricing on product variations is now also exported when using the XML export

## 2.9.5
- Fixed problem with redeeming individual coupons
- Country data for order confirmation mails will be set now
- Fixed problem with products and purchase steps
- Export of accessory and similar articles
- Removes unnecessary configuration for redirecting all pages
- Orders with Billsafe will be mapped to the payment module SwagPaymentBillsafe
- No export for products, categories and reviews also available via XML
- Export of tier prices are now available via XML

## 2.9.4
- Added order synchronisation (library function get_orders)
- Export allowed shipping countries to mobile website
- Improved compatibility to Shopware < 4.2.x

## 2.9.3
- Removed unnecessary configuration for mapping of incoming shopgate orders
- implemented method check_stock to be able to check stock in realtime
- Fixes incompatibility with plugin SwagDHL, addOrder will not fail anymore in case SwagDHL is active
- Orders with PayPal will be mapped to the payment module SwagPaymentPaypal
- Fixed problem with export of properties
- Fixed problem with wrong base price
- Fixed problem with wrong row price in order email
- updated Shopgate Library to 2.9.6

## 2.9.2
- Fixed problem with order
- updated Shopgate Library  2.9.3

## 2.9.0
- Fixed problem with missing products when using check_cart with coupons
- Fixed problem with price sorting and article export
- values of custom input fields (invoice/delivery address, order) will be added to the order history during importing an order
- updated Shopgate Library to 2.9.2
- On default not all pages are redirected anymore

## 2.7.2
- Plugin configuration now also available in english
- export of product properties now also supports multiselects
- excluded manufacturer pages from mobile redirect
- export correct position of products within categories

## 2.7.1
- Fixed problem with rounded coupon prices
- Fixed problem with empty fields in register_customer

## 2.7.0
- customer which register at the mobile site will be transferred to the shop (register_customer)
- updated Shopgate Library to 2.7.0
- Extended possibility to remotely set the plugin configuration via request
- fixed wrong tax for order items in case tax is 0 percent

## 2.5.8
- default translations of the availalable_text are now supported during product export
- added support for Showpare 4.3
- fix for wrong shipping costs in check_cart

## 2.5.7
- the plugin isntallation should not abort on a false sql statement anymore
- the prices of the order items wil now also be taken from the shopgate order

## 2.5.6
- fixed error with redirect link for non existing products

## 2.5.5
- fixed error with missing releasedate in available text
- fixed error with orders which contain only 0% tax articles

## 2.5.4
- fixed error with missing function getTaxRates

## 2.5.3
- shipping information of method check_cart now also support attribute based surcharges

## 2.5.2
- method check_cart now also returns cart item stock information
- added product properties to items export
- additional validation in attribute export of products

## 2.5.1
- fixed problem when exporting variants

## 2.5.0
- fixed problem with mobile redirect in backend
- order cancellation items are now identified by the shopgate order item id to eliminate mapping problems to the cancelled items
- updated Shopgate Library to 2.5.3

## 2.4.5
- a problem has been resolved concerning the export of variation products
- article descriptions on product export should not be empty anymore if there is actually a description set
- the plugin can now be reinstalled without having problems with previously inported orders afterwars

## 2.4.4
- the item export now operates more efficient
- fixed a bug regarding the calculation of prices while exporting the products csv file
- the product deeplink url is now exported as a seo link

## 2.4.3
- products are now added via the shopping cart while importing orders to assure that all necessary data is present. These data is then available for sending e-mails after the orders import
- article name is exported by shopware itself.

## 2.4.2
- shipping methods are now transmitted out of the shoppingsystem to the mobile shop, to display exactly the same shipping methods as they would be shown in the online shop for that specific cart
- order delivery notes are now automatically mapped using the order data instead of a, now removed, seperate plugin setting on the configuration page
- updated Shopgate Library to 2.4.16

## 2.4.1
- an error has been fixed, that prevented the creation of guest customers while importing an order

## 2.4.0
- the imgate urls while exporting products are now also built correctly, even if the host-path does not match to the called shop path
- the language setting on the Shopgate plugin configuration page has been removed, since it's not needed anymore
- the mobile redirect functionality now only redirects to the mobile shop if all neccessary settings are available from the plugin configuration
- it's now possible to remotely set the plugin configuration via request
- updated Shopgate Library to 2.4.14

## 2.3.26
- It is now possible to import the orders using the given shipping method instead of a fixed one. This feature can be enabled on the Shopgate plugin configuration page. The desired shipping methods need to match up with the corresponding ones in the shop by their names.
- The given connect-data is now checked for consistency while importing orders, whenever enough data is available for the test

## 2.3.25
- the mobile redirect now references to the main attribute, instead of the first available one, in case if a main attribute is available
- the available text is now exported to be exactly the same as it is shown by the default template of Shopware

## 2.3.24
- Improved system for article images import, which is now capable of exporting product images in regard of the sort order as well as the configurator product image mapping
- imported orders are now also transferred by the connector of plentymarkets for shops that have the dedicated module

## 2.3.23
- the cleared date is now also set correctly for the cases in which the order is not paid yet

## 2.3.22
- the default customer group is now marked accordingly while exporting the settings
- the shopgate module will not post any error messages anymore, whenever an external module tries tu execute a non existing action

## 2.3.21
- Invoice and delivery adresses are now correctly transmitted

## 2.3.20
- imported orders are now correctly inserted to the import-table

## 2.3.19
- whenever a country code can not be found while importing an order, a messege is displayed, that is now easier to understand
- a bug has been fixed which caused shipping times to be exported wrong in some cases.

## 2.3.18
- the payment method in the orders import will now be set correctly again
- canceling single positions won't affect the shipping costs, unless the complete order is canceled

## 2.3.17
- a bug has been fixed which caused shipping times to be exported wron in some cases.
- the article translations are now exported correctly if there are any available
- the default customer group of the specific subshop is now utilized for the product export, as well as the orders import

## 2.3.16
- the orders import does not stop anymore after saving the order, which has been caused to abort by the data-updates inside of the update functionality of the doctrine models

## 2.3.15
- the order import process, as well as te categories and products csv export will now also work properly on servers with high error level settings
- the product csv export does not abord anymore if a "NULL"-value is set for the articles main detail id instead of an actual article detail id.
- some missing article properties are now exported correctly

## 2.3.14
- An optimization has been done to lower the memory usage while exporting product csv files
- updated Shopgate Library to 2.3.10

## 2.3.13
- A bug has been fixed which causes wrong shipping times in some cases.

## 2.3.12
- additional functionality has been included to be able to track memory issues
- updated Shopgate Library to 2.3.9

## 2.3.11
- The function createReviewsCsv() can now be provided (via POST) with a list of articleIds (for debugging purposes).

## 2.3.10
- Article reviews should only be exported if the article itself is also exported.

## 2.3.9
- the customer login via Shopgate-Connect does not deliver wrong umlauts anymore

## 2.3.8
- the product csv file export does not fail on inconsistend data anymore
- an issue has been fixed that caused the product-csv-export to fail on computing the sort order of products in categories

## 2.3.7
- the sort order of categories has been changed in Shopware 4.1.3, that can now be handled by the plugin
- media entries without thumbnail data will no more cause error messages to be shown in the csv-file

## 2.3.6
- changing the status of order article details on orders that have been imported, will no more cause sending redundant order article cancelations

## 2.3.5
- the sort order of product images on variation articles should now be set correctly on exporting products
- on importing orders, the user should now be set correctly, even on doubles of customer numbers in the shopsystem database. The invoice- and delivery address entries for the imported order should now always contain a userID
- the order import proccess will not be terminated anymore, when expected products in the shop are missing

## 2.3.4
- the new shopware 4 sort mechanism is now supported by the plugin
- the customer group and the subshop-id is now set on creating new user accounts, the default customer group (EK) is used for this
- updated Shopgate Library to 2.3.5

## 2.3.3
- updated Shopgate Library to 2.3.4
- a problem has been fixed that happened the shipping costs to be shown with 0% tax on created pdf invoices
- the category export should now also function properly, even on inconsistent media data in the shop
- fix bug od add order if a shopgate coupon exits

## 2.3.2
- updated Shopgate Library to 2.3.2
- add Option to activate/deactivate api for each subshop
- implements new action 'get_settings' and prepare new csv format
- export sort order of products in categories
- fix bug on export descriptions

## 2.3.1
- updating the orders status of a non-Shopgate order does not cause an error message anymore

## 2.3.0
- updated Shopgate Library to 2.3.1
- an error has been solved, that happened to cause an error message on updating orders
- fix bug on export product images

## 2.2.8
- a version problem has been solved, which happened to skip the database-table updates, if plugins have been re-installed instead of being updated

## 2.2.7
- cancellations are reported to shopgate now
- attributes can be limited to given id's
- if original article image does not exists the larges thumbail will export
- payment informations will show correct in emails
- better export of basic price

## 2.2.6
- fix error with umlauts on add new orders
- insert correct gender on order import
- option for deactivating sold out articles is read from system now
- mobile website can show by JavaScript or HTTP-Header

## 2.2.5
- fix bug on export product images
- do not redirect on other languages
- fix export of parent/child articles
- do not delete internal comment on update

## 2.2.4
- Fix bug on send order confirmation mail
- export of first variant of a configurable product

## 2.2.3
- fix big confirm shipping
- clear EntityManager after save order
- resolve missing article name in orders
- fix bug for new Shopware 4.1 (e.g. mobile redirect)

## 2.2.2
- changes for Shopware marketplace
- dispatch id is set again on add order

## 2.2.1
- fix description export

## 2.2.0
- updated Shopgate Library to 2.2.0
- support for new actions check_cart and redeem_coupons to user coupons from shop on mobile devices
- change ShopgateConnect for Shopware 4.1 RC1

## 2.1.11
- updated Shopgate Library to 2.1.26
- fix article inforamtion in order mails send from shopware
- bugfix on calculationg netto amount
- option to deactivate order mails
- bugfix export preview image for master articles

## 2.1.10
- updated Shopgate Library to 2.1.24
- bug fixes

## 2.1.9
- fix bug with taxes
- fix some export bugs

## 2.1.8
- export gross prices

## 2.1.7
- updated Shopgate Library to 2.1.23
- export package unit
- fix sort of attributes
- fix attribute images

## 2.1.6
- bugfixes
- support for updates
- refactor attributes export
- added fields for min and max order quantity

## 2.1.5
- fix pament costs
- fix phone number is null
- bug fixes

## 2.1.4
- add phone number to contact
- bugfix mobile redirect

## 2.1.3
- updated Shopgate Library to 2.1.17
- BugFix

## 2.1.2
- updated Shopgate Library to 2.1.15
- fix error on add_order

## 2.1.1
- updated Shopgate Library to 2.1.4 to fix an internal error

## 2.1.0
- New Library 2.1.x

## 2.0.2
- support for shopware 4.0.4

## 2.0.1
- support for Shopware 4.0.1
- export category image
- export block prices
- add payment cost to order
- check version of Shopware during installation and cancle if version != 4

## 2.0.0
- support for Shopware 4.0.0
- migrate plugin from Shopware 3.5.x to 4.0.x
- use doctrine models

[Unreleased]: https://github.com/shopgate/interface-shopware/compare/2.9.88...HEAD
[2.9.88]: https://github.com/shopgate/interface-shopware/compare/2.9.87...2.9.88
[2.9.87]: https://github.com/shopgate/interface-shopware/compare/2.9.86...2.9.87
[2.9.86]: https://github.com/shopgate/interface-shopware/compare/2.9.85...2.9.86
[2.9.85]: https://github.com/shopgate/interface-shopware/compare/2.9.84...2.9.85
[2.9.84]: https://github.com/shopgate/interface-shopware/compare/2.9.83...2.9.84
[2.9.83]: https://github.com/shopgate/interface-shopware/compare/2.9.82...2.9.83
[2.9.82]: https://github.com/shopgate/interface-shopware/compare/2.9.81...2.9.82
[2.9.81]: https://github.com/shopgate/interface-shopware/compare/2.9.80...2.9.81
[2.9.80]: https://github.com/shopgate/interface-shopware/compare/2.9.79...2.9.80
[2.9.79]: https://github.com/shopgate/interface-shopware/compare/2.9.78...2.9.79
[2.9.78]: https://github.com/shopgate/interface-shopware/compare/2.9.77...2.9.78
[2.9.77]: https://github.com/shopgate/interface-shopware/compare/2.9.76...2.9.77
[2.9.76]: https://github.com/shopgate/interface-shopware/compare/2.9.75...2.9.76
[2.9.75]: https://github.com/shopgate/interface-shopware/compare/2.9.74...2.9.75
[2.9.74]: https://github.com/shopgate/interface-shopware/compare/2.9.73...2.9.74
[2.9.73]: https://github.com/shopgate/interface-shopware/compare/2.9.72...2.9.73
[2.9.72]: https://github.com/shopgate/interface-shopware/compare/2.9.71...2.9.72
[2.9.71]: https://github.com/shopgate/interface-shopware/compare/2.9.70...2.9.71
