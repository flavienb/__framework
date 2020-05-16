-- This is the database structure used in the example module
-- To use your own, just edit the application.php config file to match your database

-- ----------------------------
-- Table structure for articles
-- ----------------------------
DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
                            `id` int(11) NOT NULL AUTO_INCREMENT,
                            `date_create` int(11) DEFAULT NULL,
                            `author` varchar(255) DEFAULT NULL,
                            `text` varchar(255) DEFAULT NULL,
                            `deleted` int(11) DEFAULT NULL,
                            PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for translations
-- ----------------------------
DROP TABLE IF EXISTS `translations`;
CREATE TABLE `translations` (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `module` varchar(255) DEFAULT NULL,
                                `token` varchar(100) NOT NULL,
                                `fr` varchar(255) DEFAULT NULL,
                                `en` varchar(255) DEFAULT NULL,
                                `nl` varchar(255) DEFAULT NULL,
                                PRIMARY KEY (`id`),
                                KEY `key` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
                        `user_id` int(11) NOT NULL AUTO_INCREMENT,
                        `email` varchar(255) NOT NULL,
                        `password` varchar(255) NOT NULL,
                        `firstname` varchar(255) DEFAULT NULL,
                        `lastname` varchar(255) DEFAULT NULL,
                        `date_create` int(11) NOT NULL,
                        PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
