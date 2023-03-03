## 2023-03-02 4.5.5
* Fix display payment methods

## 2022-12-26 4.5.3
* Fix bug with products tax

## 2022-11-09 4.5.2
* Add validator for CRM URL

## 2022-11-09 4.5.1
* Correction of RAM overflow during ICMP product catalog generation.

## 2022-09-30 4.5.0
* Fix path for js scripts
* Migrating to PHP 7.0.
* Change logic work with ICML catalog: added streaming generation, added generators in the ICML generation process.
* Change logic work with address

## 2022-09-05 4.4.9
* Fix bug with product tax

## 2022-09-02 4.4.8
* Fix a critical bug when working with taxes

## 2022-08-10 4.4.7
* Add support for payment method on delivery

## 2022-08-06 4.4.6
* Add automatically upload ICML in CRM
* Add filter for changing ICML product information
* Important fix bug with shipping tax

## 2022-07-18 4.4.5
* Change logic work with delivery cost
* Add price rounding from WC settings
* Add functionality for changing the time interval for cron tasks
* Fix error with empty 'paidAt'
* Change processing history by sinceId
* Fix spanish accents processing in ICML
* Fix WA icon positioning

## 2022-05-26 4.4.4
* Add product description to ICML
* Fix fatal error using API without api_key
* Add priceType processing to CRM order by history
* Add method in API V5 and delete use another version
* Fix error with integration payments
* Fix bug with changing order status by history

## 2022-03-24 4.4.3
* Fix bug in updating order number by history
* Add multiple image transfer in ICML
* Add filters for custom fields
* Fix bug with create/update customer

## 2022-02-24 4.4.2
* Delete deprecated API V4. Refactoring API V5 and history getting method
* Fix bug with use xmlId
* Add order number transfer CMS -> CRM by history
* Add documentation for registering client functionality
* Delete legacy code for update customer name and surname

## 2022-01-17 4.4.1
* Added functionality to skip some orders statuses
* Improved the create/update method when registering customers
* Add mapping metadata fields in settings
* Improvement of the user interface, plugin operation, fix bugs

## 2021-12-15 4.4.0
* Migrating to PHP 5.6. We tested the module, improved performance, security and test coverage.
* Add validate countryIso. Fix bug with duplicate customer address
* Fix bugs in history
* Fix PHP warning and deprecated
* Add documentation for module

## 2021-08-30 4.3.8
* Updated logic work address
* Added transfer of the client's comment to the WC order
* Added the ability to skip inactive statuses in settings module
* Deleted option 'Do not transmit the cost of delivery'
* Fix bug in archive upload

## 2021-08-04 4.3.7
* Fixed an error with incorrect unloading of archived clients
* Removed the "Client roles" option from the module settings

## 2021-07-27 4.3.6
* Updated the presentation of the module settings
* Fixed a bug connected with adding variable products to the catalogue as usual products

## 2021-07-22 4.3.5
* Updated version in Marketplace

## 2021-07-22 4.3.4
* Minor bugs were fixed

## 2021-07-21 4.3.3
* Redesigned the WhatsApp icon
* Added the ability to enable extended logging in the module settings
* Added the"Debug info" block
* Improved ICML catalogue generation
* Added batch export of archived orders and customers

## 2021-07-05 4.3.2
* Minor bugs were fixed

## 2021-07-02 4.3.1
* Rebranding of RetailCRM module --> Simla.com

## 2021-06-30 4.3.0
* Rebranding of RetailCRM module --> Simla.com
* Fixed a bug in the "Activate link by sku (xmlId)" option
* Added the ability to use the WhatsApp chat link on the site
* Fixed minor bugs in the history and generation of the ICML catalogue

## 2021-03-15 4.2.4
* Added a display of the total number of variable products
* Added validation for the order creation date
* Added validation for orders with auto-draft status
* Updated WP and WC versions in local tests
* Fixed a bug in the "Transfer of order number" option

## 2021-01-20 4.2.3
* Updated version in the Marketplace

## 2020-12-17 4.2.2
* RetailCRM’s redesign

## 2020-12-15 4.2.1
* RetailCRM’s redesign

## 2020-12-02 4.2.0
* Fixed a bug connected with receiving the date of creation of an unregistered user
* Changed the logic of payments by deleting the "Transfer of payment amount" option
* Fixed the “shipping” address bug. If it is empty, use a “billing” address
* Added Spanish and English translations of the main page
* Fixed a bug connected with deleting products when using the "Activate link by sku (xmlId)" option

## 2020-09-21 4.1.5
* Fixed a bug connected with transferring emails. Before being sent to RetailCRM, emails are always converted to lower case.
* Fixed a bug connected with transfers of payments of no amount
* Improved the work of discounts in the order

## 2020-08-27 4.1.4
* Added translations to transfer the prime cost of the delivery
* Fixed a bug connected with an incorrect displaying the Live Chat on the login page

## 2020-08-20 4.1.3
* Added translations for the option of customers’ role
* Added the ability to optionally transfer the prime cost of delivery

