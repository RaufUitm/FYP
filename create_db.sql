-- create_db.sql: Creates the database and required tables for the Fault Prediction System

CREATE DATABASE IF NOT EXISTS fault_prediction_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE fault_prediction_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  username VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ux_users_email (email),
  UNIQUE KEY ux_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Prediction history table
CREATE TABLE IF NOT EXISTS prediction_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  file_name VARCHAR(255) DEFAULT NULL,
  results_data LONGTEXT NOT NULL,
  total_predictions INT DEFAULT NULL,
  faulty_count INT DEFAULT NULL,
  healthy_count INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ux_user_file (user_id, file_name),
  INDEX idx_user_id (user_id),
  CONSTRAINT fk_prediction_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
