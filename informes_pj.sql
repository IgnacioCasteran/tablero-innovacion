-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-08-2025 a las 14:06:10
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
-- Base de datos: `informes_pj`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos`
--

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eventos`
--

INSERT INTO `eventos` (`id`, `titulo`, `descripcion`, `fecha`) VALUES
(57, 'Reunión con el Ministro', 'Por zoom', '2025-08-26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `informes`
--

CREATE TABLE `informes` (
  `id` int(11) NOT NULL,
  `circunscripcion` varchar(50) DEFAULT NULL,
  `oficina_judicial` varchar(100) DEFAULT NULL,
  `responsable` varchar(100) DEFAULT NULL,
  `desde` date DEFAULT NULL,
  `hasta` date DEFAULT NULL,
  `rubro` varchar(100) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `empleado` varchar(100) DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `informes`
--

INSERT INTO `informes` (`id`, `circunscripcion`, `oficina_judicial`, `responsable`, `desde`, `hasta`, `rubro`, `categoria`, `empleado`, `estado`, `descripcion`, `observaciones`, `fecha_creacion`) VALUES
(5, 'II', 'Oficina Judicial Penal', 'Ignacio', '2025-07-07', '2025-07-11', 'Recursos Humanos', 'Otras', 'Juan', 'En proceso', 'Prueba', 'Pruebita', '2025-07-08 11:31:59'),
(15, 'II', 'Oficina Judicial Penal', 'Santiago', '2025-07-07', '2025-07-12', 'Sistemas', 'Recursos Humanos', 'Maximiliano', 'Finalizado', 'probando modificado', 'prueba', '2025-07-08 13:11:52'),
(16, 'I', 'Oficina Judicial Penal', 'Juan Pérez', '2025-07-07', '2025-07-11', 'Sistemas', 'Soporte', 'Laura Gómez', 'Finalizado', 'Revisión de incidencias técnicas del mes', 'Se resolvieron todos los casos', '2025-07-08 14:05:15'),
(17, 'IV', 'Judicial Penal', 'Ana Martínez', '2025-07-14', '2025-07-18', 'Recursos Humanos', 'Actualizaciones', 'Pedro Ramírez', 'En proceso', 'Actualización de legajos de empleados', 'Sin observaciones', '2025-07-08 14:08:27'),
(20, 'IV', 'Judicial Penal', 'Nico', '2025-06-30', '2025-07-04', 'OTRAS', 'PRUEBA', 'Juan', 'Inicial', 'sfd', 'sfd', '2025-07-17 14:41:27'),
(22, 'I', 'Oficina de Gestión Judicial de Familia', 'Nacho', '2025-08-11', '2025-08-22', 'Probando', 'Sistemas', 'Juan', 'Finalizado', 'fsdfsdf', 'sfdsdfsdf', '2025-08-20 13:37:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proyectos`
--

CREATE TABLE `proyectos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `responsable` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `estado` enum('En curso','Finalizado','Pendiente') NOT NULL,
  `fecha` date NOT NULL,
  `ficha` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proyectos`
--

INSERT INTO `proyectos` (`id`, `titulo`, `responsable`, `descripcion`, `estado`, `fecha`, `ficha`) VALUES
(7, 'Automatización de Turnos', 'Carla Muñoz', 'Implementación de un sistema para solicitar y asignar turnos online a través de la página oficial.', 'Pendiente', '2025-07-07', NULL),
(8, 'Portal de Capacitación Interna', 'Belén Torres', 'Creación de un portal digital con cursos, manuales y tutoriales para el personal judicial.', 'Finalizado', '2025-05-05', 'ficha_688a1d87b9186.docx'),
(25, 'Prueba ', 'Ignacio', 'Probando sección ', 'En curso', '2025-07-30', 'ficha_688a1daf32f0e.docx'),
(26, 'Probando Proyecto', 'Ignacio', 'Prueba descripción ', 'En curso', '2025-07-31', 'ficha_688b709697ca5.docx');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reuniones_actividades`
--

CREATE TABLE `reuniones_actividades` (
  `id` int(11) NOT NULL,
  `tipo` enum('proyecto','reunion') NOT NULL,
  `tarea` text NOT NULL,
  `estado` varchar(100) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `asistentes` text DEFAULT NULL,
  `archivo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `reuniones_actividades`
--

INSERT INTO `reuniones_actividades` (`id`, `tipo`, `tarea`, `estado`, `notas`, `fecha_inicio`, `fecha_fin`, `asistentes`, `archivo`) VALUES
(4, 'proyecto', 'Proyecto Certificación del recurso (sugió de Marcia)', 'En curso', 'Ya se requirio a Sistemas evaluar posible automatizacion de esa providencia. Ahora no pueden darle prioridad.', '0000-00-00', '0000-00-00', '', NULL),
(6, 'proyecto', 'Proyecto de Creación Oficina de Atención Ciudadana', 'Bloqueada', 'Se hablo con Procuración. ', '2025-04-30', '0000-00-00', '', NULL),
(9, 'proyecto', 'Presentación de Proyecto Grow ( Sala G) a concurso Juslab', 'Completado', 'Marien lo envio. No llego a finalistas.', '0000-00-00', '2025-05-28', '', NULL),
(10, 'proyecto', 'Elaboración de Manuales de Procedimiento', 'No iniciada', 'Próximamente ', '0000-00-00', '0000-00-00', '', NULL),
(11, 'reunion', 'Van a pasar el documeno del proyecto  a la Sec para enviarlo a Mario Acattoli y saber si es viable PROXIMO ENCUENTRO 4 JULIO', 'Proyecto Chat Bot', '', '2025-06-25', '0000-00-00', 'Analia, Ezequiel W, Cecilia S, Carlos, Pame y Erica', NULL),
(13, 'reunion', 'Reunión para conversar  respecto a la intervencion de la Trabajadora Social  en OG Civil', 'OJ Civil', '', '0000-00-00', '0000-00-00', 'Marcia, Luciana, Aylen, Carlos, Erica', NULL),
(14, 'reunion', 'Zoom con Coordinadoras', 'Otros', '', '2025-06-25', '0000-00-00', 'Celeste, Marcia, Carlos y Etel', NULL),
(15, 'reunion', 'Reunión para definir el plan de digitalización de expedientes de Ejecución CyQ', 'Ejecución CyQ', '', '2025-07-04', '0000-00-00', 'Cecilia Saenz , Hugo Cuñado, Carlos y Erica', 'reunion_688b6db9b67da.docx'),
(17, 'reunion', 'Probando', 'OJ Penal', 'Probando', '2025-08-06', '0000-00-00', 'Ignacio', 'reunion_68935887d901c.docx');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`) VALUES
(1, 'Administrador', 'admin@pjlp.com', '$2y$10$rHchOEvbnexUOp7udcI/U.HScpshQAMiFkPlFYjFp1ij0AnOUwE06'),
(2, 'Secretaría de Innovación', 'secteclapampa@gmail.com', '$2y$10$wvLJTbLvpAJXFewCWckMieoWWZahfCoR1bWj3T5I9FGpt/6Fa1/ku');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `eventos`
--
ALTER TABLE `eventos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `informes`
--
ALTER TABLE `informes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `reuniones_actividades`
--
ALTER TABLE `reuniones_actividades`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `eventos`
--
ALTER TABLE `eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `informes`
--
ALTER TABLE `informes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `proyectos`
--
ALTER TABLE `proyectos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `reuniones_actividades`
--
ALTER TABLE `reuniones_actividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
