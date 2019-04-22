-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-04-2019 a las 04:02:40
-- Versión del servidor: 10.1.38-MariaDB
-- Versión de PHP: 7.3.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `bemtedb`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumnos`
--

CREATE TABLE `alumnos` (
  `user_id` int(11) NOT NULL,
  `celular` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombres` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apodo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ubicacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ser_profesor` bit(1) DEFAULT NULL,
  `calificacion` decimal(10,2) DEFAULT NULL,
  `activo` bit(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `alumnos`
--

INSERT INTO `alumnos` (`user_id`, `celular`, `correo`, `nombres`, `apellidos`, `apodo`, `ubicacion`, `ciudad`, `ser_profesor`, `calificacion`, `activo`, `created_at`, `updated_at`) VALUES
(3, 'abra', 'asdad@asd.com', 'alum1', 'alummApellido', 'jejejej', 'algun lugar', 'Ibarra', b'0', '4.50', b'1', '2019-04-11 03:25:55', '2019-04-19 00:37:12'),
(4, '1231231', 'manuel3@bemte.com', 'juan', 'fonseca', 'señor x', 'ubicacion', 'Quito', b'0', NULL, b'1', '2019-04-18 20:25:41', '2019-04-18 20:25:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alumno_billetera`
--

CREATE TABLE `alumno_billetera` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `combo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `horas` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT '1',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `order`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 'Category 1', 'category-1', '2019-04-08 23:14:29', '2019-04-08 23:14:29'),
(2, NULL, 1, 'Category 2', 'category-2', '2019-04-08 23:14:29', '2019-04-08 23:14:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ciudad`
--

