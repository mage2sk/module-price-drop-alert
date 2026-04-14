# Panth Price Drop Alert -- User Guide

This guide walks a Magento store administrator through every screen
and setting of the Panth Price Drop Alert extension. No coding required.

---

## Table of contents

1. [Installation](#1-installation)
2. [Verifying the extension is active](#2-verifying-the-extension-is-active)
3. [Configuration](#3-configuration)
4. [The Dashboard](#4-the-dashboard)
5. [Managing Alerts](#5-managing-alerts)
6. [Viewing an Alert](#6-viewing-an-alert)
7. [How customers subscribe](#7-how-customers-subscribe)
8. [Automated notifications](#8-automated-notifications)
9. [Email template customization](#9-email-template-customization)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Installation

### Composer (recommended)

```bash
composer require mage2kishan/module-price-drop-alert
bin/magento module:enable Panth_Core Panth_PriceDropAlert
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Manual (zip upload)

1. Download the extension zip from the Marketplace.
2. Extract to `app/code/Panth/PriceDropAlert`.
3. Make sure `app/code/Panth/Core` is also installed.
4. Run the CLI commands above starting from `module:enable`.

---

## 2. Verifying the extension is active

```bash
bin/magento module:status Panth_PriceDropAlert
# Module is enabled
```

You should also see the **Price Drop Alert** section under
**Stores > Configuration > Panth Extensions**.

---

## 3. Configuration

Navigate to **Stores > Configuration > Panth Extensions > Price Drop Alert**.

### General Settings

| Field | Description | Default |
|---|---|---|
| Enable Price Drop Alerts | Master on/off switch. When enabled, the subscription form appears on product pages. | No |
| Allow Guest Subscriptions | When Yes, visitors who are not logged in can subscribe by entering their name and email. When No, only registered customers can subscribe. | Yes |

### Email Notifications

| Field | Description | Default |
|---|---|---|
| Email Sender Identity | Which store email identity sends the price drop emails (e.g., General Contact, Sales Representative). | General Contact |
| Email Template | The transactional email template used for notifications. Customizable under Marketing > Email Templates. | Default |

### Price Check Schedule

| Field | Description | Default |
|---|---|---|
| Check Frequency (hours) | How often the cron job checks product prices against active alerts. Lower = faster notifications but more server load. | 24 |

### Design & Colors

Colors for the Price Drop Alert component are managed centrally in your
theme configuration file. The exact file path and CSS variable section
are shown in the admin panel under this group.

---

## 4. The Dashboard

Navigate to **Stores > Panth Infotech > Price Drop Alerts > Dashboard**.

The dashboard provides an at-a-glance overview:

- **KPI cards** -- Total alerts, Active, Sent, Cancelled, Today's
  alerts, and Average target price
- **Alert Trends chart** -- a 7-day bar chart showing new
  subscriptions per day (powered by Chart.js)
- **Most Wanted Products** -- a ranked table of products with the most
  active alert subscriptions
- **Recent Alert Activity** -- a feed of the latest alerts with
  status, email, product, target price, and creation date

Click **View All Alerts** to go to the full management grid.

---

## 5. Managing Alerts

Navigate to **Stores > Panth Infotech > Price Drop Alerts > Manage Alerts**.

This is a standard Magento UI component grid with:

- **Columns**: Alert ID, Customer Name, Email, Product Name, Target
  Price, Current Price, Status, Created At, Sent At
- **Filters and sorting** on every column
- **Mass actions**: Delete selected, Send Email to selected
- **Per-row actions**: View, Delete, Send Email

### Statuses

| Status | Meaning |
|---|---|
| Active/Pending | The customer is waiting for a price drop |
| Sent/Notified | The notification email has been sent |
| Cancelled | The subscription was cancelled by the customer |

---

## 6. Viewing an Alert

Click **View** on any row in the grid to see the full alert detail page.

The detail page shows:

**Subscription Details** -- Alert ID, status (with colour badge),
customer name, email, customer type (registered or guest), subscribed
date, email sent date.

**Product & Price Details** -- Product name (linked to the catalog
editor), SKU, product type, price when subscribed, target price (or
"Any price drop"), and current price.

**Status messages** -- If the target price has been reached, a green
success message appears with a suggestion to send the email. If the
price has not dropped enough yet, an info message shows how much more
the price needs to decrease.

**Action buttons** -- Back, Send Email, Delete.

---

## 7. How customers subscribe

### On Hyva themes

An Alpine.js-powered card appears on product pages (after the price
by default). The card:

1. Checks the customer's subscription status via AJAX
2. If not subscribed, shows a form with:
   - Name and Email fields (for guests)
   - Current price display
   - Optional target price field
   - "Notify Me When Price Drops" button
3. If already subscribed, shows a compact "Price Alert Active" card
   with the current price, target price, and a "Remove" button

### On Luma themes

A vanilla JavaScript version of the same card is used, providing
identical functionality without requiring Alpine.js.

### Guest vs. logged-in

- **Logged-in customers**: name and email are auto-detected from
  section data (FPC compatible). Only the target price field is shown.
- **Guest visitors**: must enter their name and email address (if
  guest subscriptions are enabled in the configuration).

---

## 8. Automated notifications

Notifications are sent in two ways:

### Cron job (scheduled)

A cron job runs at the configured frequency (default: every 24 hours)
and checks all active alerts:

- If a target price is set: sends the email when the current price
  is at or below the target
- If no target price: sends the email when the current price is lower
  than the price at subscription time (any drop)

The cron uses the smart PriceResolver which correctly handles Simple,
Configurable, Bundle, and Grouped product types.

### Product save observer (immediate)

When an admin saves a product with a price change (price, special
price, or special price dates), the observer immediately checks all
active alerts for that product and sends notifications if thresholds
are met. This means customers do not have to wait for the next cron
cycle.

For simple products that are children of configurables, the observer
also checks alerts on the parent configurable product.

---

## 9. Email template customization

The default email template is `price_alert.html` located at
`view/frontend/email/price_alert.html`.

To customize:

1. Go to **Marketing > Email Templates > Add New Template**
2. Load the "Price Drop Alert" template
3. Edit as needed
4. Save and select the new template in the extension configuration

Template variables available:

| Variable | Description |
|---|---|
| `customer_name` | Subscriber's name |
| `product_name` | Product name |
| `product_url` | Direct link to the product page |
| `product_price` | Current price (formatted) |
| `old_price` | Price when subscribed (formatted) |
| `new_price` | Current price (formatted) |
| `discount_percent` | Percentage discount |
| `store` | Store object |

---

## 10. Troubleshooting

### The subscription form does not appear on product pages

1. Verify the module is enabled: `bin/magento module:status Panth_PriceDropAlert`
2. Check the configuration: Stores > Configuration > Panth Extensions >
   Price Drop Alert > General > Enable Price Drop Alerts = Yes
3. Clear all caches: `bin/magento cache:flush`
4. If using Varnish, purge the Varnish cache as well

### Emails are not being sent

1. Check that the cron is running: `bin/magento cron:run`
2. Verify the email sender identity is configured correctly
3. Check `var/log/system.log` for "PriceDropAlert" log entries
4. Verify your SMTP / mail transport is working (try sending a test
   order confirmation email)

### Guest subscription form does not show name/email fields

Check that **Allow Guest Subscriptions** is set to Yes in the
configuration.

### Price not updating for configurable products

The PriceResolver fetches the minimum child price. Make sure the
child simple products are enabled and have valid prices.

---

## Support

| Channel | Contact |
|---|---|
| Email | kishansavaliyakb@gmail.com |
| Website | https://kishansavaliya.com |
| WhatsApp | +91 84012 70422 |

Response time: 1-2 business days for paid licenses.
