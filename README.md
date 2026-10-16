# Restaurant Management System (Restaurant Pro)
> **Final-Year Project**: *“Design and Development of a Web-Based Restaurant Management System Using PHP and MySQL”*

[![Live Demo](https://img.shields.io/badge/Live%20Demo-Netlify-00C7B7?style=for-the-badge&logo=netlify&logoColor=white)](https://restaurationsystem.netlify.app/)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](LICENSE)

---

## 🌐 Live Web Application & Deployment

The application is deployed and accessible online:

* **🚀 Production Live URL**: **[https://restaurationsystem.netlify.app/](https://restaurationsystem.netlify.app/)**
* **📦 GitHub Repository**: **[https://github.com/Dhanin12345/Restaurant-Management-System](https://github.com/Dhanin12345/Restaurant-Management-System)**

### Dual-Architecture Deployment Model:
1. **Cloud Interactive Showcase (Netlify)**:
   - Hosted at [https://restaurationsystem.netlify.app/](https://restaurationsystem.netlify.app/).
   - Global CDN delivery with zero server latency.
   - Includes interactive Digital Menu with dietary filters, real-time floating cart with discount coupons (`WELCOME10`), 4-step smart table reservation simulator with AI seating fit engine, 6-role workstation inspection modal, and the **5-Phase Data Flow Player**.
2. **Enterprise Full-Stack Backend (PHP 8 + MariaDB/MySQL)**:
   - Runs locally via XAMPP/WAMP or on cloud container platforms (Docker, Railway, Render, VPS).
   - Features real-time Kitchen Display System (KDS) streaming, automated recipe Bill of Materials (BOM) inventory deductions, multi-tender cashier billing, thermal receipt generator, and cryptographic audit logs.

---

## 🚀 Key Highlights & Advanced Features

### 1. Step-by-Step Data Flow Simulator & Workflow Player
- **Interactive 5-Phase Pipeline**: Visual simulator demonstrating how data flows across the entire ecosystem in real time:
  1. **Phase 1: Order Ingestion & POS**: Customer/waiter places order; real-time validation, subtotal calculation, coupon processing (`WELCOME10`), and database write to `orders` and `order_items`.
  2. **Phase 2: Kitchen Display System (KDS)**: Instant streaming pickup via AJAX; Web Audio chime fires; ticket moves through Kanban states (`Pending` $\rightarrow$ `Cooking` $\rightarrow$ `Ready at Pass`).
  3. **Phase 3: Floor Service & Table State**: Waitstaff runner alerted; table status switches to `Occupied`; food delivered and marked `Served`.
  4. **Phase 4: Cashier Desk & Billing**: Bill generated with tip calculation (0%, 10%, 15%, 20%); multi-tender settlement (*Cash, Card, UPI/Mobile*); fiscal invoice generated.
  5. **Phase 5: Automated Inventory BOM & Audit Trail**: Automated recipe deduction from raw ingredient stock in `ingredients`; immutable ledger entry written to `stock_transactions` and `audit_logs`.
- **Live SQL Query Inspection**: View exact underlying SQL queries executed at every step of the lifecycle.

### 2. Real-Time Kitchen Display System (KDS)
- **Zero-Refresh Pipeline**: Streaming AJAX polling (`api/orders.php?action=kitchen_stream`) checks every 5 seconds without reloading the page.
- **Audio Chime Alerts**: Synthesized sound alert via HTML5 Web Audio API triggers immediately whenever a new order is received.
- **Kanban Workflow**:
  $$\text{Order Created} \longrightarrow \text{Pending} \longrightarrow \text{Kitchen Accepted} \longrightarrow \text{Preparing} \longrightarrow \text{Ready at Pass} \longrightarrow \text{Served} \longrightarrow \text{Completed}$$

### 3. Automatic Recipe-Based Inventory Deduction
- Every dish is mapped to a **Bill of Materials (BOM)** in `menu_ingredients`.
- When an order is placed:
  - Required quantities of each raw ingredient are automatically subtracted from `ingredients.current_stock`.
  - Immutable audit record is created in `stock_transactions` (type: `'consumption'`).
  - If $\text{Current Stock} \le \text{Reorder Level}$, the system automatically dispatches a `low_stock` alert notification to the Admin/Manager.

### 4. Smart Table Allocation Engine
- **Capacity Fit Algorithm**: Filters tables where $\text{Seating Capacity} \ge \text{Party Size}$ and ranks by smallest excess seating to prevent capacity wastage.
- **Conflict Detection**: Automatically verifies that no reservation overlaps within a $\pm 2\text{ hour}$ dining window.
- **AI Recommendation Badge**: Displays optimal recommendation (e.g. *“Table T-03 (Indoor Ground) — Perfect Fit”*) directly on the reservation form.

### 5. Enterprise Security & Audit System
- **Authentication**: Bcrypt hashing via `password_hash()` and `password_verify()`.
- **Session Protection**: `session_regenerate_id(true)` prevents session fixation attacks; HTTP-only cookies prevent XSS theft.
- **CSRF Tokens**: All POST actions are protected with cryptographic CSRF tokens.
- **Role-Based Access Control (RBAC)**: Strict role guards protect Admin, Manager, Waiter, Kitchen, Cashier, and Customer endpoints.
- **Forensic Audit Log**: Automatically captures User ID, Action, Entity, Previous State (Old Value JSON), Committed State (New Value JSON), IP Address, and Timestamp.

### 6. Business Intelligence & Analytics
- Today's, Weekly, and Lifetime Revenue.
- Average Order Value (AOV), Total Orders, and Table Occupancy Rate.
- Interactive **Chart.js** visualizations:
  - Weekly 7-Day Revenue Velocity.
  - Peak Dining Traffic Curve (Order velocity by hour).
  - Category Revenue Share (Doughnut chart).
- **1-Click CSV Export**: Download complete transaction datasets for spreadsheet analysis.

---

## 👥 Default Test Accounts (1-Click Login Ready)

| Role | Email | Password | Primary Interface |
|---|---|---|---|
| **Admin** | `admin@restaurant.com` | `Admin@123` | Executive Dashboard, Menu, Inventory BOM, Reports, Audit Logs |
| **Manager** | `manager@restaurant.com` | `Manager@123` | Service Checklist, Live Orders, Staff Roster, Daily Reports |
| **Waiter** | `waiter@restaurant.com` | `Waiter@123` | Floor Station, Interactive Tables, Touch POS Order Entry |
| **Kitchen** | `kitchen@restaurant.com` | `Kitchen@123` | Zero-Refresh KDS Board, Active Cooking Queue, Audio Chime |
| **Cashier** | `cashier@restaurant.com` | `Cashier@123` | Checkout Terminal, Tip Calculator, Multiple Tenders, Printable Receipt |
| **Customer** | `customer@restaurant.com` | `Customer@123` | Digital Menu with Dietary Filters, Smart Table Reservation, Order Tracker |

> **Pro Tip**: Use the **Quick Role Switcher** banner at the top of any page to switch between accounts in one click during evaluation.

---

## 📁 Project Directory Structure

```
restaurant-management-system/
│
├── config/
│   └── database.php             # Secure PDO connection, queries, audit & notification helpers
│
├── auth/
│   ├── login.php                # Split-screen SaaS sign-in with 1-click test credentials
│   ├── logout.php               # Secure session termination and audit logging
│   └── register.php             # Customer signup with validation and auto-login
│
├── admin/
│   ├── dashboard.php            # Executive Control Tower (KPIs, Charts, Live Pipeline, Audits)
│   ├── users.php                # Staff & user management, role assignment
│   ├── menu.php                 # Dish catalog, pricing, dietary flags, availability
│   ├── categories.php           # Category organizer
│   ├── tables.php               # Interactive dining room floorplan by zones
│   ├── reservations.php         # Reservation bookings with Smart Table Allocation
│   ├── orders.php               # Order operations hub with status filtering
│   ├── inventory.php            # Raw stock levels, recipe BOM explorer, restock orders
│   ├── payments.php             # Financial ledger, tender method breakdown
│   ├── reports.php              # Analytics charts (Chart.js) and CSV dataset export
│   ├── audit_logs.php           # Enterprise activity audit trail
│   └── settings.php             # Enterprise settings, tax engine, operating hours & KDS polling
│
├── manager/
│   ├── dashboard.php            # Operations command center & daily checklist
│   ├── orders.php               # Live orders tracking
│   ├── inventory.php            # Stock health & replenishment
│   ├── staff.php                # Staff roster
│   └── reports.php              # Daily financial reconciliation
│
├── waiter/
│   ├── dashboard.php            # Station overview and food runner pickup alerts
│   ├── tables.php               # Interactive floorplan
│   ├── create_order.php         # Touch-friendly POS order builder with live cart
│   └── orders.php               # Active service tickets and mark-served action
│
├── kitchen/
│   ├── dashboard.php            # Kitchen Display System (KDS) 3-column Kanban with audio chime
│   └── kitchen_orders.php       # Tabular cooking queue with customer prep instructions
│
├── cashier/
│   ├── dashboard.php            # Register station & shift summary
│   ├── billing.php              # Checkout terminal, tip calculator, and printable receipt
│   └── payments.php             # Payment ledger & reconciliation
│
├── customer/
│   ├── menu.php                 # Digital menu with dietary filters, calories, prep times
│   ├── qr_menu.php              # Direct table-linked QR ordering (e.g. ?table=T-01)
│   ├── reservation.php          # Online table reservation with AI seating fit engine
│   ├── orders.php               # Order history & visual kitchen cooking tracker
│   └── profile.php              # Customer settings & password management
│
├── api/
│   ├── orders.php               # REST API: create order with recipe inventory deduction, KDS stream
│   ├── reservations.php         # REST API: smart table allocation algorithm & conflict checks
│   ├── inventory.php            # REST API: stock replenishment & adjustment
│   ├── dashboard.php            # REST API: telemetry sync for live counters
│   └── notifications.php        # REST API: in-app notification dispatcher
│
├── assets/
│   ├── css/
│   │   └── style.css            # Custom SaaS Design System (obsidian dark, glassmorphism)
│   ├── js/
│   │   ├── dashboard.js         # Real-time counter polling
│   │   ├── orders.js            # POS cart, quantity increment/decrement, coupon logic
│   │   ├── kitchen.js           # Zero-refresh KDS stream & Web Audio alert synthesizer
│   │   ├── reservations.js      # Smart table recommendation AJAX evaluator
│   │   └── workflow_player.js   # 5-phase interactive workflow simulator logic
│   └── images/                  # High-resolution culinary photography for all menu items
│
├── includes/
│   ├── header.php               # Shared HTML header, fonts, icons, quick role switcher
│   ├── sidebar.php              # Role-aware navigation menu
│   ├── navbar.php               # Topbar with live notifications, date, and user menu
│   ├── footer.php               # Footer, Bootstrap 5.3 bundle, Web Audio chime
│   ├── auth_check.php           # Session guard, CSRF generation & verification
│   ├── role_check.php           # Role-Based Access Control enforcement & redirects
│   └── workflow_player_modal.php# Interactive data flow simulator modal component
│
├── database/
│   └── restaurant_db.sql        # 19 relational tables with foreign keys & seed data
│
├── index.html                   # Static Cloud SPA deployment entry for Netlify (https://restaurationsystem.netlify.app/)
├── index.php                    # Full-stack PHP application landing page
├── netlify.toml                 # Netlify build and routing configuration
├── _redirects                   # Netlify SPA routing rules
├── start_server.bat             # 1-Click automated Windows startup script (MySQL + PHP server)
└── README.md                    # Project documentation
```

---

## 🧭 Step-by-Step System Testing & Evaluation Guide

Follow this guided walkthrough to test every feature in sequence without any errors:

### Step 1: Customer Online Ordering & Cart
1. Open **[http://localhost:8000/customer/menu.php](http://localhost:8000/customer/menu.php)** (or online at **[https://restaurationsystem.netlify.app/#menu](https://restaurationsystem.netlify.app/#menu)**).
2. Filter dishes by category (*Starters, Gourmet Burgers, Pizza, Pastas, Steaks, Drinks*).
3. Toggle dietary filters (*Vegetarian, Vegan, Gluten-Free*).
4. Click **`+ Add to Order`** on any dish to place it into the dynamic cart.
5. In the cart drawer, test incrementing/decrementing quantities, special notes, and enter coupon code **`WELCOME10`** (10% discount).
6. Click **`Place Online Order`** &rarr; Order ticket is immediately generated, recipe ingredients are deducted from raw stock, and the order is sent to the kitchen.

### Step 2: Table-Side QR Ordering
1. Open **[http://localhost:8000/customer/qr_menu.php?table=T-01](http://localhost:8000/customer/qr_menu.php?table=T-01)**.
2. The page binds directly to Table T-01.
3. Add dishes and place order &rarr; Kitchen receives a table ticket tagged for Table T-01.

### Step 3: Kitchen Display System (KDS)
1. Switch to **Kitchen** via the top switcher bar (or login as `kitchen@restaurant.com`).
2. Open **`kitchen/dashboard.php`**.
3. View the live 3-column Kanban board:
   - **Pending Acceptance**: Click **`Start Cooking`** &rarr; moves to *Under Active Cooking*.
   - **Under Active Cooking**: Click **`Mark Ready (Pass)`** &rarr; moves to *Ready at the Pass* with an audible Web Audio chime alert for the waitstaff runner.

### Step 4: Waitstaff Floor Station & POS
1. Switch to **Waiter** (`waiter@restaurant.com`).
2. Open **`waiter/tables.php`** to view the live floor plan (Occupied, Available, Reserved).
3. Open **`waiter/create_order.php`** to use the touch POS station for table-side ordering.
4. Open **`waiter/orders.php`** to mark dishes as served once delivered to tables.

### Step 5: Cashier Desk & Billing
1. Switch to **Cashier** (`cashier@restaurant.com`).
2. Open **`cashier/billing.php`**.
3. Select an open order ticket.
4. Select tip percentage (0%, 10%, 15%, 20%) and payment tender (*Cash, Credit Card, Mobile Pay*).
5. Click **`Process Payment / Settle Bill`** &rarr; Displays a thermal-ready receipt modal with print trigger.

### Step 6: Manager Operations
1. Switch to **Manager** (`manager@restaurant.com`).
2. Open **`manager/dashboard.php`** to view operational KPIs, live stock health, and shift checklists.
3. Open **`manager/inventory.php`** to review ingredient stock levels and place restock orders.

### Step 7: Executive Admin Control & Settings
1. Switch to **Admin** (`admin@restaurant.com`).
2. Open **`admin/dashboard.php`** for revenue analytics, Chart.js trends, and audit feeds.
3. Open **`admin/menu.php`** to add dishes with image previews, preparation times, and recipe ingredients.
4. Open **`admin/settings.php`** to configure trading name, public contact, tax percent, service charge, operating hours, and automatic recipe BOM deduction.
5. Open **`admin/audit_logs.php`** to inspect the audit log tracking all actions across all users.

---

## 🛠 Installation & Local Setup

### ⚡ Option A: 1-Click Startup (Windows)
Double-click [`start_server.bat`](file:///d:/Restaurant%20Management%20System/start_server.bat) in the project folder. It will:
1. Verify XAMPP installation (`C:\xampp\`).
2. Automatically start MySQL/MariaDB server if not already running.
3. Launch the PHP 8 development server on `http://localhost:8000`.
4. Open your default web browser to the application landing page.

### 🛠 Option B: Manual Setup

#### 1. Requirements
- **PHP 8.0+** (with `pdo_mysql`, `session`, `json`, `mbstring` extensions enabled)
- **MySQL 5.7+ / MariaDB 10.4+** (XAMPP, WAMP, or standalone)
- Any modern web browser (Chrome, Firefox, Edge, Safari)

#### 2. Import Database
1. Start MySQL/MariaDB in your environment (e.g. via XAMPP Control Panel).
2. Create the database:
   ```sql
   CREATE DATABASE restaurant_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import the schema and seed data from `database/restaurant_db.sql`:
   ```bash
   mysql -u root -p restaurant_db < database/restaurant_db.sql
   ```

#### 3. Run Application
Run using PHP built-in web server:
```bash
php -S localhost:8000
```
Open **[http://localhost:8000](http://localhost:8000)** in your browser!

---

## ☁️ Deployment Guide

### Deploying to Netlify
1. Fork or push this repository to GitHub: `https://github.com/Dhanin12345/Restaurant-Management-System`.
2. Log into [Netlify](https://app.netlify.com/).
3. Click **Add new site** &rarr; **Import an existing project** &rarr; Select **GitHub**.
4. Choose the `Restaurant-Management-System` repository.
5. Set:
   - **Publish directory**: `.` (Root directory)
   - **Build command**: *(leave empty)*
6. Click **Deploy Site**.
7. Netlify will instantly deploy your site with SSL at: **[https://restaurationsystem.netlify.app/](https://restaurationsystem.netlify.app/)**.

---

## 📄 License
This project is open-source under the [MIT License](LICENSE). Built for academic and commercial restaurant operational excellence.
