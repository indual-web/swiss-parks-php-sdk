<!--
Available Tags:
OFFER_TITLE
OFFER_SHORT_INFO
OFFER_PRINT_LINK
OFFER_BACK_LINK
OFFER_CATEGORIES
OFFER_IMAGES
OFFER_ABSTRACT
OFFER_DESCRIPTION
OFFER_ADDITIONAL_INFO
OFFER_DATES
OFFER_DOCUMENTS
OFFER_EVENT_DETAIL -> contains:		OFFER_EVENT_LOCATION
									OFFER_EVENT_LOCATION_SHORT
									OFFER_EVENT_LOCATION_DETAILS
									OFFER_EVENT_TRANSPORT
									OFFER_EVENT_DATE_DETAILS
									OFFER_EVENT_PRICE
OFFER_PRODUCT_DETAIL -> contains: 	OFFER_PRODUCT_OPENING_HOURS
									OFFER_PRODUCT_PUBLIC_TRANSPORT
									OFFER_PRODUCT_PRICE
									OFFER_PRODUCT_INFRASTRUCTURE
									OFFER_ONLINE_SHOP_CHECKOUT_BUTTON
OFFER_BOOKING_DETAIL -> contains: 	OFFER_BOOKING_GROUPS
									OFFER_BOOKING_TRANSPORT
									OFFER_BOOKING_BENEFITS
									OFFER_BOOKING_REQUIREMENTS
									OFFER_BOOKING_PRICE
									OFFER_BOOKING_ACCOMMODATIONS
OFFER_ACTIVITY_DETAIL -> contains: 	OFFER_ACTIVITY_ROUTE
									OFFER_ACTIVITY_ARRIVAL
									OFFER_ACTIVITY_PRICE
									OFFER_ACTIVITY_CATERING
									OFFER_ACTIVITY_MATERIAL_RENT
									OFFER_ACTIVITY_SAFETY_INSTRUCTIONS
									OFFER_ACTIVITY_SIGNALIZATION
									OFFER_ACTIVITY_DATES
									OFFER_ACTIVITY_INFRASTRUCTURE
OFFER_PROJECT_DETAIL -> contains: 	OFFER_PROJECT_DURATION
									OFFER_PROJECT_STATUS

OFFER_LINKS
OFFER_ACCESSIBILITIES
OFFER_TARGET_GROUPS
OFFER_SUPPLIERS
OFFER_INSTITUTION
OFFER_CONTACT
OFFER_SUBSCRIPTION
OFFER_POI_LIST
OFFER_ROUTE_LIST
OFFER_MAP
OFFER_KEYWORDS

Language variables can be printed: __LANG[name]__

Conditions available: OFFER_EVENT, OFFER_PRODUCT, OFFER_BOOKING, OFFER_ACTIVITY, OFFER_PROJECT
Check condition: [OFFER_EVENT@start] This text is rendered based on the condition [OFFER_PRODUCT@stop]

Check if placeholder is set [ISSET(OFFER_PRODUCT)@start] [ISSET(OFFER_PRODUCT)@stop]
Check if placeholder is not set [NOTISSET(OFFER_PRODUCT_DETAIL)@start] [NOTISSET(OFFER_PRODUCT_DETAIL)@stop]
-->
<div class="detail">
	<ul class="tab_list" role="tablist">
		<li data="tab_1" class="current" role="tab" aria-controls="tab_1">__LANG[offer_description]__</li>
		<li data="tab_2" role="tab" aria-controls="tab_2">__LANG[offer_detail]__</li>

		[ISSET(OFFER_SUBSCRIPTION)@start]
			[OFFER_EVENT@start]
				<li data="tab_3" role="tab" aria-controls="tab_3">__LANG[offer_subscription]__</li>
			[OFFER_EVENT@stop]

			[OFFER_BOOKING@start]
				<li data="tab_3" role="tab" aria-controls="tab_3">__LANG[offer_subscription]__</li>
			[OFFER_BOOKING@stop]
		[ISSET(OFFER_SUBSCRIPTION)@stop]

		<li data="tab_4" role="tab" aria-controls="tab_4">__LANG[offer_map]__</li>

		[ISSET(OFFER_POI_LIST)@start]
			<li data="tab_5" role="tab" aria-controls="tab_5">

				[OFFER_ACTIVITY@start]
					__LANG[offer_poi]__
				[OFFER_ACTIVITY@stop]

				[OFFER_PROJECT@start]
					__LANG[offer_project_links]__
				[OFFER_PROJECT@stop]

			</li>
		[ISSET(OFFER_POI_LIST)@stop]

		[ISSET(OFFER_ROUTE_LIST)@start]
			<li data="tab_6" role="tab" aria-controls="tab_6">
				__LANG[offer_route_links]__
			</li>
		[ISSET(OFFER_ROUTE_LIST)@stop]
	</ul>
	<div class="links_wrap">
		__OFFER_PRINT_LINK__
		__OFFER_BACK_LINK__
		<div class="cf"></div>
	</div>
	<div class="cf"></div>
