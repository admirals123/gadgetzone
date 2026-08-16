### GadgetZone Admin Dashboard — Build Prompt

Build a fully functional, modern admin dashboard for the **GadgetZone** e-commerce platform using PHP, MySQL, JavaScript, and custom CSS.

---

### 🎨 DESIGN AESTHETIC & THEME

* **Background**: Very dark slate/navy theme (`#0d0e12` page, `#161722` surface cards, `#1c1d2c` inputs/tables)
* **Accent Colors**: Amber/Gold (`#f59e0b`) for active links and primary buttons, Teal (`#06b6d4`) for `NEW` badges, Coral/Red (`#ef4444`) for `HOT` badges, Orange (`#f97316`) for `SALE` badges
* **Typography**: IBM Plex Sans for headings, DM Sans for body text
* **Layout**: Fixed left sidebar (260px) + main content area with sticky top bar
* **UI Elements**: Rounded thumbnail previews, pill badges, inline search/filter bars, interactive modal dialogs for CRUD actions

---

### 📁 FILE STRUCTURE

```text
gadgetzone/
├── includes/
│   ├── db.php             # MySQLi connection ($conn) & session startup
│   ├── currency.php       # Multi-currency config & formatPrice() helper
│   └── functions.php      # Auth verification, data sanitization, formatting
└── admin/
    ├── index.php          # Dashboard overview with KPI stats & recent orders
    ├── products.php       # Product CRUD management (Add/Edit/Delete/Toggle Featured)
    ├── orders.php         # Order list & status update modal
    ├── users.php          # User list & role management (member, admin, super_admin)
    ├── settings.php       # Active currency switcher & Stripe API credentials
    ├── layout.php         # Shared sidebar nav, top bar header, and authorization check
    ├── footer.php         # Closing HTML tags + admin.js script inclusion
    ├── admin.css          # Admin-specific dark theme stylesheet
    ├── admin.js           # AJAX modal handlers, image preview, sidebar toggle
    └── uploads/           # Upload directory for newly added product images

```

---

### 🗄️ DATABASE TABLES USED

```sql
-- Users & Roles
users -> id, first_name, last_name, email, password, phone, address, city, avatar, role, created_at

-- Product Catalog
products -> id, category_id, name, slug, description, price, old_price, stock, badge, featured, image_url, created_at
categories -> id, name, slug, icon

-- Sales & Orders
orders -> id, user_id, order_number, total_amount, status, payment_method, shipping_address, notes, stripe_session_id, payment_status, created_at
order_items -> id, order_id, product_id, quantity, price

-- Global Settings
settings -> id, setting_key, setting_value, updated_at

```

---

### 🔒 AUTHENTICATION & ACCESS CONTROL

* `admin/layout.php` must run an authorization check at the top:
* Check if `$_SESSION['user_id']` exists and user `role` is `IN ('admin', 'super_admin')`.
* If unauthorized, redirect immediately to `/gadget/pages/login.php`.


* Display user avatar circle (or 2-letter initials fallback like `SA` for Super Admin) with role label in both sidebar bottom and top header bar.

---

### 📄 PAGES & MODULE SPECIFICATIONS

#### 1. SHARED LAYOUT (`admin/layout.php` & `admin/footer.php`)

* **Sidebar Navigation**:
* Top: Brand logo `⚡ GadgetZone` with subtext `ADMIN PANEL`.
* Navigation Links:
* 📊 `Dashboard` (`index.php`)
* 📦 `Products` (`products.php`)
* 📑 `Orders` (`orders.php`)
* 👥 `Users` (`users.php`)
* ⚙️ `Settings` (`settings.php`)
* 🌐 `View Store` (links directly to main storefront `/gadget/index.php`)


* Bottom Profile Strip: User initials, Name, Role tag, and Logout button.


* **Top Header Bar**:
* Page title indicator, notification bell icon, user avatar pill.



---

#### 2. DASHBOARD OVERVIEW (`admin/index.php`)

* **KPI Stat Cards Grid** (4 columns):
1. Total Revenue (sum of `total_amount` for paid/delivered orders)
2. Total Orders (count of all records in `orders`)
3. Total Products (count in `products`)
4. Total Customers (count in `users` where `role = 'member'`)


* **Recent Orders Table**:
* Displays the latest 10 orders with columns: `Order #`, `Customer`, `Total`, `Payment Method`, `Status` (color-coded badge), `Date`, `Action`.


* **Quick Stats Summary**:
* Low stock alerts (products with `stock < 5`).



---

#### 3. PRODUCT MANAGEMENT (`admin/products.php`)

* **Header Controls**:
* Subtitle showing total product count (e.g., `15 products total`).
* Search Bar (`Search products...`) + Category Filter Dropdown + `Filter` Button.
* Primary Amber Button: `+ Add Product` (opens modal).


* **Data Table**:
* Columns: `IMAGE` (50x50 rounded thumbnail), `NAME`, `CATEGORY`, `PRICE` (formatted), `OLD PRICE`, `STOCK`, `BADGE` (colored pill: `NEW`, `HOT`, `SALE`), `FEATURED` (gold star toggle icon), `ACTIONS` (Edit, Delete).


* **Product Add/Edit Modal**:
* Fields: Product Name, Category dropdown, Price, Old Price, Stock, Badge selection (`None`, `NEW`, `HOT`, `SALE`), Featured checkbox (`1`/`0`), Description, and Image Upload field with live image preview.
* Server handles image upload to `admin/uploads/` and slug auto-generation.



---

#### 4. ORDER MANAGEMENT (`admin/orders.php`)

* **Filter Bar**: Filter by status (`All`, `Pending`, `Processing`, `Shipped`, `Delivered`, `Cancelled`) and payment status (`Unpaid`, `Paid`).
* **Order List Table**:
* Columns: `ORDER #`, `CUSTOMER`, `ITEMS COUNT`, `TOTAL AMOUNT`, `PAYMENT METHOD`, `PAYMENT STATUS`, `ORDER STATUS`, `DATE`, `ACTIONS`.


* **Order Details & Status Update Modal**:
* Shows full shipping address, customer phone/email, itemized product list with quantities.
* Dropdown to update order status (`pending` ➔ `processing` ➔ `shipped` ➔ `delivered` ➔ `cancelled`).
* Dropdown/Toggle to update `payment_status` (`unpaid`, `paid`, `refunded`).



---

#### 5. USER MANAGEMENT (`admin/users.php`)

* **User Data Table**:
* Columns: `AVATAR`, `NAME`, `EMAIL`, `PHONE`, `CITY`, `ROLE` (`member`, `admin`, `super_admin`), `JOINED DATE`, `ACTIONS`.


* **Role Update Action**:
* Super Admin can change user roles via dynamic dropdown or modal. Prevent demoting the last `super_admin`.



---

#### 6. SETTINGS (`admin/settings.php`)

* **Multi-Currency Configuration Grid**:
* Interactive grid of supported currencies (`BDT`, `USD`, `EUR`, `GBP`, `CAD`, `INR`, etc.).
* Selecting a currency updates `setting_value` for `active_currency` in `settings` table.


* **Stripe API Credentials Card**:
* Inputs for `stripe_publishable_key`, `stripe_secret_key`, and `stripe_webhook_secret`.
* Save button updates records in the `settings` table.



---

### ⚡ JAVASCRIPT & MODAL INTERACTION (`admin.js`)

* Dynamic modal open/close controls for Add/Edit Product and Order Status updates.
* Instant file preview when selecting a new product image file.
* AJAX deletion confirmation dialogs (`Are you sure you want to delete this product?`).
* Dynamic slug generation as the user types a new product name.