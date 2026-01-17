Product Requirements Document (PRD)
Project Name: Variation-Level Product Options (Custom Plugin) Platform: WordPress / WooCommerce License: Open Source (GPLv3 recommended) Developer: Zaffarullah Contact: xaphor.emam@gmail.com Version: 1.0.0 (MVP)

1. Executive Summary
This project aims to develop a lightweight, secure, and modern WooCommerce plugin that allows store owners to add custom options (fields) to products at both the Product and Variation levels. Unlike existing free solutions, this plugin will natively support "Choice" buttons (Radio/Switch), conditional logic (chained fields), and dynamic pricing without requiring a premium upgrade.

Core Philosophy:

Zero Bloat: No external API calls, minimal CSS/JS loading.

Variation Aware: Options can be specific to a variation (e.g., a "Gold" ring has different extra options than a "Silver" ring).

Transparency: All extra costs are clearly broken down in the Cart and Order details.

2. Functional Requirements
2.1 Field Types Supported
The plugin must support the creation of the following input types on the product page:

Radio Buttons: Standard circular selection (Single choice).

Radio Switch/Toggle: A modern "pill" style switch (Yes/No or Option A/Option B).

Checkboxes: Multiple selections (e.g., "Add Gift Wrap", "Add Card").

Dropdown Select: For long lists of options.

Datepicker: A calendar input for delivery scheduling (Metadata only, no price impact).

2.2 Pricing Logic
Flat Fee Addition: Each option can have a defined price (e.g., +$50.00).

Price Calculation: When an option is selected, the product price displayed on the frontend should update dynamically (AJAX or JS-based).

Zero-Price Fields: Fields like the Datepicker or simple "Notes" fields can have a price of 0.

2.3 Conditional Logic (Chained Fields)
The system must support "If This, Then That" display rules.

Trigger: Selection of a specific value in Field A.

Action: Reveal Field B.

Example:

Field A: "Want Installation?" (Yes/No)

Condition: If "Yes" is selected → Show Field B.

Field B: "Is a Crane Required?" (Yes/No +$200).

2.4 Scope & Assignment (The "Variation" Logic)
Admin must be able to assign a Field Group to:

All Products: Global application.

Specific Products: Selected by Product ID.

Specific Variations: The critical feature.

Logic: If Product is "Palm Tree" AND Variation is "Large (10ft)", SHOW "Crane Option".

Logic: If Product is "Palm Tree" AND Variation is "Small (2ft)", HIDE "Crane Option".

3. User Interface (UI) Requirements
3.1 Admin Dashboard (Backend)
Location: WooCommerce > Product Options or a dedicated tab in Product Data.

Field Builder: A drag-and-drop or simple list interface to add fields.

Settings per Field:

Label (e.g., "Installation").

Type (Radio, Checkbox, etc.).

Options (e.g., Yes | No).

Price per Option.

Condition (Show if Field X = Y).

Styling: Native WordPress Admin styling (so it feels built-in), not a custom React app (to keep it lightweight).

3.2 Frontend (Customer View)
Placement: Fields should display immediately before the "Add to Cart" button.

Styling: "Modern & Sleek."

Radio Switches should look like mobile toggles or segmented controls, not browser defaults.

Inputs should inherit the theme's font family but maintain their own clean structure.

Responsiveness: Must work perfectly on Mobile (stacked fields).

4. Technical Specifications
4.1 Data Handling & Cart
Cart Storage: When a user clicks "Add to Cart," the selected options must be saved to the cart item data.

Price Modification: Use WooCommerce hooks (woocommerce_before_calculate_totals) to programmatically add the option prices to the cart item price.

Display:

Cart Page: Show selected options under the product name (e.g., "Installation: Yes (+$50)").

Checkout: Show options in the order summary.

Thank You Page / Emails: Options must persist in the order metadata so the admin sees them in the backend order screen.

4.2 Security & Performance
Sanitization: All user inputs (text fields) must be sanitized using sanitize_text_field() before saving.

Validation: Ensure required fields are checked before allowing "Add to Cart."

SQL Safety: Use $wpdb->prepare() for any direct database queries (though standard WP functions update_post_meta are preferred).

Nonce Verification: Secure the admin settings forms with WP Nonces.

5.0 Example JSON Structure for Saved Data
To assist the developer, here is how the data should ideally be structured in the database (post_meta):

JSON

{
  "group_id": "installation_options",
  "rules": {
    "variation_ids": [102, 105], 
    "product_ids": []
  },
  "fields": [
    {
      "id": "install_check",
      "type": "radio_switch",
      "label": "Want Installation?",
      "options": [
        {"label": "Yes", "price": 50, "value": "yes"},
        {"label": "No", "price": 0, "value": "no"}
      ]
    },
    {
      "id": "crane_check",
      "type": "checkbox",
      "label": "Crane Required?",
      "price": 200,
      "condition": {"field": "install_check", "value": "yes"}
    }
  ]
}