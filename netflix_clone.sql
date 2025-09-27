-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-09-2025 a las 01:34:29
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
(10, 6, 39, '2025-09-27 23:03:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `peliculas`
--

CREATE TABLE `peliculas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `genero` varchar(50) NOT NULL,
  `imagen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `peliculas`
--

INSERT INTO `peliculas` (`id`, `titulo`, `genero`, `imagen`) VALUES
(1, 'Avengers: Endgame', 'Acción', 'https://static.thcdn.com/images/large/original//productimg/1600/1600/12091894-1574815082301529.jpg'),
(2, 'John Wick', 'Acción', 'https://upload.wikimedia.org/wikipedia/en/thumb/9/98/John_Wick_TeaserPoster.jpg/250px-John_Wick_TeaserPoster.jpg'),
(3, 'Mad Max: Fury Road', 'Acción', 'https://m.media-amazon.com/images/M/MV5BZDRkODJhOTgtOTc1OC00NTgzLTk4NjItNDgxZDY4YjlmNDY2XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
(4, 'Jumanji: Bienvenidos a la jungla', 'Comedia', 'https://es.web.img2.acsta.net/pictures/17/11/08/14/53/3315450.jpg'),
(5, 'The Mask', 'Comedia', 'https://m.media-amazon.com/images/M/MV5BNGNmNjI0ZmMtMzI5MC00ZjUyLWFlZDEtYjUyMGZlN2E3N2E2XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
(6, 'Superbad', 'Comedia', 'https://image.tmdb.org/t/p/w500/ek8e8txUyUwd2BNqj6lFEerJfbq.jpg'),
(7, 'It', 'Terror', 'https://m.media-amazon.com/images/M/MV5BZGZmOTZjNzUtOTE4OS00OGM3LWJiNGEtZjk4Yzg2M2Q1YzYxXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
(8, 'El Conjuro', 'Terror', 'https://image.tmdb.org/t/p/w500/wVYREutTvI2tmxr6ujrHT704wGF.jpg'),
(9, 'Actividad Paranormal', 'Terror', 'https://m.media-amazon.com/images/I/A1YaZ6bLgIL._SL1500_.jpg'),
(10, 'Titanic', 'Romance', 'https://m.media-amazon.com/images/M/MV5BYzYyN2FiZmUtYWYzMy00MzViLWJkZTMtOGY1ZjgzNWMwN2YxXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
(11, 'La La Land', 'Romance', 'https://image.tmdb.org/t/p/w500/uDO8zWDhfWwoFdKS4fzkUJt0Rf0.jpg'),
(12, 'Diario de una pasión', 'Romance', 'https://irs.www.warnerbroslatino.com/keyart-jpeg/movies/media/browser/notebook_v_dd_ka_tt_2000x2841_300dpi_mx_d1c43587.jpeg'),
(13, 'Interstellar', 'Ciencia ficción', 'https://resizing.flixster.com/7c3qnZfPzZgID7Ft97PccFwEf9U=/206x305/v2/https://resizing.flixster.com/-XZAfHZM39UwaGJIFWKAE8fS0ak=/v3/t/assets/p10543523_p_v8_as.jpg'),
(14, 'Inception', 'Ciencia ficción', 'https://image.tmdb.org/t/p/w500/edv5CZvWj09upOsy2Y6IwDhK8bt.jpg'),
(15, 'Matrix', 'Ciencia ficción', 'https://image.tmdb.org/t/p/w500/aOIuZAjPaRIE6CMzbazvcHuHXDc.jpg'),
(16, 'En busca de la felicidad', 'Drama', 'https://m.media-amazon.com/images/M/MV5BOGQ2NTgzMjQtMTkwYy00NWIyLWIyMWItZDAxMTdjYjIyNzgwXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
(17, 'El Pianista', 'Drama', 'https://image.tmdb.org/t/p/w500/2hFvxCCWrTmCYwfy7yum0GKRi3Y.jpg'),
(18, 'La lista de Schindler', 'Drama', 'https://image.tmdb.org/t/p/w500/c8Ass7acuOe4za6DhSattE359gr.jpg'),
(19, 'Gladiator', 'Acción', 'https://es.web.img3.acsta.net/medias/nmedia/18/70/92/02/20149073.jpg'),
(20, 'Black Panther', 'Acción', 'https://cdn.kobo.com/book-images/7e060fa5-3a91-4ed1-94a3-57274b53e233/353/569/90/False/marvel-s-black-panther-4.jpg'),
(21, 'Mujer Maravilla', 'Acción', 'https://play-lh.googleusercontent.com/tiw0lFI1ROjxRDA4HK-L13EqU5__qTwakaTb6fPg-eDgqCze9pOO7PB6-o0RDYd5K2ms'),
(22, 'Iron Man', 'Acción', 'https://m.media-amazon.com/images/M/MV5BMTczNTI2ODUwOF5BMl5BanBnXkFtZTcwMTU0NTIzMw@@._V1_FMjpg_UX1000_.jpg'),
(23, 'The Dark Knight', 'Acción', 'https://m.media-amazon.com/images/S/pv-target-images/e9a43e647b2ca70e75a3c0af046c4dfdcd712380889779cbdc2c57d94ab63902.jpg'),
(24, 'Captain America: Civil War', 'Acción', 'https://cdn.cinencuentro.com/wp-content/uploads/2016/04/POSTER.jpg'),
(25, 'Deadpool', 'Acción', 'https://upload.wikimedia.org/wikipedia/en/2/23/Deadpool_%282016_poster%29.png'),
(26, '¿... Y dónde estan las Rubias?', 'Comedia', 'https://m.media-amazon.com/images/S/pv-target-images/34b2d39ed55f6df09da8871919da835dba6e86d68af01079ab75635836f00200.jpg'),
(27, 'Resacón en Las Vegas', 'Comedia', 'https://es.web.img3.acsta.net/medias/nmedia/18/69/99/97/20028573.jpg'),
(28, 'Anchorman: El Legendario Presentador', 'Comedia', 'https://m.media-amazon.com/images/S/pv-target-images/914f5e9c2ccef81c36bed1e56d847ec1977419513edba9cf50133c82f8b7cd74.jpg'),
(29, 'Hazme reír', 'Comedia', 'https://es.web.img3.acsta.net/c_310_420/medias/nmedia/18/69/37/46/19152941.jpg'),
(30, 'Tropic Thunder', 'Comedia', 'https://play-lh.googleusercontent.com/cxqu_vUdejinsi9wwNhOb8akeRl8wZb9LoG-PTxbe64-DAxELbpeKrAKFeYfjDqcjdY=w240-h480-rw'),
(31, 'Atrapado en el Tiempo', 'Comedia', 'https://pics.filmaffinity.com/Atrapado_en_el_tiempo-728387233-large.jpg'),
(32, 'El Gran Lebowski', 'Comedia', 'https://images.justwatch.com/poster/76802182/s718/el-gran-lebowski.jpg'),
(33, 'Terrifier 3', 'Terror', 'https://es.web.img3.acsta.net/c_310_420/img/b6/64/b6649bd264af511b3bb18089a10ac96a.jpg'),
(34, 'Scream', 'Terror', 'https://play-lh.googleusercontent.com/tP88ruXqBPZtzr60JLuecuXotu3uydosq253zsgE1A0QkFJPjaU-PW3xzy2f9GAf3FFg8OAbbjloqaYvgdM'),
(35, 'Un Lugar en Silencio', 'Terror', 'https://http2.mlstatic.com/D_NQ_NP_727679-MLU71568058670_092023-O.webp'),
(36, 'El Aro 3', 'Terror', 'https://img.chilango.com/2017/02/el-aro-3-poster-1.jpg'),
(37, 'La Casa del Terror', 'Terror', 'https://es.web.img3.acsta.net/pictures/19/09/26/16/34/3403554.jpg'),
(38, 'Devuélvemela', 'Terror', 'https://m.media-amazon.com/images/M/MV5BMjBlMzY2YzEtOGJmMy00NmNlLThmNTktODhlZmM5ODdlZTQ3XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
(39, 'Expediente Warren: El último rito', 'Terror', 'https://pics.filmaffinity.com/the_conjuring_last_rites-547210494-large.jpg'),
(40, 'Orgullo y Prejuicio', 'Romance', 'https://images.justwatch.com/poster/203481059/s718/orgullo-y-prejuicio.jpg'),
(41, 'Moulin Rouge', 'Romance', 'https://play-lh.googleusercontent.com/hxXjsyY9npV8d8hn54A2ZzYNr4CFDVidnUMVlyCJ0WtX0sBV1Qqy4oZxmRPFo4i4X2s'),
(42, 'Antes del Amanecer', 'Romance', 'https://m.media-amazon.com/images/S/pv-target-images/e5525361d6c4df27a8177ff8f87bc8b2bef943331c796f716fd08e5aac537e40.jpg'),
(43, 'Eterno Resplandor de una Mente sin Recuerdos', 'Romance', 'https://m.media-amazon.com/images/M/MV5BYjcxNDFhZDktYTllZS00ZjE2LWFlOWMtN2Y5ZGUyZGRhMDA4XkEyXkFqcGc@._V1_.jpgg'),
(44, 'Romeo + Julieta', 'Romance', 'https://m.media-amazon.com/images/M/MV5BZjBhYjkxN2EtNDc1Yy00NTViLTkxMjQtMDYxMzM0MzA3NGQ4XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
(45, 'Bajo la Misma Estrella', 'Romance', 'https://m.media-amazon.com/images/M/MV5BYzJkNTQ1MmUtZjliYi00OGQwLTgyY2UtOTk3MTM1Mzk4NmRjXkEyXkFqcGc@._V1_.jpg'),
(46, 'El Lado Bueno de las Cosas', 'Romance', 'https://es.web.img2.acsta.net/pictures/15/11/06/11/39/323127.jpg'),
(47, 'Blade Runner 2049', 'Ciencia ficción', 'https://upload.wikimedia.org/wikipedia/en/9/9b/Blade_Runner_2049_poster.png'),
(48, 'Guardianes de la Galaxia', 'Ciencia ficción', 'https://lumiere-a.akamaihd.net/v1/images/lat_2ae5e247.jpeg'),
(49, 'Star Wars: El Despertar de la Fuerza', 'Ciencia ficción', 'https://pics.filmaffinity.com/Star_Wars_Los_aultimos_Jedi-535293064-mmed.jpg'),
(50, 'Avatar', 'Ciencia ficción', 'https://m.media-amazon.com/images/M/MV5BZDYxY2I1OGMtN2Y4MS00ZmU1LTgyNDAtODA0MzAyYjI0N2Y2XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
(51, 'Ready Player One', 'Ciencia ficción', 'https://i.blogs.es/1758c6/rpo/450_1000.jpg'),
(52, 'Al Filo del Mañana', 'Ciencia ficción', 'https://es.web.img3.acsta.net/pictures/14/05/05/12/46/010208.jpg'),
(53, 'Mundo Jurásico', 'Ciencia ficción', 'https://play-lh.googleusercontent.com/RDC-pQHgvzSJc9EMB3r3q8R_qp55AkQAt9Eto4OgaKAkhn8taI5rmt0dTAHjUCPm1dAHjg'),
(54, 'Harta', 'Drama', 'https://www.lavanguardia.com/peliculas-series/images/movie/poster/2025/6/w300/eAf8Is1xX4gq4Jk14iFC1qhmT8R.jpg'),
(55, 'Sueño de Fuga', 'Drama', 'https://m.media-amazon.com/images/M/MV5BMzdhNGE2ZjAtYjFjYS00YmY2LTg4MDctZTNhN2VlOGM3NjUwXkEyXkFqcGc@._V1_.jpg'),
(56, 'El Club de la Pelea', 'Drama', 'https://i.scdn.co/image/ab67616d0000b27393c4e1b3672ce860e30f34d0'),
(57, 'El Padrino', 'Drama', 'https://m.media-amazon.com/images/M/MV5BZmNiNzM4MTctODI5YS00MzczLWE2MzktNzY4YmNjYjA5YmY1XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg'),
(58, '12 Años de Esclavitud', 'Drama', 'https://play-lh.googleusercontent.com/3_bi7bK4ujfpBOEz9AXwKZut8bGa67N0gJ7S5nztFYcsXF6-DJDP6PGm4ZG5d1W-0Bg'),
(59, 'Una Mente Brillante', 'Drama', 'https://http2.mlstatic.com/D_NQ_NP_603776-MLA76109609850_052024-OO.jpg'),
(60, 'Cambios de Reinas', 'Drama', 'https://cartelera.elpais.com/assets/uploads/2019/02/18131428/C_19722.jpg');

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
(29, 1, 'Romance'),
(30, 6, 'Terror');

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
(3, 6, 48, 'like', '2025-09-27 23:01:54');

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
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `mi_lista`
--
ALTER TABLE `mi_lista`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `peliculas`
--
ALTER TABLE `peliculas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT de la tabla `preferencias`
--
ALTER TABLE `preferencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `valoraciones`
--
ALTER TABLE `valoraciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
