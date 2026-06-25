-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: itam_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `attributions_quantite`
--

DROP TABLE IF EXISTS `attributions_quantite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attributions_quantite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(10) unsigned NOT NULL,
  `stock_id` int(10) unsigned NOT NULL,
  `etat` enum('neuf','bon','moyen','mauvais','declasse') NOT NULL,
  `quantite` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_attributions_quantite_user` (`utilisateur_id`),
  KEY `idx_attributions_quantite_stock` (`stock_id`),
  CONSTRAINT `fk_attributions_quantite_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attributions_quantite_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attributions_quantite`
--

LOCK TABLES `attributions_quantite` WRITE;
/*!40000 ALTER TABLE `attributions_quantite` DISABLE KEYS */;
INSERT INTO `attributions_quantite` VALUES (1,2,1,'neuf',1,'2026-05-03 17:42:38',NULL);
/*!40000 ALTER TABLE `attributions_quantite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attributs`
--

DROP TABLE IF EXISTS `attributs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attributs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(120) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'texte',
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `categorie_id` int(10) unsigned DEFAULT NULL,
  `type_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attribut_categorie_nom` (`categorie_id`,`nom`),
  KEY `fk_attributs_type` (`type_id`),
  CONSTRAINT `fk_attributs_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attributs_type` FOREIGN KEY (`type_id`) REFERENCES `types_equipement` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attributs`
--

LOCK TABLES `attributs` WRITE;
/*!40000 ALTER TABLE `attributs` DISABLE KEYS */;
INSERT INTO `attributs` VALUES (7,'Taille','texte',0,0,NULL,3,'2026-05-03 16:23:45'),(8,'Resolution','texte',0,0,NULL,3,'2026-05-03 16:23:45'),(9,'Type impression','texte',0,0,NULL,4,'2026-05-03 16:23:45'),(10,'Vitesse ppm','texte',0,0,NULL,4,'2026-05-03 16:23:45'),(11,'Extension','texte',0,0,NULL,5,'2026-05-03 16:23:45'),(12,'Adresse MAC','texte',0,0,NULL,5,'2026-05-03 16:23:45'),(13,'Type ordinateur','texte',0,0,NULL,6,'2026-05-03 16:24:14'),(14,'CPU','texte',0,0,NULL,6,'2026-05-03 16:24:14'),(15,'RAM','texte',0,0,NULL,6,'2026-05-03 16:24:14'),(16,'Disque','texte',0,0,NULL,6,'2026-05-03 16:24:14'),(17,'OS','texte',0,0,NULL,6,'2026-05-03 16:24:14'),(18,'Version OS','texte',0,0,NULL,6,'2026-05-03 16:24:14'),(19,'Observation','texte',0,0,NULL,6,'2026-05-03 16:24:14'),(20,'Frequence','texte',0,0,NULL,3,'2026-05-03 16:24:14'),(21,'Type dalle','texte',0,0,NULL,3,'2026-05-03 16:24:14'),(22,'Connectique','texte',0,0,NULL,3,'2026-05-03 16:24:14'),(23,'Observation','texte',0,0,NULL,3,'2026-05-03 16:24:14'),(24,'Modele','texte',0,0,NULL,7,'2026-05-03 16:24:14'),(25,'Firmware','texte',0,0,NULL,7,'2026-05-03 16:24:15'),(26,'Nombre ports','texte',0,0,NULL,7,'2026-05-03 16:24:15'),(27,'Adresse IP','texte',0,0,NULL,7,'2026-05-03 16:24:15'),(28,'Adresse MAC','texte',0,0,NULL,7,'2026-05-03 16:24:15'),(29,'Observation','texte',0,0,NULL,7,'2026-05-03 16:24:15'),(30,'Modele','texte',0,0,NULL,8,'2026-05-03 16:24:15'),(31,'Firmware','texte',0,0,NULL,8,'2026-05-03 16:24:15'),(32,'Nombre ports','texte',0,0,NULL,8,'2026-05-03 16:24:15'),(33,'Manageable','texte',0,0,NULL,8,'2026-05-03 16:24:15'),(34,'Adresse IP','texte',0,0,NULL,8,'2026-05-03 16:24:15'),(35,'Observation','texte',0,0,NULL,8,'2026-05-03 16:24:15'),(36,'Reseau','texte',0,0,NULL,4,'2026-05-03 16:24:15'),(37,'Compteur pages','texte',0,0,NULL,4,'2026-05-03 16:24:15'),(38,'Site attribution','texte',0,0,NULL,4,'2026-05-03 16:24:15'),(39,'Observation','texte',0,0,NULL,4,'2026-05-03 16:24:15'),(40,'Firmware','texte',0,0,NULL,5,'2026-05-03 16:24:15'),(41,'Observation','texte',0,0,NULL,5,'2026-05-03 16:24:15'),(42,'Norme WiFi','texte',0,0,NULL,9,'2026-05-03 16:24:15'),(43,'SSID','texte',0,0,NULL,9,'2026-05-03 16:24:15'),(44,'Adresse MAC','texte',0,0,NULL,9,'2026-05-03 16:24:15'),(45,'Bande','texte',0,0,NULL,9,'2026-05-03 16:24:15'),(46,'Observation','texte',0,0,NULL,9,'2026-05-03 16:24:15'),(47,'CPU','texte',0,0,NULL,10,'2026-05-03 16:24:15'),(48,'RAM','texte',0,0,NULL,10,'2026-05-03 16:24:15'),(49,'Stockage','texte',0,0,NULL,10,'2026-05-03 16:24:15'),(50,'OS','texte',0,0,NULL,10,'2026-05-03 16:24:15'),(51,'Version OS','texte',0,0,NULL,10,'2026-05-03 16:24:15'),(52,'Adresse IP','texte',0,0,NULL,10,'2026-05-03 16:24:15'),(53,'Observation','texte',0,0,NULL,10,'2026-05-03 16:24:15'),(56,'type','liste',0,0,2,NULL,'2026-05-03 16:40:31'),(57,'RAM','texte',0,0,1,NULL,'2026-05-09 17:35:31'),(58,'ROM','texte',0,0,1,NULL,'2026-05-09 17:35:31'),(59,'CPU','texte',0,0,1,NULL,'2026-05-09 17:35:31'),(60,'MODELE','texte',0,0,3,NULL,'2026-05-09 17:40:06'),(61,'MODELE','texte',0,0,4,NULL,'2026-05-09 17:45:09'),(62,'POUCE','nombre',0,0,4,NULL,'2026-05-09 17:45:09'),(64,'type de connexion','liste',0,0,7,NULL,'2026-05-10 00:52:08'),(65,'TYPE','liste',1,0,8,NULL,'2026-05-10 00:55:34'),(66,'modele','liste',1,0,9,NULL,'2026-05-10 16:15:00'),(67,'POUCE','nombre',1,1,9,NULL,'2026-05-10 16:15:00');
/*!40000 ALTER TABLE `attributs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorie_age_rules`
--

DROP TABLE IF EXISTS `categorie_age_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorie_age_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `categorie_id` int(10) unsigned NOT NULL,
  `min_years` decimal(6,2) DEFAULT NULL,
  `max_years` decimal(6,2) DEFAULT NULL,
  `theoretical_state` enum('neuf','bon','moyen','mauvais','declasse') NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category_age_rules_category` (`categorie_id`),
  CONSTRAINT `fk_categorie_age_rules_category` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorie_age_rules`
--

LOCK TABLES `categorie_age_rules` WRITE;
/*!40000 ALTER TABLE `categorie_age_rules` DISABLE KEYS */;
INSERT INTO `categorie_age_rules` VALUES (1,9,0.00,2.00,'neuf',0,'2026-05-10 16:15:00'),(2,9,2.00,4.00,'bon',1,'2026-05-10 16:15:00'),(3,9,4.00,6.00,'moyen',2,'2026-05-10 16:15:00'),(4,9,6.00,8.00,'mauvais',3,'2026-05-10 16:15:00'),(5,9,8.00,10.00,'declasse',4,'2026-05-10 16:15:00');
/*!40000 ALTER TABLE `categorie_age_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorie_attribut_options`
--

DROP TABLE IF EXISTS `categorie_attribut_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorie_attribut_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attribut_id` int(10) unsigned NOT NULL,
  `label` varchar(160) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attr_option` (`attribut_id`,`label`),
  CONSTRAINT `fk_categorie_attribut_options_attribut` FOREIGN KEY (`attribut_id`) REFERENCES `attributs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorie_attribut_options`
--

LOCK TABLES `categorie_attribut_options` WRITE;
/*!40000 ALTER TABLE `categorie_attribut_options` DISABLE KEYS */;
INSERT INTO `categorie_attribut_options` VALUES (1,66,'HP',0,'2026-05-10 16:15:00'),(2,66,'DELL',1,'2026-05-10 16:15:00'),(3,66,'LENOVO',2,'2026-05-10 16:15:00');
/*!40000 ALTER TABLE `categorie_attribut_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(120) NOT NULL,
  `mode_gestion` enum('unique','quantite') NOT NULL DEFAULT 'unique',
  `normal_life_years` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'ordinateur','unique',NULL,'2026-05-03 16:25:13',NULL),(2,'souris','quantite',NULL,'2026-05-03 16:40:31',NULL),(3,'CASQUES','quantite',NULL,'2026-05-09 17:40:06',NULL),(4,'ECRAN','unique',NULL,'2026-05-09 17:45:09',NULL),(7,'clavier','quantite',NULL,'2026-05-10 00:52:08',NULL),(8,'FINGER','unique',NULL,'2026-05-10 00:55:34',NULL),(9,'screen','unique',8,'2026-05-10 16:15:00',NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `demandes`
--

DROP TABLE IF EXISTS `demandes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `demandes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(10) unsigned NOT NULL,
  `type_demande` varchar(120) NOT NULL,
  `description` text NOT NULL,
  `statut` enum('en_attente','validee','refusee') NOT NULL DEFAULT 'en_attente',
  `date_demande` datetime NOT NULL,
  `demandeur_nom` varchar(150) DEFAULT NULL,
  `demandeur_matricule` varchar(50) DEFAULT NULL,
  `demandeur_statut` varchar(40) DEFAULT NULL,
  `demandeur_direction` varchar(120) DEFAULT NULL,
  `demandeur_departement` varchar(120) DEFAULT NULL,
  `demandeur_service` varchar(120) DEFAULT NULL,
  `demandeur_site` varchar(120) DEFAULT NULL,
  `nature_demande` varchar(40) DEFAULT NULL,
  `equipement_categorie` varchar(80) DEFAULT NULL,
  `equipement_type_ordinateur` varchar(40) DEFAULT NULL,
  `accessoires_json` text DEFAULT NULL,
  `souris_type` varchar(20) DEFAULT NULL,
  `nom_chef` varchar(150) DEFAULT NULL,
  `nom_manager_validation` varchar(150) DEFAULT NULL,
  `date_signature_demandeur` date DEFAULT NULL,
  `date_signature_chef` date DEFAULT NULL,
  `date_signature_manager` date DEFAULT NULL,
  `signed_file_path` varchar(255) DEFAULT NULL,
  `validated_at` datetime DEFAULT NULL,
  `validateur_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_demandes_utilisateur` (`utilisateur_id`),
  KEY `fk_demandes_validateur` (`validateur_id`),
  KEY `idx_demandes_statut` (`statut`),
  KEY `idx_demandes_date` (`date_demande`),
  CONSTRAINT `fk_demandes_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`),
  CONSTRAINT `fk_demandes_validateur` FOREIGN KEY (`validateur_id`) REFERENCES `users_system` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `demandes`
--

LOCK TABLES `demandes` WRITE;
/*!40000 ALTER TABLE `demandes` DISABLE KEYS */;
/*!40000 ALTER TABLE `demandes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipement_etat_historique`
--

DROP TABLE IF EXISTS `equipement_etat_historique`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipement_etat_historique` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `equipement_id` int(10) unsigned NOT NULL,
  `ancien_etat` varchar(20) NOT NULL,
  `nouvel_etat` varchar(20) NOT NULL,
  `agent_username` varchar(100) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_etat_hist_equipement` (`equipement_id`),
  KEY `idx_etat_hist_created` (`created_at`),
  CONSTRAINT `fk_etat_hist_equipement` FOREIGN KEY (`equipement_id`) REFERENCES `equipements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipement_etat_historique`
--

LOCK TABLES `equipement_etat_historique` WRITE;
/*!40000 ALTER TABLE `equipement_etat_historique` DISABLE KEYS */;
INSERT INTO `equipement_etat_historique` VALUES (1,3,'bon','mauvais','system','ecran cassé','2026-05-09 21:26:36'),(2,3,'mauvais','bon','system','ecran réparé','2026-05-09 22:10:55'),(3,1,'bon','moyen','system','fissure','2026-05-09 22:28:58');
/*!40000 ALTER TABLE `equipement_etat_historique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipements`
--

DROP TABLE IF EXISTS `equipements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `categorie_id` int(10) unsigned DEFAULT NULL,
  `type_id` int(10) unsigned DEFAULT NULL,
  `serial_number` varchar(120) DEFAULT NULL,
  `hostname` varchar(120) DEFAULT NULL,
  `marque` varchar(120) DEFAULT NULL,
  `utilisateur_id` int(10) unsigned DEFAULT NULL,
  `statut` enum('disponible','attribue','maintenance','declasse','hors_service') NOT NULL DEFAULT 'disponible',
  `etat` enum('neuf','bon','moyen','mauvais','declasse') NOT NULL DEFAULT 'bon',
  `etat_theorique` enum('neuf','bon','moyen','mauvais','a_declasser') NOT NULL DEFAULT 'bon',
  `date_enregistrement` datetime NOT NULL DEFAULT current_timestamp(),
  `date_achat` date DEFAULT NULL,
  `date_mise_service` date DEFAULT NULL,
  `date_fiabilite` enum('exacte','approximative','inconnue') NOT NULL DEFAULT 'inconnue',
  `annee_estimee` int(10) unsigned DEFAULT NULL,
  `date_ajout` datetime NOT NULL,
  `archived_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `fk_equipements_categorie` (`categorie_id`),
  KEY `fk_equipements_type` (`type_id`),
  KEY `fk_equipements_utilisateur` (`utilisateur_id`),
  KEY `idx_equipements_hostname` (`hostname`),
  KEY `idx_equipements_statut` (`statut`),
  KEY `idx_equipements_etat` (`etat`),
  KEY `idx_equipements_archived` (`archived_at`),
  KEY `idx_equipements_etat_theorique` (`etat_theorique`),
  CONSTRAINT `fk_equipements_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `fk_equipements_type` FOREIGN KEY (`type_id`) REFERENCES `types_equipement` (`id`),
  CONSTRAINT `fk_equipements_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipements`
--

LOCK TABLES `equipements` WRITE;
/*!40000 ALTER TABLE `equipements` DISABLE KEYS */;
INSERT INTO `equipements` VALUES (1,4,NULL,'5CD5165FBD','','',NULL,'attribue','moyen','bon','2026-05-09 20:38:24',NULL,NULL,'inconnue',NULL,'2026-05-09 18:45:59',NULL,'2026-05-09 17:45:59','2026-05-09 22:28:58'),(2,4,NULL,'WDG4823178',NULL,NULL,NULL,'attribue','bon','bon','2026-05-09 20:52:42',NULL,NULL,'approximative',2024,'0000-00-00 00:00:00',NULL,'2026-05-09 19:52:42','2026-05-10 19:42:20'),(3,4,NULL,'4CE422B7S2',NULL,NULL,NULL,'attribue','bon','a_declasser','2026-05-09 22:04:05','2026-01-07','2026-05-04','exacte',NULL,'0000-00-00 00:00:00',NULL,'2026-05-09 21:04:05','2026-05-09 22:10:55'),(4,4,NULL,'4CE422B7S8',NULL,NULL,NULL,'maintenance','bon','bon','2026-05-09 23:31:57','2026-01-07','2020-05-04','exacte',NULL,'0000-00-00 00:00:00',NULL,'2026-05-09 22:31:57','2026-06-19 10:51:49'),(5,9,NULL,'5CD5165FBE',NULL,NULL,NULL,'maintenance','bon','neuf','2026-05-10 17:16:59','2026-01-07','2026-05-04','exacte',NULL,'0000-00-00 00:00:00',NULL,'2026-05-10 16:16:59','2026-06-19 10:51:48');
/*!40000 ALTER TABLE `equipements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mouvements`
--

DROP TABLE IF EXISTS `mouvements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mouvements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `equipement_id` int(10) unsigned DEFAULT NULL,
  `stock_id` int(10) unsigned DEFAULT NULL,
  `type_mouvement` enum('attribution','transfert','retour','maintenance','declassement') NOT NULL,
  `utilisateur_source_id` int(10) unsigned DEFAULT NULL,
  `utilisateur_destination_id` int(10) unsigned DEFAULT NULL,
  `quantite` int(10) unsigned DEFAULT NULL,
  `etat` enum('neuf','bon','moyen','mauvais','declasse') DEFAULT NULL,
  `source_type` varchar(30) DEFAULT NULL,
  `source_label` varchar(160) DEFAULT NULL,
  `destination_type` varchar(30) DEFAULT NULL,
  `destination_label` varchar(160) DEFAULT NULL,
  `date_mouvement` datetime NOT NULL,
  `commentaire` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mouvements_equipement_date` (`equipement_id`,`date_mouvement`),
  KEY `idx_mouvements_stock_date` (`stock_id`,`date_mouvement`),
  KEY `idx_mouvements_source` (`utilisateur_source_id`),
  KEY `idx_mouvements_destination` (`utilisateur_destination_id`),
  CONSTRAINT `fk_mouvements_destination` FOREIGN KEY (`utilisateur_destination_id`) REFERENCES `utilisateurs` (`id`),
  CONSTRAINT `fk_mouvements_equipement` FOREIGN KEY (`equipement_id`) REFERENCES `equipements` (`id`),
  CONSTRAINT `fk_mouvements_source` FOREIGN KEY (`utilisateur_source_id`) REFERENCES `utilisateurs` (`id`),
  CONSTRAINT `fk_mouvements_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mouvements`
--

LOCK TABLES `mouvements` WRITE;
/*!40000 ALTER TABLE `mouvements` DISABLE KEYS */;
INSERT INTO `mouvements` VALUES (1,NULL,1,'attribution',1,2,1,'neuf','stock','STOCK_IT','utilisateur',NULL,'2026-05-03 18:42:38',NULL,'2026-05-03 17:42:38'),(2,1,NULL,'attribution',NULL,NULL,NULL,NULL,'fournisseur','Fournisseur','depot','Depot IT Central','2026-05-09 18:45:59','Mise en stock initiale','2026-05-09 17:45:59'),(3,1,NULL,'attribution',NULL,2,NULL,NULL,'depot','Depot IT Central','utilisateur','','2026-05-09 18:48:32','NOUVELLE','2026-05-09 17:48:32'),(4,1,NULL,'maintenance',2,NULL,NULL,NULL,'utilisateur','','warehouse','Warehouse IT','2026-05-09 18:49:02','CASSE','2026-05-09 17:49:02'),(5,1,NULL,'attribution',NULL,2,NULL,NULL,'depot','Depot IT Central','utilisateur','','2026-05-09 18:50:04','REMPLOI','2026-05-09 17:50:04'),(6,NULL,2,'maintenance',NULL,NULL,3,'mauvais','stock','NEUF','stock','MAUVAIS','2026-05-09 19:02:53',NULL,'2026-05-09 18:02:53'),(7,2,NULL,'attribution',NULL,NULL,NULL,NULL,'fournisseur','Fournisseur','depot','Depot IT Central','2026-05-09 20:52:42','Mise en stock initiale','2026-05-09 19:52:42'),(8,3,NULL,'attribution',NULL,NULL,NULL,NULL,'fournisseur','Fournisseur','depot','Depot IT Central','2026-05-09 22:04:05','Mise en stock initiale','2026-05-09 21:04:05'),(9,3,NULL,'attribution',NULL,2,NULL,NULL,'depot','Depot IT Central','utilisateur','','2026-05-09 22:04:59','Attribution depuis la fiche equipement','2026-05-09 21:04:59'),(10,4,NULL,'attribution',NULL,NULL,NULL,NULL,'fournisseur','Fournisseur','depot','Depot IT Central','2026-05-09 23:31:57','Mise en stock initiale','2026-05-09 22:31:57'),(11,5,NULL,'attribution',NULL,NULL,NULL,NULL,'fournisseur','Fournisseur','depot','Depot IT Central','2026-05-10 17:16:59','Mise en stock initiale','2026-05-10 16:16:59'),(12,5,NULL,'attribution',NULL,2,NULL,NULL,'depot','Depot IT Central','utilisateur','','2026-05-10 17:31:26','Attribution depuis la fiche equipement','2026-05-10 16:31:26'),(13,4,NULL,'attribution',NULL,2,NULL,NULL,'fournisseur','Fournisseur','utilisateur','','2026-05-10 20:42:20','Attribution auto depuis fiche equipement','2026-05-10 19:42:20'),(14,2,NULL,'attribution',NULL,2,NULL,NULL,'fournisseur','Fournisseur','utilisateur','','2026-05-10 20:42:20','Attribution auto depuis fiche equipement','2026-05-10 19:42:20'),(15,5,NULL,'retour',2,NULL,NULL,NULL,'utilisateur','','depot','Depot IT Central','2026-05-10 21:20:10','REMPLOI','2026-05-10 20:20:10'),(16,5,NULL,'maintenance',NULL,NULL,NULL,NULL,'depot','Depot IT Central','warehouse','Warehouse IT','2026-06-19 11:51:48','Maintenance groupee','2026-06-19 10:51:48'),(17,4,NULL,'maintenance',2,NULL,NULL,NULL,'utilisateur','','warehouse','Warehouse IT','2026-06-19 11:51:48','Maintenance groupee','2026-06-19 10:51:48');
/*!40000 ALTER TABLE `mouvements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Admin'),(2,'IT Agent');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_etat_historique`
--

DROP TABLE IF EXISTS `stock_etat_historique`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_etat_historique` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stock_id` int(10) unsigned NOT NULL,
  `ancien_etat` varchar(20) NOT NULL,
  `nouvel_etat` varchar(20) NOT NULL,
  `quantite` int(10) unsigned NOT NULL,
  `agent_username` varchar(100) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stock_etat_hist_stock` (`stock_id`),
  KEY `idx_stock_etat_hist_created` (`created_at`),
  CONSTRAINT `fk_stock_etat_hist` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_etat_historique`
--

LOCK TABLES `stock_etat_historique` WRITE;
/*!40000 ALTER TABLE `stock_etat_historique` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_etat_historique` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_etats`
--

DROP TABLE IF EXISTS `stock_etats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_etats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stock_id` int(10) unsigned NOT NULL,
  `etat` enum('neuf','bon','moyen','mauvais','declasse') NOT NULL,
  `quantite` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_etat` (`stock_id`,`etat`),
  CONSTRAINT `fk_stock_etats_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_etats`
--

LOCK TABLES `stock_etats` WRITE;
/*!40000 ALTER TABLE `stock_etats` DISABLE KEYS */;
INSERT INTO `stock_etats` VALUES (1,1,'neuf',12,'2026-05-03 17:39:08','2026-05-03 17:42:38'),(2,2,'neuf',7,'2026-05-09 17:40:42','2026-05-09 18:02:53'),(3,2,'mauvais',3,'2026-05-09 18:02:53',NULL),(4,3,'neuf',10,'2026-05-09 18:05:22',NULL),(5,4,'neuf',10,'2026-05-09 19:55:37',NULL);
/*!40000 ALTER TABLE `stock_etats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stocks`
--

DROP TABLE IF EXISTS `stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stocks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `categorie_id` int(10) unsigned NOT NULL,
  `date_enregistrement` datetime NOT NULL DEFAULT current_timestamp(),
  `date_achat` date DEFAULT NULL,
  `date_mise_service` date DEFAULT NULL,
  `date_fiabilite` enum('exacte','approximative','inconnue') NOT NULL DEFAULT 'inconnue',
  `annee_estimee` int(10) unsigned DEFAULT NULL,
  `date_ajout` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stocks_categorie` (`categorie_id`),
  CONSTRAINT `fk_stocks_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stocks`
--

LOCK TABLES `stocks` WRITE;
/*!40000 ALTER TABLE `stocks` DISABLE KEYS */;
INSERT INTO `stocks` VALUES (1,2,'2026-05-09 20:39:41',NULL,NULL,'inconnue',NULL,'2026-05-03 18:39:08','2026-05-03 17:39:08',NULL),(2,3,'2026-05-09 20:39:41',NULL,NULL,'inconnue',NULL,'2026-05-09 18:40:42','2026-05-09 17:40:42',NULL),(3,2,'2026-05-09 20:39:41',NULL,NULL,'inconnue',NULL,'2026-05-09 19:05:22','2026-05-09 18:05:22',NULL),(4,3,'2026-05-09 20:55:37',NULL,NULL,'inconnue',NULL,'0000-00-00 00:00:00','2026-05-09 19:55:37',NULL);
/*!40000 ALTER TABLE `stocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `types_equipement`
--

DROP TABLE IF EXISTS `types_equipement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `types_equipement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(120) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `types_equipement`
--

LOCK TABLES `types_equipement` WRITE;
/*!40000 ALTER TABLE `types_equipement` DISABLE KEYS */;
INSERT INTO `types_equipement` VALUES (3,'Ecran','2026-05-03 16:23:45'),(4,'Imprimante','2026-05-03 16:23:45'),(5,'Telephone IP','2026-05-03 16:23:45'),(6,'Ordinateur','2026-05-03 16:24:14'),(7,'Routeur','2026-05-03 16:24:14'),(8,'Switch','2026-05-03 16:24:15'),(9,'Access Point','2026-05-03 16:24:15'),(10,'Serveur','2026-05-03 16:24:15');
/*!40000 ALTER TABLE `types_equipement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users_system`
--

DROP TABLE IF EXISTS `users_system`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_system` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_users_system_role` (`role_id`),
  CONSTRAINT `fk_users_system_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users_system`
--

LOCK TABLES `users_system` WRITE;
/*!40000 ALTER TABLE `users_system` DISABLE KEYS */;
INSERT INTO `users_system` VALUES (1,'admin','$2y$10$ggs0bsCiXAbSz0B0xO3dX.qdjYrjAT.wDhwZyuiKDlmfQcbHLyP7i',1,'2026-05-03 16:23:45',NULL);
/*!40000 ALTER TABLE `users_system` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utilisateur_accessoires`
--

DROP TABLE IF EXISTS `utilisateur_accessoires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utilisateur_accessoires` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(10) unsigned NOT NULL,
  `accessoire_nom` varchar(120) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_accessoire_user` (`utilisateur_id`),
  CONSTRAINT `fk_user_accessoire_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateur_accessoires`
--

LOCK TABLES `utilisateur_accessoires` WRITE;
/*!40000 ALTER TABLE `utilisateur_accessoires` DISABLE KEYS */;
/*!40000 ALTER TABLE `utilisateur_accessoires` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utilisateur_audits`
--

DROP TABLE IF EXISTS `utilisateur_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utilisateur_audits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(10) unsigned NOT NULL,
  `action` varchar(40) NOT NULL,
  `details` text NOT NULL,
  `actor_username` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_utilisateur_audits_user` (`utilisateur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateur_audits`
--

LOCK TABLES `utilisateur_audits` WRITE;
/*!40000 ALTER TABLE `utilisateur_audits` DISABLE KEYS */;
INSERT INTO `utilisateur_audits` VALUES (1,2,'create','Creation utilisateur','admin','2026-05-03 17:05:48');
/*!40000 ALTER TABLE `utilisateur_audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utilisateurs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `matricule` varchar(50) NOT NULL,
  `telephone` varchar(40) DEFAULT NULL,
  `direction` varchar(120) DEFAULT NULL,
  `departement` varchar(120) DEFAULT NULL,
  `service` varchar(120) DEFAULT NULL,
  `site` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricule` (`matricule`),
  KEY `idx_utilisateurs_nom` (`nom`),
  KEY `idx_utilisateurs_departement` (`departement`),
  KEY `idx_utilisateurs_site` (`site`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateurs`
--

LOCK TABLES `utilisateurs` WRITE;
/*!40000 ALTER TABLE `utilisateurs` DISABLE KEYS */;
INSERT INTO `utilisateurs` VALUES (1,'STOCK_IT','STOCK_IT',NULL,'D??p??t IT','IT','Stock','D??p??t IT','2026-05-03 16:23:43',NULL),(2,'mashika kalonji noly','5609E','0830992833','IT','INFRASTRUCTURE','ASSET','SIEGE','2026-05-03 17:05:48',NULL);
/*!40000 ALTER TABLE `utilisateurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `valeurs_attributs`
--

DROP TABLE IF EXISTS `valeurs_attributs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `valeurs_attributs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `equipement_id` int(10) unsigned DEFAULT NULL,
  `stock_id` int(10) unsigned DEFAULT NULL,
  `attribut_id` int(10) unsigned NOT NULL,
  `valeur` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_valeur_equipement_attribut` (`equipement_id`,`stock_id`,`attribut_id`),
  KEY `fk_valeurs_attribut` (`attribut_id`),
  KEY `fk_valeurs_stock` (`stock_id`),
  CONSTRAINT `fk_valeurs_attribut` FOREIGN KEY (`attribut_id`) REFERENCES `attributs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_valeurs_equipement` FOREIGN KEY (`equipement_id`) REFERENCES `equipements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_valeurs_stock` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `valeurs_attributs`
--

LOCK TABLES `valeurs_attributs` WRITE;
/*!40000 ALTER TABLE `valeurs_attributs` DISABLE KEYS */;
INSERT INTO `valeurs_attributs` VALUES (1,NULL,1,56,'filaire','2026-05-03 17:39:08'),(2,NULL,2,60,'filaire','2026-05-09 17:40:42'),(5,1,NULL,61,'HP','2026-05-09 17:47:36'),(6,1,NULL,62,'18','2026-05-09 17:47:36'),(7,NULL,3,56,'SANS-FIL','2026-05-09 18:05:22'),(8,2,NULL,61,'HP','2026-05-09 19:52:42'),(9,2,NULL,62,'18','2026-05-09 19:52:42'),(10,NULL,4,60,'SANS-FIL','2026-05-09 19:55:37'),(11,3,NULL,61,'HP','2026-05-09 21:04:05'),(12,3,NULL,62,'-5','2026-05-09 21:04:05'),(13,5,NULL,66,'HP','2026-05-10 16:16:59'),(14,5,NULL,67,'20','2026-05-10 16:16:59');
/*!40000 ALTER TABLE `valeurs_attributs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-23 12:10:44
