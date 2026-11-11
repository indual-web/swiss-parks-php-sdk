<style>
	.project_title { display: none; }
</style>
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

		[NOTISSET(OFFER_PRODUCT_DETAIL)@start]
			<li data="tab_4" role="tab" aria-controls="tab_4">__LANG[offer_map]__</li>
		[NOTISSET(OFFER_PRODUCT_DETAIL)@stop]

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
		<div class="categories">__OFFER_CATEGORIES__</div>
		__OFFER_IMAGES__
		__OFFER_DESCRIPTION__
		<div class="cf"></div>
	</div>

	<div class="tab_content tab_content_2">
		<div class="detail_text text_left">

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
				__OFFER_SUBSCRIPTION__
			</div>
		[OFFER_EVENT@stop]

		[OFFER_BOOKING@start]
			<div class="tab_content tab_content_3">
				__OFFER_SUBSCRIPTION__
			</div>
		[OFFER_BOOKING@stop]
	[ISSET(OFFER_SUBSCRIPTION)@stop]

	<div class="tab_content tab_content_4">
		__OFFER_MAP__
	</div>

	[ISSET(OFFER_POI_LIST)@start]
		<div class="tab_content tab_content_5">
			__OFFER_POI_LIST__
		</div>
	[ISSET(OFFER_POI_LIST)@stop]

	[ISSET(OFFER_ROUTE_LIST)@start]
		<div class="tab_content tab_content_6">
			__OFFER_ROUTE_LIST__
		</div>
	[ISSET(OFFER_ROUTE_LIST)@stop]
</div>