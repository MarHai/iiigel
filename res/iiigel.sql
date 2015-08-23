/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;



CREATE DATABASE IF NOT EXISTS `iiigel` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `iiigel`;



CREATE TABLE IF NOT EXISTS `chapter` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sHashId` varchar(30) NOT NULL,
  `bDeleted` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `nCreate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `nIdUpdater` int(11) unsigned NOT NULL,
  `nUpdate` int(35) unsigned NOT NULL,
  `nIdModule` int(11) unsigned NOT NULL,
  `nOrder` int(5) unsigned NOT NULL,
  `sName` varchar(255) NOT NULL,
  `sText` text NOT NULL,
  `bInterpreter` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `bLiveInterpretation` tinyint(2) unsigned NOT NULL DEFAULT '1',
  `sInterpreter` varchar(50) NOT NULL,
  `bCloud` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `bObligatoryHandin` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `bLive` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `sNote` varchar(800) NOT NULL,
  PRIMARY KEY (`nId`),
  UNIQUE KEY `sHashId` (`sHashId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `cloud` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sHashId` VARCHAR(30) NOT NULL,
  `bDeleted` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `nCreate` int(35) unsigned NOT NULL,
  `nUpdate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `nTreeLeft` int(11) unsigned NOT NULL,
  `nTreeRight` int(11) unsigned NOT NULL,
  `sType` varchar(100) NOT NULL,
  `sName` varchar(255) NOT NULL,
  `bFilesystem` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `sFile` text NOT NULL,
  `bOpen` tinyint(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`nId`),
  UNIQUE INDEX `sHashId_bDeleted` (`sHashId`, `bDeleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `group` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sHashId` varchar(30) NOT NULL,
  `bDeleted` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `nCreate` int(35) unsigned NOT NULL,
  `nUpdate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `nIdUpdater` int(11) unsigned NOT NULL,
  `sName` varchar(255) NOT NULL,
  `nIdInstitution` int(11) unsigned NOT NULL,
  PRIMARY KEY (`nId`),
  UNIQUE KEY `sHashId` (`sHashId`,`bDeleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `handin` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sHashId` varchar(30) NOT NULL,
  `nCreate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `nIdGroup` int(11) unsigned NOT NULL,
  `nIdChapter` int(11) unsigned NOT NULL,
  `sInterpreter` text NOT NULL,
  `sCloud` text NOT NULL,
  `bCurrentlyUnderReview` tinyint(2) unsigned NOT NULL DEFAULT '1',
  `nRound` int(2) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`nId`),
  UNIQUE KEY `nIdCreator_nIdGroup_nIdChapter` (`nIdCreator`,`nIdGroup`,`nIdChapter`),
  UNIQUE KEY `sHashId` (`sHashId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `help` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nCreate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `nIdHelpcall` int(11) unsigned NOT NULL,
  `sResponse` varchar(255) NOT NULL,
  `bHelpful` tinyint(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`nId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `helpcall` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nCreate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `nIdChapter` int(11) unsigned NOT NULL,
  `nIdGroup` int(11) unsigned NOT NULL,
  `nUsersReached` int(3) unsigned NOT NULL,
  `sQuestion` varchar(255) NOT NULL,
  `eStatus` enum('open','request','done','withdrawn') NOT NULL DEFAULT 'open',
  PRIMARY KEY (`nId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `institution` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sHashId` varchar(30) NOT NULL,
  `bDeleted` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `nCreate` int(35) unsigned NOT NULL,
  `nUpdate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `nIdUpdater` int(11) unsigned NOT NULL,
  `sName` varchar(255) NOT NULL,
  `sDomain` varchar(10) NOT NULL,
  PRIMARY KEY (`nId`),
  UNIQUE KEY `sHashId` (`sHashId`,`bDeleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `module` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sHashId` varchar(30) NOT NULL,
  `bDeleted` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `nCreate` int(35) unsigned NOT NULL,
  `nUpdate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `nIdUpdater` int(11) unsigned NOT NULL,
  `sName` varchar(255) NOT NULL,
  `sDescription` varchar(800) NOT NULL,
  `sImage` varchar(100) NOT NULL,
  `bLive` tinyint(2) NOT NULL DEFAULT '0',
  `sDomain` varchar(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`nId`),
  UNIQUE KEY `sHashId` (`sHashId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `module2group` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bDeleted` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `nCreate` int(35) unsigned NOT NULL,
  `nUpdate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `nIdUpdater` int(11) unsigned NOT NULL,
  `nIdModule` int(11) unsigned NOT NULL,
  `nIdGroup` int(11) unsigned NOT NULL,
  PRIMARY KEY (`nId`),
  UNIQUE KEY `nIdModule_nIdGroup` (`nIdModule`,`nIdGroup`,`bDeleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `right` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sHashId` varchar(30) NOT NULL,
  `nCreate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `nIdUser` int(11) unsigned NOT NULL,
  `eType` enum('institution','module','chapter','group') NOT NULL,
  `nIdType` int(11) unsigned NOT NULL,
  PRIMARY KEY (`nId`),
  UNIQUE KEY `nIdUser_eType_nIdType` (`nIdUser`,`eType`,`nIdType`),
  UNIQUE KEY `sHashId` (`sHashId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `session` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nCreate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `sSession` varchar(50) NOT NULL,
  `nLastAction` int(35) NOT NULL,
  `sLastAction` varchar(255) NOT NULL,
  PRIMARY KEY (`nId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `user` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sHashId` varchar(30) NOT NULL,
  `bDeleted` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `nCreate` int(35) unsigned NOT NULL,
  `nUpdate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `nIdUpdater` int(11) unsigned NOT NULL,
  `sMail` varchar(80) NOT NULL,
  `sName` varchar(80) NOT NULL,
  `sPassword` varchar(35) NOT NULL,
  `bAdmin` tinyint(2) NOT NULL DEFAULT '0',
  `bMailIfOffline` tinyint(2) NOT NULL DEFAULT '0',
  `bActive` tinyint(2) NOT NULL DEFAULT '0',
  `sLanguage` varchar(5) NOT NULL,
  `bDashboardNavShown` tinyint(2) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`nId`),
  UNIQUE KEY `bDeleted_sMail` (`bDeleted`,`sMail`),
  UNIQUE KEY `sHashId` (`sHashId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `user2group` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sHashId` varchar(30) NOT NULL,
  `bDeleted` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `nCreate` int(35) unsigned NOT NULL,
  `nUpdate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `nIdUpdater` int(11) unsigned NOT NULL,
  `nStart` int(11) unsigned DEFAULT NULL,
  `nEnd` int(11) unsigned DEFAULT NULL,
  `nIdUser` int(11) unsigned NOT NULL,
  `nIdGroup` int(11) unsigned DEFAULT NULL,
  `nIdChapter` int(11) unsigned NOT NULL,
  `nIdModule` int(11) unsigned NOT NULL,
  `bAdmin` tinyint(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`nId`),
  UNIQUE KEY `sHashId` (`sHashId`),
  UNIQUE KEY `bDeleted_nIdUser_nIdGroup` (`bDeleted`,`nIdUser`,`nIdGroup`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



CREATE TABLE IF NOT EXISTS `user2institution` (
  `nId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sHashId` varchar(30) NOT NULL,
  `bDeleted` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `nCreate` int(35) unsigned NOT NULL,
  `nUpdate` int(35) unsigned NOT NULL,
  `nIdCreator` int(11) unsigned NOT NULL,
  `nIdUpdater` int(11) unsigned NOT NULL,
  `nStart` int(11) unsigned DEFAULT NULL,
  `nEnd` int(11) unsigned DEFAULT NULL,
  `nIdUser` int(11) unsigned NOT NULL,
  `nIdInstitution` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`nId`),
  UNIQUE KEY `sHashId` (`sHashId`),
  UNIQUE KEY `bDeleted_nIdUser_nIdGroup` (`bDeleted`,`nIdUser`,`nIdInstitution`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;



/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
