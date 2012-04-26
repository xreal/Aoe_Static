# AOE Static - Varnish caching for magento

This modules add advanced varnish functionalitiy to your Magento shop.
The module handle the communication between Varnish and Magento, both
on filling and on purging the cache. Therefor the modules adds
information to the response header.

In addition to that, the module has the functionality to request customer
related information such as mini cart and login state via ajax once the side 
is loaded. So this module also allows caching of pages when the customer
already has some items in the cart.

## Installation

The easiest way to install the module is with the use of modman 
(colinmollenhour/modman). Alternativly you can download the module as archive
and copy the folders accordingly. Don't simply copy the folder over your
magento installation, the pathes in the module are not the same as in the 
magento folder. See the modman file to get the relations.

## Usage

The module add a new entry into you cache list. Enable the Varnish-Cache there.
Also there is a configuration section under Advanced -> System -> Varnish Configuration.