CREATE TABLE `ciudad` (
  `ciudad` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activa` bit(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ciudad`
--

INSERT INTO `ciudad` (`ciudad`, `activa`, `created_at`, `updated_at`) VALUES
('Ambato', b'1', '2019-04-09 01:04:51', '2019-04-09 01:04:51'),
('Ibarra', b'1', '2019-04-18 00:38:10', '2019-04-18 00:38:10'),
('Quito', b'1', '2019-04-09 01:04:38', '2019-04-09 01:04:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `combos`
--

CREATE TABLE `combos` (
  `nombre` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `beneficios` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` bit(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `combos`
--

INSERT INTO `combos` (`nombre`, `descripcion`, `beneficios`, `activo`, `created_at`, `updated_at`) VALUES
('Elite', 'Combo Élite', '<p>jajajaja</p>', b'1', '2019-04-09 01:08:39', '2019-04-18 00:36:51'),
('Premium', 'Combo Básico', '<p>jojojojo</p>', b'1', '2019-04-09 01:09:02', '2019-04-18 00:36:29'),
('Premium Domicilio', 'Combo Premium', '<p>uuuuuuu</p>', b'1', '2019-04-09 01:09:40', '2019-04-18 00:36:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `combos_horas`
--

CREATE TABLE `combos_horas` (
  `id` int(10) UNSIGNED NOT NULL,
  `combo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hora` int(11) NOT NULL,
  `inversion` decimal(10,2) NOT NULL,
  `descuento` decimal(10,2) NOT NULL,
  `activo` bit(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `combos_horas`
--

INSERT INTO `combos_horas` (`id`, `combo`, `hora`, `inversion`, `descuento`, `activo`, `created_at`, `updated_at`) VALUES
(1, 'Elite', 2, '80.00', '0.00', b'1', '2019-04-17 23:11:21', '2019-04-17 23:29:22'),
(2, 'Elite', 6, '225.00', '0.00', b'1', '2019-04-17 23:31:50', '2019-04-17 23:31:50'),
(3, 'Elite', 10, '350.00', '0.00', b'1', '2019-04-17 23:32:25', '2019-04-17 23:32:25'),
(4, 'Elite', 20, '650.00', '0.00', b'1', '2019-04-17 23:33:13', '2019-04-17 23:33:13'),
(5, 'Elite', 40, '1200.00', '0.00', b'1', '2019-04-17 23:34:39', '2019-04-17 23:34:39'),
(6, 'Premium Domicilio', 2, '40.00', '0.00', b'1', '2019-04-18 00:18:23', '2019-04-18 00:47:17'),
(7, 'Premium Domicilio', 6, '105.00', '0.00', b'1', '2019-04-18 00:19:16', '2019-04-18 00:19:16'),
(8, 'Premium Domicilio', 10, '160.00', '0.00', b'1', '2019-04-18 00:19:44', '2019-04-18 00:19:44'),
(9, 'Premium Domicilio', 20, '300.00', '0.00', b'1', '2019-04-18 00:20:58', '2019-04-18 00:21:28'),
(10, 'Premium Domicilio', 40, '500.00', '0.00', b'1', '2019-04-18 00:22:29', '2019-04-18 00:22:29'),
(11, 'Premium', 2, '30.00', '0.00', b'1', '2019-04-18 00:24:42', '2019-04-18 00:24:42'),
(12, 'Premium', 6, '69.00', '0.00', b'1', '2019-04-18 00:25:14', '2019-04-18 00:25:14'),
(13, 'Premium', 10, '110.00', '0.00', b'1', '2019-04-18 00:26:20', '2019-04-18 00:26:20'),
(14, 'Premium', 20, '210.00', '0.00', b'1', '2019-04-18 00:26:59', '2019-04-18 00:26:59'),
(15, 'Premium', 40, '400.00', '0.00', b'1', '2019-04-18 00:28:06', '2019-04-19 02:04:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `data_rows`
--

CREATE TABLE `data_rows` (
  `id` int(10) UNSIGNED NOT NULL,
  `data_type_id` int(10) UNSIGNED NOT NULL,
  `field` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `browse` tinyint(1) NOT NULL DEFAULT '1',
  `read` tinyint(1) NOT NULL DEFAULT '1',
  `edit` tinyint(1) NOT NULL DEFAULT '1',
  `add` tinyint(1) NOT NULL DEFAULT '1',
  `delete` tinyint(1) NOT NULL DEFAULT '1',
  `details` text COLLATE utf8mb4_unicode_ci,
  `order` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `data_rows`
--

INSERT INTO `data_rows` (`id`, `data_type_id`, `field`, `type`, `display_name`, `required`, `browse`, `read`, `edit`, `add`, `delete`, `details`, `order`) VALUES
(1, 1, 'id', 'number', 'ID', 1, 0, 0, 0, 0, 0, '{}', 1),
(2, 1, 'name', 'text', 'Name', 1, 1, 1, 1, 1, 1, '{}', 2),
(3, 1, 'email', 'text', 'Email', 1, 1, 1, 1, 1, 1, '{}', 3),
(4, 1, 'password', 'password', 'Password', 1, 0, 0, 1, 1, 0, '{}', 4),
(5, 1, 'remember_token', 'text', 'Remember Token', 0, 0, 0, 0, 0, 0, '{}', 5),
(6, 1, 'created_at', 'timestamp', 'Created At', 0, 1, 1, 0, 0, 0, '{}', 6),
(7, 1, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, '{}', 7),
(8, 1, 'avatar', 'image', 'Avatar', 0, 1, 1, 1, 1, 1, '{}', 8),
(9, 1, 'user_belongsto_role_relationship', 'relationship', 'Role', 0, 1, 1, 1, 1, 0, '{\"model\":\"TCG\\\\Voyager\\\\Models\\\\Role\",\"table\":\"roles\",\"type\":\"belongsTo\",\"column\":\"role_id\",\"key\":\"id\",\"label\":\"display_name\",\"pivot_table\":\"roles\",\"pivot\":\"0\",\"taggable\":\"0\"}', 10),
(10, 1, 'user_belongstomany_role_relationship', 'relationship', 'Roles', 0, 1, 1, 1, 1, 0, '{\"model\":\"TCG\\\\Voyager\\\\Models\\\\Role\",\"table\":\"roles\",\"type\":\"belongsToMany\",\"column\":\"id\",\"key\":\"id\",\"label\":\"display_name\",\"pivot_table\":\"user_roles\",\"pivot\":\"1\",\"taggable\":\"0\"}', 11),
(11, 1, 'settings', 'hidden', 'Settings', 0, 0, 0, 0, 0, 0, '{}', 12),
(12, 2, 'id', 'number', 'ID', 1, 0, 0, 0, 0, 0, NULL, 1),
(13, 2, 'name', 'text', 'Name', 1, 1, 1, 1, 1, 1, NULL, 2),
(14, 2, 'created_at', 'timestamp', 'Created At', 0, 0, 0, 0, 0, 0, NULL, 3),
(15, 2, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, NULL, 4),
(16, 3, 'id', 'number', 'ID', 1, 0, 0, 0, 0, 0, NULL, 1),
(17, 3, 'name', 'text', 'Name', 1, 1, 1, 1, 1, 1, NULL, 2),
(18, 3, 'created_at', 'timestamp', 'Created At', 0, 0, 0, 0, 0, 0, NULL, 3),
(19, 3, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, NULL, 4),
(20, 3, 'display_name', 'text', 'Display Name', 1, 1, 1, 1, 1, 1, NULL, 5),
(21, 1, 'role_id', 'text', 'Role', 0, 1, 1, 1, 1, 1, '{}', 9),
(22, 4, 'id', 'number', 'ID', 1, 0, 0, 0, 0, 0, NULL, 1),
(23, 4, 'parent_id', 'select_dropdown', 'Parent', 0, 0, 1, 1, 1, 1, '{\"default\":\"\",\"null\":\"\",\"options\":{\"\":\"-- None --\"},\"relationship\":{\"key\":\"id\",\"label\":\"name\"}}', 2),
(24, 4, 'order', 'text', 'Order', 1, 1, 1, 1, 1, 1, '{\"default\":1}', 3),
(25, 4, 'name', 'text', 'Name', 1, 1, 1, 1, 1, 1, NULL, 4),
(26, 4, 'slug', 'text', 'Slug', 1, 1, 1, 1, 1, 1, '{\"slugify\":{\"origin\":\"name\"}}', 5),
(27, 4, 'created_at', 'timestamp', 'Created At', 0, 0, 1, 0, 0, 0, NULL, 6),
(28, 4, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, NULL, 7),
(29, 5, 'id', 'number', 'ID', 1, 0, 0, 0, 0, 0, NULL, 1),
(30, 5, 'author_id', 'text', 'Author', 1, 0, 1, 1, 0, 1, NULL, 2),
(31, 5, 'category_id', 'text', 'Category', 1, 0, 1, 1, 1, 0, NULL, 3),
(32, 5, 'title', 'text', 'Title', 1, 1, 1, 1, 1, 1, NULL, 4),
(33, 5, 'excerpt', 'text_area', 'Excerpt', 1, 0, 1, 1, 1, 1, NULL, 5),
(34, 5, 'body', 'rich_text_box', 'Body', 1, 0, 1, 1, 1, 1, NULL, 6),
(35, 5, 'image', 'image', 'Post Image', 0, 1, 1, 1, 1, 1, '{\"resize\":{\"width\":\"1000\",\"height\":\"null\"},\"quality\":\"70%\",\"upsize\":true,\"thumbnails\":[{\"name\":\"medium\",\"scale\":\"50%\"},{\"name\":\"small\",\"scale\":\"25%\"},{\"name\":\"cropped\",\"crop\":{\"width\":\"300\",\"height\":\"250\"}}]}', 7),
(36, 5, 'slug', 'text', 'Slug', 1, 0, 1, 1, 1, 1, '{\"slugify\":{\"origin\":\"title\",\"forceUpdate\":true},\"validation\":{\"rule\":\"unique:posts,slug\"}}', 8),
(37, 5, 'meta_description', 'text_area', 'Meta Description', 1, 0, 1, 1, 1, 1, NULL, 9),
(38, 5, 'meta_keywords', 'text_area', 'Meta Keywords', 1, 0, 1, 1, 1, 1, NULL, 10),
(39, 5, 'status', 'select_dropdown', 'Status', 1, 1, 1, 1, 1, 1, '{\"default\":\"DRAFT\",\"options\":{\"PUBLISHED\":\"published\",\"DRAFT\":\"draft\",\"PENDING\":\"pending\"}}', 11),
(40, 5, 'created_at', 'timestamp', 'Created At', 0, 1, 1, 0, 0, 0, NULL, 12),
(41, 5, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, NULL, 13),
(42, 5, 'seo_title', 'text', 'SEO Title', 0, 1, 1, 1, 1, 1, NULL, 14),
(43, 5, 'featured', 'checkbox', 'Featured', 1, 1, 1, 1, 1, 1, NULL, 15),
(44, 6, 'id', 'number', 'ID', 1, 0, 0, 0, 0, 0, NULL, 1),
(45, 6, 'author_id', 'text', 'Author', 1, 0, 0, 0, 0, 0, NULL, 2),
(46, 6, 'title', 'text', 'Title', 1, 1, 1, 1, 1, 1, NULL, 3),
(47, 6, 'excerpt', 'text_area', 'Excerpt', 1, 0, 1, 1, 1, 1, NULL, 4),
(48, 6, 'body', 'rich_text_box', 'Body', 1, 0, 1, 1, 1, 1, NULL, 5),
(49, 6, 'slug', 'text', 'Slug', 1, 0, 1, 1, 1, 1, '{\"slugify\":{\"origin\":\"title\"},\"validation\":{\"rule\":\"unique:pages,slug\"}}', 6),
(50, 6, 'meta_description', 'text', 'Meta Description', 1, 0, 1, 1, 1, 1, NULL, 7),
(51, 6, 'meta_keywords', 'text', 'Meta Keywords', 1, 0, 1, 1, 1, 1, NULL, 8),
(52, 6, 'status', 'select_dropdown', 'Status', 1, 1, 1, 1, 1, 1, '{\"default\":\"INACTIVE\",\"options\":{\"INACTIVE\":\"INACTIVE\",\"ACTIVE\":\"ACTIVE\"}}', 9),
(53, 6, 'created_at', 'timestamp', 'Created At', 1, 1, 1, 0, 0, 0, NULL, 10),
(54, 6, 'updated_at', 'timestamp', 'Updated At', 1, 0, 0, 0, 0, 0, NULL, 11),
(55, 6, 'image', 'image', 'Page Image', 0, 1, 1, 1, 1, 1, NULL, 12),
(56, 7, 'nombre', 'text', 'Nombre', 1, 1, 1, 0, 1, 0, '{}', 1),
(57, 7, 'activa', 'checkbox', 'Activa', 1, 1, 1, 1, 1, 0, '{\"on\":\"Activada\",\"off\":\"Desactivada\",\"checked\":\"true\"}', 2),
(58, 7, 'created_at', 'timestamp', 'Creada', 0, 0, 1, 0, 0, 0, '{}', 3),
(59, 7, 'updated_at', 'timestamp', 'Actualizada', 0, 0, 1, 0, 0, 0, '{}', 4),
(60, 8, 'user_id', 'text', 'User Id', 1, 0, 0, 0, 0, 0, '{}', 1),
(62, 8, 'celular', 'text', 'Celular', 1, 0, 1, 1, 0, 0, '{}', 5),
(63, 8, 'correo', 'text', 'Correo', 1, 0, 1, 1, 0, 0, '{}', 6),
(64, 8, 'nombres', 'text', 'Nombres', 1, 1, 1, 1, 0, 0, '{}', 2),
(65, 8, 'apellidos', 'text', 'Apellidos', 1, 1, 1, 1, 0, 0, '{}', 3),
(66, 8, 'apodo', 'text', 'Apodo', 1, 0, 1, 1, 0, 0, '{}', 4),
(67, 8, 'ubicacion', 'text', 'Ubicación', 0, 0, 1, 1, 0, 0, '{}', 7),
(68, 8, 'ciudad', 'text', 'Ciudad', 0, 1, 1, 1, 0, 0, '{}', 8),
(69, 8, 'ser_profesor', 'checkbox', '¿Ser Profesor?', 0, 0, 1, 1, 0, 0, '{\"on\":\"Solicitado\",\"off\":\"No Solicitar\",\"checked\":\"false\"}', 9),
(70, 8, 'calificacion', 'number', 'Calificación', 0, 1, 1, 1, 0, 0, '{\"step\":\"any\"}', 10),
(72, 8, 'activo', 'checkbox', 'Activo', 0, 1, 1, 1, 0, 0, '{\"on\":\"Activado\",\"off\":\"Desactivado\",\"checked\":\"true\"}', 11),
(73, 8, 'created_at', 'timestamp', 'Created At', 0, 0, 0, 0, 0, 0, '{}', 12),
(74, 8, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, '{}', 13),
(75, 9, 'ciudad', 'text', 'Ciudad', 1, 1, 1, 0, 1, 0, '{}', 1),
(76, 9, 'activa', 'checkbox', 'Activa', 1, 1, 1, 1, 1, 0, '{\"on\":\"Activada\",\"off\":\"Desactivada\",\"checked\":\"true\"}', 2),
(77, 9, 'created_at', 'timestamp', 'Created At', 0, 0, 0, 0, 0, 0, '{}', 3),
(78, 9, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, '{}', 4),
(86, 8, 'alumno_belongsto_ciudad_relationship', 'relationship', 'Ciudad', 0, 1, 1, 1, 0, 0, '{\"model\":\"App\\\\Ciudad\",\"table\":\"ciudad\",\"type\":\"belongsTo\",\"column\":\"ciudad\",\"key\":\"ciudad\",\"label\":\"ciudad\",\"pivot_table\":\"alumnos\",\"pivot\":\"0\",\"taggable\":\"0\"}', 8),
(87, 11, 'user_id', 'text', 'User Id', 1, 0, 0, 0, 0, 0, '{}', 1),
(88, 11, 'nombres', 'text', 'Nombres', 1, 1, 1, 1, 0, 0, '{}', 2),
(89, 11, 'apellidos', 'text', 'Apellidos', 1, 1, 1, 1, 0, 0, '{}', 3),
(90, 11, 'cedula', 'text', 'Cédula', 0, 1, 1, 1, 0, 0, '{}', 5),
(91, 11, 'celular', 'text', 'Celular', 1, 0, 1, 1, 0, 0, '{}', 6),
(92, 11, 'correo', 'text', 'Correo', 1, 0, 1, 1, 0, 0, '{}', 7),
(93, 11, 'apodo', 'text', 'Apodo', 1, 0, 1, 1, 0, 0, '{}', 4),
(94, 11, 'ubicacion', 'text', 'Ubicacion', 0, 0, 1, 1, 0, 0, '{}', 8),
(95, 11, 'ciudad', 'text', 'Ciudad', 0, 1, 1, 1, 0, 0, '{}', 9),
(96, 11, 'clases', 'checkbox', 'Clases', 0, 1, 1, 1, 0, 0, '{\"on\":\"Si\",\"off\":\"No\",\"checked\":\"true\"}', 10),
(98, 11, 'disponible', 'checkbox', 'Disponible', 0, 1, 1, 1, 0, 0, '{\"on\":\"Si\",\"off\":\"No\",\"checked\":\"true\"}', 12),
(99, 11, 'estado_cuenta', 'number', 'Estado Cuenta', 0, 0, 1, 1, 0, 0, '{\"step\":\"any\"}', 13),
(100, 11, 'hoja_vida', 'text', 'Hoja de Vida', 0, 0, 1, 1, 0, 0, '{}', 14),
(101, 11, 'titulo', 'text', 'Título Profesional', 0, 0, 1, 1, 0, 0, '{}', 15),
(102, 11, 'valor_clase', 'number', 'Valor Hora Clase', 0, 0, 1, 1, 0, 0, '{\"step\":\"any\"}', 16),
(104, 11, 'calificacion', 'number', 'Calificación', 0, 1, 1, 1, 0, 0, '{\"step\":\"any\"}', 18),
(105, 11, 'activo', 'checkbox', 'Activo', 0, 1, 1, 1, 0, 0, '{\"on\":\"Activado\",\"off\":\"Desactivado\",\"checked\":\"true\"}', 19),
(106, 11, 'created_at', 'timestamp', 'Created At', 0, 0, 0, 0, 0, 0, '{}', 20),
(107, 11, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, '{}', 21),
(108, 11, 'profesore_belongsto_ciudad_relationship', 'relationship', 'Ciudad', 0, 1, 1, 1, 0, 0, '{\"model\":\"App\\\\Ciudad\",\"table\":\"ciudad\",\"type\":\"belongsTo\",\"column\":\"ciudad\",\"key\":\"ciudad\",\"label\":\"ciudad\",\"pivot_table\":\"alumnos\",\"pivot\":\"0\",\"taggable\":\"0\"}', 9),
(109, 12, 'id', 'text', 'Id', 1, 0, 0, 0, 0, 0, '{}', 1),
(110, 12, 'user_id', 'text', 'User Id', 1, 1, 1, 1, 1, 0, '{}', 2),
(111, 12, 'combo', 'text', 'Combo', 1, 1, 1, 1, 1, 0, '{}', 3),
(112, 12, 'activo', 'checkbox', 'Activo', 1, 1, 1, 1, 1, 0, '{\"on\":\"Activado\",\"off\":\"Desactivado\",\"checked\":\"true\"}', 4),
(113, 12, 'created_at', 'timestamp', 'Created At', 0, 0, 0, 0, 0, 0, '{}', 5),
(114, 12, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, '{}', 6),
(116, 12, 'profesor_combo_belongsto_combo_relationship', 'relationship', 'Combo', 0, 1, 1, 1, 1, 0, '{\"model\":\"App\\\\Combo\",\"table\":\"combos\",\"type\":\"belongsTo\",\"column\":\"combo\",\"key\":\"nombre\",\"label\":\"nombre\",\"pivot_table\":\"alumnos\",\"pivot\":\"0\",\"taggable\":\"0\"}', 3),
(117, 13, 'id', 'text', 'Id', 1, 0, 0, 0, 0, 0, '{}', 1),
(118, 13, 'user_id', 'text', 'User Id', 1, 1, 1, 1, 1, 0, '{}', 2),
(119, 13, 'materia', 'text', 'Materia', 1, 1, 1, 1, 1, 0, '{}', 3),
(120, 13, 'created_at', 'timestamp', 'Created At', 0, 0, 0, 0, 0, 0, '{}', 5),
(121, 13, 'updated_at', 'text', 'Updated At', 0, 0, 0, 0, 0, 0, '{}', 6),
(122, 13, 'activa', 'checkbox', 'Activa', 1, 1, 1, 1, 1, 0, '{\"on\":\"Activada\",\"off\":\"Desactivada\",\"checked\":\"true\"}', 4),
(123, 13, 'profesor_materium_belongsto_materia_relationship', 'relationship', 'Materia', 0, 1, 1, 1, 1, 0, '{\"model\":\"App\\\\Materia\",\"table\":\"materias\",\"type\":\"belongsTo\",\"column\":\"materia\",\"key\":\"nombre\",\"label\":\"nombre\",\"pivot_table\":\"alumnos\",\"pivot\":\"0\",\"taggable\":\"0\"}', 3),
(125, 12, 'profesor_combo_belongsto_profesore_relationship', 'relationship', 'Profesor', 0, 1, 1, 1, 1, 0, '{\"model\":\"App\\\\User\",\"table\":\"users\",\"type\":\"belongsTo\",\"column\":\"user_id\",\"key\":\"id\",\"label\":\"name\",\"pivot_table\":\"alumnos\",\"pivot\":\"0\",\"taggable\":\"0\"}', 2),
(126, 13, 'profesor_materium_belongsto_profesore_relationship', 'relationship', 'Profesor', 0, 1, 1, 1, 1, 0, '{\"model\":\"App\\\\User\",\"table\":\"users\",\"type\":\"belongsTo\",\"column\":\"user_id\",\"key\":\"id\",\"label\":\"name\",\"pivot_table\":\"alumnos\",\"pivot\":\"0\",\"taggable\":\"0\"}', 2),
(127, 1, 'email_verified_at', 'timestamp', 'Email Verified At', 0, 1, 1, 1, 1, 1, '{}', 6),
(128, 1, 'tipo', 'select_dropdown', 'Tipo', 1, 1, 1, 1, 1, 1, '{\"options\":{\"Alumno\":\"Alumno\",\"Profesor\":\"Profesor\",\"Administrador\":\"Administrador\"}}', 12),
(129, 1, 'activo', 'checkbox', 'Activo', 1, 1, 1, 1, 1, 1, '{\"on\":\"Activado\",\"off\":\"Desactivado\",\"checked\":\"true\"}', 13),
(130, 16, 'id', 'text', 'Número', 1, 1, 1, 0, 0, 0, '{}', 1),
(131, 16, 'user_id', 'text', 'User Id', 1, 1, 1, 0, 0, 0, '{}', 2),
(132, 16, 'created_at', 'timestamp', 'Fecha Solicitud', 0, 1, 1, 0, 0, 0, '{}', 9),
(133, 16, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, '{}', 10),
(134, 16, 'cedula', 'text', 'Cédula', 1, 1, 1, 0, 0, 0, '{}', 3),
(135, 16, 'clases', 'checkbox', 'Clases', 1, 1, 1, 0, 0, 0, '{\"on\":\"Activada\",\"off\":\"Desactivada\",\"checked\":\"true\"}', 4),
(136, 16, 'proyectos', 'checkbox', 'Proyectos', 1, 1, 1, 0, 0, 0, '{\"on\":\"Activado\",\"off\":\"Desactivado\",\"checked\":\"true\"}', 5),
(137, 16, 'hoja_vida', 'text', 'Hoja de Vida', 0, 0, 1, 0, 0, 0, '{}', 6),
(138, 16, 'titulo', 'text', 'Título Profesional', 0, 0, 1, 0, 0, 0, '{}', 7),
(139, 16, 'estado', 'select_dropdown', 'Estado', 1, 1, 1, 1, 0, 0, '{\"options\":{\"Solicitada\":\"Solicitada\",\"Aprobada\":\"Aprobada\",\"Negada\":\"Negada\"}}', 8),
(140, 16, 'formulario_belongsto_alumno_relationship', 'relationship', 'Alumno', 0, 1, 1, 0, 0, 0, '{\"model\":\"App\\\\Alumno\",\"table\":\"alumnos\",\"type\":\"belongsTo\",\"column\":\"user_id\",\"key\":\"user_id\",\"label\":\"nombres\",\"pivot_table\":\"alumnos\",\"pivot\":\"0\",\"taggable\":\"0\"}', 2),
(141, 17, 'nombre', 'text', 'Nombre', 1, 1, 1, 0, 1, 0, '{}', 1),
(142, 17, 'descripcion', 'text_area', 'Descripción', 1, 1, 1, 1, 1, 0, '{}', 2),
(143, 17, 'beneficios', 'rich_text_box', 'Beneficios', 1, 0, 1, 1, 1, 0, '{}', 3),
(144, 17, 'activo', 'checkbox', 'Activo', 1, 1, 1, 1, 1, 0, '{\"on\":\"Activado\",\"off\":\"Desactivado\",\"checked\":\"true\"}', 4),
(145, 17, 'created_at', 'timestamp', 'Created At', 0, 0, 0, 0, 0, 0, '{}', 5),
(146, 17, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, '{}', 6),
(158, 19, 'id', 'text', 'Id', 1, 0, 0, 0, 0, 0, '{}', 1),
(159, 19, 'user_id', 'text', 'User Id', 1, 1, 1, 0, 0, 0, '{}', 2),
(160, 19, 'combo', 'text', 'Combo', 1, 1, 1, 0, 0, 0, '{}', 3),
(161, 19, 'horas', 'text', 'Horas', 1, 1, 1, 0, 0, 0, '{}', 4),
(162, 19, 'alumno_billetera_belongsto_combo_relationship', 'relationship', 'Combo', 0, 1, 1, 0, 0, 0, '{\"model\":\"App\\\\Combo\",\"table\":\"combos\",\"type\":\"belongsTo\",\"column\":\"combo\",\"key\":\"nombre\",\"label\":\"nombre\",\"pivot_table\":\"alumno_billetera\",\"pivot\":\"0\",\"taggable\":\"0\"}', 3),
(163, 19, 'alumno_billetera_belongsto_user_relationship', 'relationship', 'Alumno', 0, 1, 1, 0, 0, 0, '{\"model\":\"App\\\\User\",\"table\":\"users\",\"type\":\"belongsTo\",\"column\":\"user_id\",\"key\":\"id\",\"label\":\"name\",\"pivot_table\":\"alumno_billetera\",\"pivot\":\"0\",\"taggable\":\"0\"}', 2),
(164, 19, 'created_at', 'timestamp', 'Created At', 0, 0, 0, 0, 0, 0, '{}', 5),
(165, 19, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, '{}', 6),
(167, 20, 'id', 'text', 'Id', 1, 0, 0, 0, 0, 0, '{}', 1),
(168, 20, 'combo', 'text', 'Combo', 1, 1, 1, 1, 1, 0, '{}', 2),
(169, 20, 'hora', 'number', 'Hora', 1, 1, 1, 1, 1, 0, '{}', 3),
(170, 20, 'inversion', 'number', 'Inversion', 1, 1, 1, 1, 1, 0, '{\"step\":\"any\"}', 4),
(171, 20, 'descuento', 'number', 'Descuento', 1, 1, 1, 1, 1, 0, '{\"step\":\"any\"}', 5),
(172, 20, 'activo', 'checkbox', 'Activo', 1, 1, 1, 1, 1, 0, '{\"on\":\"Activada\",\"off\":\"Desactivada\",\"checked\":\"true\"}', 6),
(173, 20, 'created_at', 'timestamp', 'Created At', 0, 0, 0, 0, 0, 0, '{}', 7),
(174, 20, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, '{}', 8),
(175, 20, 'combos_hora_belongsto_combo_relationship', 'relationship', 'Combo', 0, 1, 1, 1, 1, 0, '{\"model\":\"App\\\\Combo\",\"table\":\"combos\",\"type\":\"belongsTo\",\"column\":\"combo\",\"key\":\"nombre\",\"label\":\"nombre\",\"pivot_table\":\"combos_horas\",\"pivot\":\"0\",\"taggable\":\"0\"}', 2),
(176, 21, 'nombre', 'text', 'Nombre', 1, 1, 1, 0, 1, 0, '{}', 1),
(177, 21, 'ciudad', 'text', 'Ciudad', 1, 1, 1, 1, 1, 0, '{}', 2),
(178, 21, 'activa', 'checkbox', 'Activa', 0, 1, 1, 1, 1, 0, '{\"on\":\"Activada\",\"off\":\"Desactivada\",\"checked\":\"true\"}', 3),
(179, 21, 'created_at', 'timestamp', 'Created At', 0, 0, 0, 0, 0, 0, '{}', 4),
(180, 21, 'updated_at', 'timestamp', 'Updated At', 0, 0, 0, 0, 0, 0, '{}', 5),
(181, 21, 'sede_belongsto_ciudad_relationship', 'relationship', 'Ciudad', 0, 1, 1, 1, 1, 0, '{\"model\":\"App\\\\Ciudad\",\"table\":\"ciudad\",\"type\":\"belongsTo\",\"column\":\"ciudad\",\"key\":\"ciudad\",\"label\":\"ciudad\",\"pivot_table\":\"alumno_billetera\",\"pivot\":\"0\",\"taggable\":\"0\"}', 2),
(182, 11, 'tareas', 'checkbox', 'Tareas', 0, 1, 1, 1, 0, 0, '{\"on\":\"Si\",\"off\":\"No\",\"checked\":\"true\"}', 11),
(183, 11, 'valor_tarea', 'number', 'Valor Hora Tarea', 0, 1, 1, 1, 1, 1, '{\"step\":\"any\"}', 17);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `data_types`
--

CREATE TABLE `data_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name_singular` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name_plural` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `policy_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `controller` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generate_permissions` tinyint(1) NOT NULL DEFAULT '0',
  `server_side` tinyint(4) NOT NULL DEFAULT '0',
  `details` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `data_types`
--

INSERT INTO `data_types` (`id`, `name`, `slug`, `display_name_singular`, `display_name_plural`, `icon`, `model_name`, `policy_name`, `controller`, `description`, `generate_permissions`, `server_side`, `details`, `created_at`, `updated_at`) VALUES
(1, 'users', 'users', 'User', 'Users', 'voyager-people', 'TCG\\Voyager\\Models\\User', 'TCG\\Voyager\\Policies\\UserPolicy', 'TCG\\Voyager\\Http\\Controllers\\VoyagerUserController', NULL, 1, 0, '{\"order_column\":null,\"order_display_column\":null,\"order_direction\":\"desc\",\"default_search_key\":null,\"scope\":null}', '2019-04-08 23:14:19', '2019-04-09 02:31:25'),
(2, 'menus', 'menus', 'Menu', 'Menus', 'voyager-list', 'TCG\\Voyager\\Models\\Menu', NULL, '', '', 1, 0, NULL, '2019-04-08 23:14:19', '2019-04-08 23:14:19'),
(3, 'roles', 'roles', 'Role', 'Roles', 'voyager-lock', 'TCG\\Voyager\\Models\\Role', NULL, '', '', 1, 0, NULL, '2019-04-08 23:14:19', '2019-04-08 23:14:19'),
(4, 'categories', 'categories', 'Category', 'Categories', 'voyager-categories', 'TCG\\Voyager\\Models\\Category', NULL, '', '', 1, 0, NULL, '2019-04-08 23:14:28', '2019-04-08 23:14:28'),
(5, 'posts', 'posts', 'Post', 'Posts', 'voyager-news', 'TCG\\Voyager\\Models\\Post', 'TCG\\Voyager\\Policies\\PostPolicy', '', '', 1, 0, NULL, '2019-04-08 23:14:29', '2019-04-08 23:14:29'),
(6, 'pages', 'pages', 'Page', 'Pages', 'voyager-file-text', 'TCG\\Voyager\\Models\\Page', NULL, '', '', 1, 0, NULL, '2019-04-08 23:14:30', '2019-04-08 23:14:30'),
(7, 'materias', 'materias', 'Materia', 'Materias', 'voyager-documentation', 'App\\Materia', NULL, NULL, NULL, 1, 0, '{\"order_column\":null,\"order_display_column\":null,\"order_direction\":\"asc\",\"default_search_key\":null,\"scope\":null}', '2019-04-08 23:23:59', '2019-04-19 01:56:35'),
(8, 'alumnos', 'alumnos', 'Alumno', 'Alumnos', 'voyager-study', 'App\\Alumno', NULL, NULL, NULL, 1, 0, '{\"order_column\":null,\"order_display_column\":null,\"order_direction\":\"asc\",\"default_search_key\":null,\"scope\":null}', '2019-04-09 00:52:59', '2019-04-19 00:36:33'),
(9, 'ciudad', 'ciudad', 'Ciudad', 'Ciudades', 'voyager-thumb-tack', 'App\\Ciudad', NULL, NULL, NULL, 1, 0, '{\"order_column\":null,\"order_display_column\":null,\"order_direction\":\"asc\",\"default_search_key\":null,\"scope\":null}', '2019-04-09 00:57:58', '2019-04-19 01:56:04'),
(11, 'profesores', 'profesores', 'Profesor', 'Profesores', 'voyager-person', 'App\\Profesore', NULL, NULL, NULL, 1, 0, '{\"order_column\":\"nombres\",\"order_display_column\":null,\"order_direction\":\"asc\",\"default_search_key\":null,\"scope\":null}', '2019-04-09 01:23:36', '2019-04-19 03:22:42'),
(12, 'profesor_combo', 'profesor-combo', 'Profesor-Combos', 'Profesores-Combos', 'voyager-bag', 'App\\ProfesorCombo', NULL, NULL, NULL, 1, 0, '{\"order_column\":null,\"order_display_column\":null,\"order_direction\":\"asc\",\"default_search_key\":null,\"scope\":null}', '2019-04-09 01:31:46', '2019-04-18 01:53:57'),
(13, 'profesor_materia', 'profesor-materia', 'Profesor-Materias', 'Profesores-Materias', 'voyager-file-text', 'App\\ProfesorMaterium', NULL, NULL, NULL, 1, 0, '{\"order_column\":null,\"order_display_column\":null,\"order_direction\":\"asc\",\"default_search_key\":null,\"scope\":null}', '2019-04-09 01:38:10', '2019-04-18 01:55:20'),
(16, 'formulario', 'formulario', 'Solicitud Ser Profesor', 'Solicitudes Ser Profesor', 'voyager-edit', 'App\\Formulario', NULL, NULL, NULL, 1, 0, '{\"order_column\":null,\"order_display_column\":null,\"order_direction\":\"asc\",\"default_search_key\":null,\"scope\":null}', '2019-04-09 02:56:40', '2019-04-18 02:21:29'),
(17, 'combos', 'combos', 'Combo', 'Combos', 'voyager-basket', 'App\\Combo', NULL, NULL, NULL, 1, 0, '{\"order_column\":null,\"order_display_column\":null,\"order_direction\":\"asc\",\"default_search_key\":null,\"scope\":null}', '2019-04-12 16:49:35', '2019-04-19 01:55:27'),
(19, 'alumno_billetera', 'alumno-billetera', 'Alumno Billetera', 'Alumnos Billeteras', 'voyager-wallet', 'App\\AlumnoBilletera', NULL, NULL, NULL, 1, 0, '{\"order_column\":null,\"order_display_column\":null,\"order_direction\":\"asc\",\"default_search_key\":null,\"scope\":null}', '2019-04-18 01:43:50', '2019-04-18 02:04:00'),
(20, 'combos_horas', 'combos-horas', 'Combos Hora', 'Combos Horas', 'voyager-alarm-clock', 'App\\CombosHora', NULL, NULL, NULL, 1, 0, '{\"order_column\":null,\"order_display_column\":null,\"order_direction\":\"asc\",\"default_search_key\":null,\"scope\":null}', '2019-04-19 00:53:11', '2019-04-19 00:58:42'),
(21, 'sedes', 'sedes', 'Sede', 'Sedes', 'voyager-shop', 'App\\Sede', NULL, NULL, NULL, 1, 0, '{\"order_column\":null,\"order_display_column\":null,\"order_direction\":\"asc\",\"default_search_key\":null,\"scope\":null}', '2019-04-19 02:14:58', '2019-04-19 02:16:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formulario`
--

CREATE TABLE `formulario` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cedula` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clases` bit(1) NOT NULL,
  `proyectos` bit(1) NOT NULL,
  `hoja_vida` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `nombre` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activa` bit(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`nombre`, `activa`, `created_at`, `updated_at`) VALUES
('Español', b'1', '2019-04-08 23:33:30', '2019-04-08 23:33:30'),
('Inglés', b'1', '2019-04-18 00:37:37', '2019-04-18 00:37:37'),
('Matemática', b'1', '2019-04-08 23:27:04', '2019-04-08 23:27:04');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menus`
--

CREATE TABLE `menus` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `menus`
--

INSERT INTO `menus` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'admin', '2019-04-08 23:14:20', '2019-04-08 23:14:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `menu_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '_self',
  `icon_class` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `order` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `route` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parameters` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `menu_items`
--

INSERT INTO `menu_items` (`id`, `menu_id`, `title`, `url`, `target`, `icon_class`, `color`, `parent_id`, `order`, `created_at`, `updated_at`, `route`, `parameters`) VALUES
(1, 1, 'Dashboard', '', '_self', 'voyager-boat', NULL, NULL, 1, '2019-04-08 23:14:20', '2019-04-08 23:14:20', 'voyager.dashboard', NULL),
(2, 1, 'Media', '', '_self', 'voyager-images', NULL, NULL, 11, '2019-04-08 23:14:21', '2019-04-19 02:16:50', 'voyager.media.index', NULL),
(3, 1, 'Users', '', '_self', 'voyager-person', NULL, NULL, 10, '2019-04-08 23:14:21', '2019-04-19 02:16:50', 'voyager.users.index', NULL),
(4, 1, 'Roles', '', '_self', 'voyager-lock', NULL, NULL, 9, '2019-04-08 23:14:21', '2019-04-19 02:16:50', 'voyager.roles.index', NULL),
(5, 1, 'Tools', '', '_self', 'voyager-tools', NULL, NULL, 15, '2019-04-08 23:14:21', '2019-04-19 02:16:50', NULL, NULL),
(6, 1, 'Menu Builder', '', '_self', 'voyager-list', NULL, 5, 1, '2019-04-08 23:14:21', '2019-04-09 01:51:23', 'voyager.menus.index', NULL),
(7, 1, 'Database', '', '_self', 'voyager-data', NULL, 5, 2, '2019-04-08 23:14:21', '2019-04-09 01:51:23', 'voyager.database.index', NULL),
(8, 1, 'Compass', '', '_self', 'voyager-compass', NULL, 5, 3, '2019-04-08 23:14:21', '2019-04-09 01:51:23', 'voyager.compass.index', NULL),
(9, 1, 'BREAD', '', '_self', 'voyager-bread', NULL, 5, 4, '2019-04-08 23:14:21', '2019-04-09 01:51:23', 'voyager.bread.index', NULL),
(10, 1, 'Settings', '', '_self', 'voyager-settings', NULL, NULL, 16, '2019-04-08 23:14:21', '2019-04-19 02:16:50', 'voyager.settings.index', NULL),
(11, 1, 'Categories', '', '_self', 'voyager-categories', NULL, NULL, 14, '2019-04-08 23:14:28', '2019-04-19 02:16:50', 'voyager.categories.index', NULL),
(12, 1, 'Posts', '', '_self', 'voyager-news', NULL, NULL, 12, '2019-04-08 23:14:30', '2019-04-19 02:16:50', 'voyager.posts.index', NULL),
(13, 1, 'Pages', '', '_self', 'voyager-file-text', NULL, NULL, 13, '2019-04-08 23:14:31', '2019-04-19 02:16:50', 'voyager.pages.index', NULL),
(14, 1, 'Hooks', '', '_self', 'voyager-hook', NULL, 5, 5, '2019-04-08 23:14:35', '2019-04-09 01:51:23', 'voyager.hooks', NULL),
(15, 1, 'Materias', '', '_self', 'voyager-documentation', NULL, NULL, 4, '2019-04-08 23:23:59', '2019-04-19 02:17:06', 'voyager.materias.index', NULL),
(16, 1, 'Listado', '', '_self', 'voyager-study', '#000000', 30, 1, '2019-04-09 00:53:00', '2019-04-18 02:06:01', 'voyager.alumnos.index', 'null'),
(17, 1, 'Ciudades', '', '_self', 'voyager-thumb-tack', '#000000', NULL, 3, '2019-04-09 00:57:59', '2019-04-19 02:17:06', 'voyager.ciudad.index', 'null'),
(19, 1, 'Listado', '', '_self', 'voyager-person', '#000000', 22, 1, '2019-04-09 01:23:36', '2019-04-09 01:58:36', 'voyager.profesores.index', 'null'),
(20, 1, 'Profesor-Combo', '', '_self', 'voyager-bag', '#000000', 22, 2, '2019-04-09 01:31:46', '2019-04-18 02:05:35', 'voyager.profesor-combo.index', 'null'),
(21, 1, 'Profesor-Materia', '', '_self', 'voyager-file-text', '#000000', 22, 3, '2019-04-09 01:38:11', '2019-04-18 02:05:35', 'voyager.profesor-materia.index', 'null'),
(22, 1, 'Profesores', '', '_self', 'voyager-person', '#000000', NULL, 7, '2019-04-09 01:58:21', '2019-04-19 02:17:07', NULL, ''),
(25, 1, 'Solicitudes Ser Profesor', '', '_self', 'voyager-edit', NULL, NULL, 8, '2019-04-09 02:56:40', '2019-04-19 02:17:07', 'voyager.formulario.index', NULL),
(26, 1, 'Listado', '', '_self', 'voyager-basket', '#000000', 28, 1, '2019-04-12 16:49:36', '2019-04-12 17:48:38', 'voyager.combos.index', 'null'),
(28, 1, 'Combos', '', '_self', 'voyager-basket', '#000000', NULL, 5, '2019-04-12 17:48:14', '2019-04-19 02:17:07', NULL, ''),
(29, 1, 'Alumno-Billetera', '', '_self', 'voyager-wallet', '#000000', 30, 2, '2019-04-18 01:43:50', '2019-04-18 02:11:42', 'voyager.alumno-billetera.index', 'null'),
(30, 1, 'Alumnos', '', '_self', 'voyager-study', '#000000', NULL, 6, '2019-04-18 02:05:00', '2019-04-19 02:17:07', NULL, ''),
(31, 1, 'Combo-Horas', '', '_self', 'voyager-alarm-clock', '#000000', 28, 2, '2019-04-19 00:53:11', '2019-04-19 01:57:41', 'voyager.combos-horas.index', 'null'),
(32, 1, 'Sedes', '', '_self', 'voyager-shop', NULL, NULL, 2, '2019-04-19 02:14:58', '2019-04-19 02:17:06', 'voyager.sedes.index', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2016_01_01_000000_add_voyager_user_fields', 1),
(4, '2016_01_01_000000_create_data_types_table', 1),
(5, '2016_05_19_173453_create_menu_table', 1),
(6, '2016_10_21_190000_create_roles_table', 1),
(7, '2016_10_21_190000_create_settings_table', 1),
(8, '2016_11_30_135954_create_permission_table', 1),
(9, '2016_11_30_141208_create_permission_role_table', 1),
(10, '2016_12_26_201236_data_types__add__server_side', 1),
(11, '2017_01_13_000000_add_route_to_menu_items_table', 1),
(12, '2017_01_14_005015_create_translations_table', 1),
(13, '2017_01_15_000000_make_table_name_nullable_in_permissions_table', 1),
(14, '2017_03_06_000000_add_controller_to_data_types_table', 1),
(15, '2017_04_21_000000_add_order_to_data_rows_table', 1),
(16, '2017_07_05_210000_add_policyname_to_data_types_table', 1),
(17, '2017_08_05_000000_add_group_to_settings_table', 1),
(18, '2017_11_26_013050_add_user_role_relationship', 1),
(19, '2017_11_26_015000_create_user_roles_table', 1),
(20, '2018_03_11_000000_add_user_settings', 1),
(21, '2018_03_14_000000_add_details_to_data_types_table', 1),
(22, '2018_03_16_000000_make_settings_value_nullable', 1),
(23, '2016_01_01_000000_create_pages_table', 2),
(24, '2016_01_01_000000_create_posts_table', 2),
(25, '2016_02_15_204651_create_categories_table', 2),
(26, '2017_04_11_000000_alter_post_nullable_fields_table', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pages`
--

CREATE TABLE `pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `author_id` int(11) NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `body` text COLLATE utf8mb4_unicode_ci,
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_keywords` text COLLATE utf8mb4_unicode_ci,
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pages`
--

INSERT INTO `pages` (`id`, `author_id`, `title`, `excerpt`, `body`, `image`, `slug`, `meta_description`, `meta_keywords`, `status`, `created_at`, `updated_at`) VALUES
(1, 0, 'Hello World', 'Hang the jib grog grog blossom grapple dance the hempen jig gangway pressgang bilge rat to go on account lugger. Nelsons folly gabion line draught scallywag fire ship gaff fluke fathom case shot. Sea Legs bilge rat sloop matey gabion long clothes run a shot across the bow Gold Road cog league.', '<p>Hello World. Scallywag grog swab Cat o\'nine tails scuttle rigging hardtack cable nipper Yellow Jack. Handsomely spirits knave lad killick landlubber or just lubber deadlights chantey pinnace crack Jennys tea cup. Provost long clothes black spot Yellow Jack bilged on her anchor league lateen sail case shot lee tackle.</p>\n<p>Ballast spirits fluke topmast me quarterdeck schooner landlubber or just lubber gabion belaying pin. Pinnace stern galleon starboard warp carouser to go on account dance the hempen jig jolly boat measured fer yer chains. Man-of-war fire in the hole nipperkin handsomely doubloon barkadeer Brethren of the Coast gibbet driver squiffy.</p>', 'pages/page1.jpg', 'hello-world', 'Yar Meta Description', 'Keyword1, Keyword2', 'ACTIVE', '2019-04-08 23:14:31', '2019-04-08 23:14:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permissions`
--

INSERT INTO `permissions` (`id`, `key`, `table_name`, `created_at`, `updated_at`) VALUES
(1, 'browse_admin', NULL, '2019-04-08 23:14:21', '2019-04-08 23:14:21'),
(2, 'browse_bread', NULL, '2019-04-08 23:14:21', '2019-04-08 23:14:21'),
(3, 'browse_database', NULL, '2019-04-08 23:14:21', '2019-04-08 23:14:21'),
(4, 'browse_media', NULL, '2019-04-08 23:14:21', '2019-04-08 23:14:21'),
(5, 'browse_compass', NULL, '2019-04-08 23:14:21', '2019-04-08 23:14:21'),
(6, 'browse_menus', 'menus', '2019-04-08 23:14:21', '2019-04-08 23:14:21'),
(7, 'read_menus', 'menus', '2019-04-08 23:14:21', '2019-04-08 23:14:21'),
(8, 'edit_menus', 'menus', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(9, 'add_menus', 'menus', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(10, 'delete_menus', 'menus', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(11, 'browse_roles', 'roles', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(12, 'read_roles', 'roles', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(13, 'edit_roles', 'roles', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(14, 'add_roles', 'roles', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(15, 'delete_roles', 'roles', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(16, 'browse_users', 'users', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(17, 'read_users', 'users', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(18, 'edit_users', 'users', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(19, 'add_users', 'users', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(20, 'delete_users', 'users', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(21, 'browse_settings', 'settings', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(22, 'read_settings', 'settings', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(23, 'edit_settings', 'settings', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(24, 'add_settings', 'settings', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(25, 'delete_settings', 'settings', '2019-04-08 23:14:22', '2019-04-08 23:14:22'),
(26, 'browse_categories', 'categories', '2019-04-08 23:14:28', '2019-04-08 23:14:28'),
(27, 'read_categories', 'categories', '2019-04-08 23:14:28', '2019-04-08 23:14:28'),
(28, 'edit_categories', 'categories', '2019-04-08 23:14:28', '2019-04-08 23:14:28'),
(29, 'add_categories', 'categories', '2019-04-08 23:14:28', '2019-04-08 23:14:28'),
(30, 'delete_categories', 'categories', '2019-04-08 23:14:28', '2019-04-08 23:14:28'),
(31, 'browse_posts', 'posts', '2019-04-08 23:14:30', '2019-04-08 23:14:30'),
(32, 'read_posts', 'posts', '2019-04-08 23:14:30', '2019-04-08 23:14:30'),
(33, 'edit_posts', 'posts', '2019-04-08 23:14:30', '2019-04-08 23:14:30'),
(34, 'add_posts', 'posts', '2019-04-08 23:14:30', '2019-04-08 23:14:30'),
(35, 'delete_posts', 'posts', '2019-04-08 23:14:30', '2019-04-08 23:14:30'),
(36, 'browse_pages', 'pages', '2019-04-08 23:14:31', '2019-04-08 23:14:31'),
(37, 'read_pages', 'pages', '2019-04-08 23:14:31', '2019-04-08 23:14:31'),
(38, 'edit_pages', 'pages', '2019-04-08 23:14:31', '2019-04-08 23:14:31'),
(39, 'add_pages', 'pages', '2019-04-08 23:14:31', '2019-04-08 23:14:31'),
(40, 'delete_pages', 'pages', '2019-04-08 23:14:31', '2019-04-08 23:14:31'),
(41, 'browse_hooks', NULL, '2019-04-08 23:14:35', '2019-04-08 23:14:35'),
(42, 'browse_materias', 'materias', '2019-04-08 23:23:59', '2019-04-08 23:23:59'),
(43, 'read_materias', 'materias', '2019-04-08 23:23:59', '2019-04-08 23:23:59'),
(44, 'edit_materias', 'materias', '2019-04-08 23:23:59', '2019-04-08 23:23:59'),
(45, 'add_materias', 'materias', '2019-04-08 23:23:59', '2019-04-08 23:23:59'),
(46, 'delete_materias', 'materias', '2019-04-08 23:23:59', '2019-04-08 23:23:59'),
(47, 'browse_alumnos', 'alumnos', '2019-04-09 00:53:00', '2019-04-09 00:53:00'),
(48, 'read_alumnos', 'alumnos', '2019-04-09 00:53:00', '2019-04-09 00:53:00'),
(49, 'edit_alumnos', 'alumnos', '2019-04-09 00:53:00', '2019-04-09 00:53:00'),
(50, 'add_alumnos', 'alumnos', '2019-04-09 00:53:00', '2019-04-09 00:53:00'),
(51, 'delete_alumnos', 'alumnos', '2019-04-09 00:53:00', '2019-04-09 00:53:00'),
(52, 'browse_ciudad', 'ciudad', '2019-04-09 00:57:58', '2019-04-09 00:57:58'),
(53, 'read_ciudad', 'ciudad', '2019-04-09 00:57:58', '2019-04-09 00:57:58'),
(54, 'edit_ciudad', 'ciudad', '2019-04-09 00:57:58', '2019-04-09 00:57:58'),
(55, 'add_ciudad', 'ciudad', '2019-04-09 00:57:58', '2019-04-09 00:57:58'),
(56, 'delete_ciudad', 'ciudad', '2019-04-09 00:57:58', '2019-04-09 00:57:58'),
(62, 'browse_profesores', 'profesores', '2019-04-09 01:23:36', '2019-04-09 01:23:36'),
(63, 'read_profesores', 'profesores', '2019-04-09 01:23:36', '2019-04-09 01:23:36'),
(64, 'edit_profesores', 'profesores', '2019-04-09 01:23:36', '2019-04-09 01:23:36'),
(65, 'add_profesores', 'profesores', '2019-04-09 01:23:36', '2019-04-09 01:23:36'),
(66, 'delete_profesores', 'profesores', '2019-04-09 01:23:36', '2019-04-09 01:23:36'),
(67, 'browse_profesor_combo', 'profesor_combo', '2019-04-09 01:31:46', '2019-04-09 01:31:46'),
(68, 'read_profesor_combo', 'profesor_combo', '2019-04-09 01:31:46', '2019-04-09 01:31:46'),
(69, 'edit_profesor_combo', 'profesor_combo', '2019-04-09 01:31:46', '2019-04-09 01:31:46'),
(70, 'add_profesor_combo', 'profesor_combo', '2019-04-09 01:31:46', '2019-04-09 01:31:46'),
(71, 'delete_profesor_combo', 'profesor_combo', '2019-04-09 01:31:46', '2019-04-09 01:31:46'),
(72, 'browse_profesor_materia', 'profesor_materia', '2019-04-09 01:38:11', '2019-04-09 01:38:11'),
(73, 'read_profesor_materia', 'profesor_materia', '2019-04-09 01:38:11', '2019-04-09 01:38:11'),
(74, 'edit_profesor_materia', 'profesor_materia', '2019-04-09 01:38:11', '2019-04-09 01:38:11'),
(75, 'add_profesor_materia', 'profesor_materia', '2019-04-09 01:38:11', '2019-04-09 01:38:11'),
(76, 'delete_profesor_materia', 'profesor_materia', '2019-04-09 01:38:11', '2019-04-09 01:38:11'),
(87, 'browse_formulario', 'formulario', '2019-04-09 02:56:40', '2019-04-09 02:56:40'),
(88, 'read_formulario', 'formulario', '2019-04-09 02:56:40', '2019-04-09 02:56:40'),
(89, 'edit_formulario', 'formulario', '2019-04-09 02:56:40', '2019-04-09 02:56:40'),
(90, 'add_formulario', 'formulario', '2019-04-09 02:56:40', '2019-04-09 02:56:40'),
(91, 'delete_formulario', 'formulario', '2019-04-09 02:56:40', '2019-04-09 02:56:40'),
(92, 'browse_combos', 'combos', '2019-04-12 16:49:36', '2019-04-12 16:49:36'),
(93, 'read_combos', 'combos', '2019-04-12 16:49:36', '2019-04-12 16:49:36'),
(94, 'edit_combos', 'combos', '2019-04-12 16:49:36', '2019-04-12 16:49:36'),
(95, 'add_combos', 'combos', '2019-04-12 16:49:36', '2019-04-12 16:49:36'),
(96, 'delete_combos', 'combos', '2019-04-12 16:49:36', '2019-04-12 16:49:36'),
(102, 'browse_alumno_billetera', 'alumno_billetera', '2019-04-18 01:43:50', '2019-04-18 01:43:50'),
(103, 'read_alumno_billetera', 'alumno_billetera', '2019-04-18 01:43:50', '2019-04-18 01:43:50'),
(104, 'edit_alumno_billetera', 'alumno_billetera', '2019-04-18 01:43:50', '2019-04-18 01:43:50'),
(105, 'add_alumno_billetera', 'alumno_billetera', '2019-04-18 01:43:50', '2019-04-18 01:43:50'),
(106, 'delete_alumno_billetera', 'alumno_billetera', '2019-04-18 01:43:50', '2019-04-18 01:43:50'),
(107, 'browse_combos_horas', 'combos_horas', '2019-04-19 00:53:11', '2019-04-19 00:53:11'),
(108, 'read_combos_horas', 'combos_horas', '2019-04-19 00:53:11', '2019-04-19 00:53:11'),
(109, 'edit_combos_horas', 'combos_horas', '2019-04-19 00:53:11', '2019-04-19 00:53:11'),
(110, 'add_combos_horas', 'combos_horas', '2019-04-19 00:53:11', '2019-04-19 00:53:11'),
(111, 'delete_combos_horas', 'combos_horas', '2019-04-19 00:53:11', '2019-04-19 00:53:11'),
(112, 'browse_sedes', 'sedes', '2019-04-19 02:14:58', '2019-04-19 02:14:58'),
(113, 'read_sedes', 'sedes', '2019-04-19 02:14:58', '2019-04-19 02:14:58'),
(114, 'edit_sedes', 'sedes', '2019-04-19 02:14:58', '2019-04-19 02:14:58'),
(115, 'add_sedes', 'sedes', '2019-04-19 02:14:58', '2019-04-19 02:14:58'),
(116, 'delete_sedes', 'sedes', '2019-04-19 02:14:58', '2019-04-19 02:14:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permission_role`
--

CREATE TABLE `permission_role` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permission_role`
--

INSERT INTO `permission_role` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 3),
(1, 4),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(12, 1),
(13, 1),
(14, 1),
(15, 1),
(16, 1),
(17, 1),
(18, 1),
(19, 1),
(20, 1),
(21, 1),
(22, 1),
(23, 1),
(24, 1),
(25, 1),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(30, 1),
(31, 1),
(32, 1),
(33, 1),
(34, 1),
(35, 1),
(36, 1),
(37, 1),
(38, 1),
(39, 1),
(40, 1),
(41, 1),
(42, 1),
(42, 3),
(42, 4),
(43, 1),
(43, 3),
(43, 4),
(44, 1),
(44, 3),
(45, 1),
(45, 3),
(46, 1),
(47, 1),
(47, 3),
(48, 1),
(48, 3),
(49, 1),
(49, 3),
(50, 1),
(51, 1),
(52, 1),
(52, 3),
(52, 4),
(53, 1),
(53, 3),
(53, 4),
(54, 1),
(54, 3),
(55, 1),
(55, 3),
(56, 1),
(62, 1),
(62, 3),
(62, 4),
(63, 1),
(63, 3),
(63, 4),
(64, 1),
(64, 3),
(65, 1),
(66, 1),
(67, 1),
(67, 3),
(67, 4),
(68, 1),
(68, 3),
(68, 4),
(69, 1),
(69, 4),
(70, 1),
(70, 4),
(71, 1),
(72, 1),
(72, 3),
(72, 4),
(73, 1),
(73, 3),
(73, 4),
(74, 1),
(74, 4),
(75, 1),
(75, 4),
(76, 1),
(87, 1),
(87, 3),
(88, 1),
(88, 3),
(89, 1),
(89, 3),
(90, 1),
(91, 1),
(92, 1),
(92, 3),
(92, 4),
(93, 1),
(93, 3),
(93, 4),
(94, 1),
(94, 3),
(95, 1),
(95, 3),
(96, 1),
(102, 1),
(102, 3),
(103, 1),
(103, 3),
(104, 1),
(105, 1),
(106, 1),
(107, 1),
(107, 3),
(107, 4),
(108, 1),
(108, 3),
(108, 4),
(109, 1),
(109, 3),
(110, 1),
(110, 3),
(111, 1),
(112, 1),
(112, 3),
(112, 4),
(113, 1),
(113, 3),
(113, 4),
(114, 1),
(114, 3),
(115, 1),
(115, 3),
(116, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `posts`
--

CREATE TABLE `posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `author_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `seo_title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_keywords` text COLLATE utf8mb4_unicode_ci,
  `status` enum('PUBLISHED','DRAFT','PENDING') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `posts`
--

INSERT INTO `posts` (`id`, `author_id`, `category_id`, `title`, `seo_title`, `excerpt`, `body`, `image`, `slug`, `meta_description`, `meta_keywords`, `status`, `featured`, `created_at`, `updated_at`) VALUES
(1, 0, NULL, 'Lorem Ipsum Post', NULL, 'This is the excerpt for the Lorem Ipsum Post', '<p>This is the body of the lorem ipsum post</p>', 'posts/post1.jpg', 'lorem-ipsum-post', 'This is the meta description', 'keyword1, keyword2, keyword3', 'PUBLISHED', 0, '2019-04-08 23:14:30', '2019-04-08 23:14:30'),
(2, 0, NULL, 'My Sample Post', NULL, 'This is the excerpt for the sample Post', '<p>This is the body for the sample post, which includes the body.</p>\n                <h2>We can use all kinds of format!</h2>\n                <p>And include a bunch of other stuff.</p>', 'posts/post2.jpg', 'my-sample-post', 'Meta Description for sample post', 'keyword1, keyword2, keyword3', 'PUBLISHED', 0, '2019-04-08 23:14:30', '2019-04-08 23:14:30'),
(3, 0, NULL, 'Latest Post', NULL, 'This is the excerpt for the latest post', '<p>This is the body for the latest post</p>', 'posts/post3.jpg', 'latest-post', 'This is the meta description', 'keyword1, keyword2, keyword3', 'PUBLISHED', 0, '2019-04-08 23:14:30', '2019-04-08 23:14:30'),
(4, 0, NULL, 'Yarr Post', NULL, 'Reef sails nipperkin bring a spring upon her cable coffer jury mast spike marooned Pieces of Eight poop deck pillage. Clipper driver coxswain galleon hempen halter come about pressgang gangplank boatswain swing the lead. Nipperkin yard skysail swab lanyard Blimey bilge water ho quarter Buccaneer.', '<p>Swab deadlights Buccaneer fire ship square-rigged dance the hempen jig weigh anchor cackle fruit grog furl. Crack Jennys tea cup chase guns pressgang hearties spirits hogshead Gold Road six pounders fathom measured fer yer chains. Main sheet provost come about trysail barkadeer crimp scuttle mizzenmast brig plunder.</p>\n<p>Mizzen league keelhaul galleon tender cog chase Barbary Coast doubloon crack Jennys tea cup. Blow the man down lugsail fire ship pinnace cackle fruit line warp Admiral of the Black strike colors doubloon. Tackle Jack Ketch come about crimp rum draft scuppers run a shot across the bow haul wind maroon.</p>\n<p>Interloper heave down list driver pressgang holystone scuppers tackle scallywag bilged on her anchor. Jack Tar interloper draught grapple mizzenmast hulk knave cable transom hogshead. Gaff pillage to go on account grog aft chase guns piracy yardarm knave clap of thunder.</p>', 'posts/post4.jpg', 'yarr-post', 'this be a meta descript', 'keyword1, keyword2, keyword3', 'PUBLISHED', 0, '2019-04-08 23:14:30', '2019-04-08 23:14:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `user_id` int(11) NOT NULL,
  `nombres` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellidos` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cedula` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `celular` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apodo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ubicacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clases` bit(1) DEFAULT NULL,
  `tareas` bit(1) DEFAULT NULL,
  `disponible` bit(1) DEFAULT NULL,
  `estado_cuenta` decimal(10,2) DEFAULT NULL,
  `hoja_vida` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor_clase` decimal(10,2) DEFAULT NULL,
  `valor_tarea` decimal(10,2) DEFAULT NULL,
  `calificacion` decimal(10,2) DEFAULT NULL,
  `activo` bit(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`user_id`, `nombres`, `apellidos`, `cedula`, `celular`, `correo`, `apodo`, `ubicacion`, `ciudad`, `clases`, `tareas`, `disponible`, `estado_cuenta`, `hoja_vida`, `titulo`, `valor_clase`, `valor_tarea`, `calificacion`, `activo`, `created_at`, `updated_at`) VALUES
(5, 'profe1', 'apeProfe', NULL, 'asdasd', 'prof@prof.com', 'mister', 'asdasd', 'Ambato', b'1', b'1', b'1', NULL, NULL, NULL, NULL, NULL, NULL, b'0', '2019-04-18 23:32:33', '2019-04-19 03:14:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesor_combo`
--

CREATE TABLE `profesor_combo` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `combo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` bit(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesor_materia`
--

CREATE TABLE `profesor_materia` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `materia` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  `activa` bit(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Administrator', '2019-04-08 23:14:21', '2019-04-08 23:14:21'),
(2, 'user', 'Normal User', '2019-04-08 23:14:21', '2019-04-08 23:14:21'),
(3, 'BemteAdmin', 'Administrador de Bemte', '2019-04-09 01:48:41', '2019-04-09 01:48:41'),
(4, 'Profesor', 'Profesor', '2019-04-19 01:54:13', '2019-04-19 01:54:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes`
--

CREATE TABLE `sedes` (
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ciudad` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activa` bit(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sedes`
--

INSERT INTO `sedes` (`nombre`, `ciudad`, `activa`, `created_at`, `updated_at`) VALUES
('UDLA', 'Quito', b'1', '2019-04-19 02:20:25', '2019-04-19 02:21:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `details` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int(11) NOT NULL DEFAULT '1',
  `group` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `settings`
--

INSERT INTO `settings` (`id`, `key`, `display_name`, `value`, `details`, `type`, `order`, `group`) VALUES
(1, 'site.title', 'Site Title', 'Site Title', '', 'text', 1, 'Site'),
(2, 'site.description', 'Site Description', 'Site Description', '', 'text', 2, 'Site'),
(3, 'site.logo', 'Site Logo', '', '', 'image', 3, 'Site'),
(4, 'admin.google_analytics_tracking_id', 'Google Analytics Tracking ID', NULL, '', 'text', 4, 'Admin'),
(5, 'admin.bg_image', 'Admin Background Image', 'settings\\April2019\\DOVKDpBNyg47ZjQdqxwx.png', '', 'image', 5, 'Admin'),
(6, 'admin.title', 'Admin Title', 'BEMTE', '', 'text', 1, 'Admin'),
(7, 'admin.description', 'Admin Description', 'Sé Mi Profesor', '', 'text', 2, 'Admin'),
(8, 'admin.loader', 'Admin Loader', '', '', 'image', 3, 'Admin'),
(9, 'admin.icon_image', 'Admin Icon Image', 'settings\\April2019\\dWpVmpSOPYkGWeMSUe97.png', '', 'image', 4, 'Admin'),
(10, 'site.google_analytics_client_id', 'Google Analytics Client ID (used for admin dashboard)', NULL, '', 'text', 1, 'Site'),
(11, 'admin.page_resumen', 'Resumen', 'Aplicación GRATUITA para dispositivos móviles, en la que\r\nlos estudiantes podrán acceder para recibir ayuda en sus\r\ntareas o solicitar clases personalizadas sobre cualquier tema.', NULL, 'text_area', 6, 'Admin');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `translations`
--

CREATE TABLE `translations` (
  `id` int(10) UNSIGNED NOT NULL,
  `table_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `column_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foreign_key` int(10) UNSIGNED NOT NULL,
  `locale` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `translations`
--

INSERT INTO `translations` (`id`, `table_name`, `column_name`, `foreign_key`, `locale`, `value`, `created_at`, `updated_at`) VALUES
(1, 'data_types', 'display_name_singular', 5, 'pt', 'Post', '2019-04-08 23:14:32', '2019-04-08 23:14:32'),
(2, 'data_types', 'display_name_singular', 6, 'pt', 'Página', '2019-04-08 23:14:32', '2019-04-08 23:14:32'),
(3, 'data_types', 'display_name_singular', 1, 'pt', 'Utilizador', '2019-04-08 23:14:32', '2019-04-08 23:14:32'),
(4, 'data_types', 'display_name_singular', 4, 'pt', 'Categoria', '2019-04-08 23:14:32', '2019-04-08 23:14:32'),
(5, 'data_types', 'display_name_singular', 2, 'pt', 'Menu', '2019-04-08 23:14:32', '2019-04-08 23:14:32'),
(6, 'data_types', 'display_name_singular', 3, 'pt', 'Função', '2019-04-08 23:14:32', '2019-04-08 23:14:32'),
(7, 'data_types', 'display_name_plural', 5, 'pt', 'Posts', '2019-04-08 23:14:32', '2019-04-08 23:14:32'),
(8, 'data_types', 'display_name_plural', 6, 'pt', 'Páginas', '2019-04-08 23:14:32', '2019-04-08 23:14:32'),
(9, 'data_types', 'display_name_plural', 1, 'pt', 'Utilizadores', '2019-04-08 23:14:32', '2019-04-08 23:14:32'),
(10, 'data_types', 'display_name_plural', 4, 'pt', 'Categorias', '2019-04-08 23:14:32', '2019-04-08 23:14:32'),
(11, 'data_types', 'display_name_plural', 2, 'pt', 'Menus', '2019-04-08 23:14:32', '2019-04-08 23:14:32'),
(12, 'data_types', 'display_name_plural', 3, 'pt', 'Funções', '2019-04-08 23:14:32', '2019-04-08 23:14:32'),
(13, 'categories', 'slug', 1, 'pt', 'categoria-1', '2019-04-08 23:14:32', '2019-04-08 23:14:32'),
(14, 'categories', 'name', 1, 'pt', 'Categoria 1', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(15, 'categories', 'slug', 2, 'pt', 'categoria-2', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(16, 'categories', 'name', 2, 'pt', 'Categoria 2', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(17, 'pages', 'title', 1, 'pt', 'Olá Mundo', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(18, 'pages', 'slug', 1, 'pt', 'ola-mundo', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(19, 'pages', 'body', 1, 'pt', '<p>Olá Mundo. Scallywag grog swab Cat o\'nine tails scuttle rigging hardtack cable nipper Yellow Jack. Handsomely spirits knave lad killick landlubber or just lubber deadlights chantey pinnace crack Jennys tea cup. Provost long clothes black spot Yellow Jack bilged on her anchor league lateen sail case shot lee tackle.</p>\r\n<p>Ballast spirits fluke topmast me quarterdeck schooner landlubber or just lubber gabion belaying pin. Pinnace stern galleon starboard warp carouser to go on account dance the hempen jig jolly boat measured fer yer chains. Man-of-war fire in the hole nipperkin handsomely doubloon barkadeer Brethren of the Coast gibbet driver squiffy.</p>', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(20, 'menu_items', 'title', 1, 'pt', 'Painel de Controle', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(21, 'menu_items', 'title', 2, 'pt', 'Media', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(22, 'menu_items', 'title', 12, 'pt', 'Publicações', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(23, 'menu_items', 'title', 3, 'pt', 'Utilizadores', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(24, 'menu_items', 'title', 11, 'pt', 'Categorias', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(25, 'menu_items', 'title', 13, 'pt', 'Páginas', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(26, 'menu_items', 'title', 4, 'pt', 'Funções', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(27, 'menu_items', 'title', 5, 'pt', 'Ferramentas', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(28, 'menu_items', 'title', 6, 'pt', 'Menus', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(29, 'menu_items', 'title', 7, 'pt', 'Base de dados', '2019-04-08 23:14:33', '2019-04-08 23:14:33'),
(30, 'menu_items', 'title', 10, 'pt', 'Configurações', '2019-04-08 23:14:34', '2019-04-08 23:14:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT 'users/default.png',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `settings` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `tipo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` bit(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `avatar`, `email_verified_at`, `password`, `remember_token`, `settings`, `created_at`, `updated_at`, `tipo`, `activo`) VALUES
(1, 1, 'Admin', 'admin@admin.com', 'users/default.png', NULL, '$2y$10$gXsyGJH7WQ9marK2dXesjOeNChQ.DOHezZVtbHeLXBqB1qC96kGZq', '9cHRPfZB5OJGgXyhQuLQxuhm9ZUoy5wE5hhvYCBmgyvLVfbWnyNlgqF53RTf', '{\"locale\":\"es\"}', '2019-04-08 23:14:29', '2019-04-09 01:49:53', 'Administrador', b'1'),
(2, 3, 'bemte', 'bemte@bemte.com', 'users/default.png', NULL, '$2y$10$IVYLdrZr0W8nfLoBDLjzK.hLg7h7wmp/YVI3Q2.xk1y5SWZLfLWEa', NULL, '{\"locale\":\"es\"}', '2019-04-09 01:49:36', '2019-04-09 01:49:36', 'Administrador', b'1'),
(3, 2, 'alum1 alummApellido', 'asdad@asd.com', NULL, NULL, '$2y$10$4XDSJnznOw9CtCjeddR4NOXdU45LCuE36gXX3.MXEUQDuhniwtWQ.', NULL, NULL, '2019-04-11 03:25:55', '2019-04-18 23:12:59', 'Alumno', b'1'),
(4, 2, 'juan fonseca', 'manuel3@bemte.com', NULL, NULL, '$2y$10$O/DEudTVHVTrN.VWxTinQ.fGyD65aipcQdtnKl.nHobRuFeDpXKb6', NULL, NULL, '2019-04-18 20:25:41', '2019-04-18 20:25:41', 'Alumno', b'1'),
(5, 4, 'profe1 apeProfe', 'prof@prof.com', NULL, NULL, '$2y$10$kR77QlmJ9OjLnx0vblauWex13GJL4GIJreu0UpnsBBRWhI80UEX5O', NULL, NULL, '2019-04-18 23:32:33', '2019-04-18 23:32:33', 'Profesor', b'1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `alumnos`
--
ALTER TABLE `alumnos`
  ADD PRIMARY KEY (`user_id`);

--
-- Indices de la tabla `alumno_billetera`
--
ALTER TABLE `alumno_billetera`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`),
  ADD KEY `categories_parent_id_foreign` (`parent_id`);

--
-- Indices de la tabla `ciudad`
--
ALTER TABLE `ciudad`
  ADD PRIMARY KEY (`ciudad`);

--
-- Indices de la tabla `combos`
--
ALTER TABLE `combos`
  ADD PRIMARY KEY (`nombre`);

--
-- Indices de la tabla `combos_horas`
--
ALTER TABLE `combos_horas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `data_rows`
--
ALTER TABLE `data_rows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `data_rows_data_type_id_foreign` (`data_type_id`);

--
-- Indices de la tabla `data_types`
--
ALTER TABLE `data_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `data_types_name_unique` (`name`),
  ADD UNIQUE KEY `data_types_slug_unique` (`slug`);

--
-- Indices de la tabla `formulario`
--
ALTER TABLE `formulario`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`nombre`);

--
-- Indices de la tabla `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `menus_name_unique` (`name`);

--
-- Indices de la tabla `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_items_menu_id_foreign` (`menu_id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pages_slug_unique` (`slug`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indices de la tabla `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `permissions_key_index` (`key`);

--
-- Indices de la tabla `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `permission_role_permission_id_index` (`permission_id`),
  ADD KEY `permission_role_role_id_index` (`role_id`);

--
-- Indices de la tabla `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `posts_slug_unique` (`slug`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`user_id`);

--
-- Indices de la tabla `profesor_combo`
--
ALTER TABLE `profesor_combo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `profesor_materia`
--
ALTER TABLE `profesor_materia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indices de la tabla `sedes`
--
ALTER TABLE `sedes`
  ADD PRIMARY KEY (`nombre`);

--
-- Indices de la tabla `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indices de la tabla `translations`
--
ALTER TABLE `translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `translations_table_name_column_name_foreign_key_locale_unique` (`table_name`,`column_name`,`foreign_key`,`locale`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_id_foreign` (`role_id`);

--
-- Indices de la tabla `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `user_roles_user_id_index` (`user_id`),
  ADD KEY `user_roles_role_id_index` (`role_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `alumno_billetera`
--
ALTER TABLE `alumno_billetera`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `combos_horas`
--
ALTER TABLE `combos_horas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `data_rows`
--
ALTER TABLE `data_rows`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT de la tabla `data_types`
--
ALTER TABLE `data_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `formulario`
--
ALTER TABLE `formulario`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT de la tabla `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `profesor_combo`
--
ALTER TABLE `profesor_combo`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `profesor_materia`
--
ALTER TABLE `profesor_materia`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `translations`
--
ALTER TABLE `translations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `data_rows`
--
ALTER TABLE `data_rows`
  ADD CONSTRAINT `data_rows_data_type_id_foreign` FOREIGN KEY (`data_type_id`) REFERENCES `data_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Filtros para la tabla `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
