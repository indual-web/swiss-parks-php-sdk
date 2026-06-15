<?php
/*
|-----------------------------------------------------------------------
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|-----------------------------------------------------------------------
|
| Import XML data into database
|
*/


class ParksImport
{


	/**
	 * API
	 */
	public ParksAPI $api;


	/**
	 * XML object containing all data
	 */
	private SimpleXMLElement|false $xml = false;


	/**
	 * Necessary Key-Value mappings for XML data
	 */
	private array $mappings;


	/**
	 * Constructor
	 */
	public function __construct(ParksAPI $api)
	{

		// Api instance
		$this->api = $api;

		// Setup mappings necessary for XML import
		$this->mappings = [
			'langs' => [
				'de' => 'de',
				'fr' => 'fr',
				'it' => 'it',
				'en' => 'en',
			],
			'levels' => [
				'' => 0,
				'easy' => 1,
				'medium' => 2,
				'hard' => 3,
			],
		];
	}



	/**
	 * Method for importing xml map layers into database
	 */
	public function import_map_layers(string $url): bool
	{

		// Load xml
		$this->xml = $this->api->load_external_xml($url);

		// Check XML
		if ($this->xml !== false) {

			// Speed up bulk writes with a single transaction
			$this->api->db->begin();

			// Remove all layers
			$this->api->db->delete('map_layer');

			// Import custom layers
			if (count($this->xml->MapLayer) > 0) {

				foreach ($this->xml->MapLayer as $layer) {

					if (empty($layer->URL)) {
						continue;
					}

					// Populate map layer data
					$map_layer = [
						'map_layer_id' => intval($layer->attributes()->identifier),
						'url' => (string) trim($layer->URL),
						'languages' => (string) $layer->URL->attributes()->language,
						'layer_category' => (string) $layer->LayerCategory ?? 'additional',
						'layer_position' => (int) $layer->URL->attributes()->position ?? 0,
						'visible_by_default' => (int) $layer->URL->attributes()->visibility ?? 0,
						'popup_title' => (string) $layer->Popup->Title ?? '',
						'popup_logo' => (string) $layer->Popup->Logo ?? '',
					];

					// Insert map layer
					if (! $this->api->db->insert('map_layer', $map_layer)) {
						$this->api->logger->error("Database error: " . $this->api->db->get_last_error());
						continue;
					}
					else {

						// Get last inserted map layer id
						$map_layer_id = $this->_get_last_id('map_layer_id', 'map_layer');
						if (! empty($map_layer_id)) {

							$map_i18n_fields = [];

							// Popup content
							if (! empty($layer->Popup->Content)) {
								foreach ($layer->Popup->Content as $content) {
									$language = (string) $content->attributes()->language;
									$key = $map_layer_id . '-' . $language;
									$map_i18n_fields[$key]['map_layer_id'] = $map_layer_id;
									$map_i18n_fields[$key]['language'] = $language;
									$map_i18n_fields[$key]['popup_content'] = (string) $content ?? null;
								}
							}

							// Layer title
							if (! empty($layer->LayerTitle)) {
								foreach ($layer->LayerTitle as $title) {
									$language = (string) $title->attributes()->language;
									$key = $map_layer_id . '-' . $language;
									$map_i18n_fields[$key]['map_layer_id'] = $map_layer_id;
									$map_i18n_fields[$key]['language'] = $language;
									$map_i18n_fields[$key]['layer_title'] = (string) $title ?? null;
								}
							}

							// Insert map layer i18n
							if (! empty($map_i18n_fields)) {
								foreach ($map_i18n_fields as $i18n) {
									if (! $this->api->db->insert('map_layer_i18n', $i18n)) {
										$this->api->logger->error("Database error: " . $this->api->db->get_last_error());
										continue;
									}
								}
							}

						}
					}
				}

				// Log
				$this->api->logger->info("Map layer import finished successfully.");
			}

			$this->api->db->commit();

			return true;
		}

		else {
			$this->api->logger->info("XML is not valid: " . $url);
		}

		return false;
	}



