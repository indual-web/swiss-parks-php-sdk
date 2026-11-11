[Docs](../index.md) › [Guides](./index.md) › Templating and custom view

# Templating and custom view

## Templating

Templates are located in:

- `swiss-parks-sdk/templates/standard/`
- optionally custom folders, for example `swiss-parks-sdk/templates/your-template/`

Configuration:

- `config['template_folder'] = 'your-template';`

Important:

- Do not edit core templates directly.
- Create your own template folder based on `standard`.

## Overriding methods (custom view)

### Step 1: Activate a custom view class

In `config/config.php`:

```php
$config['class_view'] = 'MyView';
```

Default is `ParksView`. For the parks.swiss theme helpers use `ParksSwissView` (shipped in `classes/`).

### Step 2: Add your custom class

File: `swiss-parks-sdk/custom/MyView.php`  
This class extends `ParksView` and is auto-loaded from `custom/`.

```php
<?php

class MyView extends ParksView
{

	public function overwriteTemplateData(array $template_data, object $offer): array
	{

		return parent::overwriteTemplateData($template_data, $offer);
	}

}
```

### Step 3: Override targeted methods

Common extension points:

- `overwriteTemplateData(array $template_data, object $offer): array`
  - Fine-grained template data changes before rendering.
- Full rendering methods from `ParksView`, for example:
  - `filter(...)`
  - `listOffers(...)`
  - `detail(...)`

Best practices:

- Override only methods you really need.
- Prefer extending behavior with `parent::...`.
- Keep customizations in `custom/`; keep core files update-safe.

## Related docs

- [Guide index](./index.md)
- [Core configuration](./configuration.md)
