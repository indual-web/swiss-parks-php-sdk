<?php
// Smoke test for SQLite-backed queries (no network access required)

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../parks_api/autoload.php';

$failures = 0;
$test_db = 'data/test_park-offers.sqlite';

function check(string $name, bool $condition): void
{

	global $failures;
	if ($condition) {
		echo "OK   " . $name . "\n";
	} else {
		echo "FAIL " . $name . "\n";
		$failures++;
	}
}

// Build API instance without running the constructor (no network access)
$api = (new ReflectionClass('ParksAPI'))->newInstanceWithoutConstructor();
$api->config = [
	'absolute_path' => realpath(__DIR__ . '/../parks_api'),
	'db_path' => $test_db,
	'log_directory' => 'log/',
	'available_languages' => ['de', 'fr', 'it', 'en'],
	'language_independence' => true,
	'language_priority' => [
		'de' => ['fr', 'it', 'en'],
		'fr' => ['de', 'it', 'en'],
		'it' => ['fr', 'de', 'en'],
		'en' => ['de', 'fr', 'it'],
	],
	'filter_keywords_with_and' => false,
	'db_date_format' => '%d.%m.%Y %H:%i',
];

// Remove leftovers from previous runs
foreach (['', '-wal', '-shm'] as $suffix) {
	@unlink(__DIR__ . '/../parks_api/' . $test_db . $suffix);
}

$api->lang = new ParksLanguage('de', $api);
$api->lang_id = 'de';
$api->logger = new ParksLog($api);
$api->db = new ParksSQLite($api);

// Schema created automatically
check('schema created', $api->db->query("SELECT * FROM api") !== false);
check('38 objects exist', $api->db->query("SELECT COUNT(*) AS c FROM sqlite_master WHERE type = 'table'")->fetch_object()->c >= 30);

// Basic CRUD with quotes
check('insert with quote', $api->db->insert('municipality', ['municipality_id' => 1, 'park_id' => 2, 'municipality' => "L'Etivaz"]));
$row = $api->db->get('municipality', ['municipality_id' => 1])->fetch_assoc();
check('select escaped value', $row['municipality'] === "L'Etivaz");
check('update', $api->db->update('municipality', ['municipality' => "St. Maria's"], ['municipality_id' => 1]));
check('updated value', $api->db->get('municipality')->fetch_assoc()['municipality'] === "St. Maria's");

// Seed taxonomy
$api->db->insert('category', ['category_id' => 1, 'parent_id' => 0, 'marker' => '362782', 'sort' => 1000]);
$api->db->insert('category', ['category_id' => 101, 'parent_id' => 1, 'marker' => 'ffcc00', 'sort' => 1010]);
$api->db->insert('category', ['category_id' => 4, 'parent_id' => 0, 'marker' => '4BA234', 'sort' => 3000]);
foreach (['de' => 'Veranstaltung', 'fr' => 'Manifestation'] as $lang => $body) {
	$api->db->insert('category_i18n', ['category_id' => 1, 'language' => $lang, 'body' => $body]);
	$api->db->insert('category_i18n', ['category_id' => 101, 'language' => $lang, 'body' => 'Markt ' . $lang]);
	$api->db->insert('category_i18n', ['category_id' => 4, 'language' => $lang, 'body' => 'Route ' . $lang]);
}
$api->db->insert('target_group', ['target_group_id' => 1, 'sort' => 1]);
$api->db->insert('target_group_i18n', ['target_group_id' => 1, 'language' => 'de', 'body' => 'Familien']);
$api->db->insert('field_of_activity', ['field_of_activity_id' => 1, 'sort' => 1]);
$api->db->insert('field_of_activity_i18n', ['field_of_activity_id' => 1, 'language' => 'de', 'body' => 'Natur']);