	/**
	 * Method for importing xml offers into database
	 */
	public function import(string $url, bool $force = false): bool
	{

		if (empty($url)) {
			$this->api->logger->error("No URL for XML file specified");

			return false;
		}

		// Speed up bulk writes with a single transaction
		$this->api->db->begin();

		// Sync target groups
		$this->sync_target_groups();

		// Sync categories
		$this->sync_categories();

		// Sync accessibility dropdown list
		$this->sync_accessibilities();

		// Sync fields of activity
		$this->sync_fields_of_activity();

		// Add param to url with last import timestamp
		if ($force !== true) {
			$api_info = $this->api->db->get('api')->fetch_assoc();
			$last_import = $api_info['last_import'];
			if (! empty($last_import)) {
				$url .= '?since=' . $last_import;
			} else {
				$force = true;
				$this->api->logger->info("Enabling FORCE mode because timestamp for last import is empty");
			}
		} else {
			$this->api->logger->info("FORCE mode is enabled");
		}

		// Load external data
		$this->xml = $this->api->load_external_xml($url);

		// Check xml
		if ($this->xml !== false) {

			// Log start
			$this->api->logger->info("Starting XML import from " . $url);

			// Init
			$ctr_imported = 0;
			$ctr_deleted = 0;
			$offers_checklist = [];

			if (! empty($this->xml)) {

				// Iterate over each offer and add it to the array
				$offer_list = [];
				foreach ($this->xml->Offer as $offer) {
					$offer_list[] = $offer;
				}

				// Chunk offers
				$offers_chunks = array_chunk($offer_list, 300);
				
				if (! empty($offers_chunks)) {

					foreach ($offers_chunks as $offers_chunk) {

						// Import every offer
						foreach ($offers_chunk as $offer) {

							// Init offer
							$offer_id = intval($offer->attributes()->identifier);
							$deleted_at = $offer->attributes()->deletedAt;
							$status = $offer->attributes()->status;

							// Check if offer should be deleted
							if (! empty($deleted_at) || ($status != OFFER_STATUS_ACTIVE)) {
								if ($force == false) {

									// Delete offer from db and continue
									$this->api->db->delete('offer', ['offer_id' => $offer_id]);
									$this->api->logger->info("\tDeleted offer with ID " . $offer_id);
									$ctr_deleted++;
								}

								continue;
							}

							// Set offer data
							$fields = [
								'park_id' => (int)$offer->ParkId,
								'park' => (string)$offer->Park,
								'institution' => $this->_address($offer->Institution),
								'institution_location' => (string)$offer->Institution->Locality,
								'institution_is_park_partner' => (int)$offer->Institution->ParkPartner,
								'contact' => $this->_address($offer->Contact),
								'contact_is_park_partner' => (int)$offer->Contact->ParkPartner,
								'is_hint' => ! empty((int) $offer->IsHint) ? 1 : 0,
								'barrier_free' => (int)$offer->BarrierFree,
								'learning_opportunity' => (int)$offer->LearningOpportunity,
								'child_friendly' => (int)$offer->ChildFriendly,
								'latitude' => (float)$offer->Latitude,
								'longitude' => (float)$offer->Longitude,
								'keywords' => (string)$offer->Keywords,
								'created_at' => $this->_datetime($offer->attributes()->createdAt),
								'modified_at' => $this->_datetime($offer->attributes()->modifiedAt),
							];

							// Error handling
							if (! $this->_insert_or_update('offer', $fields, ['offer_id' => $offer_id])) {
								$this->api->logger->error("Database error (offer id " . $offer_id . "): " . $this->api->db->get_last_error());
								continue;
							}

							// Categories
							$this->api->db->delete('category_link', ['offer_id' => $offer_id]);
							foreach ($offer->Categories->Category as $category) {
								$category_id = $category->attributes()->identifier;
								$this->api->db->insert('category_link', ['offer_id' => $offer_id, 'category_id' => $category_id]);
							}

							// Offer i18n
							$i18n = [];
							foreach ($this->mappings['langs'] as $lang) {
								$i18n[$lang] = [];
							}

							// Clean i18n data
							$this->api->db->delete('offer_i18n', ['offer_id' => $offer_id]);

							// Define i18n fields
							$i18n_fields = [
								'Title' => 'title',
								'Abstract' => 'abstract',
								'MediumDescription' => 'description_medium',
								'LongDescription' => 'description_long',
								'AdditionalInfos' => 'additional_informations',
								'Details' => 'details',
								'PriceInfos' => 'price',
								'LocationDetails' => 'location_details',
								'OpeningHours' => 'opening_hours',
								'OtherInfrastructure' => 'other_infrastructure',
								'Benefits' => 'benefits',
								'Requirements' => 'requirements',
								'CateringInfos' => 'catering_informations',
								'MaterialRent' => 'material_rent',
								'SafetyInstructions' => 'safety_instructions',
								'Signalization' => 'signalization',
								'ProjectInitialSituation' => 'project_initial_situation',
								'ProjectGoal' => 'project_goal',
								'ProjectFurtherInformation' => 'project_further_information',
								'ProjectResults' => 'project_results',
								'ProjectPartner' => 'project_partner',
							];

							// Setup i18n values
							$available_languages = [];
							foreach ($i18n_fields as $xml_node_name => $db_column_name) {

								// Node is set in xml
								if (! empty($offer->{$xml_node_name})) {
									foreach ($offer->{$xml_node_name} as $node) {
										$lang = (string)$node->attributes()->language;
										$available_languages[$lang] = null;
										$i18n[$lang][$db_column_name] = (string)$node;
									}
								}

								// Node is not set > clean this value from db
								else {
									foreach (array_keys($available_languages) as $lang) {
										$i18n[$lang][$db_column_name] = '';
									}
								}
							}

							// Route condition
							if (! empty($offer->RouteConditions->RouteCondition)) {
								foreach ($offer->RouteConditions->RouteCondition as $node) {
									$lang = (string)$node->attributes()->language;
									$i18n[$lang]['route_condition'] = (string)$node;
								}
							}

							// Route condition details
							if (! empty($offer->RouteConditions->RouteConditionDetails)) {
								foreach ($offer->RouteConditions->RouteConditionDetails as $node) {
									$lang = (string)$node->attributes()->language;
									$i18n[$lang]['route_condition_details'] = (string)$node;
								}
							}

							// Offer route url
							if (isset($offer->Route->URL)) {
								foreach ($offer->Route->URL as $url) {
									$lang = (string)$url->attributes()->language;
									$i18n[$lang]['route_url'] = (string)$url;
								}
							}

							// Insert internal informations
							$i18n_fields_intern = [
								'Costs' => 'costs',
								'Funding' => 'funding',
								'Partner' => 'partner',
								'Remarks' => 'remarks',
							];
							foreach ($i18n_fields_intern as $xml_node_name => $db_column_name) {

								// Clean data
								$this->api->db->update('offer_i18n', [$db_column_name => null], ['offer_id' => $offer_id]);

								// Node is set in xml
								if (! empty($offer->InternalInformations->{$xml_node_name})) {
									foreach ($offer->InternalInformations->{$xml_node_name} as $node) {
										$lang = (string)$node->attributes()->language;
										$available_languages[$lang] = null;
										$i18n[$lang][$db_column_name] = (string)$node;
									}
								}
								
							}

							// Insert or update i18n data for offer
							foreach ($i18n as $language => $data) {
								if (! empty($data)) {
									$this->_insert_or_update('offer_i18n', $data, ['offer_id' => $offer_id, 'language' => $language]);
								}
							}

							// Dates
							$this->api->db->delete('offer_date', ['offer_id' => $offer_id]);
							if (! empty($offer->Dates)) {
								foreach ($offer->Dates->Date as $date) {
									$date_from = $date->DateFrom . ($date->TimeFrom ? "T" . $date->TimeFrom : "");
									$date_to = null;
									if ($date->DateTo) {
										$date_to = $date->DateTo . ($date->TimeTo ? "T" . $date->TimeTo : "");
									}

									$fields = [
										'offer_id' => $offer_id,
										'date_from' => empty($date_from) ? null : $this->_datetime($date_from),
										'date_to' => empty($date_to) ? null : $this->_datetime($date_to),
									];

									$this->api->db->insert('offer_date', $fields);
								}
							}

							// TargetGroups
							$this->api->db->delete('target_group_link', ['offer_id' => $offer_id]);
							if ($offer->TargetGroups) {
								foreach ($offer->TargetGroups->TargetGroup as $target_group) {
									$target_group_id = $target_group->attributes()->identifier;
									$this->api->db->insert('target_group_link', ['offer_id' => $offer_id, 'target_group_id' => $target_group_id]);
								}
							}

							// Fields of activity
							$this->api->db->delete('field_of_activity_link', ['offer_id' => $offer_id]);
							if (! empty($offer->FieldsOfActivity->FieldOfActivity)) {
								foreach ($offer->FieldsOfActivity->FieldOfActivity as $field_of_activity) {
									$field_of_activity_id = $field_of_activity->attributes()->identifier;
									$this->api->db->insert('field_of_activity_link', ['offer_id' => $offer_id, 'field_of_activity_id' => $field_of_activity_id]);
								}
							}

							// Images
							$this->api->db->delete('image', ['offer_id' => $offer_id]);
							if ($offer->Images) {
								foreach ($offer->Images->Image as $image) {
									$fields = [];

									$fields['offer_id'] = $offer_id;
									$fields['small'] = (string)$image->Small;
									$fields['medium'] = (string)$image->Medium;
									$fields['large'] = (string)$image->Large;
									$fields['original'] = (string)$image->Original;
									$fields['copyright'] = (string)$image->Copyright;

									$this->api->db->insert('image', $fields);
								}
							}

							// Documents
							$this->api->db->delete('document', ['offer_id' => $offer_id]);
							if ($offer->Documents) {
								foreach ($offer->Documents->Document as $document) {
									$fields = [];

									$fields['offer_id'] = $offer_id;
									$fields['language'] = (string)$document->attributes()->language;
									$fields['title'] = (string)$document->Title;
									$fields['url'] = (string)$document->URL;

									$this->api->db->insert('document', $fields);
								}
							}

							// Documents intern
							$this->api->db->delete('document_intern', ['offer_id' => $offer_id]);
							if (! empty($offer->InternalInformations->DocumentsIntern->Document)) {
								foreach ($offer->InternalInformations->DocumentsIntern->Document as $document) {
									$fields = [];

									$fields['offer_id'] = $offer_id;
									$fields['language'] = (string)$document->attributes()->language;
									$fields['title'] = (string)$document->Title;
									$fields['url'] = (string)$document->URL;

									$this->api->db->insert('document_intern', $fields);
								}
							}

							// Hyperlinks
							$this->api->db->delete('hyperlink', ['offer_id' => $offer_id]);
							if ($offer->Hyperlinks) {
								foreach ($offer->Hyperlinks->Hyperlink as $hyperlink) {
									$fields = [];

									$fields['offer_id'] = $offer_id;
									$fields['language'] = (string)$hyperlink->attributes()->language;
									$fields['title'] = (string)$hyperlink->Title;
									$fields['url'] = (string)$hyperlink->URL;

									$this->api->db->insert('hyperlink', $fields);
								}
							}

							// Hyperlinks intern
							$this->api->db->delete('hyperlink_intern', ['offer_id' => $offer_id]);
							if ($offer->InternalInformations->HyperlinksIntern) {
								foreach ($offer->InternalInformations->HyperlinksIntern->Hyperlink as $hyperlink) {
									$fields = [];

									$fields['offer_id'] = $offer_id;
									$fields['language'] = (string)$hyperlink->attributes()->language;
									$fields['title'] = (string)$hyperlink->Title;
									$fields['url'] = (string)$hyperlink->URL;

									$this->api->db->insert('hyperlink_intern', $fields);
								}
							}

							// Accessibilities
							$this->api->db->delete('accessibility', ['offer_id' => $offer_id]);
							if ($offer->Accessibilities) {

								// Import main accessiblity data
								$accessibility_id = (string)$offer->Accessibilities->attributes()->identifier;
								$ginto_id = (string)$offer->Accessibilities->attributes()->gintoId;
								$ginto_icon = (string)$offer->Accessibilities->attributes()->gintoIcon;
								$ginto_link = (string)$offer->Accessibilities->attributes()->gintoLink;
								$this->api->db->insert('accessibility', [
									'accessibility_id' => $accessibility_id,
									'offer_id' => $offer_id,
									'ginto_id' => $ginto_id,
									'ginto_icon' => $ginto_icon,
									'ginto_link' => $ginto_link,
								]);

								// Import ratings
								$this->api->db->delete('accessibility_rating', ['accessibility_id' => $accessibility_id]);
								if ($offer->Accessibilities->Accessibility) {
									foreach ($offer->Accessibilities->Accessibility as $accessibility) {

										// Get rating ID
										$rating_id = (string)$accessibility->attributes()->identifier;

										// Insert or update rating
										$rating = [
											'accessibility_rating_id' => $rating_id,
											'accessibility_id' => $accessibility_id,
										];
										foreach ($accessibility->Name as $name) {
											$language = (string)$name->attributes()->language;
											$rating['description_' . $language] = (string)$name;
										}
										$rating['icon_url'] = (string)$accessibility->RatingIcon;
										$this->_insert_or_update('accessibility_rating', $rating, ['accessibility_rating_id' => $rating_id]);
									}
								}
							}

							// Municipalities
							$this->api->db->delete('offer_municipality_link', ['offer_id' => $offer_id]);
							if (! empty($offer->Municipalities)) {
								foreach ($offer->Municipalities->Municipality as $municipality) {

									// Get municipality  data
									$municipality_id = intval($municipality->attributes()->identifier);
									$municipality_name = (string)$municipality;
									
									// Build municipality
									$municipality_data = [
										'municipality_id' => $municipality_id,
										'park_id' => (int)$offer->ParkId,
										'municipality' => $municipality_name,
									];
									$this->_insert_or_update('municipality', $municipality_data, ['municipality_id' => $municipality_id, 'park_id' => $municipality_data['park_id']]);

									// Create municipality link
									$this->api->db->insert('offer_municipality_link', ['offer_id' => $offer_id, 'municipality_id' => $municipality_id]);
								}
							}

							// Get root category id
							$root_category_id = $this->_get_root_category(intval($offer->Categories->Category[0]->attributes()->identifier));

							// Subscription
							if (($root_category_id == CATEGORY_EVENT) || ($root_category_id == CATEGORY_BOOKING)) {
								$fields = [];

								$fields['subscription_mandatory'] = (int)$offer->Subscription->SubscriptionMandatory;
								$fields['online_subscription_enabled'] = (int)$offer->Subscription->OnlineSubscriptionEnabled;
								$fields['subscription_contact'] = $this->_address($offer->Subscription->SubscriptionContact);
								$fields['subscription_link'] = (string)$offer->Subscription->SubscriptionLink;
								$this->_insert_or_update('subscription', $fields, ['offer_id' => $offer_id]);

								// Subscription i18n
								if (isset($offer->Subscription->Details)) {
									foreach ($offer->Subscription->Details as $detail) {
										$subscription_detail = [];
										$subscription_detail['offer_id'] = $offer_id;
										$subscription_detail['language'] = (string)$detail->attributes()->language;
										$subscription_detail_where = $subscription_detail;
										$subscription_detail['subscription_details'] = (string)$detail;
										$this->_insert_or_update('subscription_i18n', $subscription_detail, $subscription_detail_where);
									}
								} else {
									$this->api->db->delete('subscription_i18n', ['offer_id' => $offer_id]);
								}
							}

							$fields = [];

							// Events
							if ($root_category_id == CATEGORY_EVENT) {
								$fields['is_park_event'] = (int)$offer->ParkEvent;
								$fields['is_park_partner_event'] = (int)$offer->ParkPartnerEvent;
								$fields['public_transport_stop'] = (string)$offer->PublicTransportStop;
								$fields['kind_of_event'] = (string)$offer->Kind;

								$this->_insert_or_update('event', $fields, ['offer_id' => $offer_id]);
							}

							// Products
							else if ($root_category_id == CATEGORY_PRODUCT) {
								$fields['public_transport_stop'] = (string)$offer->PublicTransportStop;
								$fields['number_of_rooms'] = (int)$offer->NumberOfRooms;
								$fields['has_conference_room'] = (int)$offer->HasConferenceRoom;
								$fields['has_playground'] = (int)$offer->HasPlayground;
								$fields['has_picnic_place'] = (int)$offer->HasPicnicPlace;
								$fields['has_fireplace'] = (int)$offer->HasFireplace;
								$fields['has_washrooms'] = (int)$offer->HasWashrooms;

								// Season months
								$fields['season_months'] = '';
								if (! empty($offer->SeasonMonths)) {
									$months = [];
									foreach ($offer->SeasonMonths->Month as $month) {
										$months[] = (int)$month;
									}
									$fields['season_months'] = implode(',', $months);
								}

								// Online shop
								if ($offer->OnlineShop) {

									// Online shop enabled
									$fields['online_shop_enabled'] = 1;

									// Online shop price
									$fields['online_shop_price'] = (float)$offer->OnlineShop->Price;

								}

								$this->_insert_or_update('product', $fields, ['offer_id' => $offer_id]);

								// Suppliers
								$this->api->db->delete('supplier', ['offer_id' => $offer_id]);
								if ($offer->Suppliers) {
									foreach ($offer->Suppliers->Supplier as $supplier) {
										$this->api->db->insert('supplier', ['offer_id' => $offer_id, 'contact' => $this->_address($supplier), 'is_park_partner' => (int)$supplier->ParkPartner]);
									}
								}

								// Online shop: i18n
								if ($offer->OnlineShop) {

									// PaymentTerms
									if (isset($offer->OnlineShop->PaymentTerms)) {
										foreach ($offer->OnlineShop->PaymentTerms as $payment_term) {
											$offer_i18n = [];
											$offer_i18n['offer_id'] = $offer_id;
											$offer_i18n['language'] = (string)$payment_term->attributes()->language;
											$offer_i18n_where = $offer_i18n;
											$offer_i18n['online_shop_payment_terms'] = (string)$payment_term;
											$this->_insert_or_update('offer_i18n', $offer_i18n, $offer_i18n_where);
										}
									}

									// DeliveryConditions
									if (isset($offer->OnlineShop->DeliveryConditions)) {
										foreach ($offer->OnlineShop->DeliveryConditions as $delivery_condition) {
											$offer_i18n = [];
											$offer_i18n['offer_id'] = $offer_id;
											$offer_i18n['language'] = (string)$delivery_condition->attributes()->language;
											$offer_i18n_where = $offer_i18n;
											$offer_i18n['online_shop_delivery_conditions'] = (string)$delivery_condition;
											$this->_insert_or_update('offer_i18n', $offer_i18n, $offer_i18n_where);
										}
									}

									// Online shop: Article
									$this->api->db->delete('product_article', ['offer_id' => $offer_id]);
									if ($offer->OnlineShop->Articles->Article) {
										foreach ($offer->OnlineShop->Articles->Article as $article) {

											// Set attributes
											$article_id = $article->attributes()->identifier;
											$is_food = ($article->attributes()->is_food == 'true') ? 1 : 0;

											// Insert article
											$this->api->db->insert('product_article', [
												'product_article_id' => $article_id,
												'offer_id' => $offer_id,
												'supplier_contact' => $this->_address($article->Supplier),
												'is_food' => (int)$is_food,
											]);

											// Article i18n: Title
											if (isset($article->ArticleTitle)) {
												foreach ($article->ArticleTitle as $title) {
													$article_i18n = [];
													$article_i18n['product_article_id'] = $article_id;
													$article_i18n['language'] = (string)$title->attributes()->language;
													$article_i18n_where = $article_i18n;
													$article_i18n['article_title'] = (string)$title;
													$this->_insert_or_update('product_article_i18n', $article_i18n, $article_i18n_where);
												}
											}

											// Article i18n: Description
											if (isset($article->ArticleDescription)) {
												foreach ($article->ArticleDescription as $description) {
													$article_i18n = [];
													$article_i18n['product_article_id'] = $article_id;
													$article_i18n['language'] = (string)$description->attributes()->language;
													$article_i18n_where = $article_i18n;
													$article_i18n['article_description'] = (string)$description;
													$this->_insert_or_update('product_article_i18n', $article_i18n, $article_i18n_where);
												}
											}

											// Article i18n: Ingredients
											if (isset($article->ArticleIngredients)) {
												foreach ($article->ArticleIngredients as $ingredients) {
													$article_i18n = [];
													$article_i18n['product_article_id'] = $article_id;
													$article_i18n['language'] = (string)$ingredients->attributes()->language;
													$article_i18n_where = $article_i18n;
													$article_i18n['article_ingredients'] = (string)$ingredients;
													$this->_insert_or_update('product_article_i18n', $article_i18n, $article_i18n_where);
												}
											}

											// Article i18n: Allergens
											if (isset($article->ArticleAllergens)) {
												foreach ($article->ArticleAllergens as $allergens) {
													$article_i18n = [];
													$article_i18n['product_article_id'] = $article_id;
													$article_i18n['language'] = (string)$allergens->attributes()->language;
													$article_i18n_where = $article_i18n;
													$article_i18n['article_allergens'] = (string)$allergens;
													$this->_insert_or_update('product_article_i18n', $article_i18n, $article_i18n_where);
												}
											}

											// Article i18n: NutritionalValues
											if (isset($article->ArticleNutritionalValues)) {
												foreach ($article->ArticleNutritionalValues as $nutritional_values) {
													$article_i18n = [];
													$article_i18n['product_article_id'] = $article_id;
													$article_i18n['language'] = (string)$nutritional_values->attributes()->language;
													$article_i18n_where = $article_i18n;
													$article_i18n['article_nutritional_values'] = (string)$nutritional_values;
													$this->_insert_or_update('product_article_i18n', $article_i18n, $article_i18n_where);
												}
											}

											// Article i18n: IdentityLabel
											if (isset($article->ArticleIdentityLabel)) {
												foreach ($article->ArticleIdentityLabel as $identity_label) {
													$article_i18n = [];
													$article_i18n['product_article_id'] = $article_id;
													$article_i18n['language'] = (string)$identity_label->attributes()->language;
													$article_i18n_where = $article_i18n;
													$article_i18n['article_identity_label'] = (string)$identity_label;
													$this->_insert_or_update('product_article_i18n', $article_i18n, $article_i18n_where);
												}
											}

											// Article i18n: QuantityIndication
											if (isset($article->ArticleQuantityIndication)) {
												foreach ($article->ArticleQuantityIndication as $quantity_indication) {
													$article_i18n = [];
													$article_i18n['product_article_id'] = $article_id;
													$article_i18n['language'] = (string)$quantity_indication->attributes()->language;
													$article_i18n_where = $article_i18n;
													$article_i18n['article_quantity_indication'] = (string)$quantity_indication;
													$this->_insert_or_update('product_article_i18n', $article_i18n, $article_i18n_where);
												}
											}

											// Import article labels
											$this->api->db->delete('product_article_label', ['product_article_id' => $article_id]);
											if (isset($article->ArticleLabels->ArticleLabel)) {

												// Iterate all article labels
												foreach ($article->ArticleLabels->ArticleLabel as $label) {

													// Set label id
													$label_id = $label->attributes()->identifier;

													// Iterate all labels
													foreach ($label->Label as $single_label) {

														// Insert article label
														$this->api->db->insert('product_article_label', [
															'product_article_id' => $article_id,
															'label_id' => $label_id,
															'language' => $single_label->attributes()->language,
															'label_title' => (string)$single_label,
															'label_url' => $single_label->attributes()->url,
															'label_icon' => $single_label->attributes()->icon,
														]);
													}
												}
											}
										}
									}
								}
							}

							// Bookings
							else if ($root_category_id == CATEGORY_BOOKING) {
								$fields['is_park_partner'] = (int)$offer->IsParkPartner;
								$fields['min_group_subscriber'] = (int)$offer->MinGroupSubscribers;
								$fields['max_group_subscriber'] = (int)$offer->MaxGroupSubscribers;
								$fields['min_individual_subscriber'] = (int)$offer->MinIndividualSubscribers;
								$fields['max_individual_subscriber'] = (int)$offer->MaxIndividualSubscribers;
								$fields['public_transport_stop'] = (string)$offer->PublicTransportStop;

								// Season months
								$fields['season_months'] = '';
								if (! empty($offer->SeasonMonths)) {
									$months = [];
									foreach ($offer->SeasonMonths->Month as $month) {
										$months[] = (int)$month;
									}
									$fields['season_months'] = implode(',', $months);
								}

								$this->_insert_or_update('booking', $fields, ['offer_id' => $offer_id]);

								// Accommodations
								$this->api->db->delete('accommodation', ['offer_id' => $offer_id]);
								if ($offer->Accommodations) {
									foreach ($offer->Accommodations->Accommodation as $accommodation) {
										$this->api->db->insert('accommodation', ['offer_id' => $offer_id, 'contact' => $this->_address($accommodation), 'is_park_partner' => (int)$accommodation->ParkPartner]);
									}
								}
							}

							// Activities
							else if ($root_category_id == CATEGORY_ACTIVITY) {
								$fields['start_place_info'] = (string)$offer->StartPlaceInfo;
								$fields['start_place_altitude'] = (int)$offer->StartPlaceAltitude;
								$fields['goal_place_info'] = (string)$offer->GoalPlaceInfo;
								$fields['goal_place_altitude'] = (int)$offer->GoalPlaceAltitude;
								$fields['route_length'] = (float)$offer->RouteLength;
								$fields['untarred_route_length'] = (float)$offer->UntarredRouteLength;
								$fields['public_transport_start'] = (string)$offer->PublicTransportStart;
								$fields['public_transport_stop'] = (string)$offer->PublicTransportStop;
								$fields['altitude_differential'] = (int)$offer->AltitudeDifferential;
								$fields['altitude_ascent'] = (int)$offer->AltitudeAscent;
								$fields['altitude_descent'] = (int)$offer->AltitudeDescent;
								$fields['time_required'] = (string)$offer->TimeRequired;
								$fields['time_required_minutes'] = (int)$offer->TimeRequiredMinutes;
								$fields['level_technics'] = $this->mappings['levels'][(string)$offer->LevelTechnics];
								$fields['level_condition'] = $this->mappings['levels'][(string)$offer->LevelCondition];
								$fields['has_playground'] = (int)$offer->HasPlayground;
								$fields['has_picnic_place'] = (int)$offer->HasPicnicPlace;
								$fields['has_fireplace'] = (int)$offer->HasFireplace;
								$fields['has_washrooms'] = (int)$offer->HasWashrooms;

								// Route condition ID and color
								if (! empty($offer->RouteConditions)) {
									$fields['route_condition_id'] = $lang = intval($offer->RouteConditions->attributes()->identifier);
									$fields['route_condition_color'] = $lang = (string)$offer->RouteConditions->attributes()->color;
								}

								// Season months
								$fields['season_months'] = '';
								if (! empty($offer->SeasonMonths)) {
									$months = [];
									foreach ($offer->SeasonMonths->Month as $month) {
										$months[] = (int)$month;
									}
									$fields['season_months'] = implode(',', $months);
								}

								// POI
								$fields['poi'] = null;
								if (! empty($offer->POI->OfferId)) {
									foreach ($offer->POI->OfferId as $poi) {
										$fields['poi'][] = $poi;
									}
									$fields['poi'] = implode(',', $fields['poi']) . ',';
								}

								$this->_insert_or_update('activity', $fields, ['offer_id' => $offer_id]);
								
							}

							// Projects and research
							else if (in_array($root_category_id, [CATEGORY_PROJECT, CATEGORY_RESEARCH])) {
								$fields['duration_from'] = (int)$offer->DurationFrom;
								$fields['duration_from_month'] = (int)$offer->DurationFromMonth;
								$fields['duration_to'] = (int)$offer->DurationTo;
								$fields['duration_to_month'] = (int)$offer->DurationToMonth;
								$fields['status'] = intval($offer->Status->attributes()->identifier);

								// POI
								$fields['poi'] = null;
								if (isset($offer->POI) && ! empty($offer->POI->OfferId)) {
									foreach ($offer->POI->OfferId as $poi) {
										$fields['poi'][] = $poi;
									}
									$fields['poi'] = implode(',', $fields['poi']);
								}

								$this->_insert_or_update('project', $fields, ['offer_id' => $offer_id]);
							}

							// Check off this offer
							array_push($offers_checklist, $offer_id);
							$ctr_imported++;

							$this->api->logger->info("\tImported offer with ID " . $offer_id);

						}
					}
				}
			}

			$this->api->logger->info("Successfully imported " . $ctr_imported . " offers.");

			// Delete offers which did not get updated
			if ($force === true) {
				$this->api->logger->info("Deleting offers which were not updated...");

				// Iterate all existing offers
				$all_offers = $this->api->db->get('offer', null, null, ['offer_id']);
				while ($offer = $all_offers->fetch_assoc()) {
					if (! in_array($offer['offer_id'], $offers_checklist)) {
						$this->api->db->delete('offer', ['offer_id' => $offer['offer_id']]);
						$this->api->logger->info("\tDeleted offer with ID " . $offer['offer_id']);
						$ctr_deleted++;
					}
				}

				if ($ctr_deleted == 0) {
					$this->api->logger->info("\tNothing to delete");
				}
			} else {
				$this->api->logger->info("Deleted " . $ctr_deleted . " offers.");
			}

			$this->api->db->update('api', ['last_import' => time()]);

			// Log message
			$message = 'Import finished successfully.';
			$this->api->logger->info($message);

			$this->api->db->commit();

			return true;
		}

		else {
			$this->api->logger->info("XML is not valid: " . $url);
		}

		$this->api->db->commit();

		return false;
	}



