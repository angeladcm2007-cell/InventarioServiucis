-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-11-2026 a las 15:57:02
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `pdvsa_ait`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `activos`
--

CREATE TABLE `activos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `categoria_id` int(10) UNSIGNED NOT NULL,
  `ubicacion_id` bigint(20) UNSIGNED NOT NULL,
  `codigo_placa` varchar(60) DEFAULT NULL,
  `codigo_inventario` varchar(60) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(120) DEFAULT NULL,
  `numero_serial` varchar(150) DEFAULT NULL,
  `estado` enum('OPERATIVO_EN_USO','EN_STOCK','EN_REPARACION','REGULAR','DADO_DE_BAJA_OBSOLETO') NOT NULL DEFAULT 'EN_STOCK',
  `fecha_adquisicion` date DEFAULT NULL,
  `valor_adquisicion` decimal(15,2) DEFAULT NULL,
  `codigo_qr` varchar(120) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `creado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL,
  `fecha_baja` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `UserName` varchar(100) DEFAULT NULL,
  `Password` varchar(100) DEFAULT NULL,
  `updationDate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos_activos`
--

CREATE TABLE `archivos_activos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activo_id` bigint(20) UNSIGNED NOT NULL,
  `mantenimiento_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `ruta_archivo` varchar(500) NOT NULL,
  `tipo_mime` varchar(120) DEFAULT NULL,
  `tamano_bytes` bigint(20) UNSIGNED DEFAULT NULL,
  `cargado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_carga` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones`
--

CREATE TABLE `asignaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activo_id` bigint(20) UNSIGNED NOT NULL,
  `persona_id` bigint(20) UNSIGNED NOT NULL,
  `numero_control` varchar(60) NOT NULL,
  `fecha_asignacion` date NOT NULL,
  `fecha_devolucion` date DEFAULT NULL,
  `estado_asignacion` enum('ACTIVA','CERRADA','ANULADA') NOT NULL DEFAULT 'ACTIVA',
  `descripcion` varchar(255) DEFAULT NULL,
  `acta_entrega` varchar(255) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `registrado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tabla_afectada` varchar(100) NOT NULL,
  `registro_id` bigint(20) UNSIGNED DEFAULT NULL,
  `accion` enum('INSERT','UPDATE','DELETE','LOGIN','LOGOUT','OTRA') NOT NULL,
  `descripcion` text DEFAULT NULL,
  `direccion_ip` varchar(45) DEFAULT NULL,
  `fecha_evento` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_activos`
--

CREATE TABLE `categorias_activos` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(40) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('ACTIVA','INACTIVA') NOT NULL DEFAULT 'ACTIVA'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias_activos`
--

INSERT INTO `categorias_activos` (`id`, `codigo`, `nombre`, `descripcion`, `estado`) VALUES
(1, 'COMPUTO', 'Equipos de computo', 'Computadores de escritorio y portatiles', 'ACTIVA'),
(2, 'IMPRESION', 'Dispositivos de impresion', 'Impresoras y equipos multifuncionales', 'ACTIVA'),
(3, 'PERIFERICO', 'Perifericos', 'Perifericos diferentes de impresoras', 'ACTIVA'),
(4, 'TELEFONIA', 'Telefonia', 'Telefonos, smartphone, router y SIM Card segun formulario', 'ACTIVA'),
(5, 'SERVIDOR', 'Servidores', 'Servidores fisicos', 'ACTIVA'),
(6, 'INFRAESTRUCTURA', 'Infraestructura tecnologica', 'Switches, routers, access points y otros dispositivos', 'ACTIVA');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dispositivos_impresion`
--

CREATE TABLE `dispositivos_impresion` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activo_id` bigint(20) UNSIGNED NOT NULL,
  `tipo_impresora` enum('LASER','INYECCION_TINTA','MATRICIAL','TERMICA','MULTIFUNCIONAL') NOT NULL,
  `direccion_ip` varchar(45) DEFAULT NULL,
  `mac` varchar(50) DEFAULT NULL,
  `fecha_ultimo_cambio_suministro` date DEFAULT NULL,
  `tipo_suministro` varchar(100) DEFAULT NULL,
  `observaciones_suministro` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos_computo`
--

CREATE TABLE `equipos_computo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activo_id` bigint(20) UNSIGNED NOT NULL,
  `tipo_equipo` enum('ESCRITORIO','PORTATIL') NOT NULL,
  `capacidad_disco` varchar(60) DEFAULT NULL,
  `tipo_disco` varchar(50) DEFAULT NULL,
  `capacidad_ram` varchar(60) DEFAULT NULL,
  `procesador` varchar(150) DEFAULT NULL,
  `direccion_ip` varchar(45) DEFAULT NULL,
  `mac` varchar(50) DEFAULT NULL,
  `version_so` varchar(120) DEFAULT NULL,
  `descripcion_equipo` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `infraestructura`
--

CREATE TABLE `infraestructura` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activo_id` bigint(20) UNSIGNED NOT NULL,
  `tipo_dispositivo` varchar(100) NOT NULL,
  `cantidad_puertos` int(10) UNSIGNED DEFAULT NULL,
  `direccion_ip` varchar(45) DEFAULT NULL,
  `mac` varchar(50) DEFAULT NULL,
  `piso` varchar(30) DEFAULT NULL,
  `descripcion_infraestructura` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimientos`
--

CREATE TABLE `mantenimientos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activo_id` bigint(20) UNSIGNED NOT NULL,
  `tipo_mantenimiento` enum('PREVENTIVO','CORRECTIVO','REPARACION') NOT NULL,
  `fecha_programada` date DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_finalizacion` date DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `trabajo_realizado` text DEFAULT NULL,
  `tecnico_responsable` varchar(150) DEFAULT NULL,
  `proveedor` varchar(150) DEFAULT NULL,
  `costo` decimal(15,2) DEFAULT NULL,
  `resultado` enum('PENDIENTE','OPERATIVO','OPERATIVO_CON_OBSERVACIONES','NO_REPARABLE') NOT NULL DEFAULT 'PENDIENTE',
  `proximo_mantenimiento` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `registrado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos_activos`
