# Templating and Custom View

## Templating

Templates are located in:

- `parks_api/template/standard/`
- optionally custom folders, for example `parks_api/template/your-template/`

Configuration:

- `config['template_folder'] = 'your-template';`

Important:

- Do not edit core templates directly.
- Create your own template folder based on `standard`.

## Overriding methods (custom view)

### Step 1: Activate a custom view class

In `config.php`:

```php
$config['class_view'] = "MyView";
```

### Step 2: Use your custom class

File: `parks_api/custom/MyView.php`  
This class extends `ParksView`.

### Step 3: Override targeted methods

Common extension points:

- `overwrite_template_data($template_data, $offer)`
  - Fine-grained template data changes before rendering.
- Full rendering methods from `ParksView`, for example:
  - `filter(...)`
  - `list_offers(...)`
  - `detail(...)`
  - helper methods like `_get_detail_event(...)`, `_get_detail_product(...)`, etc.

Best practices:

- Override only methods you really need.
- Prefer extending behavior with `parent::...`.
- Keep customizations in `custom/`; keep core files update-safe.

Additional example:

- `parks_api/custom/ParksSwissView.php`
