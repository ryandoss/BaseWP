=== Business Directory Plugin ===
Contributors: businessdirectoryplugin
Donate link: http://businessdirectoryplugin.com/premium-modules/
Tags: wordpress business directory, wordpress directory, wordpress directory plugin, wordpress directory theme, wordpress business directory, business directory,classified ads,classifieds,directory plugin,business directory plugin,directory widget
Requires at least: 3.0
Tested up to: 3.5.1
Last Updated: 2013-Apr-5
Stable tag: tags/3.0.2

Business Directory Plugin allows you to build local directories, business provider listings, Yellow-Pages style business directories, Yelp-like review sections and more. 

== Description ==

Increase interaction on your website, improve customer retention and a add revenue-generating section to your site with Business Directory Plugin!

Business Directory Plugin allows you to build local directories, business provider listings, Yellow-Pages style business directories, Yelp-like review sections and more.  You can add any kind of directory using Business Directory plugin.

= Features: =

*   Fully customizable form fields
*   Accept payment for listings OR have free listings
*   Allow for featured/sticky listings for an upgrade fee
*   Create multiple fee plans, which can be assigned to categories for posting
*   Support for reCAPTCHA to avoid spam listings
*   Users who post can edit listings without access to WP dashboard
*   SEO Friendly
*   Rate businesses in your directory (now with Microformat support) (Ratings Module)
*   Display the location of the business (categories, search, details--now with custom map sizes) (Google Maps Module)
*   Location based result filtering (Regions Module) (NEW!)
*   ZIP code radius searches (ZIP Search Module) (NEW!)
*   Assign features to paid or sticky listing plans to give users incentive to upgrade (Featured levels module) (NEW!)
*   Allow users to upload PDFs (File Upload Module) (NEW!)
*   Display Category Icons/Allow Parent-Child Category Navigation (Enhanced Category Module) (NEW!)

For a full list of features, please visit:  http://businessdirectoryplugin.com/features
 
Please report any bugs you find via http://businessdirectoryplugin.com/support-forum  

= Premium Module Links: =
These are optional modules that you can use to enhance Business Directory Plugin's functionality and
support future development:

