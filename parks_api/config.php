<?php
/*
|---------------------------------------------------------------
| parks.swiss API
| Netzwerk Schweizer Pärke
|---------------------------------------------------------------
|
| Global configuration for Parks API
|
*/

$config = [];


/*
|--------------------------------------------------------------------------
| API Hash Key
|--------------------------------------------------------------------------
|
| This hash is needed to import the data via XML.
| Create an export configuration on https://angebote.paerke.ch and
| put your Hashkey in here.
|
*/
$config['api_hash'] = "insert-your-hash-here";


/*
|--------------------------------------------------------------------------
| Your park
|--------------------------------------------------------------------------
|
| Set your park ID here.
|
*/
$config['park_id'] = 0;


/*
|--------------------------------------------------------------------------
| SEO URLs
|--------------------------------------------------------------------------
|
| Set true if your environment uses SEO URLs.
|
*/
$config['seo_urls'] = false;
$config['seo_url_detail_slug'] = 'offer-detail';
$config['seo_url_poi_slug'] = 'poi';
$config['seo_url_page_slug'] = 'page';
$config['seo_url_reset_slug'] = 'reset';


/*
|--------------------------------------------------------------------------
| MySQL Database
|--------------------------------------------------------------------------
|
| A MySQL Database is required to use the API.
| Imported offers will be stored there for better performance.
|
*/
$config['db_hostname'] = "localhost";
$config['db_username'] = "root";
$config['db_password'] = "root";
$config['db_database'] = "parks_api";


/*
|--------------------------------------------------------------------------
| Custom view
|--------------------------------------------------------------------------
|
| Set your custom view, located in your "custom" folder.
| example: "MyView"
|
*/
$config['class_view'] = "MyView";


/*
|--------------------------------------------------------------------------
| Template
|--------------------------------------------------------------------------
|
| Set the template placed in the parks_api/template/ folder.
| Create your individual template by creating your own folder,
| copied from the standard folder.
|
 */
$config['template_folder'] = 'standard';


/*
|--------------------------------------------------------------------------
| Custom view options
|--------------------------------------------------------------------------
|
| 'always_show_filter' => Show filter on detail pages
| 'show_route_filter' => Show route filter
| 'show_target_group_filter' => Show target group filter
| 'show_route_filter_min_count' => Define minimum activities for route filter
| 'offers_per_page' => How many offers should be displayed per page?
| 'n_th_point' => Each n-th point of routes shown on the overview map (speeds up overviews with map)
| 'n_th_point_min_count' => Routes with less than this amount of route points won't be shortened
| 'pagination_max_numbers' => Define how many pages should be displayed in the pagination (in overviews)
| 'detail_thumbnail_size' => Thumbnail size on detail pages
| 'placeholder_image' => Set the path to your default placeholder image (if no image is set)
| 'image_enlargement' => Link image enlargements while using plugins like fancybox or lightbox
| 'detail_limit_dates' => Limit amount of dates displayed after loading on the offer detail page
| 'filter_keywords_with_and' => Filter offers by keywords with AND restriction (default: OR)
| 'show_park_name' => Show park name on overview and on detail page
| 'show_event_location_in_overview' => Show event location on overview
| 'show_short_description_in_overview' => Show short offer description on overview
| 'show_keywords_in_overview' => Show offer keywords on overview
| 'show_button_in_overview' => Show link button in overview
| 'poi_listing_link_target' => Set the detail link target in poi listing e.g. '_blank'
| 'heading_offer_title_in_overview' => Set the heading html tag for offer titles in overview
| 'show_accessibility_filter' => Show accessibility filter
| 'show_municipality_filter' => Show municipality filter
|
*/
$config['always_show_filter'] = false;
$config['show_route_filter'] = true;
$config['show_target_group_filter'] = true;
$config['show_route_filter_min_count'] = 5;
$config['offers_per_page'] = 10;
$config['n_th_point'] = 2;
$config['n_th_point_min_count'] = 100;
$config['pagination_max_numbers'] = 5;
$config['overview_thumbnail_size'] = 'medium';
$config['detail_thumbnail_size'] = 'large';
$config['placeholder_image'] = 'https://angebote.paerke.ch/img/placeholder.png';
$config['image_enlargement'] = true;
$config['detail_limit_dates'] = 5;
$config['filter_keywords_with_and'] = false;
$config['show_park_name'] = false;
$config['show_event_location_in_overview'] = true;
$config['show_short_description_in_overview'] = false;
$config['show_keywords_in_overview'] = false;
$config['show_button_in_overview'] = false;
$config['poi_listing_link_target'] = '';
$config['heading_offer_title_in_overview'] = 'h3';
$config['show_accessibility_filter'] = true;
$config['show_municipality_filter'] = true;


