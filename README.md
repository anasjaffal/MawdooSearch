# MawdooSearch | Custom SpecialPage(s) Extension

[![N|Solid](https://crunchbase-production-res.cloudinary.com/image/upload/c_lpad,h_256,w_256,f_auto,q_auto:eco/v1439984124/cw2rup83xd8s4c3zjdt8.png)](https://mawdoo3.com)

MawdooSearch is a custom special pages extension for mediawiki framework.
  - Using google custom search API.
  - Allow registered user(s) to save favourite search results.

# New Features!

  - N/A.

### Installation

MawdooSearch requires:
- MediaWiki framework [MediaWiki Installation](https://www.mediawiki.org/wiki/Manual:Installation_guide) v1.30+.
- PHP [PHP](php.net/manual/en/install.php) v5.6+ to run.
- MySql database [MySQL](https://dev.mysql.com/downloads/installer).

```sh
$ Go to mediawiki extensions folder "cd /var/www/html/my_wiki/extensions"
$ git clone https://github.com/abu7elo/MawdooSearch.git
$ cd /var/www/html/my_wiki/
$ php maintenance/update.php "Create new table called favourites".
$ Edit your LocalSettings.php file by adding the following lines at the end of file:
   // Import MawdooSearch extension class loader.
   require_once 'extensions/MawdooSearch/MawdooSearch.php';
   // Load MawdooSearch extension.
   wfLoadExtension('MawdooSearch');
   // Google search api key.
   $wgMawdooSearchGoogleApiKey = "AIzaSyBsPd_TDU5dWp6-vfr_EStQ-St2Ibv6sT8"; 
   // Google search api cx key
   $wgMawdooSearchGoogleApiCx = "000111529748108210609:takyph7nrdm";  
```

##### Now, Open your browser and navigate to your mediawiki site.
- Go to $IP/mediawiki/index.php/Special:SpecialPages
- At the end of page there is a new group called "Mawdoo3 | Special Search Extension" which contains to link 
 * Mawdoo3 - Saved Results "Show saved results for registered user"
 * Mawdoo3 - Special Search "Allow user to search for any topic using google custom search api".

### Example
http://206.189.10.213/
