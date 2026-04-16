/*
SQLyog Community v13.2.1 (64 bit)
MySQL - 10.1.38-MariaDB : Database - sistema_venta_lai
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`sistema_venta_lai` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `sistema_venta_lai`;

/*Table structure for table `carrito` */

DROP TABLE IF EXISTS `carrito`;

CREATE TABLE `carrito` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `tipo` varchar(25) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `carrito_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=519 DEFAULT CHARSET=latin1;

/*Data for the table `carrito` */

LOCK TABLES `carrito` WRITE;

UNLOCK TABLES;

/*Table structure for table `combinaciones` */

DROP TABLE IF EXISTS `combinaciones`;

CREATE TABLE `combinaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `activo` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `combinaciones` */

LOCK TABLES `combinaciones` WRITE;

UNLOCK TABLES;

/*Table structure for table `combinaciones_detalles` */

DROP TABLE IF EXISTS `combinaciones_detalles`;

CREATE TABLE `combinaciones_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_combinacion` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_combinacion` (`id_combinacion`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `combinaciones_detalles_ibfk_1` FOREIGN KEY (`id_combinacion`) REFERENCES `combinaciones` (`id`),
  CONSTRAINT `combinaciones_detalles_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `combinaciones_detalles` */

LOCK TABLES `combinaciones_detalles` WRITE;

UNLOCK TABLES;

/*Table structure for table `productos` */

DROP TABLE IF EXISTS `productos`;

CREATE TABLE `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `orden` int(11) DEFAULT NULL,
  `activo` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;

/*Data for the table `productos` */

LOCK TABLES `productos` WRITE;

insert  into `productos`(`id`,`nombre`,`precio`,`orden`,`activo`) values 
(1,'Trago Fernet',5000.00,2,1),
(2,'Cerveza',3000.00,1,1),
(3,'Chorizo',5000.00,3,1),
(4,'Carne',6000.00,4,1),
(5,'Agua',2000.00,5,1),
(6,'Hielo',4000.00,6,1),
(7,'Coca cola 2.25 l',5000.00,7,1),
(8,'Fernet 750 ml',60000.00,8,1);

UNLOCK TABLES;

/*Table structure for table `usuarios` */

DROP TABLE IF EXISTS `usuarios`;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `clave` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

/*Data for the table `usuarios` */

LOCK TABLES `usuarios` WRITE;

insert  into `usuarios`(`id`,`nombre`,`usuario`,`clave`) values 
(1,'caja1','caja1','caja1'),
(2,'caja2','caja2','caja2'),
(3,'caja3','caja3','caja3'),
(4,'caja4','caja4','caja4'),
(5,'caja5','caja5','caja5'),
(6,'caja6','caja6','caja6'),
(7,'caja7','caja7','caja7'),
(8,'caja8','caja8','caja8'),
(9,'caja9','caja9','caja9'),
(10,'caja10','caja10','caja10');

UNLOCK TABLES;

/*Table structure for table `venta_detalles` */

DROP TABLE IF EXISTS `venta_detalles`;

CREATE TABLE `venta_detalles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `venta_id` (`venta_id`),
  KEY `producto_id` (`producto_id`),
  CONSTRAINT `venta_detalles_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `ventas` (`id`),
  CONSTRAINT `venta_detalles_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `venta_detalles` */

LOCK TABLES `venta_detalles` WRITE;

UNLOCK TABLES;

/*Table structure for table `ventas` */

DROP TABLE IF EXISTS `ventas`;

CREATE TABLE `ventas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fecha_hora` datetime NOT NULL,
  `producto` varchar(255) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `activado` tinyint(1) NOT NULL DEFAULT '1',
  `forma` varchar(25) DEFAULT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Data for the table `ventas` */

LOCK TABLES `ventas` WRITE;

UNLOCK TABLES;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
