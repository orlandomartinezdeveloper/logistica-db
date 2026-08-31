-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 30-08-2026 a las 17:38:33
-- Versión del servidor: 5.7.44
-- Versión de PHP: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `calebito_transporte_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `destinations`
--

CREATE TABLE `destinations` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `maps_url` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maintenance_alerts`
--

CREATE TABLE `maintenance_alerts` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `maintenance_item_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `triggered_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `is_resolved` tinyint(1) DEFAULT '0',
  `resolved_at` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maintenance_items`
--

CREATE TABLE `maintenance_items` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `interval_km` int(11) DEFAULT NULL,
  `interval_months` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `routes`
--

CREATE TABLE `routes` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `priority` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'em_espera',
  `store_id` int(11) DEFAULT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `route_assignments`
--

CREATE TABLE `route_assignments` (
  `id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(20) NOT NULL,
  `assigned_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `stores`
--

CREATE TABLE `stores` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `maps_url` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text,
  `priority` varchar(20) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'em_espera',
  `store_id` int(11) DEFAULT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `task_assignments`
--

CREATE TABLE `task_assignments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` varchar(20) NOT NULL,
  `assigned_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `lastname` varchar(150) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `address` varchar(500) NOT NULL,
  `cnh` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `birth_day` date NOT NULL,
  `role` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ativo',
  `password_hash` varchar(255) NOT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `lastname`, `username`, `phone`, `cep`, `address`, `cnh`, `email`, `birth_day`, `role`, `status`, `password_hash`, `photo_url`, `created_at`, `updated_at`, `reset_token`, `reset_expires`) VALUES
