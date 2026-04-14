# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] -- Initial release

### Added -- frontend subscription system
- **Hyva template** (`price-alert.phtml`) -- Alpine.js-powered card
  with AJAX subscription, real-time status check, target price input,
  one-click unsubscribe, inline toast notifications, and full FPC
  compatibility via section data detection.
- **Luma template** (`luma/price-alert.phtml`) -- vanilla JavaScript
  version providing identical functionality without Alpine.js.
- Guest and logged-in customer support (configurable).
- Optional target price -- customers choose their own threshold, or
  subscribe to "any price drop".

### Added -- admin dashboard
- KPI summary cards: total, active, sent, cancelled, today, average
  target price.
- 7-day alert trend bar chart (Chart.js via RequireJS).
- Most-wanted products table ranked by active alert count.
- Recent alert activity feed with status badges.

### Added -- admin alert management
- UI component grid (`pricedropalert_alert_listing`) with columns for
  customer name, email, product name, target price, current price,
  status, created/sent dates.
- Mass actions: delete, send email.
- Per-row actions: view, delete, send email.
- Alert detail view page with subscription details, product & price
  comparison, target-reached status messages, and action buttons.

### Added -- smart price resolution (PriceResolver)
- Simple/Virtual/Downloadable: `getFinalPrice()` including catalog
  rules, special prices, and tier prices.
- Configurable: minimum child `getFinalPrice()` (the "As low as $X"
  price).
- Bundle: minimum calculated price from the price model.
- Grouped: minimum associated simple product price.

### Added -- automated notifications
- Cron job (`PriceAlertNotification`) checks all active alerts against
  current prices using PriceResolver. Sends emails when thresholds
  are met.
- Product save observer (`ProductSaveAfterCheckAlerts`) triggers
  immediate notifications when an admin changes price, special price,
  or special price dates. Also checks parent configurable alerts when
  a child simple product price changes.
- Email sender service with template variables: customer name, product
  name, product URL, old price, new price, discount percentage.

### Added -- configurable placement
- Layout handles for 5 positions: after price, above/below
  add-to-cart, above/below description.
- Observer-based handle injection (`AddPlacementLayoutHandle`) on
  `catalog_product_view`.
- PlacementProcessor ViewModel for programmatic placement queries.

### Added -- admin configuration
- Stores > Configuration > Panth Extensions > Price Drop Alert
- General: enable/disable, allow guest subscriptions.
- Email: sender identity, email template.
- Cron: check frequency in hours.
- Design note pointing to theme CSS variable configuration.

### Quality
- Constructor injection only -- zero `ObjectManager::getInstance()`.
- All PHP files pass Magento2 coding standard (MEQP) at severity 10.
- All template outputs properly escaped with `escapeHtml`,
  `escapeUrl`, `escapeHtmlAttr`, `escapeJs`.
- Composer validate passes.

### Compatibility
- Magento Open Source / Commerce / Cloud 2.4.4 -- 2.4.8
- PHP 8.1, 8.2, 8.3, 8.4
- Hyva theme (Alpine.js) and Luma theme (vanilla JS)

---

## Support

For all questions, bug reports, or feature requests:

- **Email:** kishansavaliyakb@gmail.com
- **Website:** https://kishansavaliya.com
- **WhatsApp:** +91 84012 70422
