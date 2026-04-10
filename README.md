# Omnes MarketPlace - Role Run Guide

This file explains how to run and test the project for all 3 roles:
- Admin
- Buyer
- Seller

Everything is kept simple for this beginner project.

---

## 1) Start Project (one-time setup)

### Start services
```bash
sudo systemctl start apache2
sudo systemctl start mysql
```

### Put project in Apache folder
```bash
sudo cp -r "/home/kushal/Desktop/Omnes MarketPlace" "/var/www/html/Omnes MarketPlace"
```

### Confirm database exists
```bash
mysql -u root -e "USE omnes_marketplace; SHOW TABLES;"
```

### Open website
- Home: `http://localhost/Omnes%20MarketPlace/frontend/index.html`

---

## 2) Common Test Accounts

These sample users must exist in your MySQL database:

- Admin:
  - Email: `admin@omnes.com`
  - Password: `123456`
- Buyer:
  - Email: `buyer@omnes.com`
  - Password: `123456`

Seller account is not in sample users by default, so create one:
- Register a new user in:
  - `http://localhost/Omnes%20MarketPlace/frontend/register.html`
  - choose role: `seller`

---

## 3) Admin Platform Flow


Admin can add products and manage catalog (basic level in this project).

### Steps
1. Login:
   - `http://localhost/Omnes%20MarketPlace/frontend/login.html`
   - Use admin credentials.
2. Open add product page:
   - `http://localhost/Omnes%20MarketPlace/frontend/add_product.html`
3. Fill product fields and click **Save Product**.
4. Verify in browse page:
   - `http://localhost/Omnes%20MarketPlace/frontend/browse.html`

### Admin PHP terminal commands

Use these commands from terminal for admin tasks.

#### Add a product from terminal (calls PHP backend)
```bash
curl -X POST http://localhost/Omnes%20MarketPlace/backend/add_product.php \
  -d "name=Gaming Keyboard" \
  -d "description=RGB keyboard with basic features" \
  -d "price=39.99" \
  -d "image=https://via.placeholder.com/300x200?text=Gaming+Keyboard" \
  -d "category=Electronics" \
  -d "sale_type=buy_now"
```

#### Check all products in database
```bash
mysql -u root -e "USE omnes_marketplace; SELECT id, name, price, sale_type FROM products ORDER BY id DESC;"
```

#### Count total users and products
```bash
mysql -u root -e "USE omnes_marketplace; SELECT COUNT(*) AS total_users FROM users; SELECT COUNT(*) AS total_products FROM products;"
```

#### See latest negotiations (admin monitor)
```bash
mysql -u root -e "USE omnes_marketplace; SELECT id, product_id, buyer_id, seller_id, offer_price, status, round FROM negotiations ORDER BY id DESC LIMIT 10;"
```

---

## 4) Buyer Platform Flow

Buyer can browse, add to cart, buy, and send negotiation offers.

### Steps
1. Login as buyer:
   - `buyer@omnes.com` / `123456`
2. Browse products:
   - `http://localhost/Omnes%20MarketPlace/frontend/browse.html`
3. Click **Add to Cart**.
4. Open cart:
   - `http://localhost/Omnes%20MarketPlace/frontend/cart.html`
5. Click **Buy Now** (demo only, no real payment).

### Buyer negotiation flow
1. Open product page:
   - Example: `http://localhost/Omnes%20MarketPlace/frontend/product.html?id=3`
2. Enter offer price in **Enter your offer price**.
3. Click **Send Offer**.
4. Check status, round count, and last offer on same page.

---

## 5) Seller Platform Flow

Seller responds to buyer negotiation.

### Steps
1. Register/Login as seller.
2. Open seller negotiation view:
   - Example: `http://localhost/Omnes%20MarketPlace/frontend/product.html?id=3&role=seller`
3. You can click:
   - **Accept**
   - **Reject**
   - **Counter** (enter new counter price first)

### Rules (already implemented)
- Max 5 rounds.
- If accepted: message shows **Deal completed**.
- If rejected: message shows **Negotiation failed**.
- Buyer is expected to buy after accepted (demo message only).

---

## 6) Useful Pages

- Home: `http://localhost/Omnes%20MarketPlace/frontend/index.html`
- Browse: `http://localhost/Omnes%20MarketPlace/frontend/browse.html`
- Product: `http://localhost/Omnes%20MarketPlace/frontend/product.html?id=3`
- Cart: `http://localhost/Omnes%20MarketPlace/frontend/cart.html`
- Login: `http://localhost/Omnes%20MarketPlace/frontend/login.html`
- Register: `http://localhost/Omnes%20MarketPlace/frontend/register.html`
- Add Product: `http://localhost/Omnes%20MarketPlace/frontend/add_product.html`

---

## 7) If changes are not showing

Hard refresh browser:
- `Ctrl + Shift + R`

Or copy latest files again:
```bash
sudo cp -r "/home/kushal/Desktop/Omnes MarketPlace/frontend" "/var/www/html/Omnes MarketPlace/"
sudo cp -r "/home/kushal/Desktop/Omnes MarketPlace/backend" "/var/www/html/Omnes MarketPlace/"
```
