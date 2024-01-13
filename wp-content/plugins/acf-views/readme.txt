=== Advanced Views Lite - Display Posts, WooCommerce, and ACF fields ===
Contributors: wplake
Tags: WooCommerce, ACF, Posts, Query, image galleries, masonry, lightbox, maps, sliders, carousel, grid, Advanced Custom Fields
Requires at least: 5.5
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.4.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Smart templates to display your content with built-in post queries and automated template generation.

== Description ==

Smart Templates that enhance the development process without sacrificing creative freedom.

Display your content with built-in post queries and automated template generation. These Templates accelerate the process and handle routine tasks efficiently. Develop quickly, and maintain flexibility.

Note: "Advanced Views Lite" plugin requires [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/) plugin to be enabled on your website (either Free or Pro version).


Smart templates with features for [devs](https://wplake.org/advanced-views-lite/dev-features/) and [non-devs](https://wplake.org/advanced-views-lite/non-dev-features/).

== 🌟 One Tool. Endless ways to display ==

Advanced Views plugin can pull data from multiple sources.

Out-of-the-box the plugin supports:

* Native WordPress fields of Posts, Terms, Users, Menus
* WooCommerce Product fields
* Any meta fields from the Advanced Custom Fields (ACF) plugin

See [all supported sources](https://docs.acfviews.com/getting-started/supported-data-vendors).

== 🛸 Development without hassle ==


**💡 Automatic Data retrieval and Template generation**

Focus on critical aspects of your project rather than grappling with data conversions and extensive documentation. [Read more](https://docs.acfviews.com/getting-started/introduction/key-aspects#id-3.-view-component)

**📢 Templates that are always up-to-date**

Advanced Views maintains a seamless connection between fields in your database and their usage within templates. [Read more](https://docs.acfviews.com/getting-started/introduction/key-aspects#id-6.3-automatic-template-validation)

**⚙️ UI for WP_Query construction**

Master WP_Query instances effortlessly with the UI for argument names complete with clear descriptions. [Read more](https://docs.acfviews.com/getting-started/introduction/key-aspects#id-4.-card-component)

**🎯 Adherence to Best Practices**

Advanced Views comes prepackaged with best practices to ensure that your projects are developed correctly without the need for extensive initial setup. [Read more](https://docs.acfviews.com/getting-started/introduction/key-aspects#id-5.-modular-approach)

== 📚 Extensive Docs and Friendly Support ==

See the [official plugin documentation](https://docs.acfviews.com) for step-by-step guides and information about customization.
Questions about the Advanced Views Lite are handled through the [support forum](https://wordpress.org/support/plugin/acf-views/).

== 🔗 Get more with the Pro version ==

Build Advanced layouts without Complexity. [Read more](https://wplake.org/acf-views-pro/)

== Screenshots ==

1. Views list management via the familiar interface.
2. Get a basic setup in seconds with Demo import.
3. Assign multiple fields within your View.
4. The generated template can easily be customized.
5. Display a set of posts with a Card.
6. Posts can be filtered, sorted and styled.
7. Import and Export Tool helps with site migration.

== Installation ==

**Installation for Advanced Views Lite**

From your WordPress dashboard:

1. Visit the Plugins list, click "Add New"
2. Search for "Advanced Views Lite"
3. Click "Install" and "Activate" Advanced Views Lite
4. Visit the new menu item "Advanced Views" to create your first View

See our [plugin documentation](https://docs.acfviews.com/getting-started/introduction) for step-by-step tutorials.

**Installation for Advanced Views Pro**

To purchase a Pro license key click [here](https://wplake.org/acf-views-pro/).
After payment you'll receive an email with your license key which includes the Advanced Views Pro plugin archive.

1. Visit the Plugins list, click "Add New", then click "Upload Plugin"
2. Click on "Choose File" and locate the downloaded Advanced Views Pro package, then click "Open"
3. Click on "Install Now" and wait for the package to upload and install, then click "Activate Plugin"
   Note: Advanced Views Lite will automatically be deactivated. You can safely delete Advanced Views Lite from the Plugins list. (Don't worry, deleting the Advanced Views Lite plugin won't delete your data.)
4. In the Plugins list for Advanced Views Pro click "Activate your Pro license"
5. Copy and paste your Pro License Key, then click "Activate"

Enjoy all the features and settings Advanced Views Pro has to offer with automatic updates.
Customers with an active Pro license have personal support via our [support form](https://wplake.org/acf-views-support/).

== Frequently Asked Questions ==

= Can I display user fields? =

Yes, you set up your field groups in ACF and assign those fields to your View, paste the shortcode in the target place, add the object-id="$user$" argument to the shortcode to display the fields from the current user. See [here](https://docs.acfviews.com/shortcode-attributes/view-shortcode) for more about shortcode arguments.

= Can I display fields from my options page? =

Yes, you set up your field groups in ACF and assign those fields to your View, paste the shortcode in the target place, add the object-id="options" argument to the shortcode to display the fields from the options page. See [here](https://docs.acfviews.com/shortcode-attributes/view-shortcode) for more about shortcode arguments.

= Fields have been assigned but the page doesn't show them =

Have you checked that the fields are filled in the target object? See [steps](https://docs.acfviews.com/getting-started/introduction/creating-your-first-view) for creating a View.

= Can I display fields inside the Gutenberg Query Loop? =

You can use the View shortcode inside the Gutenberg Query Loop element. Please make sure you've added it via the built-in Shortcode block, as it won't work properly with other block types, like Code or Custom HTML.

== Changelog ==

= 3.0.0 (): =
- Made Advanced Views independent plugin
- Added file system option for template storage

= 2.4.7 (2023-12-19): =
- Updated link to the new Docs

= 2.4.6 (2023-12-19): =
- Renamed 'ACF Views' to 'Advanced Views'

= 2.4.5 (2023-12-15): =
- Improved compatibility with non-Gutenberg themes
- View: fixed 'id' related bug for the 'add new' link on the ACF Group screen
- View: improved 'Menu (WordPress)' markup
- View: improved Twig formatting and autocomplete (ACE editor)
- View: added 'Assigned Views' metabox

= 2.4.4 (2023-12-08): =
- View: Post content field - added shortcodes and custom blocks rendering
- View: shortcode - fixed bug with the inner shortcode rendering
- View & Card: improved Full screen mode (tab switching)
- Small bug fixes
- UX enhancements

= 2.4.3 (2023-11-30): =
- View: Taxonomy terms field - fixed related options appearance

= 2.4.2 (2023-11-30): =
- View field group select: improved labels
- Code editors: live suggestions
- View's shortcode: fixed 'term-id' related bug (when there was Post with the same ID)
- View & Card info icon: turned into dashicon

= 2.4.1 (2023-11-24): =
- View & Card: UI improvement (instructions into tooltips)

= 2.4.0 (2023-11-22): =
- View & Card user assets: improved minification
- View & Card: web components by default
- View & Card shortcode: added 'class' attribute support
- View & Card: list look - hidden excerpt
- View: custom fields - enabled the defaultValue setting support for string types
- View: added '$Menu$' group

= 2.3.7 (2023-11-07): =
- View: Custom Markup - fixed bug with the newly added fields with multiple spaces in the name
- Edit screen: workaround for the initial Full Screen Mode (Gutenberg)
- Jetpack: added workaround to avoid break post_content by the 'Markdown' module

= 2.3.6 (2023-11-04): =
- View: fixed bug with the 'object-id' argument (for the old '$user$' and '$term$' values)
- Card: fixed Meta and Card filters bug on wp.com hosting

= 2.3.5 (2023-11-03): =
- View: map field - added "Multiple map markers" plugin compatibility
- View: map field - added "OpenStreetMap" plugin compatibility
- View: added '$Comment$' group
- UX: Labels

= 2.3.4 (2023-10-29): =
- Edit screen: added workaround for the missing ACF ajax validation on the wordpress.com hosting

= 2.3.3 (2023-10-29): =
- Edit screen: fixed conflict with the Fusion Builder (Avada theme)

= 2.3.2 (2023-10-27): =
- Workaround for the Avada theme ob_start() bug
- Improved switching between Basic and Pro versions

= 2.3.1 (2023-10-27): =
- Workaround for the Divi theme with the 'Enable Classic Editor' option enabled
- Edit screen: added warning about suppressed Gutenberg editor
- Added compatibility with the 'Gutenberg' plugin (from WP repo, that overrides Gutenberg from WP core)

= 2.3.0 (2023-10-26): =
- Edit screen: improved compatibility with third-party plugins
- Shortcode: added 'user' and 'term' aliases for the 'object-id' argument
- Templates: moved to the wp-content/uploads dir to avoid the file permissions issue
- View: added $Term$ group
- View: extended the 'linkLabel' setting to all the link-related fields
- View: added a new 'Open link in a new tab' setting to all the link-related fields (Field options tab)

= 2.2.5 (2023-10-20): =
- Improved UX (moved fields, renamed tabs, added version to the plugin's name)
- Card: sort by field - fixed bug

= 2.2.4 (2023-10-19): =
- Improved performance for View and Card edit screens
- Fixed 'clone' bug
- View & Card: added Custom Markup validation on save
- Added support for terms
- Added rule for the 'Classic Editor' plugin

= 2.2.3 (2023-10-12): =
- Internal improvements
- Minor UI improvements
- Improved UX: added 'related' meta boxes and list columns
- Settings page

= 2.2.2 (2023-10-06): =
- Added back compatibility with the old external CSS (the isMarkupWithDigitalId flag for Views and Cards markups)

= 2.2.1 (2023-10-06): =
- Small improvements
- Fixed: Clone item
- Added: shortcodes refresh for new items
- Fixed: UI bug on the list page with arrows
- Fixed: issue with ids in JS files (map, loadmore)

= 2.2.0 (2023-10-03): =
- View: new $User$ and $WooCommerce$ groups and 'user-id' argument for the shortcode
- View and Card: multilingual support for labels
- View and Card: new unique ids
- Tools: export/import tools
- New UI

= 2.1.2 (2023-09-27): =
- View: $Post$ group, added a workaround to avoid the double encoding

= 2.1.1 (2023-09-26): =
- Card: Unlocked 'Custom Markup' feature
- View: improved $Post$.Excerpt
- Readme

= 2.1.0 (2023-09-25): =
- Internal improvements (assets, code)
- View: improved markup, added new markup options (BEM name, isWithCommonClasses, isRenderWhenEmpty, isWithUnnecessaryWrappers)
- View: now $Taxonomy$ is avoiding double escaping
- View: fixed '$Post$.excerpt' bug for CPTs
- View: unlocked 'Custom Markup' feature
- Card: added new 'BEM name' option

= 2.0.1 (2023-09-14): =
- Fixed the PHP warning for array default values (select, checkbox)

= 2.0.0 (2023-09-14): =
- Added Twig engine for the templates
- Changed Code editor (for the View/Card code fields)

= 1.9.9 (2023-08-28): =
- Google Maps API: wrapped the script to avoid the conflict
- Styles and scripts: improved buffering

= 1.9.7 (2023-08-21): =
- View: added workaround for the Gutenberg Query Loop (shortcode block)

= 1.9.6 (2023-08-09): =
- Compatibility with the Pro version

= 1.9.5 (2023-07-10): =
- UX

= 1.9.4 (2023-06-29): =
- UX

= 1.9.3 (2023-06-08): =
- Readme

= 1.9.2 (2023-05-20): =
- Date fields within group/repeater: fixed bug

= 1.9.1 (2023-05-19): =
- New $Post$ field: 'Featured image with link'

= 1.9.0 (2023-05-17): =
- Labels, descriptions: added support of multilingual
- Date/time fields: added support of multilingual
- Updated ACF api (acf-groups package version)
- UX: improved labels

= 1.8.9 (2023-04-28): =
- Updated readme
- UX (labels)

= 1.8.8 (2023-04-21): =
- View: fixed plugin's $Taxonomy$ field group, added the delimiter support to it

= 1.8.7 (2023-04-20): =
- Updated readme
- View: fixed taxonomy bug (didn't support 'appearance options' with the Single values)
- View: added the new 'delimiter' option for multiselect fields ('select', 'post_object', 'page_link', 'relationship', 'taxonomy', 'user',)
- Improved PHP 8 support
- Improved the Basic/Pro switcher process
- Improved UX (labels)

= 1.8.6 (2023-03-16): =
- View: Improved UX (repeater/group fields stub is showed)
- View: added support of Shortcode within the Gutenberg Query Loop (added a workaround)

= 1.8.5 (2023-02-18): =
- Fixed skipping Fields Groups from JSON only
- Improved UX (hidden Pro banner)
- Updated Readme (Installation tab)

= 1.8.4 (2023-02-01): =
- Improved support of block themes (CSS loading)
- Updated Readme

= 1.8.3 (2023-01-31): =
- Improved Textarea support (auto converting '\n' to 'br')
- Fixed a Lightbox gap (from the plus svg at the bottom)
- Updated Overview page
- Updated Readme

= 1.8.2 (2023-01-18): =
- Shortcode 'object-id' argument: added support for the '$user$' value
- Updated contact links

= 1.8.1 (2023-01-16): =
- Fixed a syntax error and improved support of multilingual websites ('trash' option is missing in CPT for them)

= 1.8.0 (2023-01-13): =
- Improved CSS including: moved to the head tag
- Improved saving process (JSON, excluded default values)
- Improved UX (opcache conflict message)
- Updated Docs link
- Improved Analytics
- Improved Conditional rules for Repeater fields

= 1.7.23 (2023-01-04): =
- Added a notice for admins (about opcache compatibility issue)
- Minor improvements

= 1.7.22 (2023-01-02): =
- Fixed unnecessary output

= 1.7.21 (2023-01-02): =
- Minor improvements

= 1.7.20 (2022-12-16): =
- Added 'options' support to the 'object-id' argument
- Updated readme
- Updated Overview page
- Added a survey link

= 1.7.19 (2022-11-23): =
- Updated YouTube video link

= 1.7.18 (2022-11-22): =
- Improved dashboard links (supporting of custom site urls, like wp.org/wordpress)

= 1.7.17 (2022-11-22): =
- Improved UX (labels)
- Improved dashboard links (supporting of custom site urls, like wp.org/wordpress)

= 1.7.16 (2022-11-15): =
- Improved WooCommerce supporting (product loops)
- Updated Readme, Overview page

= 1.7.15 (2022-11-08): =
- Improved UX (more read more links)

= 1.7.14 (2022-11-04): =
- View : improved author, image field types support
- View : added taxonomies support

= 1.7.13 (2022-11-03): =
- Fixed bug with missing fields

= 1.7.12 (2022-11-02): =
- Improved code : no PHP warnings on the ACF options page
- Readme

= 1.7.11 (2022-11-01): =
- View : supporting of the google map field
- UX : links to Docs

= 1.7.10 (2022-10-28): =
- Bug fixed : automatic deactivation on activation of some plugins
- UX improvement : removed automatic redirection to the Overview page
- More supported field types : oembed, gallery, button_group, post_object, relationship, taxonomy, user

= 1.7.0 (2022-10-27): =
- View, Card : MountPoints feature
- View, Card : improved CSS shortcuts

= 1.6.17 (2022-10-24): =
- Updated readme
- Updated field labels

= 1.6.13 (2022-10-21): =
- Performance : improved caching

= 1.6.12 (2022-10-21): =
- Copy to clipboard : improved working on HTTP protocol, fixed the roles shortcode copying

= 1.6.11 (2022-10-21): =
- Copy to clipboard : improved working on HTTP protocol

= 1.6.10 (2022-10-21): =
- Demo import : fixed a bug
- Gutenberg block feature : improved notice
- Improvement : removed double slashing for View/Card fields in DB

= 1.6.0 (2022-10-21): =
- Performance improving : View/Card settings now in JSON from post_content instead of using postMeta
- Gutenberg block feature : fixed a bug

= 1.5.10 (2022-10-17): =
- Card Shortcodes postbox : fixed wrong argument name
- ACF dependency : improved links (to the local add-plugin page)
- Improved redirection after activation (to use TRANSIENTS)
- Automatic deactivation one of instances when both Basic & PRO activated
- Added information about restricting access to View/Card by user roles
- Added escaping output of plain field types
- Improved import

= 1.5.0 (2022-10-13): =
- Downgraded ACF dependency from PRO to Basic
- New shortcode arguments : user-with-roles, user-without-roles
- Fixed ImageSize for repeater fields

= 1.4.10 (2022-10-12): =
- View : preview feature
- Card : preview feature, custom variables filter
- Improved 'ACF PRO' dependency notice

= 1.4.0 (2022-10-10): =
- View : reordered fields (new tab)
- View : image size field : dynamic list instead of hard coded, $Post$ thumbnail support

= 1.3.1 (2022-10-04): =
- View : improved Gutenberg block description
- Toolbar improved
- Code structure improved
- Filters added

= 1.3.0 (2022-09-30): =
- Backend optimization
- Card : fixed CSS classes field
- Card : new tab - "Layout"
- View : improved UX (field settings is displayed only for specific field types)
- View&Card : disabled autocomplete

= 1.2.1 (2022-09-28): =
- Overview page content
- Demo imported error fixed
- Demo import improved (added ACF Card)

= 1.2.0 (2022-09-27): =
- Card markup preview field
- Card no posts found message
- Preview of PRO fields
- Admin Table bug fixed (select all)
- clone item feature improved

= 1.1.1 (2022-09-25): =
- Markup and other improvements

= 1.1.0 (2022-09-20): =
- Markup improvements

= 1.0.11 (2022-09-19): =
- ACF Cards, readme

= 1.0.10 (2022-09-09): =
- Readme, assets

= 1.0.9 (2022-09-09): =
- Minor improvements, readme

= 1.0.8 (2022-09-01): =
- Improved code editor

= 1.0.7 (2022-08-31): =
- JS code feature
- Link and Page_link field types

= 1.0.6 (2022-07-30): =
- Code improving

= 1.0.5 (2022-06-24): =
- Demo import feature

= 1.0.4 (2022-06-18): =
- Video review

= 1.0.3 (2022-06-09): =
- Readme

= 1.0.2 (2022-06-09): =
- Plugin's version

= 1.0.1 (2022-06-09): =
- Screenshots, plugin's version
