CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','editor') NOT NULL DEFAULT 'admin',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  description MEDIUMTEXT NULL,
  client VARCHAR(255) NULL,
  art_director VARCHAR(255) NULL,
  designer VARCHAR(255) NULL,
  cover_image VARCHAR(512) NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  sort_order INT NOT NULL DEFAULT 9999,
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_status (status),
  INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_media (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  type ENUM('image','video_local','video_external') NOT NULL,
  path VARCHAR(512) NULL,
  external_url VARCHAR(1024) NULL,
  caption VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 9999,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  slug VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_tags (
  project_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY(project_id, tag_id),
  FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_settings (
  id TINYINT NOT NULL PRIMARY KEY,
  hero_video_path VARCHAR(512) NULL,
  hero_poster_path VARCHAR(512) NULL,
  header_title VARCHAR(255) NULL,
  footer_text VARCHAR(255) NULL,
  email VARCHAR(255) NULL,
  phone VARCHAR(64) NULL,
  socials JSON NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stats_pageviews (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  path VARCHAR(512) NOT NULL,
  project_id INT NULL,
  ip_hash CHAR(64) NOT NULL,
  ua_hash CHAR(64) NOT NULL,
  referrer VARCHAR(512) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  email VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO site_settings (id, hero_video_path, hero_poster_path, header_title, footer_text, email, phone, socials)
VALUES (1, '/movies/hero.mp4', '/images/hero_poster.jpg', 'Архвиз & Реал-тайм', '© Portfolio', 'mail@example.com', '+7 700 000 00 00', JSON_OBJECT());