--

CREATE TABLE `movimientos_activos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activo_id` bigint(20) UNSIGNED NOT NULL,
  `ubicacion_origen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ubicacion_destino_id` bigint(20) UNSIGNED NOT NULL,
  `fecha_movimiento` datetime NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `autorizado_por` varchar(150) DEFAULT NULL,
  `registrado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perifericos`
--

CREATE TABLE `perifericos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activo_id` bigint(20) UNSIGNED NOT NULL,
  `tipo_periferico` varchar(80) NOT NULL,
  `descripcion_periferico` text DEFAULT NULL,
  `equipo_asociado_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personas`
--

CREATE TABLE `personas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombres` varchar(150) NOT NULL,
  `identificacion` varchar(40) DEFAULT NULL,
  `cargo` varchar(120) DEFAULT NULL,
  `area_departamento` varchar(120) DEFAULT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `telefono` varchar(40) DEFAULT NULL,
  `estado` enum('ACTIVA','INACTIVA') NOT NULL DEFAULT 'ACTIVA',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `fecha_creacion`) VALUES
(1, 'Administrador', 'Acceso completo al sistema', '2026-11-06 15:11:52'),
(2, 'Encargado de inventario', 'Gestion de activos, asignaciones, ubicaciones y mantenimientos', '2026-11-06 15:11:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes`
--

CREATE TABLE `sedes` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `estado` enum('ACTIVA','INACTIVA') NOT NULL DEFAULT 'ACTIVA',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sedes`
--

INSERT INTO `sedes` (`id`, `nombre`, `codigo`, `direccion`, `estado`, `fecha_creacion`) VALUES
(1, 'Rionegro', 'RIO', NULL, 'ACTIVA', '2026-11-06 15:11:52'),
(2, 'Apartado', 'APA', NULL, 'ACTIVA', '2026-11-06 15:11:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servidores`
--

CREATE TABLE `servidores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activo_id` bigint(20) UNSIGNED NOT NULL,
  `hostname` varchar(120) DEFAULT NULL,
  `estado_equipo` varchar(80) DEFAULT NULL,
  `piso` varchar(30) DEFAULT NULL,
  `rack_gabinete` varchar(100) DEFAULT NULL,
  `direccion_ip` varchar(45) DEFAULT NULL,
  `procesador` varchar(180) DEFAULT NULL,
  `memoria_ram` varchar(80) DEFAULT NULL,
  `sistema_operativo` varchar(120) DEFAULT NULL,
  `version_so` varchar(100) DEFAULT NULL,
  `aplicaciones_roles` text DEFAULT NULL,
  `descripcion_servidor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servidor_discos`
--

CREATE TABLE `servidor_discos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `servidor_id` bigint(20) UNSIGNED NOT NULL,
  `numero_disco` int(10) UNSIGNED NOT NULL,
  `tipo_disco` varchar(60) DEFAULT NULL,
  `capacidad` varchar(60) NOT NULL,
  `modelo` varchar(120) DEFAULT NULL,
  `numero_serial` varchar(150) DEFAULT NULL,
  `configuracion_raid` varchar(80) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblactivos`
--

CREATE TABLE `tblactivos` (
  `id` int(11) NOT NULL,
  `tipo_activo` varchar(20) NOT NULL,
  `descrip` varchar(100) NOT NULL,
  `estado` varchar(100) DEFAULT NULL,
  `etiqueta` varchar(100) NOT NULL,
  `serial` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `ip_mac` varchar(100) NOT NULL,
  `ubicacion` varchar(100) NOT NULL,
  `Creationdate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblasig`
--

CREATE TABLE `tblasig` (
  `StudentId` int(11) NOT NULL,
  `fecha` varchar(100) DEFAULT NULL,
  `number` varchar(100) DEFAULT NULL,
  `nota` varchar(100) DEFAULT NULL,
  `ClassId` int(11) DEFAULT NULL,
  `usuarioID` int(11) NOT NULL,
  `RegDate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL,
  `Status` int(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblusuarios`
--

CREATE TABLE `tblusuarios` (
  `id` int(11) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `cedula` varchar(100) DEFAULT NULL,
  `indicador` varchar(100) NOT NULL,
  `negocio` varchar(100) NOT NULL,
  `Creationdate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefonia`
--

CREATE TABLE `telefonia` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activo_id` bigint(20) UNSIGNED NOT NULL,
  `numero_extension` varchar(60) DEFAULT NULL,
  `proveedor` varchar(120) DEFAULT NULL,
  `tipo_equipo` enum('SMARTPHONE','TELEFONO_ESCRITORIO','ROUTER','SIM_CARD') NOT NULL,
  `descripcion_telefonia` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicaciones`
--

CREATE TABLE `ubicaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sede_id` int(10) UNSIGNED NOT NULL,
  `tipo_ubicacion` varchar(60) DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `piso` varchar(30) DEFAULT NULL,
  `area_departamento` varchar(120) DEFAULT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `estado` enum('ACTIVA','INACTIVA') NOT NULL DEFAULT 'ACTIVA',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_sistema`
--

CREATE TABLE `usuarios_sistema` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rol_id` int(10) UNSIGNED NOT NULL,
  `nombre_completo` varchar(150) NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `correo` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `ultimo_acceso` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios_sistema`
--

INSERT INTO `usuarios_sistema` (`id`, `rol_id`, `nombre_completo`, `usuario`, `correo`, `password_hash`, `estado`, `ultimo_acceso`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 1, 'Departamento de sistemas', 'Sistemas', NULL, '$2y$10$mqOy7.9H0iN6KJk.HQ9Fp.k6Uas0Nn0HHstjOViNosXYYhHB7Pa7a', 'ACTIVO', '2026-11-06 10:49:11', '2026-11-06 15:48:40', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `activos`
--
ALTER TABLE `activos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_activos_codigo_inventario` (`codigo_inventario`),
  ADD UNIQUE KEY `uk_activos_codigo_qr` (`codigo_qr`),
  ADD KEY `idx_activos_categoria` (`categoria_id`),
  ADD KEY `idx_activos_ubicacion` (`ubicacion_id`),
  ADD KEY `idx_activos_serial` (`numero_serial`),
  ADD KEY `idx_activos_placa` (`codigo_placa`),
  ADD KEY `idx_activos_estado` (`estado`),
  ADD KEY `fk_activos_creado_por` (`creado_por`);

--
-- Indices de la tabla `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `archivos_activos`
--
ALTER TABLE `archivos_activos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_archivos_activo` (`activo_id`),
  ADD KEY `idx_archivos_mantenimiento` (`mantenimiento_id`),
  ADD KEY `fk_archivos_usuario` (`cargado_por`);

--
-- Indices de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_asignaciones_numero_control` (`numero_control`),
  ADD KEY `idx_asignaciones_activo` (`activo_id`),
  ADD KEY `idx_asignaciones_persona` (`persona_id`),
  ADD KEY `idx_asignaciones_estado` (`estado_asignacion`),
  ADD KEY `fk_asignaciones_usuario` (`registrado_por`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auditoria_usuario` (`usuario_id`),
  ADD KEY `idx_auditoria_tabla_registro` (`tabla_afectada`,`registro_id`),
  ADD KEY `idx_auditoria_fecha` (`fecha_evento`);

--
-- Indices de la tabla `categorias_activos`
--
ALTER TABLE `categorias_activos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_categorias_codigo` (`codigo`),
  ADD UNIQUE KEY `uk_categorias_nombre` (`nombre`);

--
-- Indices de la tabla `dispositivos_impresion`
--
ALTER TABLE `dispositivos_impresion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_impresion_activo` (`activo_id`),
  ADD KEY `idx_impresion_ip` (`direccion_ip`);

--
-- Indices de la tabla `equipos_computo`
--
ALTER TABLE `equipos_computo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_computo_activo` (`activo_id`),
  ADD KEY `idx_computo_ip` (`direccion_ip`);

--
-- Indices de la tabla `infraestructura`
--
ALTER TABLE `infraestructura`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_infraestructura_activo` (`activo_id`),
  ADD KEY `idx_infraestructura_ip` (`direccion_ip`);

--
-- Indices de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mantenimientos_activo` (`activo_id`),
  ADD KEY `idx_mantenimientos_fecha` (`fecha_inicio`),
  ADD KEY `idx_mantenimientos_tipo` (`tipo_mantenimiento`),
  ADD KEY `idx_mantenimientos_resultado` (`resultado`),
  ADD KEY `fk_mantenimientos_usuario` (`registrado_por`);

--
-- Indices de la tabla `movimientos_activos`
--
ALTER TABLE `movimientos_activos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_movimientos_activo` (`activo_id`),
  ADD KEY `idx_movimientos_fecha` (`fecha_movimiento`),
  ADD KEY `fk_movimientos_origen` (`ubicacion_origen_id`),
  ADD KEY `fk_movimientos_destino` (`ubicacion_destino_id`),
  ADD KEY `fk_movimientos_usuario` (`registrado_por`);

--
-- Indices de la tabla `perifericos`
--
ALTER TABLE `perifericos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_periferico_activo` (`activo_id`),
  ADD KEY `idx_periferico_equipo_asociado` (`equipo_asociado_id`);

--
-- Indices de la tabla `personas`
--
ALTER TABLE `personas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_personas_identificacion` (`identificacion`),
  ADD KEY `idx_personas_nombres` (`nombres`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_roles_nombre` (`nombre`);

--
-- Indices de la tabla `sedes`
--
ALTER TABLE `sedes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sedes_codigo` (`codigo`),
  ADD UNIQUE KEY `uk_sedes_nombre` (`nombre`);

--
-- Indices de la tabla `servidores`
--
ALTER TABLE `servidores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_servidor_activo` (`activo_id`),
  ADD KEY `idx_servidor_hostname` (`hostname`),
  ADD KEY `idx_servidor_ip` (`direccion_ip`);

--
-- Indices de la tabla `servidor_discos`
--
ALTER TABLE `servidor_discos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_servidor_numero_disco` (`servidor_id`,`numero_disco`),
  ADD KEY `idx_servidor_disco_serial` (`numero_serial`);

--
-- Indices de la tabla `tblactivos`
--
ALTER TABLE `tblactivos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tblasig`
--
ALTER TABLE `tblasig`
  ADD PRIMARY KEY (`StudentId`),
  ADD KEY `ClassId` (`ClassId`),
  ADD KEY `usuarioID` (`usuarioID`);

--
-- Indices de la tabla `tblusuarios`
--
ALTER TABLE `tblusuarios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `telefonia`
--
ALTER TABLE `telefonia`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_telefonia_activo` (`activo_id`),
  ADD KEY `idx_telefonia_numero_extension` (`numero_extension`);

--
-- Indices de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ubicaciones_sede` (`sede_id`),
  ADD KEY `idx_ubicaciones_area` (`area_departamento`);

--
-- Indices de la tabla `usuarios_sistema`
--
ALTER TABLE `usuarios_sistema`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_usuarios_usuario` (`usuario`),
  ADD UNIQUE KEY `uk_usuarios_correo` (`correo`),
  ADD KEY `idx_usuarios_rol` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `activos`
--
ALTER TABLE `activos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `archivos_activos`
--
ALTER TABLE `archivos_activos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias_activos`
--
ALTER TABLE `categorias_activos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `dispositivos_impresion`
--
ALTER TABLE `dispositivos_impresion`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `equipos_computo`
--
ALTER TABLE `equipos_computo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `infraestructura`
--
ALTER TABLE `infraestructura`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimientos_activos`
--
ALTER TABLE `movimientos_activos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `perifericos`
--
ALTER TABLE `perifericos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `personas`
--
ALTER TABLE `personas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `sedes`
--
ALTER TABLE `sedes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `servidores`
--
ALTER TABLE `servidores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `servidor_discos`
--
ALTER TABLE `servidor_discos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tblactivos`
--
ALTER TABLE `tblactivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tblasig`
--
ALTER TABLE `tblasig`
  MODIFY `StudentId` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tblusuarios`
--
ALTER TABLE `tblusuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `telefonia`
--
ALTER TABLE `telefonia`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios_sistema`
--
ALTER TABLE `usuarios_sistema`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `activos`
--
ALTER TABLE `activos`
  ADD CONSTRAINT `fk_activos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_activos` (`id`),
  ADD CONSTRAINT `fk_activos_creado_por` FOREIGN KEY (`creado_por`) REFERENCES `usuarios_sistema` (`id`),
  ADD CONSTRAINT `fk_activos_ubicacion` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`);

--
-- Filtros para la tabla `archivos_activos`
--
ALTER TABLE `archivos_activos`
  ADD CONSTRAINT `fk_archivos_activo` FOREIGN KEY (`activo_id`) REFERENCES `activos` (`id`),
  ADD CONSTRAINT `fk_archivos_mantenimiento` FOREIGN KEY (`mantenimiento_id`) REFERENCES `mantenimientos` (`id`),
  ADD CONSTRAINT `fk_archivos_usuario` FOREIGN KEY (`cargado_por`) REFERENCES `usuarios_sistema` (`id`);

--
-- Filtros para la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD CONSTRAINT `fk_asignaciones_activo` FOREIGN KEY (`activo_id`) REFERENCES `activos` (`id`),
  ADD CONSTRAINT `fk_asignaciones_persona` FOREIGN KEY (`persona_id`) REFERENCES `personas` (`id`),
  ADD CONSTRAINT `fk_asignaciones_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios_sistema` (`id`);

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios_sistema` (`id`);

--
-- Filtros para la tabla `dispositivos_impresion`
--
ALTER TABLE `dispositivos_impresion`
  ADD CONSTRAINT `fk_impresion_activo` FOREIGN KEY (`activo_id`) REFERENCES `activos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `equipos_computo`
--
ALTER TABLE `equipos_computo`
  ADD CONSTRAINT `fk_computo_activo` FOREIGN KEY (`activo_id`) REFERENCES `activos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `infraestructura`
--
ALTER TABLE `infraestructura`
  ADD CONSTRAINT `fk_infraestructura_activo` FOREIGN KEY (`activo_id`) REFERENCES `activos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD CONSTRAINT `fk_mantenimientos_activo` FOREIGN KEY (`activo_id`) REFERENCES `activos` (`id`),
  ADD CONSTRAINT `fk_mantenimientos_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios_sistema` (`id`);

--
-- Filtros para la tabla `movimientos_activos`
--
ALTER TABLE `movimientos_activos`
  ADD CONSTRAINT `fk_movimientos_activo` FOREIGN KEY (`activo_id`) REFERENCES `activos` (`id`),
  ADD CONSTRAINT `fk_movimientos_destino` FOREIGN KEY (`ubicacion_destino_id`) REFERENCES `ubicaciones` (`id`),
  ADD CONSTRAINT `fk_movimientos_origen` FOREIGN KEY (`ubicacion_origen_id`) REFERENCES `ubicaciones` (`id`),
  ADD CONSTRAINT `fk_movimientos_usuario` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios_sistema` (`id`);

--
-- Filtros para la tabla `perifericos`
--
ALTER TABLE `perifericos`
  ADD CONSTRAINT `fk_periferico_activo` FOREIGN KEY (`activo_id`) REFERENCES `activos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_periferico_equipo` FOREIGN KEY (`equipo_asociado_id`) REFERENCES `activos` (`id`);

--
-- Filtros para la tabla `servidores`
--
ALTER TABLE `servidores`
  ADD CONSTRAINT `fk_servidor_activo` FOREIGN KEY (`activo_id`) REFERENCES `activos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `servidor_discos`
--
ALTER TABLE `servidor_discos`
  ADD CONSTRAINT `fk_servidor_discos_servidor` FOREIGN KEY (`servidor_id`) REFERENCES `servidores` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tblasig`
--
ALTER TABLE `tblasig`
  ADD CONSTRAINT `tblasig_ibfk_1` FOREIGN KEY (`ClassId`) REFERENCES `tblactivos` (`id`),
  ADD CONSTRAINT `tblasig_ibfk_2` FOREIGN KEY (`usuarioID`) REFERENCES `tblusuarios` (`id`);

--
-- Filtros para la tabla `telefonia`
--
ALTER TABLE `telefonia`
  ADD CONSTRAINT `fk_telefonia_activo` FOREIGN KEY (`activo_id`) REFERENCES `activos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  ADD CONSTRAINT `fk_ubicaciones_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`);

--
-- Filtros para la tabla `usuarios_sistema`
--
ALTER TABLE `usuarios_sistema`
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