## 2020-08-20 4.1.2
* Fixed a bug connected with missing data books settings

## 2020-08-08 4.1.1
* Added a setting for selecting customer roles for upload to RetailCRM

## 2020-08-05 4.1.0
* Added the ability to connect the Live Chat

## 2020-07-28 4.0.1
* Fixed transfer of payment status

## 2020-06-18 4.0.0
* Support for corporate customers
* Support for changing a customer in an order

## 2020-06-18 3.6.4
* Passing the region / state / province name instead of the code

## 2020-06-10 3.6.3
* Improved order data updating by history

## 2020-04-13 3.6.2
* Fixed a bug that led to duplication of some customers

## 2019-03-31 3.6.1
* Fixed a bug connected with generating a product catalogue

## 2020-03-25 3.6.0
* Added the setting for transferring the payment amount

## 2019-10-07 3.5.4
* Added the ability to process identical product items

## 2019-04-22 3.5.2
* Fixed a bug connected with exporting orders to RetailCRM
* Fixed a translation error

## 2019-04-16 3.5.1
* Fixed a bug connected with plugin activation

## 2019-03-06 3.5.0
* Added a setting to deactivate uploading order changes to RetailCRM
* Added a setting for activating SKU exporting to xmlId and linking products by the “xmlId” field
* Added a setting for transferring order numbers to RetailCRM

## 2019-03-06 3.4.5
* Fixed a bug connected with adding a discount when decreasing the quantity of a product
* Moved the initialization of the settings form after the initialization of all plugins

## 2019-02-25 3.4.4
* Added generation of a unique id to the externalId of the payment being sent

## 2019-02-15 3.4.3
* Fixed saving of the payment type when creating an order when processing the history of changes on the WC side
* Fixed saving of the payment type when changing an order when processing the history of changes on the WC side
* Fixed connecting files using the checkCustomFile method

## 2019-02-07 3.4.2
* Fixed change of payment type on the WC side
* Added inactive payment types in the settings
* Removed external code generation of a customer

## 2019-01-22 v3.4.1
* Fixed archive export of customers

## 2019-01-17 v3.4.0
* Added Daemon Collector setting
* Changed the logic of data transfer for orders and customers. Delivery data is transferred to the order, payment data to the customer card.

## 2018-12-14 v3.3.8
* Added export of images for product categories to ICML

## 2018-12-11 v3.3.7
* Fixed a bug connected with activation

## 2018-12-06 v3.3.6
* Fixed module activation in RetailCRM’s marketplace when using api v4
* Expanded configuration for sending

## 2018-10-25 v3.3.5
* Added module activation in RetailCRM’s marketplace

## 2018-08-30 v3.3.4
* Fixed a bug connected with zeroing the quantity of the product in the WC order

## 2018-08-30 v3.3.3
* Added buttons to go to the plugin settings and to generate a catalogue in the WordPress admin panel
* Added transfer of payment status on v5

## 2018-08-22 v3.3.2
* Removed check for the existence of tasks in wp-cron on every page loading
* Tasks in wp-cron are now activated in the plugin settings

## 2018-08-09 v3.3.1
* Fixed a bug connected with duplication of products from WC

## 2018-08-06 v3.3.0
* Reworked the mechanics of handling change history (added merging of all changes)
* Added filter "retailcrm_history_before_save" to modify the history data

## 2018-07-19 v3.2.0
* Improved the method for selection of data on deliveries and payments in the plugin settings. (All types of payments are selected, not just allowed ones. Deliveries that are created for individual zones are transferred as services)
* Fixed bugs when processing change history
* Added tests for processing history of changes

## 2018-06-19 v3.1.1
* Fixed the code for sending data to UA
* Added new filters, and added transfer of new parameters to existing ones

## 2018-05-28 v3.1.0
* Added the ability to manually export orders to the plugin settings interface
* Fixed initialization of the UA code for sending orders on all pages

## 2018-04-26 v3.0.0
* Added tests
* Refactoring of the code
* Webhooks added

## 2018-03-22 v2.1.4
* Fixed a bug connected with the activated module without settings
* Added a filter when forming an order array
* Fixed generation of icml with incomplete product picture

## 2018-03-22 v2.1.3
* Fixed a bug on php5.3

## 2018-03-21 v2.1.2
* Added plugin localization
* Added integration with UA

## 2018-03-12 v2.1.1
* Fixed a bug connected with editing customer information

## 2018-02-26 v2.1.0
* Reworked mechanics of generating icml product catalog
* Added tax rate export to icml catalog
* Fixed recalculation of totals after changing the quantity of a product in RetailCRM

## 2018-02-19 v2.0.6
* Fixed occurrence of a Warning in PHP 7.2 when generating a product catalog
* Added a setting for exporting orders from RetailCRM with certain order methods
* Changes are exported from RetailCRM by sinceId

## 2018-02-02 v2.0.5
* Fixed an incorrect calculation of discounts for products