	/**
	 * Clean up offers (remove inactive offers with active id)
	 */
	public function clean_up_offers(string $url): bool
	{

		// Load xml
		$this->xml = $this->api->load_external_xml($url);

		// Init
		$active_offers = [];
		$ctr_deleted = 0;

		// Check xml
		if ($this->xml !== false) {

			// Get active offers
			if (! empty($this->xml)) {
				foreach ($this->xml->Offer as $active_offer) {
					$active_offers[] = intval($active_offer->attributes()->identifier);
				}
			}

			// Remove inactive offers
			$this->api->logger->info("Clean up offers");
			$this->api->db->begin();
			$all_offers = $this->api->db->get('offer', null, null, ['offer_id']);

			// Iterate all existing offers
			while ($offer = $all_offers->fetch_assoc()) {

				// Delete inactive offer
				if (! in_array($offer['offer_id'], $active_offers)) {
					$this->api->db->delete('offer', ['offer_id' => $offer['offer_id']]);
					$this->api->logger->info("\tDeleted inactive offer with ID " . $offer['offer_id']);
					$ctr_deleted++;
				}
			}

			$this->api->db->commit();

			// Log result
			if ($ctr_deleted == 0) {
				$this->api->logger->info("\tNo inactive offers");
			} else {
				$this->api->logger->info("Deleted " . $ctr_deleted . " inactive offer(s).");
			}

			return true;
		}

		else {
			$this->api->logger->info("XML is not valid: " . $url);
		}

		return false;
	}



