CREATE TABLE `roles` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(50),
  `description` varchar(255)
);

CREATE TABLE `users` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(100),
  `cpf` varchar(14) UNIQUE,
  `whatsapp` VARCHAR(100) UNIQUE,
  `email` varchar(100) UNIQUE,
  `password` varchar(255),
  `photo_url` varchar(255),
  `role_id` int,
  `created_at` datetime DEFAULT (CURRENT_TIMESTAMP),
  `updated_at` datetime DEFAULT (CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
);

CREATE TABLE `vehicles` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `plate_number` varchar(20) UNIQUE,
  `model` varchar(100),
  `status` enum(ativo,inativo,manutenção) DEFAULT 'ativo',
  `created_at` datetime DEFAULT (CURRENT_TIMESTAMP),
  `updated_at` datetime DEFAULT (CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
);

CREATE TABLE `stores` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `name` varchar(100),
  `address` varchar(255),
  `city` varchar(100),
  `state` varchar(50),
  `maps_url` varchar(255),
  `created_at` datetime DEFAULT (CURRENT_TIMESTAMP),
  `updated_at` datetime DEFAULT (CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
);

CREATE TABLE `tasks` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `title` varchar(100),
  `description` text,
  `priority` enum(normal,importante,urgente) DEFAULT 'normal',
  `status` enum(em_espera,em_processo,concluida) DEFAULT 'em_espera',
  `store_id` int,
  `created_by` int,
  `created_at` datetime DEFAULT (CURRENT_TIMESTAMP),
  `updated_at` datetime DEFAULT (CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
);

CREATE TABLE `routes` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `driver_id` int,
  `assistant_id` int,
  `vehicle_id` int,
  `store_id` int,
  `date` date,
  `priority` enum(normal,importante,urgente) DEFAULT 'normal',
  `status` enum(em_espera,em_processo,concluida) DEFAULT 'em_espera',
  `created_by` int,
  `created_at` datetime DEFAULT (CURRENT_TIMESTAMP),
  `updated_at` datetime DEFAULT (CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
);

CREATE TABLE `notifications` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `user_id` int,
  `message` varchar(255),
  `is_read` boolean DEFAULT false,
  `created_at` datetime DEFAULT (CURRENT_TIMESTAMP)
);

ALTER TABLE `users` ADD FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

ALTER TABLE `tasks` ADD FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`);

ALTER TABLE `tasks` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `routes` ADD FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`);

ALTER TABLE `routes` ADD FOREIGN KEY (`assistant_id`) REFERENCES `users` (`id`);

ALTER TABLE `routes` ADD FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

ALTER TABLE `routes` ADD FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`);

ALTER TABLE `routes` ADD FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

ALTER TABLE `notifications` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
