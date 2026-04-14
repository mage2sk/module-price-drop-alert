# Panth Price Drop Alert for Magento 2

[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange)]()
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue)]()
[![Hyva Compatible](https://img.shields.io/badge/Hyva-Compatible-green)]()
[![Luma Compatible](https://img.shields.io/badge/Luma-Compatible-green)]()

**Let customers subscribe to price drop notifications** on any product
in your Magento 2 store. When the price drops to or below their target,
they receive an email automatically. Works with Simple, Configurable,
Bundle, and Grouped products.

---

## Features

### Frontend — customer-facing
- Beautiful, responsive "Get Price Drop Alerts" card on product pages
  (Hyva Alpine.js version + Luma vanilla JS version)
- Guest and logged-in customer support (configurable)
- Optional target price — customers choose their own threshold, or
  subscribe to "any price drop"
- Real-time subscription status check (already subscribed? shows
  compact "Active" card with one-click unsubscribe)
- Full-page-cache compatible — uses section data and AJAX for
  customer detection

### Admin — store-owner facing
- **Dashboard** (Stores > Panth Infotech > Price Drop Alerts > Dashboard)
  with KPI cards (total, active, sent, cancelled, today, avg target),
  7-day trend chart (Chart.js), most-wanted products table, and recent
  activity feed
- **Manage Alerts** grid with UI component listing — filter, sort,
  mass-delete, mass-send, per-row view/delete/send actions
- **Alert detail view** showing subscription details, product & price
  comparison, and target-reached / waiting-for-drop status messages
- Configurable email sender identity and email template
- Configurable cron frequency for automated price checks

### Automated notifications
- **Cron job** checks all active alerts against current product prices
  using the smart PriceResolver (handles all product types)
- **Product save observer** — when an admin changes a product price,
  matching alerts are triggered immediately (no waiting for cron)
- Email includes product name, old price, new price, discount
  percentage, and direct product link

### Smart price resolution
- Simple/Virtual/Downloadable: uses `getFinalPrice()` (includes
  catalog rules, special prices, tier prices)
- Configurable: minimum child `getFinalPrice()` ("As low as $X")
- Bundle: minimum calculated price from the price model
- Grouped: minimum associated simple product price

### Configurable placement
- Layout handles for 5 positions: after price, above/below add-to-cart,
  above/below description
- Observer-based handle injection on `catalog_product_view`

---

## Installation

### Via Composer (recommended)

```bash
composer require mage2kishan/module-price-drop-alert
bin/magento module:enable Panth_Core Panth_PriceDropAlert
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Via uploaded zip

1. Download the extension zip from the Marketplace
2. Extract to `app/code/Panth/PriceDropAlert`
3. Make sure `app/code/Panth/Core` is also installed
4. Run the same commands above starting from `module:enable`

---

## Requirements

| | Required |
|---|---|
| Magento | 2.4.4 -- 2.4.8 (Open Source / Commerce / Cloud) |
| PHP | 8.1 / 8.2 / 8.3 / 8.4 |
| `mage2kishan/module-core` | ^1.0 (installed automatically as a composer dependency) |

---

## Configuration

Open **Stores > Configuration > Panth Extensions > Price Drop Alert**.

### General Settings
- **Enable Price Drop Alerts** — master on/off switch
- **Allow Guest Subscriptions** — let non-logged-in visitors subscribe
  by entering their email and name

### Email Notifications
- **Email Sender Identity** — choose which store identity sends the
  notifications (General Contact, Sales Representative, etc.)
- **Email Template** — select or customize the transactional email
  template under Marketing > Email Templates

### Price Check Schedule
- **Check Frequency (hours)** — how often the cron checks prices
  (default: 24 hours, minimum recommended: 1 hour)

### Design & Colors
Colors are managed via your theme's configuration file. See the admin
panel note for the exact file path and CSS variable section.

---

## Support

| Channel | Contact |
|---|---|
| Email | kishansavaliyakb@gmail.com |
| Website | https://kishansavaliya.com |
| WhatsApp | +91 84012 70422 |

Response time: 1-2 business days for paid licenses.

---

## License

Commercial -- see `LICENSE.txt`. One license per Magento production
installation. Includes 12 months of free updates and email support.

---

## About the developer

Built and maintained by **Kishan Savaliya** -- https://kishansavaliya.com.
Builds high-quality Magento 2 extensions and themes for both Hyva and
Luma storefronts.