	/**
	 * Synchronise target groups
	 */
	public function sync_target_groups(): void
	{

		// Get and parse json
		$json = file_get_contents($this->api->config['json_export_target_groups']);
		$target_groups = json_decode($json, true);

		// Sync target groups
		if (! empty($target_groups) && is_array($target_groups)) {
			$this->api->model->sync_target_groups($target_groups);
		}
	}


	
	/**
	 * Synchronise fields of activity
	 */
	public function sync_fields_of_activity(): void
	{

		// Get and parse json
		$json = file_get_contents($this->api->config['json_export_fields_of_activity']);
		$fields_of_activity = json_decode($json, true);

		// Sync fields of activity
		if (! empty($fields_of_activity) && is_array($fields_of_activity)) {
			$this->api->model->sync_fields_of_activity($fields_of_activity);
		}
		
	}


	
	/**
	 * Synchronise categories
	 */
	public function sync_categories(): void
	{

		// Get and parse json
		$json = file_get_contents($this->api->config['json_export_categories']);
		$categories = json_decode($json, true);

		// Sync categories
		if (! empty($categories) && is_array($categories)) {
			$this->api->model->sync_categories($categories);
		}
		
	}



	/**
	 * Synchronise accessibility dropdown list
	 */
	public function sync_accessibilities(): void
	{

		// Get and parse json
		$json = file_get_contents($this->api->config['json_export_accessibilities']);
		$options = json_decode($json, true);

		// Sync dropdown options
		if (! empty($options) && is_array($options)) {
			$this->api->model->sync_accessibilities($options);
		}
	}



