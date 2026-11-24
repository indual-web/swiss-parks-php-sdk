<!--
Swiss Parks PHP SDK – detail.tpl
Placeholders, conditions and language variables:
https://github.com/indual-web/swiss-parks-php-sdk/blob/main/docs/sdk-reference/template-placeholders.md
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