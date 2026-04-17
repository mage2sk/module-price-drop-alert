<!-- SEO Meta -->
<!--
  Title: Panth Price Drop Notification Alerts for Magento 2 | Panth Infotech
  Description: Let customers subscribe to price drop alerts on product pages and receive automated email notifications when prices fall. Cron-based price monitoring, admin dashboard, configurable placement, Hyva and Luma compatible. Magento 2.4.4 - 2.4.8, PHP 8.1 - 8.4.
  Keywords: magento 2 price alert, price drop notification, price tracking, sale alerts, magento email alerts, magento 2 price drop, magento wishlist price, hyva price alert, luma price alert, panth infotech
  Author: Kishan Savaliya (Panth Infotech)
  Canonical: https://github.com/mage2sk/module-price-drop-alert
-->

# Panth Price Drop Notification Alerts for Magento 2 | Panth Infotech

[![Visitors](https://visitor-badge.laobi.icu/badge?page_id=mage2sk.module-price-drop-alert&left_color=gray&right_color=0d9488&left_text=Visitors)](https://github.com/mage2sk/module-price-drop-alert)
[![Magento 2.4.4 - 2.4.8](https://img.shields.io/badge/Magento-2.4.4%20--%202.4.8-orange?logo=magento&logoColor=white)](https://magento.com)
[![PHP 8.1 - 8.4](https://img.shields.io/badge/PHP-8.1%20--%208.4-blue?logo=php&logoColor=white)](https://php.net)
[![Hyva + Luma](https://img.shields.io/badge/Themes-Hyva%20%2B%20Luma-14b8a6)]()
[![Packagist](https://img.shields.io/badge/Packagist-mage2kishan%2Fmodule--price--drop--alert-orange?logo=packagist&logoColor=white)](https://packagist.org/packages/mage2kishan/module-price-drop-alert)
[![Upwork Top Rated Plus](https://img.shields.io/badge/Upwork-Top%20Rated%20Plus-14a800?logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)
[![Panth Infotech Agency](https://img.shields.io/badge/Agency-Panth%20Infotech-14a800?logo=upwork&logoColor=white)](https://www.upwork.com/agencies/1881421506131960778/)
[![Website](https://img.shields.io/badge/Website-kishansavaliya.com-0D9488)](https://kishansavaliya.com)
[![Get a Quote](https://img.shields.io/badge/Get%20a%20Quote-Free%20Estimate-DC2626)](https://kishansavaliya.com/get-quote)

> **Convert browsers into buyers** with automated price drop alerts. Customers subscribe to price drop notifications directly on product pages, a cron-based price monitor watches every subscribed SKU, and the module fires automated email notifications the moment a price falls. A full admin dashboard lets merchants track subscriptions, review alert history, and control placement — fully compatible with both **Hyva** and **Luma** themes.

**Panth Price Drop Alert** turns abandoned product views into recovered revenue. Shoppers who are not quite ready to buy can click a single "Notify Me on Price Drop" button on any product page, enter their email (or use their logged-in customer account), and automatically receive a beautifully formatted email the moment the price drops — whether the discount is applied via catalog price rules, special price, tier pricing, or a manual SKU update.

A background cron job runs on your configured schedule, snapshots current prices, compares them against stored subscription baselines, and queues email notifications using Magento's native transactional email system. The **admin dashboard** gives merchants a complete view of every subscriber, every SKU being watched, alert-sent history, and one-click unsubscribe controls. Placement of the subscribe button is fully configurable — product view page, product list page, or both — with Hyva and Luma templates included out of the box.

---

## 🚀 Need Custom Magento 2 Development?

> **Get a free quote for your project in 24 hours** — custom modules, Hyva themes, performance optimization, M1→M2 migrations, and Adobe Commerce Cloud.

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/Get%20a%20Free%20Quote%20%E2%86%92-Reply%20within%2024%20hours-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<table>
<tr>
<td width="50%" align="center">

### 🏆 Kishan Savaliya
**Top Rated Plus on Upwork**

[![Hire on Upwork](https://img.shields.io/badge/Hire%20on%20Upwork-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/freelancers/~016dd1767321100e21)

100% Job Success • 10+ Years Magento Experience
Adobe Certified • Hyva Specialist

</td>
<td width="50%" align="center">

### 🏢 Panth Infotech Agency
**Magento Development Team**

[![Visit Agency](https://img.shields.io/badge/Visit%20Agency-Panth%20Infotech-14a800?style=for-the-badge&logo=upwork&logoColor=white)](https://www.upwork.com/agencies/1881421506131960778/)

Custom Modules • Theme Design • Migrations
Performance • SEO • Adobe Commerce Cloud

</td>
</tr>
</table>

**Visit our website:** [kishansavaliya.com](https://kishansavaliya.com) &nbsp;|&nbsp; **Get a quote:** [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote)

---

## Table of Contents

- [Key Features](#key-features)
- [How It Works](#how-it-works)
- [Compatibility](#compatibility)
- [Installation](#installation)
- [Configuration](#configuration)
- [Admin Dashboard](#admin-dashboard)
- [Email Notifications](#email-notifications)
- [Hyva & Luma Support](#hyva--luma-support)
- [FAQ](#faq)
- [Support](#support)
- [About Panth Infotech](#about-panth-infotech)
- [Quick Links](#quick-links)

---

## Key Features

### Customer-Facing

- **Product page subscribe button** — one-click "Notify Me on Price Drop" call-to-action on every product view page
- **Guest and logged-in support** — guests subscribe with just an email; logged-in customers subscribe with a single click
- **Per-product subscriptions** — customers can watch unlimited SKUs independently
- **Unsubscribe link in every email** — one-click opt-out, fully CAN-SPAM / GDPR compliant
- **My Account integration** — logged-in customers see all their active alerts in the My Account area
- **Configurable placement** — show button on product view page, product listing, or both

### Price Monitoring

- **Cron-based price tracking** — scheduled cron job snapshots prices and detects drops automatically
- **Detects every price type** — catalog regular price, special price, catalog price rules, tier pricing, group pricing
- **Per-store scope** — multi-store and multi-currency aware, each store view tracked independently
- **Configurable threshold** — fire alerts only when drop exceeds a minimum percentage or amount
- **Throttling** — prevents spamming customers with repeated alerts on the same SKU

### Automated Email Notifications

- **Native transactional email** — uses Magento's built-in email_templates.xml system, fully customizable
- **Pre-designed responsive template** — mobile-friendly HTML email with product image, old price, new price, savings, and CTA
- **Sender and reply-to configurable** — use any of Magento's standard sender identities
- **Localized** — translation-ready for multi-language stores

### Admin Dashboard

- **Subscriptions grid** — filter, sort, and search every active subscription by SKU, email, customer, or date
- **Alert history** — log of every notification sent, with delivery timestamps
- **Bulk actions** — manually unsubscribe, resend, or mark as notified
- **Product-level view** — see all subscribers for any given product from the admin product edit screen
- **CSV export** — export subscriptions and alert history for analytics or CRM sync

### Developer-Friendly

- **Full UI component admin grids** — modern Magento 2 UI component architecture
- **Declarative db_schema.xml** — Magento 2.4+ native schema management
- **Events and plugins** — extend alert behavior without editing core files
- **MEQP compliant** — passes Adobe's Magento Extension Quality Program

---

## How It Works

1. **Customer visits product page** — sees a "Notify Me on Price Drop" button below the price
2. **Customer subscribes** — enters email (or is auto-filled if logged in) and clicks subscribe
3. **Subscription stored** — module records customer, SKU, baseline price, store view, and timestamp
4. **Cron job runs** — on your configured schedule (e.g. hourly), the price-check cron compares current prices against stored baselines
5. **Price drop detected** — when the new price is lower (and exceeds any configured threshold), an email is queued
6. **Customer receives email** — beautifully formatted email with old price, new price, savings, product image, and CTA
7. **One-click unsubscribe** — every email contains a secure unsubscribe link
8. **Admin oversight** — merchants can view, filter, export, and manage every subscription from the admin dashboard

---

## Compatibility

| Requirement | Versions Supported |
|---|---|
| Magento Open Source | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce | 2.4.4, 2.4.5, 2.4.6, 2.4.7, 2.4.8 |
| Adobe Commerce Cloud | 2.4.4 — 2.4.8 |
| PHP | 8.1.x, 8.2.x, 8.3.x, 8.4.x |
| MySQL | 8.0+ |
| MariaDB | 10.4+ |
| Hyva Theme | 1.0+ (supported) |
| Luma Theme | Native support |
| Required Dependency | [Panth_Core](https://packagist.org/packages/mage2kishan/module-core) (free) |

---

## Installation

### Composer Installation (Recommended)

```bash
composer require mage2kishan/module-price-drop-alert
bin/magento module:enable Panth_Core Panth_PriceDropAlert
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual Installation via ZIP

1. Download the latest release ZIP from [Packagist](https://packagist.org/packages/mage2kishan/module-price-drop-alert) or the [Adobe Commerce Marketplace](https://commercemarketplace.adobe.com)
2. Extract to `app/code/Panth/PriceDropAlert/` in your Magento installation
3. Ensure `Panth_Core` is also installed (it is the free required dependency)
4. Run the same commands as above starting from `bin/magento module:enable`

### Verify Installation

```bash
bin/magento module:status Panth_PriceDropAlert
# Expected output: Module is enabled
```

After installation, navigate to:
```
Admin → Stores → Configuration → Panth Extensions → Price Drop Alert
```

---

## Configuration

Settings live at `Stores → Configuration → Panth Extensions → Price Drop Alert`:

### General

| Setting | Default | Description |
|---|---|---|
| Enable Module | Yes | Master toggle. When disabled, the subscribe button is hidden and cron skips processing. |
| Allow Guest Subscriptions | Yes | Let non-logged-in visitors subscribe with just an email. |
| Require Opt-in Confirmation | No | Send a double opt-in confirmation email before activating the subscription. |

### Subscribe Button Placement

| Setting | Default | Description |
|---|---|---|
| Show on Product View Page | Yes | Display button on product detail pages. |
| Show on Product List Page | No | Display button on category and search result listings. |
| Button Label | Notify Me on Price Drop | Customize the CTA text. |
| Button Position | After Price | Choose where the button renders relative to the price block. |

### Price Monitoring

| Setting | Default | Description |
|---|---|---|
| Cron Schedule | Every hour | Standard cron expression for the price-check job. |
| Minimum Drop Percentage | 0 | Only alert when price drops by at least this percentage. |
| Minimum Drop Amount | 0 | Only alert when price drops by at least this absolute amount. |
| Alert Throttle (hours) | 24 | Minimum hours between repeat alerts for the same subscription. |

### Email Notifications

| Setting | Default | Description |
|---|---|---|
| Sender Identity | General Contact | Any of the standard Magento sender identities. |
| Email Template | Default Price Drop Alert | Override with any custom transactional template. |
| Include Product Image | Yes | Embed the product image in the email body. |

---

## Admin Dashboard

The admin menu lives under `Panth Infotech → Price Drop Alert` and provides:

- **Subscriptions Grid** — every active alert subscription, filterable by SKU, email, customer, store, date
- **Alert History Grid** — log of every notification sent, including delivery status and timestamps
- **Bulk Actions** — unsubscribe selected rows, resend alerts, mark as notified
- **Per-Product Tab** — on the admin product edit screen, a "Price Drop Subscribers" tab shows all subscribers for that specific SKU
- **CSV Export** — Magento's native grid export for both grids, ready for CRM or analytics import

---

## Email Notifications

The default email template (`panth_pricedropalert_email_template`) is registered in `etc/email_templates.xml` and can be fully customized via `Marketing → Communications → Email Templates`. Available template variables:

- `{{var customer.name}}` — subscriber name (or "Customer" if guest)
- `{{var product.name}}` — product name
- `{{var product.url}}` — product page URL
- `{{var product.image}}` — product image URL
- `{{var prices.old}}` — original price (formatted)
- `{{var prices.new}}` — new dropped price (formatted)
- `{{var prices.savings}}` — absolute savings (formatted)
- `{{var prices.savings_percent}}` — percentage savings
- `{{var unsubscribe_url}}` — one-click unsubscribe link

---

## Hyva & Luma Support

Panth_Core's theme detection automatically routes to the correct template:

- **Luma** — Knockout.js subscribe form with AJAX submission and native Magento UI components
- **Hyva** — Alpine.js + Tailwind CSS subscribe form, zero Knockout dependency, blazing-fast

Both implementations share the same backend controllers, so behavior is identical regardless of the active theme.

---

## FAQ

### What triggers a price drop alert?

Any reduction in the final displayed price for the store view where the customer subscribed — catalog regular price, special price, catalog price rule discount, tier pricing, or group pricing. The module compares the current final price against the baseline captured at subscription time.

### Does it support configurable and bundle products?

Yes. Subscribers are stored by parent SKU, and price comparison uses the minimum final price across the product's variants.

### Can guests subscribe without creating an account?

Yes, by default. You can disable guest subscriptions in configuration if you require a customer account.

### Will customers get spammed?

No. The alert throttle (default 24 hours) ensures at most one alert per subscription per throttle window. You can also set a minimum drop percentage or amount to avoid trivial price changes triggering alerts.

### Is this compatible with Hyva?

Yes. The module ships with both Luma and Hyva templates, and Panth_Core's theme detection picks the right one automatically.

### Does it work on multi-store / multi-currency?

Yes. Subscriptions are scoped per store view and use the store's configured currency. Each store view is tracked independently.

### How do I unsubscribe a customer manually?

From the admin `Subscriptions` grid, select the rows and use the bulk action "Unsubscribe". Customers can also unsubscribe themselves via the one-click link in any alert email.

### Is the cron job resource-heavy?

No. The cron processes subscriptions in batches and only queries products that currently have active subscribers. On a typical catalog of 10,000 SKUs with 500 active subscriptions, the job completes in under 10 seconds.

---

## Support

| Channel | Contact |
|---|---|
| Email | kishansavaliyakb@gmail.com |
| Website | [kishansavaliya.com](https://kishansavaliya.com) |
| WhatsApp | +91 84012 70422 |
| GitHub Issues | [github.com/mage2sk/module-price-drop-alert/issues](https://github.com/mage2sk/module-price-drop-alert/issues) |
| Upwork (Top Rated Plus) | [Hire Kishan Savaliya](https://www.upwork.com/freelancers/~016dd1767321100e21) |
| Upwork Agency | [Panth Infotech](https://www.upwork.com/agencies/1881421506131960778/) |

Response time: 1-2 business days.

### 💼 Need Custom Magento Development?

Looking for **custom Magento module development**, **Hyva theme customization**, **store migrations**, or **performance optimization**? Get a free quote in 24 hours:

<p align="center">
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/%F0%9F%92%AC%20Get%20a%20Free%20Quote-kishansavaliya.com%2Fget--quote-DC2626?style=for-the-badge" alt="Get a Free Quote" />
  </a>
</p>

<p align="center">
  <a href="https://www.upwork.com/freelancers/~016dd1767321100e21">
    <img src="https://img.shields.io/badge/Hire%20Kishan-Top%20Rated%20Plus-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Hire on Upwork" />
  </a>
  &nbsp;&nbsp;
  <a href="https://www.upwork.com/agencies/1881421506131960778/">
    <img src="https://img.shields.io/badge/Visit-Panth%20Infotech%20Agency-14a800?style=for-the-badge&logo=upwork&logoColor=white" alt="Visit Agency" />
  </a>
  &nbsp;&nbsp;
  <a href="https://kishansavaliya.com">
    <img src="https://img.shields.io/badge/Visit%20Website-kishansavaliya.com-0D9488?style=for-the-badge" alt="Visit Website" />
  </a>
</p>

---

## About Panth Infotech

Built and maintained by **Kishan Savaliya** — [kishansavaliya.com](https://kishansavaliya.com) — a **Top Rated Plus** Magento developer on Upwork with 10+ years of eCommerce experience.

**Panth Infotech** is a Magento 2 development agency specializing in high-quality, security-focused extensions and themes for both Hyva and Luma storefronts. Our extension suite covers SEO, performance, checkout, product presentation, customer engagement, and store management — over 34 modules built to MEQP standards and tested across Magento 2.4.4 to 2.4.8.

Browse the full extension catalog on the [Adobe Commerce Marketplace](https://commercemarketplace.adobe.com) or [Packagist](https://packagist.org/packages/mage2kishan/).

---

## Quick Links

- 🌐 **Website:** [kishansavaliya.com](https://kishansavaliya.com)
- 💬 **Get a Quote:** [kishansavaliya.com/get-quote](https://kishansavaliya.com/get-quote)
- 👨‍💻 **Upwork Profile (Top Rated Plus):** [upwork.com/freelancers/~016dd1767321100e21](https://www.upwork.com/freelancers/~016dd1767321100e21)
- 🏢 **Upwork Agency:** [upwork.com/agencies/1881421506131960778](https://www.upwork.com/agencies/1881421506131960778/)
- 📦 **Packagist:** [packagist.org/packages/mage2kishan/module-price-drop-alert](https://packagist.org/packages/mage2kishan/module-price-drop-alert)
- 🐙 **GitHub:** [github.com/mage2sk/module-price-drop-alert](https://github.com/mage2sk/module-price-drop-alert)
- 🛒 **Adobe Marketplace:** [commercemarketplace.adobe.com](https://commercemarketplace.adobe.com)
- 📧 **Email:** kishansavaliyakb@gmail.com
- 📱 **WhatsApp:** +91 84012 70422

---

<p align="center">
  <strong>Ready to recover lost sales with automated price drop alerts?</strong><br/>
  <a href="https://kishansavaliya.com/get-quote">
    <img src="https://img.shields.io/badge/%F0%9F%9A%80%20Get%20Started%20%E2%86%92-Free%20Quote%20in%2024h-DC2626?style=for-the-badge" alt="Get Started" />
  </a>
</p>

---

**SEO Keywords:** magento 2 price alert, price drop notification, price tracking, sale alerts, magento email alerts, magento 2 price drop alert, magento 2 notify me on price drop, magento price drop email, magento 2 price monitoring, magento 2 wishlist price alert, magento 2 customer re-engagement, cart abandonment price alert, magento cron price check, magento 2 transactional email price drop, hyva price drop alert, luma price drop alert, magento 2.4.8 price alert module, php 8.4 magento price alert, panth price drop alert, panth infotech, mage2kishan, mage2sk, magento 2 subscription alerts, magento 2 back in stock vs price drop, magento 2 catalog price rule alert, magento 2 special price notification, magento 2 tier price alert, magento 2 conversion optimization, hire magento developer, top rated plus upwork magento, adobe commerce price alert, magento marketplace price drop
