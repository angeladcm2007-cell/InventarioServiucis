-- phpMyAdmin SQL Dump
-- version 3.4.5
-- http://www.phpmyadmin.net
--
-- Servidor: localhost
-- Tiempo de generación: 10-02-2006 a las 14:45:50
-- Versión del servidor: 5.5.16
-- Versión de PHP: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de datos: `pdvsa_ait`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin`
--

CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `UserName` varchar(100) DEFAULT NULL,
  `Password` varchar(100) DEFAULT NULL,
  `updationDate` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Volcado de datos para la tabla `admin`
--

INSERT INTO `admin` (`id`, `UserName`, `Password`, `updationDate`) VALUES
(1, 'administrador@gmail.com', '21232f297a57a5a743894a0e4a801fc3', '2022-09-04 10:30:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblactivos`
--

CREATE TABLE IF NOT EXISTS `tblactivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_activo` varchar(20) NOT NULL,
  `descrip` varchar(100) NOT NULL,
  `estado` varchar(100) DEFAULT NULL,
  `etiqueta` varchar(100) NOT NULL,
  `serial` varchar(100) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `marca` varchar(100) NOT NULL,
  `ip_mac` varchar(100) NOT NULL,
  `ubicacion` varchar(100) NOT NULL,
  `Creationdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdationDate` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblasig`
--

CREATE TABLE IF NOT EXISTS `tblasig` (
  `StudentId` int(11) NOT NULL AUTO_INCREMENT,
  `fecha` varchar(100) DEFAULT NULL,
  `number` varchar(100) DEFAULT NULL,
  `nota` varchar(100) DEFAULT NULL,
  `ClassId` int(11) DEFAULT NULL,
  `usuarioID` int(11) NOT NULL,
  `RegDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdationDate` timestamp NULL DEFAULT NULL,
  `Status` int(1) DEFAULT NULL,
  PRIMARY KEY (`StudentId`),
  KEY `ClassId` (`ClassId`),
  KEY `usuarioID` (`usuarioID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tblusuarios`
--

CREATE TABLE IF NOT EXISTS `tblusuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombres` varchar(100) NOT NULL,
  `cedula` varchar(100) DEFAULT NULL,
  `indicador` varchar(100) NOT NULL,
  `negocio` varchar(100) NOT NULL,
  `Creationdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdationDate` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `tblasig`
--
ALTER TABLE `tblasig`
  ADD CONSTRAINT `tblasig_ibfk_1` FOREIGN KEY (`ClassId`) REFERENCES `tblactivos` (`id`),
  ADD CONSTRAINT `tblasig_ibfk_2` FOREIGN KEY (`usuarioID`) REFERENCES `tblusuarios` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