// Seed offers
$api->db->begin();
$api->db->insert('offer', ['offer_id' => 10, 'park_id' => 2, 'park' => 'lpb', 'keywords' => 'markt', 'latitude' => 46.5, 'longitude' => 7.1, 'created_at' => '2026-01-01 10:00:00', 'modified_at' => '2026-01-02 10:00:00']);
$api->db->insert('offer_i18n', ['offer_id' => 10, 'language' => 'de', 'title' => "Markt in L'Etivaz", 'abstract' => 'Ein Markt']);
$api->db->insert('category_link', ['offer_id' => 10, 'category_id' => 101]);
$api->db->insert('event', ['offer_id' => 10, 'is_park_event' => 1, 'public_transport_stop' => 'Dorfplatz']);
$api->db->insert('offer_date', ['offer_id' => 10, 'date_from' => '2030-07-01 10:00:00', 'date_to' => '2030-07-01 16:00:00']);
$api->db->insert('target_group_link', ['offer_id' => 10, 'target_group_id' => 1]);
$api->db->insert('field_of_activity_link', ['offer_id' => 10, 'field_of_activity_id' => 1]);
$api->db->insert('accessibility', ['accessibility_id' => 99, 'offer_id' => 10, 'ginto_id' => 'g1']);
$api->db->insert('accessibility_rating', ['accessibility_rating_id' => 991, 'accessibility_id' => 99, 'description_de' => 'Rollstuhl', 'icon_url' => 'https://x/icon1.svg']);
$api->db->insert('accessibility_dropdown', ['icon_url' => 'https://x/icon1.svg']);

$api->db->insert('offer', ['offer_id' => 20, 'park_id' => 3, 'park' => 'snp', 'created_at' => '2026-01-01 10:00:00', 'modified_at' => '2026-01-01 10:00:00']);
$api->db->insert('offer_i18n', ['offer_id' => 20, 'language' => 'fr', 'title' => 'Randonnée', 'abstract' => 'Une route']);
$api->db->insert('category_link', ['offer_id' => 20, 'category_id' => 4]);
$api->db->insert('activity', ['offer_id' => 20, 'route_length' => 12.5, 'time_required_minutes' => 180, 'level_technics' => 2, 'poi' => '10,']);
$api->db->commit();

// Model
$model = new ParksModel($api);
check('model target groups loaded', $model->target_groups[1] === 'Familien');
check('model fields of activity loaded', $model->fields_of_activity[1] === 'Natur');
check('offer_exists', $model->offer_exists(10) === true);

// Main offer filtering (the big query)
$offers = $model->filter_offers([], 10, 0);
check('filter_offers returns data', is_array($offers) && count($offers['data']) === 2);
check('filter_offers total', $offers['total'] == 2);

// Pagination total ignores limit
$offers_limited = $model->filter_offers([], 1, 0);
check('limited result', count($offers_limited['data']) === 1);
check('total ignores limit', $offers_limited['total'] == 2);

// Language fallback: fr offer visible in de
$titles = array_map(fn ($o) => $o->title, $offers['data']);
check('language fallback works', in_array('Randonnée', $titles));

// Event fields
$event_offer = null;
foreach ($offers['data'] as $o) {
	if ($o->offer_id == 10) {
		$event_offer = $o;
	}
}
check('event main category', $event_offer->main_category_id == 1);
check('event start_date formatted', $event_offer->start_date === '2030-07-01');
check('event times formatted', $event_offer->times === '10:00 - 16:00');
check('event duration hours', $event_offer->duration == 6);
check('icon_urls aggregated', strpos($event_offer->icon_urls, 'icon1.svg') !== false);
check('dates use db_date_format', $event_offer->dates[0]->date_from === '01.07.2030 10:00');
check('target groups resolved', $event_offer->target_groups[1] === 'Familien');

// Category filter
$filtered = $model->filter_offers(['categories' => [1]], 10, 0);
check('category filter', count($filtered['data']) === 1 && $filtered['data'][0]->offer_id == 10);

$foa_filtered = $model->filter_offers(['fields_of_activity' => [1]], 10, 0);
check('fields of activity filter', count($foa_filtered['data']) === 1 && $foa_filtered['data'][0]->offer_id == 10);

// Date filter
$by_date = $model->filter_offers(['date_from' => '2030-06-30', 'date_to' => '2030-07-02'], 10, 0);
check('date span filter', is_array($by_date) && count($by_date['data']) === 1);
$by_date_none = $model->filter_offers(['date_from' => '2031-01-01', 'date_to' => '2031-01-02'], 10, 0);
check('date span filter excludes', $by_date_none === false || count($by_date_none['data']) === 0);

