# Omnes MarketPlace

Omnes MarketPlace is a lightweight PHP + MySQL marketplace demo with three roles:

- **Admin**: manage sellers and add products
- **Seller**: list products and manage seller profile appearance
- **Buyer**: browse products, purchase, bid (auction), and negotiate

## Requirements

- **Git** (optional, recommended)
- **PHP** (8.x recommended)
- **MySQL / MariaDB**
- A local web server (choose one):
  - **Apache 2**
  - **XAMPP** (Windows/macOS/Linux)
  - **MAMP** (macOS)
  - **Laragon** (Windows)

## Get the code

Clone with Git:

```bash
git clone https://github.com/arlicto/omnesMarketPlace.git
cd omnesMarketPlace
```

Or download the repository as a ZIP from GitHub and extract it.

## Quick start (local via Apache)

1) Start services

```bash
sudo systemctl start apache2
sudo systemctl start mysql
```

2) Copy the project into Apache

From your project folder (the repo root), copy the project into Apache's web directory:

```bash
sudo cp -r "<path-to-your-project>/Omnes MarketPlace" "/var/www/html/Omnes MarketPlace"
```

Notes:

- Replace `<path-to-your-project>` with wherever you cloned/extracted the project on your machine.
- Your Apache web root may be different on non-Ubuntu systems.

3) Confirm the database exists

```bash
mysql -u root -e "USE omnes_marketplace; SHOW TABLES;"
```

4) Open the app

- Home: `http://localhost/Omnes%20MarketPlace/frontend/index.html`

## Running on Windows / macOS

This project is plain PHP + MySQL (no build step). The simplest way is to use a local PHP stack:

- **Windows:** XAMPP or Laragon
- **macOS:** MAMP or XAMPP

High-level steps:

1) Install a stack (Apache + PHP + MySQL).
2) Start **Apache** and **MySQL** from the stack control panel.
3) Create a database named `omnes_marketplace`.
4) Import your SQL/init scripts if you have them, or reuse an existing local DB.
5) Place the project folder into your web root:
   - XAMPP: `htdocs/`
   - MAMP: `htdocs/` (or the configured DocumentRoot)
6) Ensure `backend/db.php` matches your DB credentials.
7) Open the app in your browser (the base URL depends on your stack configuration).

## Test accounts

These users should exist in `omnes_marketplace.users`:

- **Admin**
  - Email: `admin@omnes.com`
  - Password: `123456`
- **Buyer**
  - Email: `buyer@omnes.com`
  - Password: `123456`

## Role notes

### Admin

- Can access:
  - `Manage Sellers` (admin-only UI)
  - `Add Product`

**Manage Sellers (admin-only):**

- Page: `frontend/manage_sellers.html`
- Features:
  - Create seller users
  - Remove seller users

Security:

- The page redirects non-admin users.
- Backend endpoints require an authenticated **admin** session.

### Buyer

- Can browse products and purchase items that are **Buy Now**.
- For products that are not immediate purchase:
  - Auction products show **Bid** (opens product page)
  - Negotiation products show **Negotiate** (opens product page)

### Seller

- Can add products.
- Can configure seller profile background and profile picture via **My Account**.

## Troubleshooting

### Changes not showing

Hard refresh:

- `Ctrl + Shift + R`

Or copy updated files again:

```bash
sudo cp -r "/home/kushal/Desktop/Omnes MarketPlace/frontend" "/var/www/html/Omnes MarketPlace/"
sudo cp -r "/home/kushal/Desktop/Omnes MarketPlace/backend" "/var/www/html/Omnes MarketPlace/"
```

### Database connection

Database config is in:

- `backend/db.php`
