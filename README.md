Aligent Australian Tax Rules Bundle for OroCommerce
===================================================

Facts
-----
- version: 1.0.0
- composer name: aligent/orocommerce-australia-bundle

Description
-----------
This bundle provides an series of OroCommerce fixtures that creates the necessary 
tax codes, rules, etc to implement Australian GST in OroCommerce.

Once installed the following changes will be visible via the Admin interface:
* Product and Customer Tax codes for GSTable and GST Exampt
* GST Tax rate under Taxes > Taxes
* Tax Rules to implement GST under Taxes > Tax Rules
* Tax Jurisdiction for GST under Taxes > Tax Jurisdictions

In order for GST to be calulated a product must be assigned to the "GST" tax code, 
the customer must be assigned to the "GST" tax code, and the Shipping Address for 
the order must be in Australia.  If all of these conditions are met, a tax of 10% 
will be applied to the order.

Installation Instructions
-------------------------
1. Install this module via Composer

        composer require aligent/orocommerce-australia-bundle

1. Clear cache and load fixtures
        
        php app/console cache:clear --env=prod
        php app/console oro:migration:data:load --force --env=prod
        

Support
-------
If you have any issues with this bundle, please create a 
[pull request](https://github.com/aligent/orocommerce-australia-bundle/pulls) 
with a failing test that demonstrates the problem you've found.  If you're really 
stuck, feel free to open [GitHub issue](https://github.com/aligent/orocommerce-australia-bundle/issues).

Contribution
------------
Any contribution is highly appreciated. The best way to contribute code is to open a [pull request on GitHub](https://help.github.com/articles/using-pull-requests).

Developer
---------
Jim O'Halloran <jim@aligent.com.au>

Licence
-------
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)

Copyright
---------
(c) 2017 Aligent Consulting