NEW! [Featured Levels Module](http://businessdirectoryplugin.com/premium-modules/featured-levels-module?ref=wp)

NEW! [ZIP Code Search Module](http://businessdirectoryplugin.com/premium-modules/zip-search-module?ref=wp)

NEW! [File Upload Module](http://businessdirectoryplugin.com/premium-modules/file-upload-module?ref=wp)

NEW! [Regions Module](http://businessdirectoryplugin.com/premium-modules/regions-module?ref=wp)

[Ratings Module](http://businessdirectoryplugin.com/premium-modules/ratings-module?ref=wp)

[Google Maps Module](http://businessdirectoryplugin.com/premium-modules/google-maps-module?ref=wp)

[PayPal Gateway Module](http://businessdirectoryplugin.com/premium-modules/paypal-module?ref=wp)

[2Checkout Gateway Module](http://businessdirectoryplugin.com/premium-modules/2checkout-module?ref=wp)

For more information and support please visit [http://businessdirectoryplugin.com](http://businessdirectoryplugin.com?ref=wp)
For plugin support please visit [http://businessdirectoryplugin.com/forums](http://businessdirectoryplugin.com/forums)

If you love the plugin, [please consider purchasing a Premium Module](http://businessdirectoryplugin.com/premium-modules/?ref=wp)!


== Credits ==

Copyright 2009-13, D. Rodenbaugh 
Original Author: A. Lewis

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA



== Installation ==

Backup your WordPress database and files before you install or update. If you are upgrading from any of versions WP Business Directory Manager 1.0 - 1.7 it is important to note that Business Directory Plugin is built for Wordpress 3.0 and above and will not work properly with versions of WordPress prior to 3.0. 

1. 	Upload the folder business-directory-plugin with its contents to wp-content/plugins/

2.  Activate the plugin

3.	Create a new WordPress Page for your business directory. You can name the page whatever you want. Insert ONE of the following codes into the page [WPBUSDIRMANUI] or [businessdirectory]. The plugin needs this code to work.

4.  You should see a menu tab with the name Directory Admin in your admin dashboard sidebar. You need to click the tab to access "Manage Options", "Manage Fees" and "Manage Form Fields" and other options. 

5. 	You will also see a new dashboard menu tab named "Directory". This tab is where you manage your listings. It works very much the same as the "Posts" tab. Your directory listings are saved as a custom post type. You create your directory categories by going to Directory->Categories and adding the directory categories there. 

6.  You can access your directory on your site by visiting http://[your-site-url]/[page-slug-from step-3].  For example, if you created a page called "Business Listings", that would have a slug of business-listings and the URL would be http://site.com/business-listings.


Manage Form Fields allows you to change the form to suit your needs. You can edit the existing fields and add new fields

Manage Options allows you to control various settings such as whether or not to allow image uploads, whether or not to charge a fee for listings among other options.

To style the directory refer to wpbusdirman.css inside the css folder in the plugin directory  

Some users might have display issues due to incompatibility with the plugin and their WordPress theme. If this is you please refer to  the Usage Doc section for instructions on how to create compatible templates for use with the plugin.

Once you have configured your options, adjusted your form fields to suit your needs, and setup your fees if you are charging fees for adding listings, your directory should now be ready and working. 

If you are having problems please visit http://www.businessdirectoryplugin.com/support-forum/ for help.


== Screenshots ==

[Visit the main site for feature lists and screenshots](http://businessdirectoryplugin.com/features/)


== Changelog ==

Version 3.0.2

   1. Further additions to allow for restoration of "invalid" fields for 3.0 users affected by 2.2->3.0 upgrade
   

Version 3.0.1

   1. Added code to workaround critical WordPress dbDelta issue with PHP 5.2.x users
   
   2. Fixed category display bug where "Hide Empty Categories" would hide all categories instread.
   

Version 3.0

New Modules Added
- Featured Levels
- Category Icons
- File uploads (PDF only)
- ZIP code search

Form Fields API
- New form fields API. (#273).
- Allow for label hiding in form fields. (#226).
- New URL field. (#273).
- URL Validator now supports more schemes other than http or https. (#253).
- Social fields marked as "optional" do no longer fail to validate when empty. (#293).
- Make social fields work correctly with URL Validator. (#294).
- Fix an issue with single checkbox fields. (#316).
- Now possible to specify if a URL field has rel="nofollow" or not. (#319).
- Fix a re-ordering issue with bottom most fields. (#325).
- Fix an issue with radio button fields showing labels twice. (#329).

Misc. Fixes
- Restore ability to hide the search form. (#274, #280).
- Simplify WordPress queries to improve loading speed for directory pages. (#260).
- Search results are now sorted using the directory configured setting. (#257).
- Added option to notify admins via email when new listings are posted. (#245).
- Allow listings to be upgraded to featured during the initial submit process. (#244). 
  This option is controlled by the "Offer upgrades during submit process?" setting.
- Include listings in children categories in search results when searching a category. (#292).
- Make tag association work correctly with checkboxes. (#284).
- All BD buttons have now class 'button' or 'submit' depending on their type for easier theming. (#264).
- Form elements in widgets, search form, etc. now use specific IDs for easier theming. (#236).
- Handle accented words and apostrophes correctly when importing listings via CSV files. (#258, #269).
- Allow users with edit permissions to change listing images in backend. (#263).
- Fix category posts being incorrectly counted when there were expired listings. (#303).
- CSV import now complaints when a required field column is not present. (#295).
- CSV import now complaints when a user isn't found. (#251).
- Do not change listing author when using quick edit in the admin. (#301).
- Plugin checked for WPMU compatibility. (#14).
- Preserve the language parameter in BD links when qTranslate plugin is found to be active. (#313).
- Fix image issue due to conflicts with Jetpack plugin. (#268).
- Add "Listings Tagged: [tag]" to the title & header of tag pages. (#312).
- Fix an issue where paid listings were being published even when the payment was skipped. (#317).
- Add Lightbox support. (#147).
- Admin menus minor reorganization. (#349).
- Add a "Clear" button to the search form. (#344).
- Make use of listing emails before others for delivering renewal emails. (#330).
- Detect some conflicting social plugins (AddThis Social Bookmarking Widget, Jamie Social Icons, TF Social Share, Shareaholic, Quick AdSense) and disable them on directory pages. (#327).

Internal API
- Call 'handle_action' further down the action pipeline to avoid problems with other plugins. (#283).
- Rewrite rules and query vars cleanup. (#281).
- Fix canonical redirects so the BD page works even when set as home page. (#281).
- Lots of deprecated functions removed and code cleanups.
- Better detection of the current action being performed inside BD pages. (#302).
- Do not overwrite titles of pages outside of BD. Fixes a conflict with AWPCP. (#302).

SEO related
- Produce correct 'canonical' link head tags for listings, categories and tag pages. (#291).
- Make BD support Yoast SEO plugin's metadata and title tags on listing pages. (#108, #331).
- Include RSS feed links for listings and categories in directory pages. (#224).
   

Version 2.2

   1. Added support for Regions module
   
   2. Fixed CSV import error for PHP character munging
   

Version 2.1.6.1

   1. Fix PHP 5.4 error during install
   
   2. Fix error "Fee must be associated to a category" when a category was selected.
   

Version 2.1.6

   1. Support for all currencies supported by PayPal.
   
   2. Do not make URL fields required when marked as optional.
   
   3. When searching a field with multiple options, the options are now ORed.
   
   4. Twitter field type for @ handles. On display, a "Follow" button appears.
   
   5. Facebook field type for pages. On display, a "Like" button appears.
   
   6. LinkedIn field type for company IDs (not supported for individual profiles). On display, a "Follow" button appears.
   
   7. Image settings in "Manage Options" are always respected (no approximate sizes are used). Thumbnails of the correct size are automatically created when an image is uploaded.
   
   8. If image settings change, new thumbnails are created on the fly when a listing is visited. This happens only on "single view" to avoid consuming a lot of server resources.
    
   9. When uploading images at the submit/edit listing form useful errors are now displayed. This happens when images are of a bigger size than allowed or are not really image files. Previously, it wasn't clear why an upload could fail.
   
   11. Long description can now be set to Optional, required fields that can't be changed have disabled checkbox shown
   
   12. Empty URL field labels are not displayed
   
   13. CSV importer fixes for descriptions with newlines
   
   14. Fixed PO file to include Directory button label
   
   15. Hide payment & fee info on posts from non-administrators
   
   16. Upgrade PayPal interface for compatibility alert due in Feb 2013 (HTTP headers)


Version 2.1.5.2

   1. Added support for PayPal currencies:  BRL, TRY, MRY
   
   2. Fixed bug where upgrade of Website address did not auto-copy the link text
   
   3. Fixed advanced search issues with categories
   
   4. Fixed advanced search to use OR instead of AND for multi-select listboxes
   
   5. Fixed URL validator to not make field required when URL is empty
   
   6. Fixed permalink rules to work with index.php-based approach
   

Version 2.1.5.1

   1. Fixed WP API change: Missing argument 2 for wp_kses()
   

Version 2.1.5

   1. Added a Back to Directory button for listings
   
   2. Added sorting order for ratings (by ratings and count)
   
   3. Minor HTML tweaks for layout
   
   4. Added Link Text for Business Website (to create a complete anchor tag with SEO value)
   
   5. Changed fields to be hideable from Advanced Search Form
   
   6. Added better error messages displayed when payment gateways are not correctly configured for use
   
   7. Added payment skipping for admins, including a message that clearly shows the payments are being skipped because the current user is an admin
   
   8. Comments in listings are correctly displayed now
   
   9. CSV import now supports newlines in the import fields
   
   10. Contributors can't manage listings now
   
   11. Removed HTML comments from core templates that caused problems with some themes
   
   12. Category and tag names are now displayed in the page title
   
   13. Fixed Google Checkout Seller ID issue (removed field)
   
   14. Fixed labels for directory so that the colon is not outside the label
   
   15. rel attribute added to images
   
   16. Search widget added
   
   17. CSV import now supports multiple categories using the category separator
   
   18. Fixed stripslashes bug with field labels
   

Version 2.1.4.1

   1. Fixed issue with default display of form fields (caused fields to hide in listing)
   

Version 2.1.4

   1. Added support for Ratings module
   
   2. Fixed problems with comments
   
   3. Fixed problem with featured listings not displaying on the page with the correct count
   
   4. Improved speed of queries for main listings
   
   5. Fixed search pagination issue with lengthy result sets
   
   6. Fixed issue where number of posts per page did not include featured listings


Version 2.1.3

   1. Fixed shortcode usage problems with new codes
   
   2. Fixed permalink issues with categories and listings
   
   3. Added the Directory button back to most pages for simpler navigation.
   
   4. Added the ability to hide the directory button in the admin options
   
   5. Fixed HTML template issues for listings and single view pages (buttons showed HTML as text)
   
   6. Added error message when the main shortcode is not present on any page
   
   7. Fixed 2Checkout plugin for 2.1.2
   
   8. Fixed search page pagination
   
   9. Added back pagination API from WordPress for listings page, fixed bugs where post display was not honored
   
   10. Added checks for tags and slugs using permalink rules
   
   11. Fixed submit listing to only submit listing once when clicked repeatedly (or refreshed by accident)
   
   12. Added reCAPTCHA support to comments AND listings both
   
   13. Fixed CSV import issues with permalinks and dates
   
   14. Fixed payment gateway deactivation issues when plugin is upgraded.
   
   15. Improved search query speeds
   
   16. Added ability to select whether a field should appear on the listing's single view and/or excerpt view separately.

   17. Admins can edit / upgrade listings through the frontend regardless of the listing payment status.

   18. A confirmation dialog is now shown when deleting a listing.

   19. Comments on listings always follow the setting inside "Manage Options" (don't have to enable/disable comments individually on listings through the admin side).

   20. Fixed issue with custom CSS: files can be placed inside the "wp-plugin" directory now (per the docs).
   

Version 2.1.2

   1. Fixed critical query issue that prevented short code pages from working (Add Listing/Edit Listing)
   

Version 2.1.1

   1. Fixed Directory button to display in listings
   
   2. Minor CSS tweaks to show labels in bold
   
   3. Fixed bug with category and single listing links where they would not display when clicked (404 error)
   

Version 2.1

   1. Added support for CSV import, including images in ads
   
   2. Added support for custom CSS extensions via CSS files outside of BD
   
   3. Added new shortcode names (easier to remember).  Old shortcodes still supported.
   
   4. Added support for parameters on WPBUSDIRMANMVIEWLISTINGS/businessdirectory-viewlistings to allow for single category display on a page.
   
   5. Upgraded search to have "Advanced Search" link now, all fields are searchable.
   
   6. Streamlined the submit process for Free ads (no longer asks for payment)
   
   7. Website URL opens in new window (new field option for URLValidated fields)
   
   8. New widgets:  Featured Listings, Latest Listings, Random Listings
   
   9. HTML emails:  Email sent in HTML format instead of text now
   
   10. Preview Form is back.
   
   11. Google Maps now recognized on Premium Modules panel as installed/not installed
   
   12. MASSIVE upgrade to theming/theme integration, much easier to get BD going now.  Still have override templates, but fewer than before.
   
   13. Page based navigation--allows for tighter theme integration and easier upgrades.  Requires the use of permalinks for pretty URLs
   
   14. Fixed problem with URL validator not working right.
   
   15. Fixed problem with IE9 and button display
   
   16. Fixed ad pagination problem.
   
   17. Fixed problem with ad expiration validation (where 9999 caused weird 404 errors)
   
   18. Category list call from API now working again.
   
   19. Subcategory display problem in admin panel fixed.
   
   20. Users must select a category when creating a fee plan
   
   21. Fixed bug with Manage Form Fields button on Main Menu (permission issue)
   

Version 2.0.5

   1. Added ability to use bulk operations from admin menu (Upgrade/Downgrade, Set Paid, etc)
   
   2. Fixed problem with PayPal module not redirecting after payment on some sites
   
   3. Removed BD metadata from deleted posts (speeds searches)
   
   4. Fixed wp_pagenavi() warning
   
   5. Fixed bug where website field could not be cleared of data
   
   6. Fixed issue where admin could not edit other listings
   
   7. Added confirmation email to be sent after listing is complete
   
   8. Changed Upgrade to only work for Admins
   
   9. Updated plugin to use wpdb->query for all DB operations
   
   10. Fixed pagination problem with "Older listings" link
   
   11. Fixed duplicate search issue (duplicate results returned on searches)
   
   12. Fixed sticky/featured listing sorting problem to be consistent for category, listing and page views
   

Version 2.0.4.1

   1. Fixed image upgrade issues (missing thumbnails, duplicate pictures in listings)
   
   2. Fixed edit glitch with empty tags field
   
   3. Added control over Submit a Listing button to show/hide.  Added support for other listing buttons as well.
   
   4. Fixed minor _x() calls with missing parameters
   
   5. Fixed an issue with submitting listings for all blank fields (PHP warning)
   
   6. Fixed issue where empty fields would display labels when no value was present

Version 2.0.4

   1. Upgrade to image upload/management system
   
   2. Fixed form for creating new listings, both admin and user side
   
   3. Fixed listing renewal, emails now go out prior to expiration
   
   4. Payment modules upgraded to handle renewals
   
   5. POST variables now properly configured for Windows and Linux
   
   6. div's properly closed on Submit Listing
   
   7. Comments will display when option is checked now
   
   8. Directory definition issue with wp-content fixed
   
   9. Require registration option now properly displays in admin panel
   
   10. Field metadata is hidden and properly namespaced
   
   11. Sticky listings in category no longer cause No Listings Found
   
   12. Translation issue fixed
   
   13. Location field will not disappear on upgrade
   
   14. Invalid argument foreach() issue fixed
   
   15. Email now goes to site owner as expected
   
   16. Submit Listing layout problems fixed
   
   17. Submit listing duplicate fields display fixed
   
   18. Manage fees upgraded for renewals
   
   19. Image displays in list and single view properly now
   
   20. Editing listings will not randomly change category now
   
   21. Users can control which image is set for the thumbnail of a listing now
   
   22. Support for multiple categories in a listing added
   
   23. Add New Listing navigation bug fixed

Version 2.0.3

   1. Autocreate required fields
   
   2. Directory admin menu set to be visible only to administrator role
   
   3. Content field will not show as link now
   
   4. Reply emails go to the business listing instead of site owner
   
   5. Listings without photos now no longer have placeholder
   
   6. Submit Listing no longer displays raw HTML code to user
   
   7. Plugin now uses UTF-8 functions to properly handle non-ASCII characters
   
   8. BD now integrates in a more friendly way with themes, avoids overriding when possible
   
   9. preg_match() warning fixed
   
   10. Extra line breaks removed from listing display
   
   11. Conflict with Yoast All in One SEO fixed
   
   12. Changing the slug now checks for reserved/already used slugs to avoid conflict
   
   13. Form fields now custom ordered
   
   14. Module detection code fixed, now displays which modules are installed for PayPal/2Checkout


Version 2.0.2

   1. A revamped form-fields system that would allow plugins to create or use their own custom fields and validation rules.
   
   2. A new "Manage Form Fields" page which looks better and conveys information in a more friendly way.
   
   3. Custom fields can now be ordered as the user wishes. This ordering is used when displaying both the add/edit forms and listing information.
   
   4. Allow users to specify if a particular field should be displayed in excerpt or not at all.
   
   5. A better add/edit form-field page including some AJAX tricks to hide unnecessary fields. For instance, when you select a "Textfield" the "Options" field is hidden because the field isn't multivalued. The form is now also a standard WP form.
   
   6. A new "field description" field that could be used by admins to display additional information along the form fields.
   
   7. Validation when adding/editing custom form-fields to prevent users from removing or modifying required fields (like title, content, etc.)
   
   8. New validation framework for fields.
   
   9. Fixed bug where Featured ads could not be downgraded.
   
   10. Fixed delete field bug.
   
   11. Fixed ereg() warning bug.
    

Version 2.0.1

    1. Fixed scripting error with booleans for some WP installs
    
    2. Fixed category 404 problems, clean up navigation
    
    3. Fixed short description display issue
    
    4. Fixed shortcode usage problems (content displayed below directory, no matter where shortcode was)


Version 2.0 

    1. Reset versioning to sensible convention
    
    2. Fixed search CSS and searching functionality
    
    3. Changed category management to simplify
    
    4. Added payment status, featured status into listing screen directly
    
    5. Google Checkout bug fix
    
    6. Fixed various scripting errors (22 bug fixes total)
    
    7. Button alignment fixes and admin UI cleanup
    

    
Version 1.0 (1.9.6) Business Directory Plugin carries over from the last version of WP Business Directory Manager (1.9.5) Fixes include:

	1. Sticky listings bug fix
	
	2. Fixed broken submit form (raw html code displaying on front end)
	
	3. Tags fixed (previously clicking on tag links did not display any tag related content)
	
	4. New classes added for sticky listing styling
	
	5. Addition of basic search feature
	

Previous changelog from WP Business Directory Manager

Version 1.9.3 - Sticky listings fix for error where upgraded listings were not being listed under "Manage Featured". 
		
		Paid listings fix for error where paid listings were not being listed under "Manage Payments"
		
		Applied fix for payment calculation and handling where users are allowed to submit listing to multiple categories
		
		Fixed bug with images where number of images left wass sometimes greater than the total number of images allowed
		
		[ Thanks to Mike Bronner for his help with the below list of improvements and fixes to the plugin]

		- Fixed issue where shortcodes would always cause the Add Listing Form to display prior to content.

		- XHTML compliant.

		- More versatile arrangement of form elements.

		- Improved label handling (added proper IDs and FOR attributes).

		- Changed 'wp_dropdown_categories' function call to pass in an array of arguments, and adding arguments "echo" and "class".

		- Added functionality so that required fields receive a class name 'required'. This will allow for highlighting of required fields, etc.

		- Added "required" class to input fields in AddListing form, allowing them to be stylized.

		- Internal code optimization and cleanup.

		- Added hooks (filters) on functions that display HTML to allow user overrides of those functions in functions.php in their template. This is possible by removing the filters, then re-adding them and pointing to their own functions using the functions.php file in their theme.

		- "Add Listing" form will now submit back to itself. This will allow you to retain styling of the form separate from the directory. It is also no longer necessary for the Add Listing page to be a child of the Directory page.

		- Expanded slightly on the hookability of the add listing functionality, further allowing other developers to extend the plugin.

		- Updated text to be displayed on add listing form is user wasn't logged in.


		Bug Fixes:

		========

		- Addressed issue where shortcodes would always be displayed above content. Now you can nest shortcodes between content.

		- Updated HTML code to be XHTML complient 

		- Wrapped notifications in <p> tags, where not already done so.

		- Wrapped free-standing text in token function, where not already done so (__("text", "WPBDM")).

		- Fixed heading "Step 2" on image upload form to be H2 tag instead of H3 tag (syntactically correct, as there is otherwise no H2 tag).



Version 1.9.2 - Attempts to fix "undefined function flush_rewrite_rules" issue
		Fixes "Wrong datatype for second argument" error when using checkbox field type for category field
		Fixes default picture bug ( default picture not displaying )


Version 1.9.1 - Fixes a bug that defaults all extra fields to a type of text box regardless what the administrator selects. This bug was only present in version 1.9.0.

Version 1.9.0 - This version attempts yet again to fix the issue with categories showing "No Listings found in category" even while there are actually listings in the category. 
		Also attempts to fix situation where listings show up across all categories.
		Adds the ability to post listings to multiple categories. To use this you must set the category associated field as a checkbox type.
		Attempts to fix problem where when editing a listing if you had not uploaded any pictures the first time around it does not load the upload form on edit but says you don't have the ability to upload images
		Attempts to fix the issue with the View button not taking you to the actual listing in Internet Explorer browser
		Fixes a problem in the fields manager where if you saved a field without a label you then cannot delete the field.
		Fixes URL validation bug
		

Version 1.8.9 - This version has a small fix for the Google checkout page and a fix for some permalink issues causing a variety of problems with pages. Regarding the google checkout it seems some previous versions were displaying the shortcode at all times and not showing the actual page content. Change made: The line 
		
		add_shortcode('WPBUSDIRMANGOOGLECHECKOUT', 'wpbusdirman_do_googlecheckout'); 
		
		was changed to 
		
		if($wpbusdirman_hasgooglecheckoutmodule == 1)
		{
			add_shortcode('WPBUSDIRMANGOOGLECHECKOUT', 'wpbusdirman_do_googlecheckout');
		}
		
		and moved to below
		
		if($wpbusdirman_hastwocheckoutmodule == 1)
		{
			add_shortcode('WPBUSDIRMANTWOCHECKOUT', 'wpbusdirman_do_twocheckout');
		}
		
		The update also adds 3 new options under a new setting "Permalink Options" that allows you to change the wpbdm-directory slug, the wpbdm-category slug and the wpbdm-tags slug. Please take note of the fact that you cannot use "category" or "tags" or any already reserved WordPress taxonomy names for your slugs as it will make your pages fail to load.


Version 1.8.8 - This version has a small fix in a function related to the payment process on sites that have only one fee setting applied to all categories.

Version 1.8.7 - This version attempts to fix the issues with the contact form not sending the email to the business but instead sending it to the user who was logged in when the listing was submitted.
		
		This version attempts to fix the issues with some top level categories showing no listings found. 
		
		This version fixes the issue with the extra thumbnails not showing up in the listing
		
		This version should fix the issue with the cannot redeclare username_exists() error 
		
		
		

Version 1.8.6 - Removes call to include the WordPress registration.php in order to fix "Fatal error: Cannot redeclare username_exists() (previously declared in..." error 

Version 1.8.5 - Fixes a bug that prevented the payment step from kicking in (applies for version 1.8 and above only)

Version 1.8.4 - Removes automatic creation of pages at setup to correct bug that caused multiple instances of the business directory page and sub pages to be created.
`		Installation process now includes manual creation of the necessary pages.

Version 1.8.3 - Removes conversion process that attempted to convert listings made with versions 1.0 - 1.7 to the new 1.8 custom post type format


Version 1.8.2 - Fixes IE bug where "View" button does not work in listing excerpts
		Fixes Missing argument 1 for wpbusdirman_the_listing_meta() bug
		Fixes missing quote bug near img alt on a couple of lines 



Version 1.8 of Business Directory Plugin makes use of WordPress 3.0+ custom post types. The way the plugin works has been completely changed to use custom post types. When you upgrade to version 1.8 it will convert your previous installation to the new format. The new format conversion is not guaranteed to work 100% so you are very strongly advised if you have listings in your directory presently to export those listings in case you might need to re-add them manually. 

Plugin now uses a custom post type for posts related to the directory

Plugin now uses a custom category taxonomy for categories related to the directory

Plugin now uses a custom tags taxonomy for tags related to the directory listings

Plugin now allows for better integration with your theme. See the "Usage Doc" section for information on how to customize your theme to use the plugin functions.

Bug related to "Manage Fees" has been fixed. This bug resulted in admin being told payments were not turned on even though payments were in fact turned on.



== Upgrade Notice ==

1. If you are upgrading please export your current data first. There is always the possibility of something going wrong during the upgrading process. Failing to save your data before you upgrade can prove costly if something goes wrong and your data gets lost. Backup your database. Backup your files. 

2. If you are not using WP 3.0 or above you should not install Business Directory Plugin. 

3. If you are upgrading it is recommended that you do a manual upgrade rather than using the automatic upgrade option available via your dashboard. If you use the automatic upgrade option please, again, backup your data first.

== Usage Doc ==

For complete documentation on the plugin, please visit the [BD Documentation Site](http://businessdirectoryplugin.com/docs)

1. Setting up your categories

Go to your Dashboard and look for the "Directory" menu box. Listed under the Directory menu box are the links "Add New", "Categories" and "Tags". Click the "Categories" link and add your categories just the same way you would normally add categories under Posts->Categories.

2. Matching the design to your theme

Please visit this page for complete and up-to-date instructions on matching BD to your theme. 

[How to match BD to your theme](http://businessdirectoryplugin.com/docs/matching-the-design-to-your-theme/)

3. Adding a menu of the directory categories

To add a menu of the directory categories to your theme use the code below:
<?php global $wpbdmposttypecategory; wp_list_categories('title_li=&taxonomy='.$wpbdmposttypecategory); ?>
You can filter the function the way you would normally filter wp_list_categories. Just make sure it includes 'taxonomy='.$wpbdmposttypecategory so that it knows you only want the plugin directory categories.

4. Adding a latest posts widget

NEW:  BD now comes with 3 standard widgets--Latest Listings, Random Listings and Featured Listings.  Place in any
widget-ready theme.

== Frequently Asked Questions ==

For the definitive FAQ section, please vist the [FAQ Section of the Forum](http://businessdirectoryplugin.com/support-forum/faq)

1. How do I setup my categories?
You setup your categories under the "Directory" menu tab that will appear in the dashboard menu when you install the plugin. Under the Directory menu tab there is a link "Categories". You click this and add your categories the same way you do with your normal WordPress blog.

2. Why isn't the email address showing up in the listings?
The email address is not showing up because it is being programmatically blocked. The reason for this is to protect the privacy of your users so that their email address does not appear for public viewing where email harvesting bots can grab them and add them to spam email lists. 

3. Okay, I understand that the plugin is blocking the email address to protect the user's privacy, but I still prefer to display the email address in the listing pages. How can I do that?
To override the email block, go to the WPBusDirMan menu tab that appears in the dashboard menu. Click the "Manage Options" link. Look under "Miscellaneous settings". You will see an option "Override email Blocking?". Check that option and it should make the email address visible.