	/**
	 * Private method for retrieving the root category
	 */
	private function _get_root_category(int $category_id): int|false
	{
		if (! empty($category_id)) {

			while ($category_id > 0) {
				$q_category = $this->api->db->get('category', ['category_id' => $category_id]);
				if ($q_category->num_rows > 0) {
					$category = $q_category->fetch_object();
					$category_id = $category->parent_id;
					$last_category_id = $category->category_id;
				}
			}

			return $last_category_id;
		}

		return false;
	}



	/**
	 * Private method for inserting or updating db record
	 */
	private function _insert_or_update(string $table, array $fields, array $where): bool
	{
		if (! empty($table) && ! empty($fields) && ! empty($where)) {

			// Check if offer exists in database
			$exists = $this->api->db->get($table, $where);

			// Update db record
			if ($exists->num_rows > 0) {
				return $this->api->db->update($table, $fields, $where);
			}

			// Insert new db record
			else {
				$fields = array_merge($fields, $where);
				return $this->api->db->insert($table, $fields);
			}
			
		}

		return false;
	}



	/**
	 * Private method for parsing Date/Time
	 */
	private function _datetime(string $value, string $format = "Y-m-d H:i:s"): string
	{
		$datetime = new DateTime($value);
		return $datetime->format($format);
	}



