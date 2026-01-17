## Purpose

Short, focused instructions to help an AI coding agent be immediately productive in this repository (Variation Product Options — a WooCommerce plugin).

## Big picture (why & architecture)
- This is a self-contained WordPress/WooCommerce plugin that allows admins to define "field groups" (JSON saved in an option) and display product/variation-level custom fields on product pages. Pricing for selected options is calculated on the frontend (JS + AJAX) and applied server-side when items are added to cart.
- Main runtime flow: Admin UI -> save group -> persisted to WP option `vpo_field_groups` -> frontend renders fields (PHP) -> JS collects selections and calls `admin-ajax.php?action=vpo_calculate_price` for authoritative calculation -> Add to cart posts `vpo` payload -> `VPO_Cart_Handler` reads `vpo_options`, adjusts item price and persists option labels to order meta.

## Key files and responsibilities
- `variation-product-options.php` — plugin bootstrap, constants, activation checks, entrypoint and initialization order (loads files conditionally based on is_admin()/wp_doing_ajax()).
- `includes/class-vpo-core.php` — core bootstrap (lightweight).
- `includes/class-vpo-data-handler.php` — canonical source of truth for field groups; stores/returns groups via option `vpo_field_groups`. Prefer to change data schema here.
- `includes/class-vpo-field-types.php` — renders individual field HTML; add new field types and render_* methods here.
- `admin/class-vpo-admin.php` — admin screens, meta box wiring, enqueueing admin assets, and form handlers (`admin_post_vpo_save_group`, `admin_post_vpo_delete_group`).
- `admin/class-vpo-field-builder.php` — field builder UI markup and templates used by `admin.js`.
- `frontend/class-vpo-frontend.php` — registers AJAX handlers early, renders fields on product pages and enqueues frontend assets; localizes `vpoData` (ajaxUrl, nonce).
- `frontend/class-vpo-cart-handler.php` — reads `$_POST['vpo']` during `woocommerce_add_cart_item_data`, stores cart meta `vpo_options`, adjusts price in `woocommerce_before_calculate_totals`, and writes order item meta on checkout.
- `assets/js/frontend.js` — client-side logic (collects `vpo` data, local quick calculation, AJAX for authoritative calculate, listens for `found_variation`, updates conditional visibility).
- `assets/js/admin.js` — admin field-builder interactions (add/remove fields/options, Select2 integration).

## Data model & conventions
- Field groups are stored as an associative array in option `vpo_field_groups` (see `VPO_Data_Handler::OPTION_NAME`). Each group contains `group_id`, `rules` (`all_products`, `product_ids`, `variation_ids`), and `fields`.
- Field ids are sanitized with `sanitize_key()` and used as HTML names `vpo[field_id]` in forms and in the cart payload (server expects `$_POST['vpo']`).
- Field JSON example is in README.md and the PRD — follow the same keys: `id`, `type`, `label`, `options` (for select/radio), `price` (checkbox), `condition` (field/value).

## Security, i18n, and patterns
- Nonces: AJAX uses `vpo-ajax-nonce` (localized via `vpoData.nonce`). Admin forms are protected with `wp_nonce_field('vpo_save_group')` and checked in `handle_save_group`/`handle_delete_group`.
- Sanitization: codebase consistently uses `sanitize_key()`, `sanitize_text_field()`, `absint()`, and `$wpdb->prepare()` when needed. Continue this pattern.
- Singleton pattern: most classes use `get_instance()` + private constructor. Follow this when adding services.
- Text domain: `variation-product-options` — use `__()`/`esc_html__()` and load textdomain in bootstrap.

## Runtime hooks to know (integration points)
- Frontend render: `woocommerce_before_add_to_cart_button` — fields are injected before the Add-to-Cart button.
- Cart capture: `woocommerce_add_cart_item_data` — plugin reads `$_POST['vpo']` here.
- Price update: `woocommerce_before_calculate_totals` — cart item price adjusted by `VPO_Cart_Handler`.
- Order persistence: `woocommerce_checkout_create_order_line_item` — writes option labels to order item meta.

