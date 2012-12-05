DROP TABLE IF EXISTS `dabase_photos`;
CREATE TABLE `dabase_photos` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `userId` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
insert  into `dabase_photos`(`id`,`userId`,`file`,`name`) values (1,1,'0544f790141c422d970663e85be39f54','me and mike'),(2,2,'147487d65c842446f0ea327934846e1a',''),(3,2,'30e6bb0597d734d7ace70df4434ed17f','in London'),(4,3,'a0a414c2767ec6ac0c35aa3a22a88043','HNY 2008'),(5,3,'d697ae471f45331a2cb527e805002e69',''),(6,3,'64eef35fc11d4bd4d5a079bcfee572d7','friends 4ever');

DROP TABLE IF EXISTS `dabase_photos_comments`;
CREATE TABLE `dabase_photos_comments` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `userId` int(11) NOT NULL,
  `photoId` int(11) NOT NULL,
  `text` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
insert  into `dabase_photos_comments`(`id`,`userId`,`photoId`,`text`) values (1,1,4,'very nice'),(2,2,4,'i know this guy?'),(3,1,3,'hhhahhaha))) so funny!'),(4,2,3,':)'),(5,2,1,'LOL'),(6,3,1,'WTF does LOL ever means???');


DROP TABLE IF EXISTS `dabase_users`;
CREATE TABLE `dabase_users` (
  `id` bigint(11) unsigned NOT NULL auto_increment,
  `login` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `posts` int(11) NOT NULL default '0',
  `isModerator` tinyint(1) NOT NULL default '0',
  `isRoot` tinyint(1) NOT NULL default '0',
  `isActive` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `users_login` (`login`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
insert  into `dabase_users`(`id`,`login`,`password`,`posts`,`isModerator`,`isRoot`,`isActive`) values (1,'andrey','8aaffd2c9c0341ec6fb91a8bc7d194f8',135,1,0,1),(3,'olya','7dc2e994d82a3b5b2a6d44743763a706',14,1,1,0),(2,'sergey','7dc2e994d82a3b5b2a6d44743763a322',52,0,0,1);

DROP TABLE IF EXISTS `dabase_videos`;
CREATE TABLE `dabase_videos` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `userId` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
insert  into `dabase_videos`(`id`,`userId`,`file`,`name`) values (1,1,'0544f790141c422d970663e85be39f54','costa rico'),(2,2,'147487d65c842446f0ea327934846e1a','my brother'),(3,2,'30e6bb0597d734d7ace70df4434ed17f',''),(4,3,'a0a414c2767ec6ac0c35aa3a22a88043',''),(5,3,'d697ae471f45331a2cb527e805002e69','Jake'),(6,3,'64eef35fc11d4bd4d5a079bcfee572d7','');

DROP TABLE IF EXISTS `dabase_directories_tree`;
CREATE TABLE `dabase_directories_tree` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `leftId` int(11) NOT NULL,
  `rightId` int(11) NOT NULL,
  `parentId` int(11) unsigned NOT NULL,
  `level` int(11) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `left` (`leftId`),
  KEY `right` (`rightId`),
  KEY `parent` (`parentId`)
) ENGINE=InnoDB;