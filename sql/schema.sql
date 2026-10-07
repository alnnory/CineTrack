-- CineTrack Database Schema
-- Stage 1: Users Table Initialization

CREATE DATABASE IF NOT EXISTS cinetrack_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cinetrack_db;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    date_created DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
