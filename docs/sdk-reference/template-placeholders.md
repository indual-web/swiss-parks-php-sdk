[Docs](../index.md) › Template placeholders

# Template placeholders

Reference for `detail.tpl` and `filter.tpl` in a template folder (see [Templating and custom view](../guides/templating-and-custom-view.md)). The shipped `templates/standard/` uses most of them and is the best starting point for an own template.

## Syntax

| Syntax | Meaning |
| --- | --- |
| `__OFFER_TITLE__` | Placeholder. Replaced with HTML, or with an empty string when there is no content. Only the placeholders listed on this page are replaced |
| `__LANG[name]__` | Label `name` from the language files in `lang/` for the active language. Unknown labels become an empty string |
| `[OFFER_EVENT@start] … [OFFER_EVENT@stop]` | Condition block (`detail.tpl` only). Kept when the offer has this type, otherwise removed. See [conditions](#conditions) |
| `[ISSET(OFFER_MAP)@start] … [ISSET(OFFER_MAP)@stop]` | Kept when the placeholder has content, otherwise removed. Use it to hide wrappers and headings of empty blocks |
| `[NOTISSET(OFFER_MAP)@start] … [NOTISSET(OFFER_MAP)@stop]` | Kept when the placeholder is empty, otherwise removed. Use it for fallbacks |
| `<!-- … -->` | HTML comments are removed when the template is loaded |

To change the content of a placeholder, override `overwriteTemplateData()` or the matching method in a custom view.

## Detail template (`detail.tpl`)

### General

| Placeholder | Content |
| --- | --- |
| `__OFFER_TITLE__` | Offer title |
| `__OFFER_SHORT_INFO__` | Short info line depending on the offer type, for example the next date or the season |
| `__OFFER_BACK_LINK__` | Link back to the overview |
| `__OFFER_PRINT_LINK__` | Deprecated, always empty. Kept so older templates do not show raw text |
| `__OFFER_CATEGORIES__` | Categories of the offer, without hidden categories |
| `__OFFER_IMAGES__` | Image gallery |
| `__OFFER_ABSTRACT__` | Abstract (lead text) |
| `__OFFER_DESCRIPTION__` | Description |
| `__OFFER_ADDITIONAL_INFO__` | Additional information block |
| `__OFFER_DATES__` | Dates of the offer |
| `__OFFER_DOCUMENTS__` | Public documents (downloads) |
| `__OFFER_LINKS__` | Hyperlinks in the active language |
| `__OFFER_KEYWORDS__` | Keywords |
| `__OFFER_ACCESSIBILITIES__` | Accessibility information |
| `__OFFER_TARGET_GROUPS__` | Target groups |
| `__OFFER_SUPPLIERS__` | Suppliers |
| `__OFFER_INSTITUTION__` | Institution |
| `__OFFER_CONTACT__` | Contact |
| `__OFFER_SUBSCRIPTION__` | Subscription details, only when subscription is mandatory |
| `__OFFER_POI_LIST__` | Linked points of interest |
| `__OFFER_ROUTE_LIST__` | Linked routes in the vicinity |
| `__OFFER_MAP__` | Detail map |
| `__OFFER_INTERNAL_INFOS__` | Internal information (costs, funding, partners, remarks, internal links and documents), only when the export contains it |

### Type-specific blocks

Each `…_DETAIL` placeholder contains the complete block for one offer type and is only filled for offers of that type. The placeholders below it are the parts of that block; you can also place them on their own instead of the whole block.

| Placeholder | Content |
| --- | --- |
| `__OFFER_EVENT_DETAIL__` | Event block: location, dates, price, transport |
| `__OFFER_EVENT_LOCATION__` | Event location |
| `__OFFER_EVENT_LOCATION_SHORT__` | Short location text |
| `__OFFER_EVENT_LOCATION_DETAILS__` | Location details |
| `__OFFER_EVENT_DATE_DETAILS__` | Date details |
| `__OFFER_EVENT_TRANSPORT__` | Public transport stop with SBB timetable link |
| `__OFFER_EVENT_PRICE__` | Price |
| `__OFFER_PRODUCT_DETAIL__` | Product block: opening hours, price, transport, infrastructure |
| `__OFFER_PRODUCT_OPENING_HOURS__` | Opening hours |
| `__OFFER_PRODUCT_PUBLIC_TRANSPORT__` | Public transport stop with SBB timetable link |
| `__OFFER_PRODUCT_PRICE__` | Price |
| `__OFFER_PRODUCT_INFRASTRUCTURE__` | Infrastructure |
| `__OFFER_ONLINE_SHOP_CHECKOUT_BUTTON__` | Online shop checkout button |
| `__OFFER_BOOKING_DETAIL__` | Booking block: groups, benefits, requirements, accommodation, price, transport |
| `__OFFER_BOOKING_GROUPS__` | Group information |
| `__OFFER_BOOKING_BENEFITS__` | Benefits |
| `__OFFER_BOOKING_REQUIREMENTS__` | Requirements |
| `__OFFER_BOOKING_ACCOMMODATIONS__` | Accommodation |
| `__OFFER_BOOKING_TRANSPORT__` | Public transport stop with SBB timetable link |
| `__OFFER_BOOKING_PRICE__` | Price |
| `__OFFER_ACTIVITY_DETAIL__` | Activity block: route, arrival, price, catering, safety, infrastructure |
| `__OFFER_ACTIVITY_ROUTE__` | Route information |
| `__OFFER_ACTIVITY_ARRIVAL__` | Arrival: start and end point of the route |
| `__OFFER_ACTIVITY_PRICE__` | Price |
| `__OFFER_ACTIVITY_CATERING__` | Catering information |
| `__OFFER_ACTIVITY_MATERIAL_RENT__` | Material rental |
| `__OFFER_ACTIVITY_SAFETY_INSTRUCTIONS__` | Safety instructions |
| `__OFFER_ACTIVITY_SIGNALIZATION__` | Signalization |
| `__OFFER_ACTIVITY_INFRASTRUCTURE__` | Infrastructure |
| `__OFFER_ACTIVITY_DATES__` | Not filled, always empty. Activity dates are in `__OFFER_DATES__` |
| `__OFFER_PROJECT_DETAIL__` | Project block: duration and status |
| `__OFFER_PROJECT_DURATION__` | Project duration |
| `__OFFER_PROJECT_STATUS__` | Project status |

### Conditions

| Condition | Kept for |
| --- | --- |
| `[OFFER_EVENT@start] … [OFFER_EVENT@stop]` | Events |
| `[OFFER_PRODUCT@start] … [OFFER_PRODUCT@stop]` | Products |
| `[OFFER_BOOKING@start] … [OFFER_BOOKING@stop]` | Bookings (packages) |
| `[OFFER_ACTIVITY@start] … [OFFER_ACTIVITY@stop]` | Activities |
| `[OFFER_PROJECT@start] … [OFFER_PROJECT@stop]` | Projects |

## Filter template (`filter.tpl`)

Most filters are only filled when enabled in the [configuration](../guides/configuration.md#display-and-filter-ui) or the `$filter` array (see [filter options](./filter-options.md)) and when there is something to choose from.

| Placeholder | Content |
| --- | --- |
| `__FILTER_FORM_START__` | Opening `<form>` tag |
| `__FILTER_FORM_STOP__` | Closing `</form>` tag |
| `__FILTER_SHOW_LINK__` | Link that shows the filter |
| `__FILTER_TEXT_SEARCH__` | Text search field |
| `__FILTER_CATEGORIES__` | Category selection |
| `__FILTER_DATES__` | Date range |
| `__FILTER_TARGET_GROUPS__` | Target groups |
| `__FILTER_FIELDS_OF_ACTIVITY__` | Fields of activity |
| `__FILTER_MUNICIPALITIES__` | Municipalities, when offers exist in more than one |
| `__FILTER_ACCESSIBILITIES__` | Accessibility |
| `__FILTER_PARKS__` | Parks, when the export contains more than one park |
| `__FILTER_PROJECT__` | Project status |
| `__FILTER_ROUTE_LENGTH__` | Route length |
| `__FILTER_ROUTE_TIME__` | Route duration |
| `__FILTER_ROUTE_CONDITION__` | Route condition and technique |
| `__FILTER_KEYWORDS__` | Keyword filter from `keyword_filter` |
| `__FILTER_RESET_BUTTON__` | Link that resets the filter |
| `__FILTER_SUBMIT_BUTTON__` | Submit button |

`ISSET`, `NOTISSET` and `__LANG[name]__` work in `filter.tpl` as well; the type conditions do not.

## Related docs

- [Templating and custom view](../guides/templating-and-custom-view.md)
- [SwissParksSdk methods](./swiss-parks-sdk.md#template-helpers)
- [Filter options](./filter-options.md)
