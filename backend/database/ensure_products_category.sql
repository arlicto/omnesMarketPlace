-- Run once: ensure products.category exists for rare / high-end / regular.
-- If you see "Duplicate column", your table already has `category` — skip this file.

ALTER TABLE products ADD COLUMN category VARCHAR(50) NOT NULL DEFAULT 'regular';

-- Optional: put old free-text categories into "regular"
UPDATE products SET category = 'regular' WHERE category NOT IN ('rare', 'high-end', 'regular');
