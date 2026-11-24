<?php
/*
| Swiss Parks PHP SDK
| https://github.com/indual-web/swiss-parks-php-sdk
|
| Built by indual · www.indual.ch
|
*/

namespace SwissParks\Sdk;

use SimpleXMLElement;


class Import
{


	/**
	 * SDK instance
	 */
	public SwissParksSdk $sdk;


	/**
	 * XML object containing all data
	 */
	private SimpleXMLElement|false $xml = false;


	/**
	 * Necessary Key-Value mappings for XML data
	 */
	private array $mappings;


	/**
	 * Stats from the last import / map-layer / cleanup run (for CLI output)
	 */
	public int $lastImportedOffers = 0;
	public int $lastDeletedOffers = 0;
	public bool $lastImportForced = false;
	public int $lastImportedMapLayers = 0;
	public int $lastCleanupDeletedOffers = 0;


	/**
	 * Constructor
	 */
	public function __construct(SwissParksSdk $sdk)
	{

		$this->sdk = $sdk;

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
	public function importMapLayers(string $url): bool
	{

		$this->lastImportedMapLayers = 0;

		// Load xml
		$this->xml = $this->sdk->loadExternalXml($url);

		// Check XML
		if ($this->xml !== false) {

			// Speed up bulk writes with a single transaction
			$this->sdk->db->begin();

			// Remove all layers
			$this->sdk->db->delete('map_layer');

			// Import custom layers
			if (count($this->xml->MapLayers) > 0) {

				foreach ($this->xml->MapLayers->MapLayer as $layer) {

					if (empty($layer->URL)) {
						continue;
					}

					$legacyUrl = trim((string) $layer->URL);
					$languagesCsv = (string) $layer->URL->attributes()->language;

					// Popup titles: language-specific + language-less fallback for older SDKs
					$fallbackPopupTitle = '';
					$popupTitlesByLang = [];
					if (! empty($layer->Popup->Title)) {
						foreach ($layer->Popup->Title as $title) {
							$language = (string) $title->attributes()->language;
							$titleValue = (string) $title;
							if ($language === '') {
								$fallbackPopupTitle = $titleValue;
								continue;
							}
							$popupTitlesByLang[$language] = $titleValue;
						}
					}

					// Populate map layer data (legacy URL stays on XML node URL for older consumers)
					$mapLayer = [
						'map_layer_id' => intval($layer->attributes()->identifier),
						'languages' => $languagesCsv,
						'layer_category' => (string) $layer->LayerCategory ?? 'additional',
						'layer_position' => (int) $layer->URL->attributes()->position ?? 0,
						'visible_by_default' => (int) $layer->URL->attributes()->visibility ?? 0,
						'popup_title' => $fallbackPopupTitle,
						'popup_logo' => (string) $layer->Popup->Logo ?? '',
					];

					// Insert map layer
					if (! $this->sdk->db->insert('map_layer', $mapLayer)) {
						$this->sdk->logger->error("Database error: " . $this->sdk->db->getLastError());
						continue;
					}
					else {

						$this->lastImportedMapLayers++;

						// Get last inserted map layer id
						$mapLayerId = $this->getLastId('map_layer_id', 'map_layer');
						if (! empty($mapLayerId)) {

							$mapI18nFields = [];

							// Popup title per language
							foreach ($popupTitlesByLang as $language => $titleValue) {
								$key = $mapLayerId . '-' . $language;
								$mapI18nFields[$key]['map_layer_id'] = $mapLayerId;
								$mapI18nFields[$key]['language'] = $language;
								$mapI18nFields[$key]['popup_title'] = $titleValue;
							}

							// Popup content
							if (! empty($layer->Popup->Content)) {
								foreach ($layer->Popup->Content as $content) {
									$language = (string) $content->attributes()->language;
									$key = $mapLayerId . '-' . $language;
									$mapI18nFields[$key]['map_layer_id'] = $mapLayerId;
									$mapI18nFields[$key]['language'] = $language;
									$mapI18nFields[$key]['popup_content'] = (string) $content ?? null;
								}
							}

							// Layer title
							if (! empty($layer->LayerTitle)) {
								foreach ($layer->LayerTitle as $title) {
									$language = (string) $title->attributes()->language;
									$key = $mapLayerId . '-' . $language;
									$mapI18nFields[$key]['map_layer_id'] = $mapLayerId;
									$mapI18nFields[$key]['language'] = $language;
									$mapI18nFields[$key]['layer_title'] = (string) $title ?? null;
								}
							}

							// Prefer ServiceURL per language; fill gaps from legacy URL for CSV languages
							if (! empty($layer->ServiceURL)) {
								foreach ($layer->ServiceURL as $serviceUrl) {
									$language = (string) $serviceUrl->attributes()->language;
									if ($language === '') {
										continue;
									}
									$key = $mapLayerId . '-' . $language;
									$mapI18nFields[$key]['map_layer_id'] = $mapLayerId;
									$mapI18nFields[$key]['language'] = $language;
									$mapI18nFields[$key]['url'] = trim((string) $serviceUrl);
								}
							}

							// Fallback to legacy URL for CSV languages
							foreach (array_filter(array_map('trim', explode(',', $languagesCsv))) as $language) {
								$key = $mapLayerId . '-' . $language;
								if (! empty($mapI18nFields[$key]['url'])) {
									continue;
								}
								$mapI18nFields[$key]['map_layer_id'] = $mapLayerId;
								$mapI18nFields[$key]['language'] = $language;
								$mapI18nFields[$key]['url'] = $legacyUrl;
							}

							// Insert map layer i18n
							if (! empty($mapI18nFields)) {
								foreach ($mapI18nFields as $i18n) {
									if (! isset($i18n['url'])) {
										$i18n['url'] = '';
									}
									if (! $this->sdk->db->insert('map_layer_i18n', $i18n)) {
										$this->sdk->logger->error("Database error: " . $this->sdk->db->getLastError());
										continue;
									}
								}
							}

						}
					}
				}

				// Log
				$this->sdk->logger->info("Map layer import finished successfully.");
			}

			$this->sdk->db->commit();

			return true;
		}

		else {
			$this->sdk->logger->info("XML is not valid: " . $url);
		}

		return false;
	}



	/**
	 * Method for importing xml offers into database
	 */
	public function import(string $url, bool $force = false): bool
	{

		$this->lastImportedOffers = 0;
		$this->lastDeletedOffers = 0;
		$this->lastImportForced = false;

		if (PHP_SAPI === 'cli') {
			@set_time_limit(0);
		}

		if (empty($url)) {
			$this->sdk->logger->error("No URL for XML file specified");

			return false;
		}

		// One transaction for meta-sync; offer writes use a fresh transaction per chunk (see loop below)
		$this->sdk->db->begin();

		// Sync target groups
		$this->syncTargetGroups();

		// Sync categories
		$this->syncCategories();

		// Sync accessibility dropdown list
		$this->syncAccessibilities();

		// Sync fields of activity
		$this->syncFieldsOfActivity();

		// Add param to url with last import timestamp; a changed api_hash points to another export, so import everything
		if ($force !== true) {
			$lastImport = $this->sdk->meta->last_import ?? null;
			$hashChanged = ($this->sdk->meta->hash_fingerprint ?? null) !== $this->hashFingerprint();
			if (! empty($lastImport) && ! $hashChanged) {
				$url .= '?since=' . $lastImport;
			} else {
				$force = true;
				$this->sdk->logger->info("Enabling FORCE mode because timestamp for last import is empty or api_hash changed");
			}
		} else {
			$this->sdk->logger->info("FORCE mode is enabled");
		}

		$this->lastImportForced = ($force === true);

		// Load external data
		$this->xml = $this->sdk->loadExternalXml($url);

		// Check xml
		if ($this->xml !== false) {

			// Log start
			$this->sdk->logger->info("Starting XML import from " . $url);

			// Init
			$ctrImported = 0;
			$ctrDeleted = 0;
			$offersChecklist = [];

			if (! empty($this->xml)) {

				// Iterate over each offer and add it to the array
				$offerList = [];
				foreach ($this->xml->Offer as $offer) {
					$offerList[] = $offer;
				}

				// Chunk offers
				$offersChunks = array_chunk($offerList, 300);
				$chunkTotal = count($offersChunks);
				$chunkIndex = 0;

				if (! empty($offersChunks)) {

					foreach ($offersChunks as $offersChunk) {

						$chunkIndex++;
						$chunkImportedIds = [];

						// Import every offer
						foreach ($offersChunk as $offer) {

							// Init offer
							$offerId = intval($offer->attributes()->identifier);
							$deletedAt = $offer->attributes()->deletedAt;
							$status = $offer->attributes()->status;

							// Check if offer should be deleted
							if (! empty($deletedAt) || ($status != OfferStatus::ACTIVE)) {
								if ($force == false) {

									// Delete offer from db and continue
									$this->sdk->db->delete('offer', ['offer_id' => $offerId]);
									$this->sdk->logger->info("\tDeleted offer with ID " . $offerId);
									$ctrDeleted++;
								}

								continue;
							}

							// Set offer data
							$fields = [
								'park_id' => (int)$offer->ParkId,
								'park' => (string)$offer->Park,
								'institution' => $this->formatContactAddress($offer->Institution),
								'institution_location' => (string)$offer->Institution->Locality,
								'institution_is_park_partner' => (int)$offer->Institution->ParkPartner,
								'contact' => $this->formatContactAddress($offer->Contact),
								'contact_is_park_partner' => (int)$offer->Contact->ParkPartner,
								'is_hint' => ! empty((int) $offer->IsHint) ? 1 : 0,
								'barrier_free' => (int)$offer->BarrierFree,
								'learning_opportunity' => (int)$offer->LearningOpportunity,
								'child_friendly' => (int)$offer->ChildFriendly,
								'latitude' => (float)$offer->Latitude,
								'longitude' => (float)$offer->Longitude,
								'keywords' => (string)$offer->Keywords,
								'created_at' => Date::normalizeDateTime($offer->attributes()->createdAt),
								'modified_at' => Date::normalizeDateTime($offer->attributes()->modifiedAt),
							];

							// Error handling
							if (! $this->insertOrUpdate('offer', $fields, ['offer_id' => $offerId])) {
								$this->sdk->logger->error("Database error (offer id " . $offerId . "): " . $this->sdk->db->getLastError());
								continue;
							}

							// Categories, most park exports only contain the assigned categories but queries need the full chain up to the root
							$categoryIds = [];
							foreach ($offer->Categories->Category as $category) {
								$categoryId = (int) $category->attributes()->identifier;
								$categoryIds = array_merge($categoryIds, [$categoryId], $this->sdk->model->getCategoryPath($categoryId));
							}
							$categoryIds = array_unique($categoryIds);
							sort($categoryIds);

							$this->sdk->db->delete('category_link', ['offer_id' => $offerId]);
							foreach ($categoryIds as $categoryId) {
								$this->sdk->db->insert('category_link', ['offer_id' => $offerId, 'category_id' => $categoryId]);
							}

							// Offer i18n
							$i18n = [];
							foreach ($this->mappings['langs'] as $lang) {
								$i18n[$lang] = [];
							}

							// Clean i18n data
							$this->sdk->db->delete('offer_i18n', ['offer_id' => $offerId]);

							// Define i18n fields
							$i18nFields = [
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
							$availableLanguages = [];
							foreach ($i18nFields as $xmlNodeName => $dbColumnName) {

								// Node is set in xml
								if (! empty($offer->{$xmlNodeName})) {
									foreach ($offer->{$xmlNodeName} as $node) {
										$lang = (string)$node->attributes()->language;
										$availableLanguages[$lang] = null;
										$i18n[$lang][$dbColumnName] = (string)$node;
									}
								}

								// Node is not set > clean this value from db
								else {
									foreach (array_keys($availableLanguages) as $lang) {
										$i18n[$lang][$dbColumnName] = '';
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
							$i18nFieldsIntern = [
								'Costs' => 'costs',
								'Funding' => 'funding',
								'Partner' => 'partner',
								'Remarks' => 'remarks',
							];
							foreach ($i18nFieldsIntern as $xmlNodeName => $dbColumnName) {

								// Clean data
								$this->sdk->db->update('offer_i18n', [$dbColumnName => null], ['offer_id' => $offerId]);

								// Node is set in xml
								if (! empty($offer->InternalInformations->{$xmlNodeName})) {
									foreach ($offer->InternalInformations->{$xmlNodeName} as $node) {
										$lang = (string)$node->attributes()->language;
										$availableLanguages[$lang] = null;
										$i18n[$lang][$dbColumnName] = (string)$node;
									}
								}
								
							}

							// Insert or update i18n data for offer
							foreach ($i18n as $language => $data) {
								if (! empty($data)) {
									$this->insertOrUpdate('offer_i18n', $data, ['offer_id' => $offerId, 'language' => $language]);
								}
							}

							// Dates
							$this->sdk->db->delete('offer_date', ['offer_id' => $offerId]);
							if (! empty($offer->Dates)) {
								foreach ($offer->Dates->Date as $date) {
									$dateFrom = $date->DateFrom . ($date->TimeFrom ? "T" . $date->TimeFrom : "");
									$dateTo = null;
									if ($date->DateTo) {
										$dateTo = $date->DateTo . ($date->TimeTo ? "T" . $date->TimeTo : "");
									}

									$fields = [
										'offer_id' => $offerId,
										'date_from' => empty($dateFrom) ? null : Date::normalizeDateTime($dateFrom),
										'date_to' => empty($dateTo) ? null : Date::normalizeDateTime($dateTo),
									];

									$this->sdk->db->insert('offer_date', $fields);
								}
							}

							// TargetGroups
							$this->sdk->db->delete('target_group_link', ['offer_id' => $offerId]);
							if ($offer->TargetGroups) {
								foreach ($offer->TargetGroups->TargetGroup as $targetGroup) {
									$targetGroupId = $targetGroup->attributes()->identifier;
									$this->sdk->db->insert('target_group_link', ['offer_id' => $offerId, 'target_group_id' => $targetGroupId]);
								}
							}

							// Fields of activity
							$this->sdk->db->delete('field_of_activity_link', ['offer_id' => $offerId]);
							if (! empty($offer->FieldsOfActivity->FieldOfActivity)) {
								foreach ($offer->FieldsOfActivity->FieldOfActivity as $fieldOfActivity) {
									$fieldOfActivityId = $fieldOfActivity->attributes()->identifier;
									$this->sdk->db->insert('field_of_activity_link', ['offer_id' => $offerId, 'field_of_activity_id' => $fieldOfActivityId]);
								}
							}

							// Images
							$this->sdk->db->delete('image', ['offer_id' => $offerId]);
							if ($offer->Images) {
								foreach ($offer->Images->Image as $image) {
									$fields = [];

									$fields['offer_id'] = $offerId;
									$fields['small'] = (string)$image->Small;
									$fields['medium'] = (string)$image->Medium;
									$fields['large'] = (string)$image->Large;
									$fields['original'] = (string)$image->Original;
									$fields['copyright'] = (string)$image->Copyright;

									$this->sdk->db->insert('image', $fields);
								}
							}

							// Documents
							$this->sdk->db->delete('document', ['offer_id' => $offerId]);
							if ($offer->Documents) {
								foreach ($offer->Documents->Document as $document) {
									$fields = [];

									$fields['offer_id'] = $offerId;
									$fields['language'] = (string)$document->attributes()->language;
									$fields['title'] = (string)$document->Title;
									$fields['url'] = (string)$document->URL;

									$this->sdk->db->insert('document', $fields);
								}
							}

							// Documents intern
							$this->sdk->db->delete('document_intern', ['offer_id' => $offerId]);
							if (! empty($offer->InternalInformations->DocumentsIntern->Document)) {
								foreach ($offer->InternalInformations->DocumentsIntern->Document as $document) {
									$fields = [];

									$fields['offer_id'] = $offerId;
									$fields['language'] = (string)$document->attributes()->language;
									$fields['title'] = (string)$document->Title;
									$fields['url'] = (string)$document->URL;

									$this->sdk->db->insert('document_intern', $fields);
								}
							}

							// Hyperlinks
							$this->sdk->db->delete('hyperlink', ['offer_id' => $offerId]);
							if ($offer->Hyperlinks) {
								foreach ($offer->Hyperlinks->Hyperlink as $hyperlink) {
									$fields = [];

									$fields['offer_id'] = $offerId;
									$fields['language'] = (string)$hyperlink->attributes()->language;
									$fields['title'] = (string)$hyperlink->Title;
									$fields['url'] = (string)$hyperlink->URL;

									$this->sdk->db->insert('hyperlink', $fields);
								}
							}

							// Hyperlinks intern
							$this->sdk->db->delete('hyperlink_intern', ['offer_id' => $offerId]);
							if ($offer->InternalInformations->HyperlinksIntern) {
								foreach ($offer->InternalInformations->HyperlinksIntern->Hyperlink as $hyperlink) {
									$fields = [];

									$fields['offer_id'] = $offerId;
									$fields['language'] = (string)$hyperlink->attributes()->language;
									$fields['title'] = (string)$hyperlink->Title;
									$fields['url'] = (string)$hyperlink->URL;

									$this->sdk->db->insert('hyperlink_intern', $fields);
								}
							}

							// Accessibilities
							$this->sdk->db->delete('accessibility', ['offer_id' => $offerId]);
							if ($offer->Accessibilities) {

								// Import main accessiblity data
								$accessibilityId = (string)$offer->Accessibilities->attributes()->identifier;
								$gintoId = (string)$offer->Accessibilities->attributes()->gintoId;
								$gintoIcon = (string)$offer->Accessibilities->attributes()->gintoIcon;
								$gintoLink = (string)$offer->Accessibilities->attributes()->gintoLink;
								$this->sdk->db->insert('accessibility', [
									'accessibility_id' => $accessibilityId,
									'offer_id' => $offerId,
									'ginto_id' => $gintoId,
									'ginto_icon' => $gintoIcon,
									'ginto_link' => $gintoLink,
								]);

								// Import ratings
								$this->sdk->db->delete('accessibility_rating', ['accessibility_id' => $accessibilityId]);
								if ($offer->Accessibilities->Accessibility) {
									foreach ($offer->Accessibilities->Accessibility as $accessibility) {

										// Get rating ID
										$ratingId = (string)$accessibility->attributes()->identifier;

										// Insert or update rating
										$rating = [
											'accessibility_rating_id' => $ratingId,
											'accessibility_id' => $accessibilityId,
										];
										foreach ($accessibility->Name as $name) {
											$language = (string)$name->attributes()->language;
											$rating['description_' . $language] = (string)$name;
										}
										$rating['icon_url'] = (string)$accessibility->RatingIcon;
										$this->insertOrUpdate('accessibility_rating', $rating, ['accessibility_rating_id' => $ratingId]);
									}
								}
							}

							// Municipalities
							$this->sdk->db->delete('offer_municipality_link', ['offer_id' => $offerId]);
							if (! empty($offer->Municipalities)) {
								foreach ($offer->Municipalities->Municipality as $municipality) {

									// Get municipality  data
									$municipalityId = intval($municipality->attributes()->identifier);
									$municipalityName = (string)$municipality;
									
									// Build municipality
									$municipalityData = [
										'municipality_id' => $municipalityId,
										'park_id' => (int)$offer->ParkId,
										'municipality' => $municipalityName,
									];
									$this->insertOrUpdate('municipality', $municipalityData, ['municipality_id' => $municipalityId, 'park_id' => $municipalityData['park_id']]);

									// Create municipality link
									$this->sdk->db->insert('offer_municipality_link', ['offer_id' => $offerId, 'municipality_id' => $municipalityId]);
								}
							}

							// Get root category id
							$rootCategoryId = $this->sdk->model->getRootCategory(intval($offer->Categories->Category[0]->attributes()->identifier));

							// Subscription
							if (($rootCategoryId == Category::EVENT) || ($rootCategoryId == Category::BOOKING)) {
								$fields = [];

								$fields['subscription_mandatory'] = (int)$offer->Subscription->SubscriptionMandatory;
								$fields['online_subscription_enabled'] = (int)$offer->Subscription->OnlineSubscriptionEnabled;
								$fields['subscription_contact'] = $this->formatContactAddress($offer->Subscription->SubscriptionContact);
								$fields['subscription_link'] = (string)$offer->Subscription->SubscriptionLink;
								$this->insertOrUpdate('subscription', $fields, ['offer_id' => $offerId]);

								// Subscription i18n
								if (isset($offer->Subscription->Details)) {
									foreach ($offer->Subscription->Details as $detail) {
										$subscriptionDetail = [];
										$subscriptionDetail['offer_id'] = $offerId;
										$subscriptionDetail['language'] = (string)$detail->attributes()->language;
										$subscriptionDetailWhere = $subscriptionDetail;
										$subscriptionDetail['subscription_details'] = (string)$detail;
										$this->insertOrUpdate('subscription_i18n', $subscriptionDetail, $subscriptionDetailWhere);
									}
								} else {
									$this->sdk->db->delete('subscription_i18n', ['offer_id' => $offerId]);
								}
							}

							$fields = [];

							// Events
							if ($rootCategoryId == Category::EVENT) {
								$fields['is_park_event'] = (int)$offer->ParkEvent;
								$fields['is_park_partner_event'] = (int)$offer->ParkPartnerEvent;
								$fields['public_transport_stop'] = (string)$offer->PublicTransportStop;
								$fields['kind_of_event'] = (string)$offer->Kind;

								$this->insertOrUpdate('event', $fields, ['offer_id' => $offerId]);
							}

							// Products
							else if ($rootCategoryId == Category::PRODUCT) {
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

								$this->insertOrUpdate('product', $fields, ['offer_id' => $offerId]);

								// Suppliers
								$this->sdk->db->delete('supplier', ['offer_id' => $offerId]);
								if ($offer->Suppliers) {
									foreach ($offer->Suppliers->Supplier as $supplier) {
										$this->sdk->db->insert('supplier', ['offer_id' => $offerId, 'contact' => $this->formatContactAddress($supplier), 'is_park_partner' => (int)$supplier->ParkPartner]);
									}
								}

								// Online shop: i18n
								if ($offer->OnlineShop) {

									// PaymentTerms
									if (isset($offer->OnlineShop->PaymentTerms)) {
										foreach ($offer->OnlineShop->PaymentTerms as $paymentTerm) {
											$offerI18n = [];
											$offerI18n['offer_id'] = $offerId;
											$offerI18n['language'] = (string)$paymentTerm->attributes()->language;
											$offerI18nWhere = $offerI18n;
											$offerI18n['online_shop_payment_terms'] = (string)$paymentTerm;
											$this->insertOrUpdate('offer_i18n', $offerI18n, $offerI18nWhere);
										}
									}

									// DeliveryConditions
									if (isset($offer->OnlineShop->DeliveryConditions)) {
										foreach ($offer->OnlineShop->DeliveryConditions as $deliveryCondition) {
											$offerI18n = [];
											$offerI18n['offer_id'] = $offerId;
											$offerI18n['language'] = (string)$deliveryCondition->attributes()->language;
											$offerI18nWhere = $offerI18n;
											$offerI18n['online_shop_delivery_conditions'] = (string)$deliveryCondition;
											$this->insertOrUpdate('offer_i18n', $offerI18n, $offerI18nWhere);
										}
									}

									// Online shop: Article
									$this->sdk->db->delete('product_article', ['offer_id' => $offerId]);
									if ($offer->OnlineShop->Articles->Article) {
										foreach ($offer->OnlineShop->Articles->Article as $article) {

											// Set attributes
											$articleId = $article->attributes()->identifier;
											$isFood = ($article->attributes()->is_food == 'true') ? 1 : 0;

											// Insert article
											$this->sdk->db->insert('product_article', [
												'product_article_id' => $articleId,
												'offer_id' => $offerId,
												'supplier_contact' => $this->formatContactAddress($article->Supplier),
												'is_food' => (int)$isFood,
											]);

											// Article i18n: Title
											if (isset($article->ArticleTitle)) {
												foreach ($article->ArticleTitle as $title) {
													$articleI18n = [];
													$articleI18n['product_article_id'] = $articleId;
													$articleI18n['language'] = (string)$title->attributes()->language;
													$articleI18nWhere = $articleI18n;
													$articleI18n['article_title'] = (string)$title;
													$this->insertOrUpdate('product_article_i18n', $articleI18n, $articleI18nWhere);
												}
											}

											// Article i18n: Description
											if (isset($article->ArticleDescription)) {
												foreach ($article->ArticleDescription as $description) {
													$articleI18n = [];
													$articleI18n['product_article_id'] = $articleId;
													$articleI18n['language'] = (string)$description->attributes()->language;
													$articleI18nWhere = $articleI18n;
													$articleI18n['article_description'] = (string)$description;
													$this->insertOrUpdate('product_article_i18n', $articleI18n, $articleI18nWhere);
												}
											}

											// Article i18n: Ingredients
											if (isset($article->ArticleIngredients)) {
												foreach ($article->ArticleIngredients as $ingredients) {
													$articleI18n = [];
													$articleI18n['product_article_id'] = $articleId;
													$articleI18n['language'] = (string)$ingredients->attributes()->language;
													$articleI18nWhere = $articleI18n;
													$articleI18n['article_ingredients'] = (string)$ingredients;
													$this->insertOrUpdate('product_article_i18n', $articleI18n, $articleI18nWhere);
												}
											}

											// Article i18n: Allergens
											if (isset($article->ArticleAllergens)) {
												foreach ($article->ArticleAllergens as $allergens) {
													$articleI18n = [];
													$articleI18n['product_article_id'] = $articleId;
													$articleI18n['language'] = (string)$allergens->attributes()->language;
													$articleI18nWhere = $articleI18n;
													$articleI18n['article_allergens'] = (string)$allergens;
													$this->insertOrUpdate('product_article_i18n', $articleI18n, $articleI18nWhere);
												}
											}

											// Article i18n: NutritionalValues
											if (isset($article->ArticleNutritionalValues)) {
												foreach ($article->ArticleNutritionalValues as $nutritionalValues) {
													$articleI18n = [];
													$articleI18n['product_article_id'] = $articleId;
													$articleI18n['language'] = (string)$nutritionalValues->attributes()->language;
													$articleI18nWhere = $articleI18n;
													$articleI18n['article_nutritional_values'] = (string)$nutritionalValues;
													$this->insertOrUpdate('product_article_i18n', $articleI18n, $articleI18nWhere);
												}
											}

											// Article i18n: IdentityLabel
											if (isset($article->ArticleIdentityLabel)) {
												foreach ($article->ArticleIdentityLabel as $identityLabel) {
													$articleI18n = [];
													$articleI18n['product_article_id'] = $articleId;
													$articleI18n['language'] = (string)$identityLabel->attributes()->language;
													$articleI18nWhere = $articleI18n;
													$articleI18n['article_identity_label'] = (string)$identityLabel;
													$this->insertOrUpdate('product_article_i18n', $articleI18n, $articleI18nWhere);
												}
											}

											// Article i18n: QuantityIndication
											if (isset($article->ArticleQuantityIndication)) {
												foreach ($article->ArticleQuantityIndication as $quantityIndication) {
													$articleI18n = [];
													$articleI18n['product_article_id'] = $articleId;
													$articleI18n['language'] = (string)$quantityIndication->attributes()->language;
													$articleI18nWhere = $articleI18n;
													$articleI18n['article_quantity_indication'] = (string)$quantityIndication;
													$this->insertOrUpdate('product_article_i18n', $articleI18n, $articleI18nWhere);
												}
											}

											// Import article labels
											$this->sdk->db->delete('product_article_label', ['product_article_id' => $articleId]);
											if (isset($article->ArticleLabels->ArticleLabel)) {

												// Iterate all article labels
												foreach ($article->ArticleLabels->ArticleLabel as $label) {

													// Set label id
													$labelId = $label->attributes()->identifier;

													// Iterate all labels
													foreach ($label->Label as $singleLabel) {

														// Insert article label
														$this->sdk->db->insert('product_article_label', [
															'product_article_id' => $articleId,
															'label_id' => $labelId,
															'language' => $singleLabel->attributes()->language,
															'label_title' => (string)$singleLabel,
															'label_url' => $singleLabel->attributes()->url,
															'label_icon' => $singleLabel->attributes()->icon,
														]);
													}
												}
											}
										}
									}
								}
							}

							// Bookings
							else if ($rootCategoryId == Category::BOOKING) {
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

								$this->insertOrUpdate('booking', $fields, ['offer_id' => $offerId]);

								// Accommodations
								$this->sdk->db->delete('accommodation', ['offer_id' => $offerId]);
								if ($offer->Accommodations) {
									foreach ($offer->Accommodations->Accommodation as $accommodation) {
										$this->sdk->db->insert('accommodation', ['offer_id' => $offerId, 'contact' => $this->formatContactAddress($accommodation), 'is_park_partner' => (int)$accommodation->ParkPartner]);
									}
								}
							}

							// Activities
							else if ($rootCategoryId == Category::ACTIVITY) {
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

								$this->insertOrUpdate('activity', $fields, ['offer_id' => $offerId]);
								
							}

							// Projects and research
							else if (in_array($rootCategoryId, [Category::PROJECT, Category::RESEARCH])) {
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

								$this->insertOrUpdate('project', $fields, ['offer_id' => $offerId]);
							}

							// Check off this offer
							$offersChecklist[$offerId] = true;
							$chunkImportedIds[] = $offerId;
							$ctrImported++;

						}

						$this->sdk->logger->info(
							"\tChunk " . $chunkIndex . "/" . $chunkTotal . ": imported " . count($chunkImportedIds)
							. " offers (ids: " . implode(',', $chunkImportedIds) . ")"
						);

						// Persist this chunk: Keeps finished work if the import aborts mid-run.
						$this->sdk->db->commit();
						$this->sdk->db->begin();
					}
				}
			}

			$this->sdk->logger->info("Successfully imported " . $ctrImported . " offers.");

			// Delete offers which did not get updated
			if ($force === true) {
				$this->sdk->logger->info("Deleting offers which were not updated...");

				// Iterate all existing offers
				$allOffers = $this->sdk->db->get('offer', null, null, ['offer_id']);
				while ($offer = $allOffers->fetchAssoc()) {
					if (! isset($offersChecklist[$offer['offer_id']])) {
						$this->sdk->db->delete('offer', ['offer_id' => $offer['offer_id']]);
						$this->sdk->logger->info("\tDeleted offer with ID " . $offer['offer_id']);
						$ctrDeleted++;
					}
				}

				if ($ctrDeleted == 0) {
					$this->sdk->logger->info("\tNothing to delete");
				}
			} else {
				$this->sdk->logger->info("Deleted " . $ctrDeleted . " offers.");
			}

			$this->sdk->db->update('meta', ['last_import' => time(), 'hash_fingerprint' => $this->hashFingerprint()]);

			// Rebuild full-text search index after offer mutations
			if ($this->sdk->db->rebuildOfferFts()) {
				$this->sdk->logger->info('Rebuilt offer full-text search index.');
			}

			$this->lastImportedOffers = $ctrImported;
			$this->lastDeletedOffers = $ctrDeleted;

			// Log message
			$message = 'Import finished successfully.';
			$this->sdk->logger->info($message);

			$this->sdk->db->commit();

			return true;
		}

		else {
			$this->sdk->logger->info("XML is not valid: " . $url);
		}

		$this->sdk->db->commit();

		return false;
	}



	/**
	 * Clean up offers (remove inactive offers with active id)
	 */
	public function cleanUpOffers(string $url): bool
	{

		$this->lastCleanupDeletedOffers = 0;

		// Load xml
		$this->xml = $this->sdk->loadExternalXml($url);

		// Init
		$activeOffers = [];
		$ctrDeleted = 0;

		// Check xml
		if ($this->xml !== false) {

			// Get active offers
			if (! empty($this->xml)) {
				foreach ($this->xml->Offer as $activeOffer) {
					$activeOffers[] = intval($activeOffer->attributes()->identifier);
				}
			}

			// Skip cleanup on an empty list, otherwise every offer would be deleted
			if (empty($activeOffers)) {
				$this->sdk->logger->error("Active offers list is empty, clean up skipped");

				return false;
			}

			// Remove inactive offers
			$this->sdk->logger->info("Clean up offers");
			$this->sdk->db->begin();
			$allOffers = $this->sdk->db->get('offer', null, null, ['offer_id']);

			// Iterate all existing offers
			while ($offer = $allOffers->fetchAssoc()) {

				// Delete inactive offer
				if (! in_array($offer['offer_id'], $activeOffers)) {
					$this->sdk->db->delete('offer', ['offer_id' => $offer['offer_id']]);
					$this->sdk->logger->info("\tDeleted inactive offer with ID " . $offer['offer_id']);
					$ctrDeleted++;
				}
			}

			$this->sdk->db->commit();

			if ($ctrDeleted > 0) {
				$this->sdk->db->rebuildOfferFts();
			}

			$this->lastCleanupDeletedOffers = $ctrDeleted;

			// Log result
			if ($ctrDeleted == 0) {
				$this->sdk->logger->info("\tNo inactive offers");
			} else {
				$this->sdk->logger->info("Deleted " . $ctrDeleted . " inactive offer(s).");
			}

			return true;
		}

		else {
			$this->sdk->logger->info("XML is not valid: " . $url);
		}

		return false;
	}



	/**
	 * Synchronise target groups
	 */
	public function syncTargetGroups(): void
	{

		// Get and parse json
		$json = file_get_contents($this->sdk->config['json_export_target_groups']);
		$targetGroups = json_decode($json, true);

		// Sync target groups
		if (! empty($targetGroups) && is_array($targetGroups)) {
			$this->sdk->model->saveTargetGroups($targetGroups);
		}
	}


	
	/**
	 * Synchronise fields of activity
	 */
	public function syncFieldsOfActivity(): void
	{

		// Get and parse json
		$json = file_get_contents($this->sdk->config['json_export_fields_of_activity']);
		$fieldsOfActivity = json_decode($json, true);

		// Sync fields of activity
		if (! empty($fieldsOfActivity) && is_array($fieldsOfActivity)) {
			$this->sdk->model->saveFieldsOfActivity($fieldsOfActivity);
		}
		
	}


	
	/**
	 * Synchronise categories
	 */
	public function syncCategories(): void
	{

		// Get and parse json
		$json = file_get_contents($this->sdk->config['json_export_categories']);
		$categories = json_decode($json, true);

		// Sync categories
		if (! empty($categories) && is_array($categories)) {
			$this->sdk->model->saveCategories($categories);
		}
		
	}



	/**
	 * Synchronise accessibility dropdown list
	 */
	public function syncAccessibilities(): void
	{

		// Get and parse json
		$json = file_get_contents($this->sdk->config['json_export_accessibilities']);
		$options = json_decode($json, true);

		// Sync dropdown options
		if (! empty($options) && is_array($options)) {
			$this->sdk->model->saveAccessibilities($options);
		}
	}



	/**
	 * Private method for inserting or updating db record
	 */
	private function insertOrUpdate(string $table, array $fields, array $where): bool
	{
		if (! empty($table) && ! empty($fields) && ! empty($where)) {

			// Check if offer exists in database
			$exists = $this->sdk->db->get($table, $where);

			// Update db record
			if ($exists->numRows > 0) {
				return $this->sdk->db->update($table, $fields, $where);
			}

			// Insert new db record
			else {
				$fields = array_merge($fields, $where);
				return $this->sdk->db->insert($table, $fields);
			}
			
		}

		return false;
	}



	/**
	 * Format contact address string from XML contact node
	 */
	private function formatContactAddress(object $contact): string
	{

		// Multi-line contact block for autoTextFormat (company, name, address, phones, email, URL)
		$address = [];

		if (! empty($contact->Company)) {
			$address[] = $contact->Company;
		}

		if (! empty($contact->FirstName) || ! empty($contact->LastName)) {

			// Named key keeps the person line in place when company is missing
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

		// Skip scheme-only placeholders from the XML export
		if (! empty($contact->URL) && ($contact->URL != 'https://') && ($contact->URL != 'http://')) {
			$address[] = $contact->URL;
		}

		return implode("\n", $address);
	}



	/**
	 * Get last ID
	 */
	private function getLastId(string $nameId, string $table): int
	{
		$query =  $this->sdk->db->query('SELECT MAX(' . $nameId . ') as id FROM ' . $table);
		$result = $query->fetchObject();

		return $result->id;
	}



	/**
	 * Get fingerprint of the active API hash, so the key itself is not stored in the database
	 */
	private function hashFingerprint(): string
	{

		return hash('sha256', $this->sdk->hash);
	}


}
