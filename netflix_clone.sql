-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-10-2025 a las 21:59:05
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
-- Base de datos: `netflix_clone`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mi_lista`
--

CREATE TABLE `mi_lista` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `pelicula_id` int(11) DEFAULT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mi_lista`
--

INSERT INTO `mi_lista` (`id`, `usuario_id`, `pelicula_id`, `fecha_agregado`) VALUES
(1, 6, 38, '2025-09-27 20:46:47'),
(2, 6, 25, '2025-09-27 20:46:50'),
(3, 6, 12, '2025-09-27 20:46:54'),
(6, 1, 38, '2025-09-27 22:03:51'),
(7, 1, 53, '2025-09-27 22:25:13'),
(8, 1, 24, '2025-09-27 22:25:25'),
(9, 6, 33, '2025-09-27 23:03:08'),
(10, 6, 39, '2025-09-27 23:03:18'),
(11, 1, 32, '2025-10-07 01:23:02'),
(12, 1, 2, '2025-10-15 06:40:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `peliculas`
--

CREATE TABLE `peliculas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `genero` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen` varchar(255) NOT NULL,
  `archivo_video` varchar(255) DEFAULT NULL,
  `duracion` varchar(20) DEFAULT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `peliculas`
--

INSERT INTO `peliculas` (`id`, `titulo`, `genero`, `descripcion`, `imagen`, `archivo_video`, `duracion`, `fecha_subida`, `usuario_id`, `activo`) VALUES
(1, 'Avengers: Endgame', 'Acción', NULL, 'https://static.thcdn.com/images/large/original//productimg/1600/1600/12091894-1574815082301529.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(2, 'John Wick', 'Acción', NULL, 'https://upload.wikimedia.org/wikipedia/en/thumb/9/98/John_Wick_TeaserPoster.jpg/250px-John_Wick_TeaserPoster.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(3, 'Mad Max: Fury Road', 'Acción', NULL, 'https://m.media-amazon.com/images/M/MV5BZDRkODJhOTgtOTc1OC00NTgzLTk4NjItNDgxZDY4YjlmNDY2XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(4, 'Jumanji: Bienvenidos a la jungla', 'Comedia', NULL, 'https://es.web.img2.acsta.net/pictures/17/11/08/14/53/3315450.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(5, 'The Mask', 'Comedia', NULL, 'https://m.media-amazon.com/images/M/MV5BNGNmNjI0ZmMtMzI5MC00ZjUyLWFlZDEtYjUyMGZlN2E3N2E2XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(6, 'Superbad', 'Comedia', NULL, 'https://image.tmdb.org/t/p/w500/ek8e8txUyUwd2BNqj6lFEerJfbq.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(7, 'It', 'Terror', NULL, 'https://m.media-amazon.com/images/M/MV5BZGZmOTZjNzUtOTE4OS00OGM3LWJiNGEtZjk4Yzg2M2Q1YzYxXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(8, 'El Conjuro', 'Terror', NULL, 'https://image.tmdb.org/t/p/w500/wVYREutTvI2tmxr6ujrHT704wGF.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(9, 'Actividad Paranormal', 'Terror', NULL, 'https://m.media-amazon.com/images/I/A1YaZ6bLgIL._SL1500_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(10, 'Titanic', 'Romance', NULL, 'https://m.media-amazon.com/images/M/MV5BYzYyN2FiZmUtYWYzMy00MzViLWJkZTMtOGY1ZjgzNWMwN2YxXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(11, 'La La Land', 'Romance', NULL, 'https://image.tmdb.org/t/p/w500/uDO8zWDhfWwoFdKS4fzkUJt0Rf0.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(12, 'Diario de una pasión', 'Romance', NULL, 'https://irs.www.warnerbroslatino.com/keyart-jpeg/movies/media/browser/notebook_v_dd_ka_tt_2000x2841_300dpi_mx_d1c43587.jpeg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(13, 'Interstellar', 'Ciencia ficción', NULL, 'https://resizing.flixster.com/7c3qnZfPzZgID7Ft97PccFwEf9U=/206x305/v2/https://resizing.flixster.com/-XZAfHZM39UwaGJIFWKAE8fS0ak=/v3/t/assets/p10543523_p_v8_as.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(14, 'Inception', 'Ciencia ficción', NULL, 'https://image.tmdb.org/t/p/w500/edv5CZvWj09upOsy2Y6IwDhK8bt.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(15, 'Matrix', 'Ciencia ficción', NULL, 'https://image.tmdb.org/t/p/w500/aOIuZAjPaRIE6CMzbazvcHuHXDc.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(16, 'En busca de la felicidad', 'Drama', NULL, 'https://m.media-amazon.com/images/M/MV5BOGQ2NTgzMjQtMTkwYy00NWIyLWIyMWItZDAxMTdjYjIyNzgwXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(17, 'El Pianista', 'Drama', NULL, 'https://image.tmdb.org/t/p/w500/2hFvxCCWrTmCYwfy7yum0GKRi3Y.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(18, 'La lista de Schindler', 'Drama', NULL, 'https://image.tmdb.org/t/p/w500/c8Ass7acuOe4za6DhSattE359gr.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(19, 'Gladiator', 'Acción', NULL, 'https://es.web.img3.acsta.net/medias/nmedia/18/70/92/02/20149073.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(20, 'Black Panther', 'Acción', NULL, 'https://cdn.kobo.com/book-images/7e060fa5-3a91-4ed1-94a3-57274b53e233/353/569/90/False/marvel-s-black-panther-4.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(21, 'Mujer Maravilla', 'Acción', NULL, 'https://play-lh.googleusercontent.com/tiw0lFI1ROjxRDA4HK-L13EqU5__qTwakaTb6fPg-eDgqCze9pOO7PB6-o0RDYd5K2ms', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(22, 'Iron Man', 'Acción', NULL, 'https://m.media-amazon.com/images/M/MV5BMTczNTI2ODUwOF5BMl5BanBnXkFtZTcwMTU0NTIzMw@@._V1_FMjpg_UX1000_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(23, 'The Dark Knight', 'Acción', NULL, 'https://m.media-amazon.com/images/S/pv-target-images/e9a43e647b2ca70e75a3c0af046c4dfdcd712380889779cbdc2c57d94ab63902.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(24, 'Captain America: Civil War', 'Acción', NULL, 'https://cdn.cinencuentro.com/wp-content/uploads/2016/04/POSTER.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(25, 'Deadpool', 'Acción', NULL, 'https://upload.wikimedia.org/wikipedia/en/2/23/Deadpool_%282016_poster%29.png', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(26, '¿... Y dónde estan las Rubias?', 'Comedia', NULL, 'https://m.media-amazon.com/images/S/pv-target-images/34b2d39ed55f6df09da8871919da835dba6e86d68af01079ab75635836f00200.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(27, 'Resacón en Las Vegas', 'Comedia', NULL, 'https://es.web.img3.acsta.net/medias/nmedia/18/69/99/97/20028573.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(28, 'Anchorman: El Legendario Presentador', 'Comedia', NULL, 'https://m.media-amazon.com/images/S/pv-target-images/914f5e9c2ccef81c36bed1e56d847ec1977419513edba9cf50133c82f8b7cd74.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(29, 'Hazme reír', 'Comedia', NULL, 'https://es.web.img3.acsta.net/c_310_420/medias/nmedia/18/69/37/46/19152941.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(30, 'Tropic Thunder', 'Comedia', NULL, 'https://play-lh.googleusercontent.com/cxqu_vUdejinsi9wwNhOb8akeRl8wZb9LoG-PTxbe64-DAxELbpeKrAKFeYfjDqcjdY=w240-h480-rw', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(31, 'Atrapado en el Tiempo', 'Comedia', NULL, 'https://m.media-amazon.com/images/M/MV5BMjJiY2NhMTAtNDcyMS00NjU1LWE1ODAtYzc5MTE3MTE5ZTFkXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(32, 'El Gran Lebowski', 'Comedia', NULL, 'https://images.justwatch.com/poster/76802182/s718/el-gran-lebowski.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(33, 'Terrifier 3', 'Terror', NULL, 'https://es.web.img3.acsta.net/c_310_420/img/b6/64/b6649bd264af511b3bb18089a10ac96a.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(34, 'Scream', 'Terror', NULL, 'https://play-lh.googleusercontent.com/tP88ruXqBPZtzr60JLuecuXotu3uydosq253zsgE1A0QkFJPjaU-PW3xzy2f9GAf3FFg8OAbbjloqaYvgdM', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(35, 'Un Lugar en Silencio', 'Terror', NULL, 'https://http2.mlstatic.com/D_NQ_NP_727679-MLU71568058670_092023-O.webp', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(36, 'El Aro 3', 'Terror', NULL, 'https://img.chilango.com/2017/02/el-aro-3-poster-1.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(37, 'La Casa del Terror', 'Terror', NULL, 'https://es.web.img3.acsta.net/pictures/19/09/26/16/34/3403554.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(38, 'Devuélvemela', 'Terror', NULL, 'https://m.media-amazon.com/images/M/MV5BMjBlMzY2YzEtOGJmMy00NmNlLThmNTktODhlZmM5ODdlZTQ3XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(39, 'Expediente Warren: El último rito', 'Terror', NULL, 'https://pics.filmaffinity.com/the_conjuring_last_rites-547210494-large.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(40, 'Orgullo y Prejuicio', 'Romance', NULL, 'https://images.justwatch.com/poster/203481059/s718/orgullo-y-prejuicio.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(41, 'Moulin Rouge', 'Romance', NULL, 'https://play-lh.googleusercontent.com/hxXjsyY9npV8d8hn54A2ZzYNr4CFDVidnUMVlyCJ0WtX0sBV1Qqy4oZxmRPFo4i4X2s', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(42, 'Antes del Amanecer', 'Romance', NULL, 'https://m.media-amazon.com/images/S/pv-target-images/e5525361d6c4df27a8177ff8f87bc8b2bef943331c796f716fd08e5aac537e40.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(43, 'Eterno Resplandor de una Mente sin Recuerdos', 'Romance', NULL, 'https://m.media-amazon.com/images/M/MV5BYjcxNDFhZDktYTllZS00ZjE2LWFlOWMtN2Y5ZGUyZGRhMDA4XkEyXkFqcGc@._V1_.jpgg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(44, 'Romeo + Julieta', 'Romance', NULL, 'https://m.media-amazon.com/images/M/MV5BZjBhYjkxN2EtNDc1Yy00NTViLTkxMjQtMDYxMzM0MzA3NGQ4XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(45, 'Bajo la Misma Estrella', 'Romance', NULL, 'https://m.media-amazon.com/images/M/MV5BYzJkNTQ1MmUtZjliYi00OGQwLTgyY2UtOTk3MTM1Mzk4NmRjXkEyXkFqcGc@._V1_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(46, 'El Lado Bueno de las Cosas', 'Romance', NULL, 'https://es.web.img2.acsta.net/pictures/15/11/06/11/39/323127.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(47, 'Blade Runner 2049', 'Ciencia ficción', NULL, 'https://upload.wikimedia.org/wikipedia/en/9/9b/Blade_Runner_2049_poster.png', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(48, 'Guardianes de la Galaxia', 'Ciencia ficción', NULL, 'https://lumiere-a.akamaihd.net/v1/images/lat_2ae5e247.jpeg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(49, 'Star Wars: El Despertar de la Fuerza', 'Ciencia ficción', NULL, 'https://static.bookscovers.es/imagenes/9788408/978840815083.JPG', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(50, 'Avatar', 'Ciencia ficción', NULL, 'https://m.media-amazon.com/images/M/MV5BZDYxY2I1OGMtN2Y4MS00ZmU1LTgyNDAtODA0MzAyYjI0N2Y2XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(51, 'Ready Player One', 'Ciencia ficción', NULL, 'https://i.blogs.es/1758c6/rpo/450_1000.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(52, 'Al Filo del Mañana', 'Ciencia ficción', NULL, 'https://es.web.img3.acsta.net/pictures/14/05/05/12/46/010208.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(53, 'Mundo Jurásico', 'Ciencia ficción', NULL, 'https://play-lh.googleusercontent.com/RDC-pQHgvzSJc9EMB3r3q8R_qp55AkQAt9Eto4OgaKAkhn8taI5rmt0dTAHjUCPm1dAHjg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(54, 'Harta', 'Drama', NULL, 'https://www.lavanguardia.com/peliculas-series/images/movie/poster/2025/6/w300/eAf8Is1xX4gq4Jk14iFC1qhmT8R.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(55, 'Sueño de Fuga', 'Drama', NULL, 'https://m.media-amazon.com/images/M/MV5BMzdhNGE2ZjAtYjFjYS00YmY2LTg4MDctZTNhN2VlOGM3NjUwXkEyXkFqcGc@._V1_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(56, 'El Club de la Pelea', 'Drama', NULL, 'https://i.scdn.co/image/ab67616d0000b27393c4e1b3672ce860e30f34d0', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(57, 'El Padrino', 'Drama', NULL, 'https://m.media-amazon.com/images/M/MV5BZmNiNzM4MTctODI5YS00MzczLWE2MzktNzY4YmNjYjA5YmY1XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(58, '12 Años de Esclavitud', 'Drama', NULL, 'https://play-lh.googleusercontent.com/3_bi7bK4ujfpBOEz9AXwKZut8bGa67N0gJ7S5nztFYcsXF6-DJDP6PGm4ZG5d1W-0Bg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(59, 'Una Mente Brillante', 'Drama', NULL, 'https://http2.mlstatic.com/D_NQ_NP_603776-MLA76109609850_052024-OO.jpg', NULL, NULL, '2025-10-09 02:16:31', NULL, 1),
(60, 'Cambios de Reinas', 'Drama', 'Tras años de guerra entre Francia y España, que han dejado a los dos países debilitados, Felipe de Orleans, el regente de Francia, pone en marcha un plan para consolidar la paz entre ambas naciones. Casará a su hija de 12 años, señorita de Montpensier, con el heredero del trono español, y a Luis XV, próximo Rey de Francia, con Mariana Victoria, de 4 años, Infanta de España. Una adolescente y una niña se verán atrapadas en una red de alianzas, traiciones y juegos de poder.', 'https://cartelera.elpais.com/assets/uploads/2019/02/18131428/C_19722.jpg', NULL, '1h 45m', '2025-10-09 02:16:31', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preferencias`
--

CREATE TABLE `preferencias` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `genero` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `preferencias`
--

INSERT INTO `preferencias` (`id`, `usuario_id`, `genero`) VALUES
(9, 2, 'Acción'),
(30, 6, 'Terror'),
(50, 1, 'Ciencia ficción');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`) VALUES
(1, 'less', 'adanaquemazaleslyegianella25@gmail.com', '$2y$10$eFEvWWt8g1eaJSifpEs81epBOc2oEbTuFDeruN7XCyFv8MjrMPemW'),
(2, 'Deybi', 'deybi@gmail.com', '$2y$10$72YLzAI.W9EY8744mC1ltOcWky7FRbP9SohRVN.KXIc4Pbr88Qjfm'),
(6, 'Paolo', 'le@gmail.com', '$2y$10$DBUzcVO.5CcXC9ZN7KYx0.JEyzWFB8ZFZxqbNiJK9AFcU6BG8l0xu');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `valoraciones`
--

CREATE TABLE `valoraciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pelicula_id` int(11) NOT NULL,
  `valoracion` enum('like','dislike') NOT NULL,
  `fecha_valoracion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `valoraciones`
--

INSERT INTO `valoraciones` (`id`, `usuario_id`, `pelicula_id`, `valoracion`, `fecha_valoracion`) VALUES
(1, 1, 45, 'like', '2025-09-27 22:02:54'),
(2, 1, 30, 'like', '2025-09-27 22:25:20'),
(3, 6, 48, 'like', '2025-09-27 23:01:54'),
(4, 1, 23, 'like', '2025-10-07 01:23:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `genero` varchar(50) NOT NULL,
  `archivo_video` varchar(255) NOT NULL,
  `imagen` varchar(255) NOT NULL,
  `duracion` varchar(20) DEFAULT NULL,
  `fecha_subida` timestamp NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `mi_lista`
--
ALTER TABLE `mi_lista`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_usuario_pelicula` (`usuario_id`,`pelicula_id`),
  ADD KEY `pelicula_id` (`pelicula_id`);

--
-- Indices de la tabla `peliculas`
--
ALTER TABLE `peliculas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `preferencias`
--
ALTER TABLE `preferencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `valoraciones`
--
ALTER TABLE `valoraciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_usuario_pelicula` (`usuario_id`,`pelicula_id`),
  ADD KEY `pelicula_id` (`pelicula_id`);

--
-- Indices de la tabla `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `mi_lista`
--
ALTER TABLE `mi_lista`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `peliculas`
--
ALTER TABLE `peliculas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT de la tabla `preferencias`
--
ALTER TABLE `preferencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `valoraciones`
--
ALTER TABLE `valoraciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `mi_lista`
--
ALTER TABLE `mi_lista`
  ADD CONSTRAINT `mi_lista_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `mi_lista_ibfk_2` FOREIGN KEY (`pelicula_id`) REFERENCES `peliculas` (`id`);

--
-- Filtros para la tabla `peliculas`
--
ALTER TABLE `peliculas`
  ADD CONSTRAINT `peliculas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `preferencias`
--
ALTER TABLE `preferencias`
  ADD CONSTRAINT `preferencias_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `valoraciones`
--
ALTER TABLE `valoraciones`
  ADD CONSTRAINT `valoraciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `valoraciones_ibfk_2` FOREIGN KEY (`pelicula_id`) REFERENCES `peliculas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
