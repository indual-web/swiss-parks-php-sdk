<!--
Available Tags:
FILTER_TEXT_SEARCH
FILTER_CATEGORIES
FILTER_DATES
FILTER_TARGET_GROUPS
FILTER_ACCESSIBILITIES
FILTER_PARKS
FILTER_PROJECT
FILTER_ROUTE_LENGTH
FILTER_ROUTE_TIME
FILTER_ROUTE_CONDITION
FILTER_KEYWORDS
FILTER_SHOW_LINK
FILTER_FORM_START
FILTER_FORM_STOP
FILTER_RESET_BUTTON
FILTER_SUBMIT_BUTTON


Language variables can be printed: __LANG[name]__

Conditions available:
Check condition: [OFFER_EVENT@start] This text is rendered based on the condition [OFFER_PRODUCT@stop]

Check if placeholder is set [ISSET(OFFER_PRODUCT)@start] [ISSET(OFFER_PRODUCT)@stop]
-->
<div class="filter">
	__FILTER_SHOW_LINK__
	__FILTER_FORM_START__
	<div class="offer_filter_form">
		__FILTER_TEXT_SEARCH__
		__FILTER_PARKS__
		__FILTER_CATEGORIES__
		__FILTER_DATES__
		__FILTER_TARGET_GROUPS__
		__FILTER_MUNICIPALITIES__
		__FILTER_ACCESSIBILITIES__
		__FILTER_ROUTE_LENGTH__
		__FILTER_ROUTE_TIME__
		__FILTER_ROUTE_CONDITION__
		__FILTER_PROJECT__
		<div class="cf"></div>
	</div>
	<div class="form_element submit">
		__FILTER_RESET_BUTTON__
		__FILTER_SUBMIT_BUTTON__
		<div class="cf"></div>
	</div>
	__FILTER_FORM_STOP__
	<div class="cf"></div>
</div>