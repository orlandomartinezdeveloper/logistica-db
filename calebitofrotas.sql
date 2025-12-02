SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-----------------------------------------------------------
-- USERS
-----------------------------------------------------------
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(20),
  email VARCHAR(150),
  role ENUM('motorista', 'ajudante', 'gestor', 'administrador') NOT NULL,
  status ENUM('ativo', 'inativo') DEFAULT 'ativo',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-----------------------------------------------------------
-- STORES
-----------------------------------------------------------
CREATE TABLE stores (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  address VARCHAR(255),
  city VARCHAR(100),
  state VARCHAR(50),
  maps_url VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-----------------------------------------------------------
-- DESTINATIONS
-----------------------------------------------------------
CREATE TABLE destinations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
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

-----------------------------------------------------------
-- VEHICLES
-----------------------------------------------------------
CREATE TABLE vehicles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  plate_number VARCHAR(20) UNIQUE NOT NULL,
  model VARCHAR(100),
  status ENUM('ativo','inativo','manutencao') DEFAULT 'ativo',
  current_km BIGINT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-----------------------------------------------------------
-- MAINTENANCE ITEMS
-----------------------------------------------------------
CREATE TABLE maintenance_items (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255),
  interval_km INT,
  interval_months INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-----------------------------------------------------------
-- VEHICLE MAINTENANCES
-----------------------------------------------------------
CREATE TABLE vehicle_maintenances (
  id INT PRIMARY KEY AUTO_INCREMENT,
  vehicle_id INT NOT NULL,
  maintenance_item_id INT NOT NULL,
  performed_at_date DATE,
  performed_at_km BIGINT,
  next_due_km BIGINT,
  next_due_date DATE,
  notes VARCHAR(255),
  created_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
  FOREIGN KEY (maintenance_item_id) REFERENCES maintenance_items(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-----------------------------------------------------------
-- MAINTENANCE ALERTS
-----------------------------------------------------------
CREATE TABLE maintenance_alerts (
  id INT PRIMARY KEY AUTO_INCREMENT,
  vehicle_id INT NOT NULL,
  maintenance_item_id INT NOT NULL,
  triggered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  message VARCHAR(255),
  is_resolved BOOLEAN DEFAULT FALSE,
  resolved_at DATETIME NULL,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
  FOREIGN KEY (maintenance_item_id) REFERENCES maintenance_items(id)
) ENGINE=InnoDB;

-----------------------------------------------------------
-- TASKS
-----------------------------------------------------------
CREATE TABLE tasks (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(100) NOT NULL,
  description TEXT,
  priority ENUM('normal','importante','urgente') DEFAULT 'normal',
  status ENUM('em_espera','em_processo','concluida') DEFAULT 'em_espera',
  store_id INT NULL,
  destination_id INT NULL,
  created_by INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (store_id) REFERENCES stores(id),
  FOREIGN KEY (destination_id) REFERENCES destinations(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-----------------------------------------------------------
-- TASK ASSIGNMENTS (MÚLTIPLES MOTORISTAS + AJUDANTES)
-----------------------------------------------------------
CREATE TABLE task_assignments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  task_id INT NOT NULL,
  user_id INT NOT NULL,
  role ENUM('motorista','ajudante') NOT NULL,
  assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (task_id) REFERENCES tasks(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-----------------------------------------------------------
-- ROUTES
-----------------------------------------------------------
CREATE TABLE routes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  driver_id INT,
  assistant_id INT,
  vehicle_id INT,
  store_id INT,
  destination_id INT,
  date DATE,
  priority ENUM('normal','importante','urgente') DEFAULT 'normal',
  status ENUM('em_espera','em_processo','concluida') DEFAULT 'em_espera',
  created_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (driver_id) REFERENCES users(id),
  FOREIGN KEY (assistant_id) REFERENCES users(id),
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
  FOREIGN KEY (store_id) REFERENCES stores(id),
  FOREIGN KEY (destination_id) REFERENCES destinations(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-----------------------------------------------------------
-- VEHICLE USAGES
-----------------------------------------------------------
CREATE TABLE vehicle_usages (
  id INT PRIMARY KEY AUTO_INCREMENT,
  vehicle_id INT NOT NULL,
  driver_id INT NOT NULL,
  route_id INT,
  start_km BIGINT,
  end_km BIGINT,
  date DATETIME DEFAULT CURRENT_TIMESTAMP,
  notes VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
  FOREIGN KEY (driver_id) REFERENCES users(id),
  FOREIGN KEY (route_id) REFERENCES routes(id)
) ENGINE=InnoDB;

-----------------------------------------------------------
-- NOTIFICATIONS
-----------------------------------------------------------
CREATE TABLE notifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  message VARCHAR(255),
  is_read BOOLEAN DEFAULT FALSE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
