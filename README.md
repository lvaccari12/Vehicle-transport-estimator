Vehicle Transport Estimator

Installation

- Copy the `vehicle-transport-estimator` folder into your WordPress `wp-content/plugins/` directory or place it in your development site plugins folder.
- Activate the plugin from the WordPress admin Plugins screen.

Usage

- Add the shortcode `[vehicle_transport_estimator]` into a post or page where you want the form to appear.

Admin Settings

- Settings → Transport Estimator
  - Phone Number (string) — validated with `sanitize_text_field`.
  - Next Step URL (URL) — validated with `esc_url_raw`.

Security & Sanitization Notes

- All output in templates uses escaping where appropriate (`esc_html`, `esc_attr`).
- Settings are sanitized on save using `sanitize_text_field` for the phone and `esc_url_raw` for URLs.
- Script data is passed using `wp_localize_script` and values are JS-escaped in PHP.

Testing Steps

1. Activate the plugin.
2. Create a page and insert the shortcode: `[vehicle_transport_estimator]`.
3. View the page; verify dropdowns populate with Florida, New York, California, Texas, Georgia.
4. Select `Florida` → `New York` and confirm estimate appears matching the spec.
5. Select an unsupported route and confirm fallback message appears.
6. Select same state for both and confirm the message "Pick-up and drop-off can't be the same." appears.
7. Click `Call Now!` — it should open the phone dialer with the configured phone.
8. Click `Next Step!` — it should redirect to the configured `Next Step URL`.

Notes

- Assets are enqueued only when the shortcode runs.
- No external JS frameworks are used.

