=== RealHomes Memberships ===
Contributors: inspirythemes, saqibsarwar, fahidjavid
Tags: Membership, Real estate, Paid listing, Real estate memberships, Payments
Requires at least: 6.0
Tested up to: 6.6.0
Stable tag: 3.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Membership packages plugin for RealHomes Real Estate theme only.

== Description ==

## RealHomes Memberships Plugin

Unlock the full potential of the [RealHomes](https://realhomes.io/) theme with our streamlined **membership plugin**. Designed for simplicity and ease of use, our plugin centralizes all functionalities — from settings to membership management and customer payment receipts — within a single, intuitive menu.

### Simplified Management
- **One Menu** for all settings and features
- Easy management of memberships and customer receipts

### Stripe Integration
- Receive payments for membership packages using Stripe payment method and supported cards
- Create and link Stripe subscriptions with on-site memberships using Stripe Plan ID for recurring payments

### PayPal Payments
- Accept payments via MasterCard, Visa, and other PayPal-supported credit cards
- Create and link PayPal subscriptions with on-site memberships using PayPal Plan ID for recurring payments

### Wire Transfer Support
- For traditional payment methods, our plugin is equipped to handle Wire Transfers
- Automatically email customers with membership and bank account details for Wire Transfer

### WooCommerce Payments
- Leverage the power of WooCommerce Payments for secure and efficient membership transactions
- Benefit from a wide array of payment methods supported through WooCommerce's extensive gateway network

### Recurring Memberships
- Create recurring memberships for a steady revenue stream
- Compatible with both Stripe and PayPal
- Automatic receipt generation and email notifications post-payment

Simplify your membership management and elevate user experience with our comprehensive, one-stop membership solution.

### Helpful Resources
- [Learn How to Implement the Plugin with RealHomes Theme](https://inspirythemes.com/realhomes-memberships-setup/)


== Installation ==

### Method 1: Manual Installation via FTP

1. **Extract the Contents**: Locate the downloaded `inspiry-memberships.zip` file and extract its contents using your preferred file decompression tool.
2. **Upload to WordPress**: Using an FTP client or your web hosting control panel's file manager, upload the extracted `inspiry-memberships` folder to the `/wp-content/plugins/` directory on your WordPress website's server.
3. **Activate the Plugin**: Log in to your WordPress dashboard, navigate to the 'Plugins' menu, and find `RealHomes Memberships` in the list of available plugins. Click 'Activate' to enable the plugin's features on your site.

### Method 2: Install Directly Through WordPress

1. **Access the WordPress Dashboard**: Log in to your WordPress site's backend.
2. **Navigate to Plugins**: On the dashboard menu, click on 'Plugins', then select 'Add New'.
3. **Search for the Plugin**: In the 'Search plugins...' box, type in `RealHomes Memberships`.
4. **Install the Plugin**: Locate `RealHomes Memberships` in the search results, click 'Install Now' and wait for the installation to complete.
5. **Activate the Plugin**: After installation, click 'Activate' to start using the plugin on your website.

== Screenshots ==

1. Dashboard Menu
2. Adding New Membership Package
3. Adding New Receipt
4. Basic Settings
5. Stripe Settings
6. PayPal Settings
7. Wire Transfer Settings

== Changelog ==

= 3.0.2 =
* Improved entry file information
* Improved readme.txt and readme.md file
* Updated language file
* Tested plugin with WordPress 6.6.0

= 3.0.1 =
Added - Settings action notices support.
Added - Settings page header and footer areas.
Added - Plugin action links on plugins' management page area.
Added - Membership cancel alert popup.
Improved - Memberships front-end styles.
Improved - Memberships settings structure and styles.
Updated - Stripe library.
Updated - Language POT file.

= 3.0.0 =
* Improved overall membership payments experience
* Improved plugin code WRT optimization and speed
* Improved plugin settings descriptions and guide links
* Improved plugin settings styles completely
* Improved membership add/edit page
* Improved order complete page for Wire Transfers
* Updated PayPal API to the latest JS SDK API
* Updated Stripe API to the latest PHP SDK API
* Updated plugin information
* Updated language POT file
* Tested plugin with WordPress 6.4.1

= 2.4.4 =
* Fixed packages page PHP notice
* Updated plugin information
* Updated language POT file
* Tested plugin with WordPress 6.3.1

= 2.4.3 =
* Updated plugin information

= 2.4.2 =
* Added packages upgrade/downgrade price adjustment option
* Improved plugin version utilization system
* Updated language files
* Tested plugin with WordPress 6.2.2

= 2.4.1 =
* Tested plugin with WordPress 6.0.1

= 2.4.0 =
* Fixed missing columns' data warning issues on Memberships CPT Index (backend) page.
* Tested plugin with WordPress 5.9.3

= 2.3.0 =
* Added free package subscription support when WC Payments are also enabled.
* Fixed package subscription expiration issue where it was not expiring on its ending date sometimes.
* Fixed instant receipt expiration issue.
* Fixed dual receipt creation issue.
* Fixed some other minor issues.
* Updated POT language file.
* Tested plugin with WordPress 5.8.2

= 2.2.0 =
* Upgraded Stripe API to latest v3 version.
* Made Stripe payments SCA (strong customer authentication) compliance.
* Added package thumbnail image support for the Stripe transaction image meta purpose.
* Improved Stripe payment process by optimizing code and adding user guide notices.
* Removed individual membership template support related code. Memberships are managed form user dashboard already.
* Fixed membership expiration label issue in receipt status column.
* Updated POT language file.

= 2.1.0 =
* Added WooCommerce Payments support for the Membership Packages.
* Added new setting control to choose between Custom Payments or WooCommerce payments.
* Improved membership packages checkout process.
* Improved recurring payments option in checkout page.
* Improved membership receipts.
* Improved Stripe and WireTransfer payment handlers to process and store data efficiently.
* Improved some plugin hooks to the appropriate use context.
* Improved memberships upgrade and downgrade process.
* Improved memberships packages interface and overall functionality.
* Improved membership subscription benefits adjustment according to the current published properties.
* Improved overall memberships code for the optimization and enhanced efficiency.
* Improved memberships notification emails contents and design.
* Improved memberships from various other aspects.
* Fixed receipt expiration problem when another receipt is already active for the same package.
* Fixed featured properties limit based on allowed numbers in a package.
* Updated translation files.
* Tested plugin with WordPress 5.7.2

= 2.0.0 =
* Added subscription cancellation option that fixes the instant expiration issues for resubscription.
* Improved admin and user email notification templates.
* Improved Membership custom post type.
* Improved Receipt custom post type.
* Refactored whole plugin code to the latest standards.
* Optimized plugin code for the better performance.
* Fixed user redirection after the membership subscription cancellation.
* Removed welcome page of the plugin.
* Tested plugin with WordPress 5.7

= 1.3.0 =
* Added "Weeks" to the expiry duration options list.
* Added membership receipt email notification to the website administrator.
* Excluded "Memberships" and "Receipts" from blog search.
* Some other minor code improvements.
* Tested plugin with WordPress 5.6.2

= 1.2.2 =
* Fixed a warning issue on checkout page.

= 1.2.1 =
* Improved some functions call.
* Tested plugin with WordPress 5.6

= 1.2.0 =
* Added RealHomes new dashboard support.
* Added excerpt support to the membership package.
* Added option to set a package as popular.
* Added checkout form with proper steps from package selection to payment.
* Added terms and & Conditions option.
* Added change membership package support.
* Fixed several minor issues.
* Improved receipt post type.
* Improved membership package pricing.
* Improved membership payment option selection and methods.
* Improved recurring payments process.
* Improved subscription and paymetns process alert and success memssages.
* Improved packages appearance for the missing information e.g price. 
* Improved overall plugin code drastically.
* Removed thumbnail support of membership package.
* Updated language files.
* Tested plugin with WordPress 5.5.3

= 1.1.2 =
* Tested plugin with WordPress 5.5

= 1.1.1 =
* Tested plugin with WordPress 5.4

= 1.1.0 =
* Improved memberships subscription area UI & UX.
* Improved subscription related notification emails.
* Updated recurring payment processes according to the latest PayPal & Stripe APIs.
* Updated plugin welcome page and its menu location.
* Updated language file.
* Tested with WordPress 5.3.2

= 1.0.5 =
* Tested with WordPress 5.2.2

= 1.0.4 =
* Added function to make duration translation ready
* Added manual cancellation of wire transfer based membership
* Fixed free membership subscription issue while using PayPal
* Fixed receipt status change bug
* Updated translation file

= 1.0.3 =
* Fixed text-domain loading issue.
* Basic testing and WordPress version update.

= 1.0.2 =
* Basic testing and WordPress version update.

= 1.0.1 =
* Fixed some bugs related to basic use cases.

= 1.0.0 =
* Initial release.