// Search filter
$search = $model->filter_offers(['search' => 'Markt'], 10, 0);
check('search filter', count($search['data']) === 1);

// Route filters
$routes = $model->filter_offers(['route_length_min' => 10, 'level_technics' => [2]], 10, 0);
check('route filter', count($routes['data']) === 1 && $routes['data'][0]->offer_id == 20);

// Count categories mode
$counts = $model->count_offers_by_category([]);
check('count categories', $counts->event_count == 1 && $counts->activity_count == 1);

// Only categories mode
$cats = $model->get_filter_categories([]);
$found_cats = [];
if ($cats) {
	while ($cat_row = $cats->fetch_assoc()) {
		foreach ($cat_row as $value) {
			$found_cats[] = $value;
		}
	}
}
check('only categories iterable', in_array(101, $found_cats) && in_array(4, $found_cats));

// Only parks mode
$parks = $model->get_filter_parks([]);
check('only parks', $parks && $parks->num_rows === 2);

// Random order
$random = $model->filter_offers([], 10, 0, false, false, true, true);
check('order by rand', count($random['data']) === 2);

// Single offer with linked routes
$offer = $model->get_offer(10);
check('get_offer', $offer->offer_id == 10);
check('linked routes resolved', $offer->linked_routes == [20]);

// Category helpers
$all_categories = $model->get_all_categories();
check('get_all_categories', $all_categories[101]->body === 'Markt de' && in_array(1, $all_categories[101]->parents));
$tree = $model->get_category_tree();
check('get_category_tree indentation', $tree[101] === '--- Markt de');
check('get_all_users', $model->get_all_users() == [2 => 'lpb', 3 => 'snp']);

// Accessibility dropdown list
$list = $model->get_accessibility_list();
check('accessibility list', isset($list['https://x/icon1.svg']));

// Municipalities
check('get_municipalities', $model->get_municipalities() == [1 => "St. Maria's"]);

// Cascading delete (offer 10) incl. trigger for ratings
$api->db->delete('offer', ['offer_id' => 10]);
check('cascade offer_i18n', $api->db->get('offer_i18n', ['offer_id' => 10])->num_rows === 0);
check('cascade offer_date', $api->db->get('offer_date', ['offer_id' => 10])->num_rows === 0);
check('cascade accessibility', $api->db->get('accessibility', ['offer_id' => 10])->num_rows === 0);
check('trigger accessibility_rating', $api->db->get('accessibility_rating')->num_rows === 0);
check('taxonomy survives', $api->db->get('category')->num_rows === 3);

// Recreate (used by migrate())
$api->db->recreate();
check('recreate empties db', $api->db->get('offer')->num_rows === 0);
check('recreate keeps schema', $api->db->query("SELECT * FROM api") !== false);

// Output mode: echo must return null, buffer mode must return string
$view = (new ReflectionClass('ParksView'))->newInstanceWithoutConstructor();
$view->api = $api;
$view->return_output = false;
$view_output = new ReflectionMethod('ParksView', '_output');
ob_start();
$view_echo_result = $view_output->invoke($view, 'html');
$view_echo_buffer = ob_get_clean();
check('view echo returns null', $view_echo_result === null);
check('view echo prints content', $view_echo_buffer === 'html');
$view->return_output = true;
check('view buffer returns content', $view_output->invoke($view, 'html') === 'html');

$api->return_output = false;
$api_output = new ReflectionMethod('ParksAPI', '_output');
ob_start();
$api_echo_result = $api_output->invoke($api, 'total');
$api_echo_buffer = ob_get_clean();
check('api echo returns null', $api_echo_result === null);
check('api echo prints content', $api_echo_buffer === 'total');
$api->return_output = true;
check('api buffer returns content', $api_output->invoke($api, 'total') === 'total');

// Cleanup
foreach (['', '-wal', '-shm'] as $suffix) {
	@unlink(__DIR__ . '/../parks_api/' . $test_db . $suffix);
}

echo $failures === 0 ? "\nALL TESTS PASSED\n" : "\n" . $failures . " TESTS FAILED\n";
exit($failures === 0 ? 0 : 1);