</div>
<div class="detail">

	<div class="tab_content tab_content_1">
		<h1>__OFFER_TITLE__</h1>
		<div class="categories">__OFFER_CATEGORIES__</div>
		__OFFER_DESCRIPTION__
		__OFFER_IMAGES__
		<div class="cf"></div>
	</div>

	<div class="tab_content tab_content_2">
		<div class="detail_text text_left">
			<h1>__OFFER_TITLE__</h1>

			[OFFER_EVENT@start]
				__OFFER_EVENT_DETAIL__
			[OFFER_EVENT@stop]

			[OFFER_PRODUCT@start]
				__OFFER_PRODUCT_DETAIL__
			[OFFER_PRODUCT@stop]

			[OFFER_BOOKING@start]
				__OFFER_BOOKING_DETAIL__
			[OFFER_BOOKING@stop]

			[OFFER_ACTIVITY@start]
				__OFFER_ACTIVITY_DETAIL__
			[OFFER_ACTIVITY@stop]

			[OFFER_PROJECT@start]
				__OFFER_PROJECT_DETAIL__
			[OFFER_PROJECT@stop]

			[ISSET(OFFER_INTERNAL_INFOS)@start]
				__OFFER_INTERNAL_INFOS__
			[ISSET(OFFER_INTERNAL_INFOS)@stop]

			__OFFER_DOCUMENTS__
			__OFFER_LINKS__
			__OFFER_ACCESSIBILITIES__
			__OFFER_TARGET_GROUPS__

			__OFFER_SUPPLIERS__
			__OFFER_INSTITUTION__
			__OFFER_CONTACT__

			__OFFER_ONLINE_SHOP_CHECKOUT_BUTTON__
		</div>
	</div>

	[ISSET(OFFER_SUBSCRIPTION)@start]
		[OFFER_EVENT@start]
			<div class="tab_content tab_content_3">
				<h1>__OFFER_TITLE__</h1>
				__OFFER_SUBSCRIPTION__
			</div>
		[OFFER_EVENT@stop]

		[OFFER_BOOKING@start]
			<div class="tab_content tab_content_3">
				<h1>__OFFER_TITLE__</h1>
				__OFFER_SUBSCRIPTION__
			</div>
		[OFFER_BOOKING@stop]
	[ISSET(OFFER_SUBSCRIPTION)@stop]

	<div class="tab_content tab_content_4">
		<h1>__OFFER_TITLE__</h1>
		__OFFER_MAP__
	</div>

	[ISSET(OFFER_POI_LIST)@start]
		<div class="tab_content tab_content_5">
			<h1>__OFFER_TITLE__</h1>
			__OFFER_POI_LIST__
		</div>
	[ISSET(OFFER_POI_LIST)@stop]

	[ISSET(OFFER_ROUTE_LIST)@start]
		<div class="tab_content tab_content_6">
			<h1>__OFFER_TITLE__</h1>
			__OFFER_ROUTE_LIST__
		</div>
	[ISSET(OFFER_ROUTE_LIST)@stop]
</div>