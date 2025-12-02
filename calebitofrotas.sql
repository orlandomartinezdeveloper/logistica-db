-- ===========================================================
-- BANCO DE DADOS: Calebito Gestão Logística
-- ===========================================================

CREATE DATABASE IF NOT EXISTS calebito_logistica;
USE calebito_logistica;

-- ===========================================================
-- TABELA: employees (motoristas, ajudantes, gestores, admins)
-- ===========================================================

CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(150),
    role ENUM('motorista', 'ajudante', 'gestor', 'administrador') NOT NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ===========================================================
-- TABELA: vehicles
-- ===========================================================

CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plate VARCHAR(20) NOT NULL UNIQUE,
    model VARCHAR(100),
    brand VARCHAR(100),
    year INT,
    km_atual INT DEFAULT 0,
    status ENUM('ativo', 'manutencao', 'inativo') DEFAULT 'ativo',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ===========================================================
-- TABELA: tasks (tarefas)
-- ===========================================================

CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pendente', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'pendente',
    priority ENUM('baixa', 'media', 'alta') DEFAULT 'media',
    vehicle_id INT,
    created_by INT NOT NULL,         -- quem criou a tarefa
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (created_by) REFERENCES employees(id)
);

-- ===========================================================
-- TABELA: task_assignments 
-- (motoristas + ajudantes no mesmo campo)
-- ===========================================================

CREATE TABLE task_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    employee_id INT NOT NULL,
    role ENUM('motorista', 'ajudante') NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- ===========================================================
-- TABELA: km_logs (registro de quilometragem)
-- ===========================================================

CREATE TABLE km_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    km_anterior INT NOT NULL,
    km_atual INT NOT NULL,
    data_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    registrado_por INT NOT NULL,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (registrado_por) REFERENCES employees(id)
);

-- ===========================================================
-- TABELA: maintenance (manutenções preventivas e corretivas)
-- ===========================================================

CREATE TABLE maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    tipo ENUM('troca_oleo', 'filtro', 'correia_dentada', 'freios', 'embreagem', 'outro') NOT NULL,
    km_previsto INT,
    km_realizado INT,
    custo DECIMAL(10,2),
    observacoes TEXT,
    status ENUM('pendente', 'realizado') DEFAULT 'pendente',
    data_prevista DATE,
    data_realizada DATE,
    registrado_por INT NOT NULL,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (registrado_por) REFERENCES employees(id)
);

-- ===========================================================
-- TABELA: task_files (arquivos, fotos, comprovantes)
-- ===========================================================

CREATE TABLE task_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id),
    FOREIGN KEY (uploaded_by) REFERENCES employees(id)
);

-- ===========================================================
-- TABELA: notifications (alertas do sistema)
-- ===========================================================

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('nova', 'lida') DEFAULT 'nova',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);