	/**
	 * Private method for parsing Address from XML
	 */
	private function _address(object $contact): string
	{
		$address = [];

		if (! empty($contact->Company)) {
			$address[] = $contact->Company;
		}

		if (! empty($contact->FirstName) || ! empty($contact->LastName)) {
			$address['name'] = '';
			
			if (! empty($contact->FirstName)) {
				$address['name'] = $contact->FirstName;
			}
			if (! empty($contact->LastName) && ! empty($contact->FirstName)) {
				$address['name'] .= ' ';
			}
			if (! empty($contact->LastName)) {
				$address['name'] .= $contact->LastName;
			}
		}

		if (! empty($contact->Address)) {
			$address[] = $contact->Address;
		}

		if (! empty($contact->Locality)) {
			$address[] = ($contact->ZipCode > 0 ? $contact->ZipCode . ' ' : '') . $contact->Locality;
		}

		if (! empty($contact->Phone)) {
			$address[] = 'T ' . $contact->Phone;
		}

		if (! empty($contact->Mobile)) {
			$address[] = 'Mobile ' . $contact->Mobile;
		}

		if (! empty($contact->Fax)) {
			$address[] = 'Fax ' . $contact->Fax;
		}

		if (! empty($contact->Email)) {
			$address[] = $contact->Email;
		}

		if (! empty($contact->URL) && ($contact->URL != 'https://') && ($contact->URL != 'http://')) {
			$address[] = $contact->URL;
		}

		return implode("\n", $address);
	}



	/**
	 * Get last ID
	 */
	private function _get_last_id(string $name_id, string $table): int
	{
		$query =  $this->api->db->query('SELECT MAX(' . $name_id . ') as id FROM ' . $table);
		$result = $query->fetch_object();

		return $result->id;
	}



}