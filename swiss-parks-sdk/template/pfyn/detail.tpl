<!--
Available Tags:
OFFER_TITLE
OFFER_SHORT_INFO
OFFER_PRINT_LINK
OFFER_BACK_LINK
OFFER_CATEGORIES
OFFER_IMAGES
OFFER_DESCRIPTION
OFFER_ADDITIONAL_INFO
OFFER_DATES
OFFER_DOCUMENTS
OFFER_EVENT_DETAIL -> contains:		OFFER_EVENT_LOCATION
									OFFER_EVENT_LOCATION_DETAILS
									OFFER_EVENT_TRANSPORT
									OFFER_EVENT_DATE_DETAILS
									OFFER_EVENT_PRICE
OFFER_PRODUCT_DETAIL -> contains: 	OFFER_PRODUCT_OPENING_HOURS
									OFFER_PRODUCT_PUBLIC_TRANSPORT
									OFFER_PRODUCT_PRICE
									OFFER_PRODUCT_INFRASTRUCTURE
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

Language variables can be printed: __LANG[name]__

Conditions available: OFFER_EVENT, OFFER_PRODUCT, OFFER_BOOKING, OFFER_ACTIVITY, OFFER_PROJECT
Check condition: [OFFER_EVENT@start] This text is rendered based on the condition [OFFER_PRODUCT@stop]

Check if placeholder is set [ISSET(OFFER_PRODUCT)@start] [ISSET(OFFER_PRODUCT)@stop]
-->
<div class="detail links">
	<div class="links_wrap">
		__OFFER_PRINT_LINK__
		__OFFER_BACK_LINK__
		<div class="cf"></div>
	</div>
	<div class="cf"></div>
</div>
<div class="detail">
	<div class="detail_content first">
		<div class="categories">__OFFER_CATEGORIES__</div>
		<h1>__OFFER_TITLE__</h1>
		__OFFER_IMAGES__
		<h2>__OFFER_ABSTRACT__</h2>
		__OFFER_DESCRIPTION__
		<div class="cf"></div>
	</div>

	<div class="detail_content">
		<div class="detail_text text_left">

			<h2>__OFFER_ABSTRACT__</h2>

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

			__OFFER_DOCUMENTS__
			__OFFER_LINKS__
			__OFFER_ACCESSIBILITIES__
			__OFFER_TARGET_GROUPS__

			__OFFER_SUPPLIERS__
			__OFFER_INSTITUTION__
			__OFFER_CONTACT__
		</div>
	</div>

	[ISSET(OFFER_SUBSCRIPTION)@start]
		[OFFER_EVENT@start]
			<div class="detail_content">
				<h1>__LANG[offer_subscription]__</h1>
				__OFFER_SUBSCRIPTION__
			</div>
		[OFFER_EVENT@stop]

		[OFFER_BOOKING@start]
			<div class="detail_content">
				<h1>__LANG[offer_subscription]__</h1>
				__OFFER_SUBSCRIPTION__
			</div>
		[OFFER_BOOKING@stop]
	[ISSET(OFFER_SUBSCRIPTION)@stop]

	<div class="detail_content">
		__OFFER_MAP__
	</div>

	[ISSET(OFFER_POI_LIST)@start]
		<div class="detail_content">
			<h1>__LANG[offer_poi]__</h1>
			__OFFER_POI_LIST__
		</div>
	[ISSET(OFFER_POI_LIST)@stop]

	[ISSET(OFFER_ROUTE_LIST)@start]
		<div class="detail_content">
			<h1>__LANG[offer_route_links]__</h1>
			__OFFER_ROUTE_LIST__
		</div>
	[ISSET(OFFER_ROUTE_LIST)@stop]
</div>