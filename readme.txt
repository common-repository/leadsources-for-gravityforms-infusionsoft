=== LeadSources for Gravity Forms Infusionsoft Add-On ===
Contributors: jhorowitz
Donate link: https://www.aod-tech.com/donation/1N5ZUXdqqxVcuNCroh8txV5en32KM5zoSA?label=LeadSources%20for%20Gravity%20Forms%20Infusionsoft%20Add-On%20Donation%20Address&message=Donation%20to%20LeadSources%20for%20Gravity%20Forms%20Infusionsoft%20Add-On%20WordPress%20Plugin
Tags: infusionsoft, lead sources, gravity forms, leadsource, gravityforms
Requires at least: 3.0
Tested up to: 4.7
Stable tag: 1.06
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

LeadSources for Gravity Forms Infusionsoft Add-On.

== Description ==

LeadSources for Gravity Forms Infusionsoft Add-On lets you create and assign LeadSources to your Infusionsoft contacts automatically through Gravity Forms.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/leadsources-for-gravityforms-infusionsoft` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. You're Done!

== Frequently Asked Questions ==

= How do I use this plugin? =

While creating your Infusionsoft feed through the standard Gravity Forms Infusionsoft Add-On interface, you will be able to specify form fields that map to Lead Sources!
If an existing LeadSource is found with the exact same data, it will be used. Otherwise, a new Lead Source will be automatically created!

= What if my visitors browse for awhile before filling out my form? What happens to my LeadSource URL parameters? =

When a visitor first comes to your site, the landing page parameters will be saved in a cookie in their browser. When Gravity Forms is used later on,
any parameters that were present on the landing page will be sent along with their data, allowing for dynamically populated fields to function as intended.

For example, a visitor lands on page A with the parameter utm_campaign=AwesomeCampaign. They then browse around for awhile, the utm_parameter long since disappearing from the address bar.
They then land on your form page, and submit the form. You have a hidden field that populates from value of utm_parameter. The original landing page utm_parameter field will be used by Gravity Forms.
Note: In this example, if the form page itself contained the utm_campaign parameter, that value would be used instead of the landing page's utm_campaign value.

== Screenshots ==

1. No Screenshots

== Changelog ==

= 1.06 =
* Support WordPress v4.7.
* Add support for saving Landing Page URL parameters! See the FAQ for details.

= 1.05 =
* Fix copy/paste bug in method definitions.
* Add Lead Source link to entry information.
* Add Note to entry regarding assigned Lead Source.

= 1.04 =
* Cache Lead Source fields in transients.

= 1.03 =
* Fix indexing error for existing Lead Sources/Lead Source Categories.

= 1.02 =
* Change default Lead Source Name to "Source - Medium".
* Fix lookup bug for existing Lead Sources/Lead Source Categories.

= 1.01 =
* Change Infusionsoft links

= 1.00 =
* Initial Release
