# ⚡ GadgetZone — Premium Modern E-Commerce Platform

A complete, full-featured, responsive tech gadget e-commerce web application with customer storefront, shopping cart, multi-currency live conversions, dual light/dark theme system, Stripe checkout, and a full administrative management portal.

---

## ✨ Key Features

### 🛍️ Storefront & Customer Experience
- **🎨 Dual Light / Dark Theme**: Instant toggle between sleek Obsidian Dark mode and crisp Light mode with zero-flicker `localStorage` persistence.
- **💱 Live Multi-Currency Converter**: Header dropdown supporting **12 global currencies** (INR `₹`, USD `$`, EUR `€`, GBP `£`, JPY `¥`, CAD `$`, AUD `$`, SGD `$`, AED `د.إ`, SAR `﷼`, BDT `৳`, MYR `RM`) with real-time exchange rates. Base currency is **INR (`₹`)**.
- **⚡ 1-Click "Buy Now" Direct Checkout**: Instant express checkout bypasses cart friction.
- **🏷️ Category Quick-Navigation Sub-Header**: 1-click quick links for *Today's Deals*, *Smartphones*, *Laptops*, *Audio*, *Cameras*, *Wearables*, *Accessories*, etc.
- **🚚 Free Shipping Dynamic Progress Meter**: Visual progress meter that celebrates unlocking free delivery.
- **🛡️ Amazon-Style Trust Badges**: 1-Year Warranty, 7-Day Easy Returns, 24-48h Fast Delivery, 100% Encrypted Checkout.
- **🔔 Animated Toast Alerts**: Instant visual feedback when items are added to the cart.
- **🔥 Deal of the Day**: Real-time interactive countdown timer (HH:MM:SS).
- **💳 Multi-Gateway Checkout**: Stripe (Cards, Apple Pay, Google Pay) and Cash on Delivery (COD).

### 🛠️ Administrative Control Center
- **📊 Executive KPI Dashboard**: Real-time sales metrics, Today's breakdown, fulfillment status pipeline, and low-stock alerts.
- **📦 Inventory & Product Catalog**:
  - Filter tabs (`All`, `Low Stock`, `Featured`, `Out of Stock`).
  - 1-Click **Quick Stock Adjustment Modal** without opening full product forms.
  - Live discount `% OFF` calculator and instant image thumbnail preview.
  - Direct *"View in Shop ↗"* links.
- **🏷️ Category Management (`admin/categories.php`)**:
  - Add, edit, and delete categories with auto-slug generation.
  - 22 preset gadget emoji picker buttons (`🎮`, `📱`, `💻`, `🎧`, `📷`, `⌚`, `🔌`, etc.).
  - Reassignment & delete confirmation safety modals.
- **🧾 Orders & Invoices**: Multi-attribute search, status filter tabs, inline status changer, customer communication links, and printable invoice views.
- **👥 User & Role Management**: Manage customer accounts and administrative staff permissions.
- **⚙️ Storefront Currency Control**: Admin panel to enable/disable specific currencies and configure default storefront currency.

---

## 🚀 Quick Setup & Installation

### Requirements
- **PHP 8.0+** with `mysqli` and `curl` extensions enabled
- **MySQL 5.7+** or MariaDB
- **Apache** (XAMPP / WAMP / LEMP)

### Installation on Localhost (XAMPP)
1. Clone or copy this repository into your web server directory:
   ```bash
   # In c:\xampp\htdocs\Gadgetzone
   git clone https://github.com/admirals123/gadgetzone.git
   ```
2. Import the Database:
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin/`).
   - Go to the **SQL** tab.
   - Import / execute `database_setup.sql`.
3. Verify Database Credentials in `includes/db.php`:
   ```php
   $host = 'localhost';
   $user = 'root';
   $pass = '';
   $dbname = 'gadgetzone';
   ```
4. Access the Website:
   - **Storefront**: [http://localhost/Gadgetzone/index.php](http://localhost/Gadgetzone/index.php)
   - **Admin Portal**: [http://localhost/Gadgetzone/admin/index.php](http://localhost/Gadgetzone/admin/index.php)

---

## 🔐 Default Admin Credentials

- **URL**: `http://localhost/Gadgetzone/admin/index.php`
- **Email**: `admin@gadgetzone.com`
- **Password**: `Admin@1234`

---

## 💳 Stripe Payment Gateway Setup (Optional)
1. Obtain test API keys from your [Stripe Dashboard](https://dashboard.stripe.com/test/apikeys).
2. Log into the Admin Portal → **Settings** (`/admin/settings.php`).
3. Enter your **Stripe Publishable Key** (`pk_test_...`) and **Secret Key** (`sk_test_...`) and save.
4. Test with standard Stripe test cards (e.g. `4242 4242 4242 4242`).

---

## 🛡️ Security & Architecture
- Prepared SQL statements (`mysqli_prepare`) across all queries preventing SQL injection.
- Secure password hashing (`PASSWORD_DEFAULT` bcrypt).
- Context-aware XSS escaping (`htmlspecialchars` via `e()` helper).
- Responsive vanilla CSS design tokens for maximum performance without heavy framework overhead.
