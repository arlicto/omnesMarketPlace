-- Simple "notify me" saved searches (run once on your MySQL database)
-- If you get a FOREIGN KEY error, delete the CONSTRAINT line below and run again.
CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  keyword VARCHAR(255) NOT NULL,
  message VARCHAR(500) NOT NULL,
  INDEX idx_notifications_user (user_id),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
