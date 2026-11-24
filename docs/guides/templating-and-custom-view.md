[Docs](../index.md) › Templating and custom view

# Templating and custom view

## Templating

Templates are located in:

- `swiss-parks-sdk/templates/standard/`
- optionally custom folders, for example `swiss-parks-sdk/templates/your-template/` (ZIP) or any folder in your project (Composer)

Configuration:

- `'template_folder' => 'your-template'` — folder name under `swiss-parks-sdk/templates/`
- `'template_folder' => __DIR__ . '/../templates/parks'` — absolute path to your own folder; use this for Composer installations so the template stays outside `vendor/`

Important:

- Do not edit core templates directly.
- Create your own template folder based on `standard`.
- Available placeholders, conditions and language variables: [Template placeholders](../sdk-reference/template-placeholders.md).
- `SwissParksSdk::loadTemplate()` loads `detail.tpl` / `filter.tpl`; `compileTemplate($section, $templateData, $conditions)` fills placeholders and condition blocks (see [SwissParksSdk methods](../sdk-reference/swiss-parks-sdk.md#template-helpers)).

## Overriding methods (custom view)

### Step 1: Activate a custom view class

In your config file (`config.local.php` or the project config):

```php
'class_view' => MyView::class,
```

The value is a fully qualified class name and is used as given. Default is `\SwissParks\Sdk\View::class`.

### Step 2: Add your custom class

The class extends `SwissParks\Sdk\View`. Where it lives depends on the installation:

- ZIP: `swiss-parks-sdk/custom/MyView.php`. `autoload.php` loads every PHP file in `custom/`.
- Composer: anywhere in your project, loaded by your own autoloader (for example a PSR-4 entry in your `composer.json`).

```php
<?php

use SwissParks\Sdk\View;

class MyView extends View
{

	public function overwriteTemplateData(array $templateData, object $offer): array
	{

		return parent::overwriteTemplateData($templateData, $offer);
	}

}
```

### Step 3: Override targeted methods

Common extension points:

- `overwriteTemplateData(array $templateData, object $offer): array`
  - Fine-grained template data changes before rendering.
- Full rendering methods from `View`, for example:
  - `filter(...)`
  - `listOffers(...)`
  - `detail(...)`

Best practices:

- Override only methods you really need.
- Prefer extending behavior with `parent::...`.
- Keep customizations in `custom/` (ZIP) or your project (Composer); keep core files update-safe.

## Examples

The repository folder [`examples/`](../../examples/) holds real-world customizations. They are not part of the release ZIP or the Composer package:

- `examples/parks-swiss/` — `ParksSwissView` custom view and templates used on parks.swiss
- `examples/nationalpark/templates/` — alternative template set (nationalpark)
- `examples/pfyn/templates/` — alternative template set (pfyn)

Copy what you need into your project and adapt it.

## Related docs

- [Template placeholders](../sdk-reference/template-placeholders.md)
- [Configuration](./configuration.md#view-and-template)