## How to add a new field type (practical steps)
1. Add the label to `VPO_Field_Types::get_field_types()`.
2. Add a `render_<type>()` method in `includes/class-vpo-field-types.php` mirroring existing render helpers (follow naming and escaping patterns used there).
3. Update admin builder UI (`VPO_Field_Builder::render_field_row`) if the type needs specific admin controls (options, price, etc.).
4. Update JS if client-side behavior or data attributes are required (front `assets/js/frontend.js`, admin `assets/js/admin.js`).
5. Update server-side calculation paths: `VPO_Frontend::ajax_calculate_price()` and `VPO_Cart_Handler::calculate_cart_item_price()` so the new type's pricing logic is accounted for.

## Manual test & debug recipe (smoke tests)
1. Copy the plugin folder into `wp-content/plugins/` on a test WP site with WooCommerce active (WP_DEBUG on). Activate plugin.
2. Admin: Go to WooCommerce > Product Options, create a field group, assign to a product/variation (use real IDs from Admin), add fields with prices and conditional rules.
3. Frontend: Open the product page, select a variation (if variable), verify fields appear, test conditional show/hide, see the additional total update.
4. Add to cart: Verify cart shows option labels under product name and total price includes options. Complete checkout; verify order item meta contains option labels.
5. Debugging: turn on WP_DEBUG and WP_DEBUG_LOG, open browser DevTools Network tab and inspect `admin-ajax.php` requests (actions `vpo_calculate_price`, `vpo_get_variation_fields`). Look for JSON responses and server errors.

## Known Issues & Solutions

### Issue: Fields don't show when assigned to specific variations only
- **Symptom**: Assign field group to variation ID 2627 (not "All Products"), fields don't appear on product page
- **Root Cause**: On initial page load, no variation is selected, so `.vpo-product-options` container is never created. When user selects variation, AJAX tries to inject fields but has no container.
- **Solution**: JavaScript `loadOptionsForVariation()` now creates the container dynamically if it doesn't exist (see `assets/js/frontend.js` line ~232)
- **Debug**: Open DevTools Console (F12), select a variation, look for logs starting with `VPO: loadOptionsForVariation`
- **Full Guide**: See `VARIATION_ASSIGNMENT_DEBUG.md` for comprehensive testing and troubleshooting

### Issue: CSS hiding injected variation-specific fields
- **Symptom**: Fields load via AJAX (server returns HTML), but options are invisible
- **Root Cause**: Astra theme CSS specificity overrides VPO styles; also injected fields need `!important` flags
- **Solution**: `assets/css/frontend.css` has `!important` flags on `.vpo-field`, `.vpo-switch-option`, `.vpo-switch-label` (applied Jan 16, 2026)
- **If Issue Persists**: Check browser DevTools Elements tab, verify computed styles have `display: block` or `visibility: visible` with `!important`

## Performance & Caching Notes
- Field groups are stored in WordPress options (`vpo_field_groups`) with no caching layer — cache plugins may need to exclude admin-ajax.php AJAX actions
- LiteSpeed Cache: Ensure `wp-admin/admin-ajax.php?action=vpo_*` is not cached (should be by default)
- Lazy Loading: AJAX handler `vpo_get_variation_fields` only fires when variation is actually selected; no unnecessary requests on page load

## Useful quick references for the agent
- AJAX actions: `vpo_calculate_price`, `vpo_get_variation_fields` (registered in `VPO_Frontend::register_ajax_handlers`).
- Option storage key: `vpo_field_groups` (in `VPO_Data_Handler`).
- Cart meta key used in cart items: `vpo_options`.
- Admin form actions: `admin_post_vpo_save_group`, `admin_post_vpo_delete_group`.

## CI & local smoke tests
- A lightweight GitHub Actions workflow was added at `.github/workflows/ci.yml` — it runs PHP syntax checks (php -l) on all PHP files for push/PR events.
- A local PowerShell smoke script was added at `scripts/smoke-test.ps1` (Windows friendly). It:
	- Runs `php -l` on all PHP files.
	- Optionally copies the plugin into a provided WordPress install path (uses `wp`/WP-CLI), activates the plugin, and verifies `VPO_Data_Handler` is available.

Run the local script in PowerShell:

```powershell
./scripts/smoke-test.ps1
# or with a WP path to auto-install + activate:
./scripts/smoke-test.ps1 -WpPath "C:\path\to\wordpress"
```

If anything in this summary looks incomplete or you want me to add short examples (e.g., exact JSON shapes, a small unit-test scaffold, or CI hooks), tell me which part to expand and I will iterate.
