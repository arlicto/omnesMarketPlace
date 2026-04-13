# Omnes MarketPlace

Omnes MarketPlace is a lightweight PHP + MySQL marketplace demo with three roles:

- **Admin**: manage sellers and add products
- **Seller**: list products and manage seller profile appearance
- **Buyer**: browse products, purchase, bid (auction), and negotiate

## Requirements

- **Linux**
- **Apache 2**
- **MySQL / MariaDB**
- **PHP** (CLI + Apache module)

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