(1, 'Orlando', 'Martinez', 'orlando', '(22) 99246-3411', '27965-055', 'Avenida Otoniel Gomes Tavares, Bloco 5, Apto 204, São José do Barreto- Macaé/RJ', 'E', 'omjuniormusic@gmail.com', '1982-03-28', 'motorista', 'ativo', '$2y$10$UZ6FBMQ.Qrmr3sykTMV1MubBjbqSD/Fj3u0O32WMTw2tgOsM7h.EW', 'img/users/user_6a83d4fd46df51.46191820.jpg', '2026-04-28 17:08:37', '2026-08-23 22:05:08', '4677b98cbd9912465303cf67f0162f2c0a052425711b77e3d886b07323b53fc0', '2026-05-20 01:12:00'),
(19, 'Leonardo', 'Vinicius Cyriaco', 'leonardo', '(22) 99706-1535', '', 'Parque Aeroporto', 'D', 'cyriacoleonardo53@gmail.com', '1984-11-13', 'motorista', 'ativo', '$2y$10$6cGyQRC.F2K7KI/iCwyLzeoHaEV3x5et3Im6b/PbH3VpgbyYxALkG', 'img/users/user_6a8a03bf69d643.57974274.jpg', '2026-08-21 14:58:18', '2026-08-22 19:42:55', NULL, NULL),
(20, 'Alex', 'Rodrigues Iacomini', 'alex', '(21) 96429-1030', '', 'Macaé', 'B', 'alexiaco@gmail.com', '1979-07-15', 'gestor_logistica', 'ativo', '$2y$10$wJvYWwJo3pu9TbcH5EOkU.2IZkUPvaY99nuWVGwhbTpID6thXGQBW', 'img/users/user_6a8a036e652c46.05667620.jpg', '2026-08-21 15:02:10', '2026-08-22 17:28:37', NULL, NULL),
(13, 'Jose', 'Nunes da Silva Junior', 'junior', '(22) 98834-8527', '', 'Macaé', 'nao_tenho', 'soulkalibourjr@gmail.com', '1979-01-07', 'ajudante', 'ativo', '$2y$10$ArjS05hmply6lMFumOHEluMCNCC59sCuUfV6575hT63QPLX4lsuD2', 'img/users/user_6a851545536308.95660235.jpg', '2026-08-18 23:30:29', '2026-08-19 08:02:58', NULL, NULL),
(15, 'Leilson', 'Silva Gregorio', 'leilson', '(22) 99988-7400', '', 'Lagomar Macaé', 'D', 'netleilson1@gmail.com', '1977-11-19', 'motorista', 'ativo', '$2y$10$Kbz3C5BjIJBcTgo6O4bv9.roHhLUsSqnxLkmXtLVDp8jiKOhEfiFS', 'img/users/user_6a857b6c2d0183.87681421.jpg', '2026-08-19 06:46:20', '2026-08-23 21:57:47', NULL, NULL),
(16, 'Dyego', 'Alves', 'dyego', '(84) 87526-701', '', 'Lagomar', 'B', 'antoniodyego8@gmail.com', '1991-07-11', 'motorista', 'ativo', '$2y$10$cnT3HelbccRa2KPe4fDFv.MuEngl3rHqmj1a2gfvph3DW7tjGxzDe', 'img/users/user_6a8a035d55f020.21665691.jpg', '2026-08-19 06:52:16', '2026-08-22 17:24:18', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `fancy_name` varchar(100) DEFAULT NULL,
  `renavam` varchar(20) DEFAULT NULL,
  `chassis_number` varchar(20) DEFAULT NULL,
  `model` varchar(100) NOT NULL,
  `year_model` varchar(4) DEFAULT NULL,
  `year_manufactured` varchar(4) DEFAULT NULL,
  `fuel` varchar(30) DEFAULT NULL,
  `gross_weight` decimal(10,2) DEFAULT NULL,
  `capacity` decimal(10,2) DEFAULT NULL,
  `species_type` varchar(50) DEFAULT NULL,
  `bodywork` varchar(50) DEFAULT NULL,
  `exercise_year` varchar(4) DEFAULT NULL,
  `owner_document` varchar(20) DEFAULT NULL,
  `owner_name` varchar(150) DEFAULT NULL,
  `power_displacement` varchar(50) DEFAULT NULL,
  `cmt` decimal(10,2) DEFAULT NULL,
  `axles` varchar(10) DEFAULT NULL,
  `occupancy` varchar(20) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ativo',
  `current_km` bigint(20) DEFAULT '0',
  `photo_url` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Volcado de datos para la tabla `vehicles`
--

INSERT INTO `vehicles` (`id`, `plate_number`, `fancy_name`, `renavam`, `chassis_number`, `model`, `year_model`, `year_manufactured`, `fuel`, `gross_weight`, `capacity`, `species_type`, `bodywork`, `exercise_year`, `owner_document`, `owner_name`, `power_displacement`, `cmt`, `axles`, `occupancy`, `status`, `current_km`, `photo_url`, `created_at`, `updated_at`) VALUES
(1, 'LUE6J73', 'Kia Bongo (UK2500)', '01347060666', '9UWSHX76APN035543', 'I/KIA UK2500 HD SC', '2023', '2022', 'DIESEL', 3.47, 1.81, 'CARGA CAMINHONETE', 'CARROCERIA FECHADA', '2026', '18.069.164/0001-30', 'P L S CORPUS BENT C E S DE S ARTESANAIS', '131CV/2497', 4.87, '2', '03P', 'ativo', 150000, 'img/vehicles/vehicle_6a943b3dae1089.83135439.jpg', '2026-08-23 23:01:02', '2026-08-30 17:22:40'),
(2, 'TTG7I66', 'Renault Kangoo', '', '', 'RENAULT KANGOO', '', '', '', NULL, NULL, '', '', '', '', '', '', NULL, '', '', 'ativo', 40000, 'img/vehicles/vehicle_6a943b55aec0d5.45762434.jpg', '2026-08-23 23:17:57', '2026-08-30 17:29:49'),
(3, 'SSD2B95', 'Kia Bongo 2025', '01409707323', '9UWSHX76ASN039852', 'I/KIA UK2500 HD SC 4WD', '2025', '2024', 'Diesel', 3.47, 1.81, 'CARGA CAMINHONETE', 'CARROCERIA FECHADA', '2025', '49.984.157/0001-98', 'CALEBITO I B DE ALIMENTOS LTDA', '130CV/2497', 4.87, '2', '03P', 'ativo', 90000, 'img/vehicles/vehicle_6a943b49377b94.36937861.jpg', '2026-08-25 07:54:38', '2026-08-30 14:33:50'),
(4, 'TTY8C90', 'Caminhão VW Delivery', '01484336647', '9535E6TB2TR033651', 'VW/DELIVERY 11.180', '2026', '2025', 'Diesel', 10.80, 7.26, 'CARGA CAMINHAO', 'CARROCERIA FECHADA', '2026', '49.984.157/0001-98', 'CALEBITO I B DE ALIMENTOS LTDA', '175CV/3800', 13.20, '2', '03P', 'ativo', 30000, 'img/vehicles/vehicle_6a943b621f7295.47818284.jpg', '2026-08-25 22:56:48', '2026-08-30 14:30:23'),
(5, 'LML3H28', 'Chevrolet Montana', '01120996535', '9BGCA8030JB115984', 'CHEVROLET/MONTANA LS2', '2018', '2017', 'GASOLINA/ALCOOL/GAS NATURAL', 1.80, 0.70, 'CARGA CAMINHONETE', 'CARROCERIA ABERTA', '2026', '18.069.164/0001-30', 'PLS CORPUS BENT C E S SORV ARTESANAIS', '99CV/1400', 3.00, '*', '02P', 'ativo', 90000, 'img/vehicles/vehicle_6a943b2ca1a9b5.38181525.jpg', '2026-08-26 21:43:15', '2026-08-30 12:39:13'),
(6, 'LUA5E04', 'Renault Oroch', '01218218557', '93YSR3H5LJ224731', 'RENAULT/OROCH DYN 16 SCE', '2020', '2019', 'GASOLINA/ALCOOL/GAS NATURAL', 1.94, 0.50, 'Especial', 'Aberta', '2026', '18.069.164/0001-30', 'PLS C B S ARTESANAL', '120CV/1599', 2.90, '2', '05P', 'ativo', 60000, 'img/vehicles/vehicle_6a94929f073996.36439212.jpg', '2026-08-30 17:29:19', '2026-08-30 17:29:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehicle_maintenances`
--

CREATE TABLE `vehicle_maintenances` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `maintenance_item_id` int(11) NOT NULL,
  `performed_at_date` date DEFAULT NULL,
  `performed_at_km` bigint(20) DEFAULT NULL,
  `next_due_km` bigint(20) DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehicle_usages`
--

CREATE TABLE `vehicle_usages` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `route_id` int(11) DEFAULT NULL,
  `start_km` bigint(20) NOT NULL,
  `end_km` bigint(20) DEFAULT NULL,
  `date` datetime NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `maintenance_alerts`
--
ALTER TABLE `maintenance_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `maintenance_item_id` (`maintenance_item_id`);

--
-- Indices de la tabla `maintenance_items`
--
ALTER TABLE `maintenance_items`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `store_id` (`store_id`),
  ADD KEY `destination_id` (`destination_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `route_assignments`
--
ALTER TABLE `route_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `stores`
--
ALTER TABLE `stores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `store_id` (`store_id`),
  ADD KEY `destination_id` (`destination_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_number` (`plate_number`);

--
-- Indices de la tabla `vehicle_maintenances`
--
ALTER TABLE `vehicle_maintenances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `maintenance_item_id` (`maintenance_item_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indices de la tabla `vehicle_usages`
--
ALTER TABLE `vehicle_usages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `route_id` (`route_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `maintenance_alerts`
--
ALTER TABLE `maintenance_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `maintenance_items`
--
ALTER TABLE `maintenance_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `routes`
--
ALTER TABLE `routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `route_assignments`
--
ALTER TABLE `route_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `stores`
--
ALTER TABLE `stores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `vehicle_maintenances`
--
ALTER TABLE `vehicle_maintenances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `vehicle_usages`
--
ALTER TABLE `vehicle_usages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