/*
|--------------------------------------------------------------------------
| Keyword filter
|--------------------------------------------------------------------------
|
| Set keywords for the optinally keyword filter.
| The first entry is the title of the filter, the second entry is the label to show all entries.
|
| Example:
| 	$config['keyword_filter'] = array(
|		'de' => array('Kategorien', 'Alle', 'Kinder', 'Kultur', 'Entdecken'),
|		'en' => array('Categories', 'All', 'Children', 'Culture', 'Discover')
| 	);
|
*/
$config['keyword_filter'] = array(
	'de' => array(),
	'fr' => array(),
	'it' => array(),
	'en' => array()
);


/*
|--------------------------------------------------------------------------
| View mode
|--------------------------------------------------------------------------
|
| Set if view should be returned or directly displayed
| Default: false, output will automatically displayed
|
*/
$config['return_output'] = false;


/*
|--------------------------------------------------------------------------
| CSS and JS include mode
|--------------------------------------------------------------------------
|
| Set if view should not include API CSS and JS files automatically.
| If true, you have to include these files manually in your system.
|
*/
$config['prevent_css_js_include'] = false;


/*
|--------------------------------------------------------------------------
| Favorites
|--------------------------------------------------------------------------
|
| Enable favorites module to list all favorites
| If enabled, set the script path to the parks_api/scripts/favorites.php
| file, e.g. /plugins/parks_api/scripts (without an ending slash)
|
*/
$config['favorites_extension_available'] = false;
$config['favorites_script_path'] = '';


/*
|--------------------------------------------------------------------------
| API prefixes
|--------------------------------------------------------------------------
|
| Prevent equal namespaces with a unique prefix
|
*/
$config['form_prefix'] = '';
$config['url_param_prefix'] = '';


/*
|--------------------------------------------------------------------------
| Languages
|--------------------------------------------------------------------------
|
| Set all available languages in your environment and your default language.
| Also specifiy if offers should be displayed or not whether it exists in the selected language
|
*/
$config['default_language'] = 'de';
$config['available_languages'] = array('de', 'fr', 'it', 'en');
$config['language_independence'] = true;
$config['language_priority'] = array(
	'de' => array('fr', 'it', 'en'),
	'fr' => array('de', 'it', 'en'),
	'it' => array('fr', 'de', 'en'),
	'en' => array('de', 'fr', 'it'),
);


/*
|--------------------------------------------------------------------------
| PHP session
|--------------------------------------------------------------------------
|
*/
$config['use_sessions'] = true;
$config['session_name'] = "parks_api";


/*
|--------------------------------------------------------------------------
| Logs
|--------------------------------------------------------------------------
|
| Define the folder where log files are stored.
| Make sure this directory is writeable.
|
*/
$config['log_directory'] = "log/";


/*
|--------------------------------------------------------------------------
| Formats
|--------------------------------------------------------------------------
|
| Specify date format for PHP & MySQL
|
*/
$config['mysql_date_format'] = "%d.%m.%Y %H:%i";
$config['php_date_format'] = "d.m.Y H:i";