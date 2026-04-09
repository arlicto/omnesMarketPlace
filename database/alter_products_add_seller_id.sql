-- Run once if your `products` table has no seller_id column (needed for negotiation product page).
ALTER TABLE products ADD COLUMN seller_id INT UNSIGNED NULL DEFAULT NULL;
