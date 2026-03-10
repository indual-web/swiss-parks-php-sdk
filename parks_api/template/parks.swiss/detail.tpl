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
-->

<div class="links_wrap">
	__OFFER_PRINT_LINK__
	__OFFER_BACK_LINK__
	<div class="cf"></div>
</div>
<div class="detail">
	<div class="heading">
		<h1>__OFFER_TITLE__</h1>
		<div class="categories">__OFFER_CATEGORIES__</div>
		<div class="introduction">__OFFER_SHORT_INFO__</div>
	</div>
	<div class="cf"></div>
	<div class="mix_container">
		<div class="content_inner flex_wrap">
			<div class="content_right">
				__OFFER_DESCRIPTION__
				<div class="parks_detail_accordeon_wrap">
					<div class="parks_detail_accordeon">
						[OFFER_EVENT@start]
							[ISSET(OFFER_EVENT_DETAIL)@start]
								<div class="accordeon_entry">
									<div class="accordeon_title"><h2 class="accordeon_title_selector" aria-expanded="false" tabindex="0">__LANG[offer_detail]__</h2></div>
									<div class="accordeon_content" aria-role="region">
										<div class="detail_block_wrap">
											__OFFER_ADDITIONAL_INFO__
											__OFFER_EVENT_LOCATION_DETAILS__
											__OFFER_EVENT_TRANSPORT__
											__OFFER_EVENT_DATE_DETAILS__
											__OFFER_EVENT_PRICE__
										</div>
									</div>
								</div>
							[ISSET(OFFER_EVENT_DETAIL)@stop]
						[OFFER_EVENT@stop]
						
						[OFFER_PRODUCT@start]
							[ISSET(OFFER_PRODUCT_DETAIL)@start]
								<div class="accordeon_entry">
									<div class="accordeon_title"><h2 class="accordeon_title_selector" aria-expanded="false" tabindex="0">__LANG[offer_detail]__</h2></div>
									<div class="accordeon_content" aria-role="region">
										__OFFER_ADDITIONAL_INFO__
										__OFFER_PRODUCT_OPENING_HOURS__
										__OFFER_PRODUCT_PUBLIC_TRANSPORT__
										__OFFER_PRODUCT_INFRASTRUCTURE__
										__OFFER_ONLINE_SHOP_CHECKOUT_BUTTON__
									</div>
								</div>								
							[ISSET(OFFER_PRODUCT_DETAIL)@stop]
						[OFFER_PRODUCT@stop]
						
						[OFFER_BOOKING@start]
							[ISSET(OFFER_BOOKING_DETAIL)@start]
								<div class="accordeon_entry">
									<div class="accordeon_title"><h2 class="accordeon_title_selector" aria-expanded="false" tabindex="0">__LANG[offer_detail]__</h2></div>
									<div class="accordeon_content" aria-role="region">
										__OFFER_BOOKING_DETAIL__	
									</div>
								</div>
							[ISSET(OFFER_BOOKING_DETAIL)@stop]
						[OFFER_BOOKING@stop]
						
						[OFFER_ACTIVITY@start]
							[ISSET(OFFER_ACTIVITY_ROUTE)@start]
								<div class="accordeon_entry">
									<div class="accordeon_title"><h2 class="accordeon_title_selector" aria-expanded="false" tabindex="0">__LANG[offer_route_info]__</h2></div>
									<div class="accordeon_content" aria-role="region">
										__OFFER_ACTIVITY_ROUTE__
										<div>__LANG[offer_elevation_profile]__</div>
									</div>
								</div>
							[ISSET(OFFER_ACTIVITY_ROUTE)@stop]
							[ISSET(OFFER_ACTIVITY_DETAIL)@start]
								<div class="accordeon_entry">
									<div class="accordeon_title"><h2 class="accordeon_title_selector" aria-expanded="false" tabindex="0">__LANG[offer_details]__</h2></div>
									<div class="accordeon_content" aria-role="region">
										__OFFER_ADDITIONAL_INFO__
										__OFFER_ACTIVITY_PRICE__
										__OFFER_ACTIVITY_CATERING__
										__OFFER_ACTIVITY_MATERIAL_RENT__
										__OFFER_ACTIVITY_SAFETY_INSTRUCTIONS__
										__OFFER_ACTIVITY_SIGNALIZATION__
										__OFFER_ACTIVITY_INFRASTRUCTURE__
									</div>
								</div>
							[ISSET(OFFER_ACTIVITY_DETAIL)@stop]
							[ISSET(OFFER_ACTIVITY_ARRIVAL)@start]
								<div class="accordeon_entry">
									<div class="accordeon_title"><h2 class="accordeon_title_selector" aria-expanded="false" tabindex="0">__LANG[offer_arrival]__</h2></div>
									<div class="accordeon_content" aria-role="region">
										__OFFER_ACTIVITY_ARRIVAL__
									</div>
								</div>
							[ISSET(OFFER_ACTIVITY_ARRIVAL)@stop]
						[OFFER_ACTIVITY@stop]
						
						[ISSET(OFFER_SUBSCRIPTION)@start]
							<div class="accordeon_entry">
								<div class="accordeon_title"><h2 class="accordeon_title_selector" aria-expanded="false" tabindex="0">__LANG[offer_subscription]__</h2></div>
								<div class="accordeon_content" aria-role="region">
									__OFFER_SUBSCRIPTION__
								</div>
							</div>
						[ISSET(OFFER_SUBSCRIPTION)@stop]
						
						[ISSET(OFFER_TARGET_GROUPS)@start]
							<div class="accordeon_entry">
								<div class="accordeon_title"><h2 class="accordeon_title_selector" aria-expanded="false" tabindex="0">__LANG[offer_target_group]__</h2></div>
								<div class="accordeon_content" aria-role="region">
									__OFFER_TARGET_GROUPS__
								</div>
							</div>
						[ISSET(OFFER_TARGET_GROUPS)@stop]
						
						[ISSET(OFFER_POI_LIST)@start]
							<div class="accordeon_entry">
								<div class="accordeon_title">
									<h2 class="accordeon_title_selector" aria-expanded="false" tabindex="0">
										[OFFER_ACTIVITY@start]
											__LANG[offer_poi_activity]__
										[OFFER_ACTIVITY@stop]
								
										[OFFER_PROJECT@start]
											__LANG[offer_project_links]__
										[OFFER_PROJECT@stop]
									</h2>
								</div>
								<div class="accordeon_content" aria-role="region">
									__OFFER_POI_LIST__
								</div>
							</div>
						[ISSET(OFFER_POI_LIST)@stop]
						
						[ISSET(OFFER_ROUTE_LIST)@start]
							<div class="accordeon_entry">
								<div class="accordeon_title"><h2 class="accordeon_title_selector" aria-expanded="false" tabindex="0">__LANG[offer_route_links]__</h2></div>
								<div class="accordeon_content" aria-role="region">
									__OFFER_ROUTE_LIST__
								</div>
							</div>
						[ISSET(OFFER_ROUTE_LIST)@stop]
					</div>
				</div>
			</div>
			<div class="content_left">
			
				[ISSET(OFFER_IMAGES)@start]
					<div class="portlet">
						__OFFER_IMAGES__
					</div>
				[ISSET(OFFER_IMAGES)@stop]
			
				[OFFER_PRODUCT@start]
					[ISSET(OFFER_PRODUCT_DETAIL)@start]
					<div class="portlet">
						__OFFER_PRODUCT_PRICE__
					</div>
					[ISSET(OFFER_PRODUCT_DETAIL)@stop]
				[OFFER_PRODUCT@stop]
			
				[ISSET(OFFER_DATES)@start]
					<div class="portlet">
						__OFFER_DATES__
					</div>
				[ISSET(OFFER_DATES)@stop]
			
				[OFFER_EVENT@start]
					<div class="portlet">
						__OFFER_EVENT_LOCATION__
					</div>
				[OFFER_EVENT@stop]
			
				[ISSET(OFFER_SUPPLIERS)@start]
					<div class="portlet">
						__OFFER_SUPPLIERS__
					</div>
				[ISSET(OFFER_SUPPLIERS)@stop]
			
				[ISSET(OFFER_INSTITUTION)@start]
					<div class="portlet">
						__OFFER_INSTITUTION__
					</div>
				[ISSET(OFFER_INSTITUTION)@stop]
			
				[ISSET(OFFER_CONTACT)@start]
					<div class="portlet">
						__OFFER_CONTACT__
					</div>
				[ISSET(OFFER_CONTACT)@stop]
			
				[ISSET(OFFER_DOCUMENTS)@start]
					<div class="portlet">
						__OFFER_DOCUMENTS__
					</div>
				[ISSET(OFFER_DOCUMENTS)@stop]
			
				[ISSET(OFFER_ACCESSIBILITIES)@start]
					<div class="portlet">
						__OFFER_ACCESSIBILITIES__
					</div>
				[ISSET(OFFER_ACCESSIBILITIES)@stop]
			
				[ISSET(OFFER_LINKS)@start]
					<div class="portlet">
						__OFFER_LINKS__
					</div>
				[ISSET(OFFER_LINKS)@stop]
			
			</div>
		</div>
		<div class="content_inner">
			<h2 class="map_wrap_title">__LANG[offer_map]__</h2>
			__OFFER_MAP__
		</div>
	</div>
</div>