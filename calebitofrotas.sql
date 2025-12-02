SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ===========================================
-- ROLES
-- ===========================================
CREATE TABLE roles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50),
  description VARCHAR(255)
) ENGINE=InnoDB;

-- ===========================================
-- USERS
-- ===========================================
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100),
  cpf VARCHAR(14) UNIQUE,
  whatsapp VARCHAR(100) UNIQUE,
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  photo_url VARCHAR(255),
  role_id INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===========================================
-- VEHICLES
-- ===========================================
CREATE TABLE vehicles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  plate_number VARCHAR(20) UNIQUE,
  model VARCHAR(100),
  status ENUM('ativo','inativo','manutenção') DEFAULT 'ativo',
  current_km BIGINT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===========================================
-- STORES (Lojas próprias)
-- ===========================================
CREATE TABLE stores (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100),
  address VARCHAR(255),
  city VARCHAR(100),
  state VARCHAR(50),
  maps_url VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===========================================
-- DESTINATIONS
-- ===========================================
CREATE TABLE destinations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(150),
  address VARCHAR(255),
  city VARCHAR(100),
  state VARCHAR(50),
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),
  type ENUM('store','client','depot','other') DEFAULT 'other',
  maps_url VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===========================================
-- TASKS
-- ===========================================
CREATE TABLE tasks (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(100),
  description TEXT,
  priority ENUM('normal','importante','urgente') DEFAULT 'normal',
  status ENUM('em_espera','em_processo','concluida') DEFAULT 'em_espera',
  store_id INT NULL,
  destination_id INT NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (store_id) REFERENCES stores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (destination_id) REFERENCES destinations(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===========================================
-- ROUTES
-- ===========================================
CREATE TABLE routes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  driver_id INT NULL,
  assistant_id INT NULL,
  vehicle_id INT NULL,
  store_id INT NULL,
  destination_id INT NULL,
  date DATE,
  priority ENUM('normal','importante','urgente') DEFAULT 'normal',
  status ENUM('em_espera','em_processo','concluida') DEFAULT 'em_espera',
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (driver_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (assistant_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (store_id) REFERENCES stores(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (destination_id) REFERENCES destinations(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===========================================
-- VEHICLE USAGES
-- ===========================================
CREATE TABLE vehicle_usages (
  id INT PRIMARY KEY AUTO_INCREMENT,
  vehicle_id INT NULL,
  driver_id INT NULL,
  route_id INT NULL,
  start_km BIGINT,
  end_km BIGINT,
  date DATETIME DEFAULT CURRENT_TIMESTAMP,
  notes VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (driver_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (route_id) REFERENCES routes(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===========================================
-- MAINTENANCE ITEMS
-- ===========================================
CREATE TABLE maintenance_items (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100),
  description VARCHAR(255),
  interval_km INT,
  interval_months INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===========================================
-- VEHICLE MAINTENANCES (Histórico)
-- ===========================================
CREATE TABLE vehicle_maintenances (
  id INT PRIMARY KEY AUTO_INCREMENT,
  vehicle_id INT,
  maintenance_item_id INT,
  performed_at_date DATE,
  performed_at_km BIGINT,
  next_due_km BIGINT,
  next_due_date DATE,
  notes VARCHAR(255),
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (maintenance_item_id) REFERENCES maintenance_items(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===========================================
-- MAINTENANCE ALERTS
-- ===========================================
CREATE TABLE maintenance_alerts (
  id INT PRIMARY KEY AUTO_INCREMENT,
  vehicle_id INT,
  maintenance_item_id INT,
  triggered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  message VARCHAR(255),
  is_resolved BOOLEAN DEFAULT FALSE,
  resolved_at DATETIME NULL,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (maintenance_item_id) REFERENCES maintenance_items(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===========================================
-- NOTIFICATIONS
-- ===========================================
CREATE TABLE notifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NULL,
  message VARCHAR(255),
  is_read BOOLEAN DEFAULT FALSE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===========================================
-- INDEXES (RECOMENDADOS)
-- ===========================================
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_priority ON tasks(priority);
CREATE INDEX idx_routes_date ON routes(date);
CREATE INDEX idx_vehicle_usages_vehicle_id ON vehicle_usages(vehicle_id);
CREATE INDEX idx_vehicle_usages_driver_id ON vehicle_usages(driver_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);

SET FOREIGN_KEY_CHECKS = 1;
