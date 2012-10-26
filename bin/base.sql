-- This is base SQL script for create core database structure.
-- This script don't drop tables.

CREATE TABLE IF NOT EXISTS users (
  id INT NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role TINYINT(3) NOT NULL DEFAULT 0,

  full_name VARCHAR(255) NOT NULL,
  address TEXT NOT NULL DEFAULT '',
  phone VARCHAR(75) NOT NULL DEFAULT '',
  emergency_phone VARCHAR(75) NOT NULL DEFAULT '',
  emergency_full_name VARCHAR(255) NOT NULL DEFAULT '',
  birthdate DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',

  created DATETIME NOT NULL,
  updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE(email),
  INDEX(email, password),
  INDEX(role),
  INDEX(full_name)
)
;
INSERT IGNORE INTO users SET
  id = 1,
  email = 'harmen@futurumshop.com',
  password = SHA1('superadmin'),
  role = 100,
  full_name = 'Harmen van der Meulen',
  created = NOW()
;

-- THIS IS TEMPORARY DEV DATA
INSERT IGNORE INTO users (id, email, password, role, created, full_name) VALUES
(2, 'test_1@gmail.com', SHA1('test_1'), 20, NOW(), 'Test User 1'),
(3, 'test_2@gmail.com', SHA1('test_2'), 20, NOW(), 'Test User 2'),
(4, 'test_3@gmail.com', SHA1('test_3'), 20, NOW(), 'Test User 3'),
(5, 'test_4@gmail.com', SHA1('test_4'), 20, NOW(), 'Test User 4'),
(6, 'test_5@gmail.com', SHA1('test_5'), 50, NOW(), 'Test User 5')
;

CREATE TABLE IF NOT EXISTS groups (
  id INT NOT NULL AUTO_INCREMENT,
  group_name VARCHAR(255) NOT NULL,
  color VARCHAR (10) NOT NULL DEFAULT 'FFF',
  PRIMARY KEY (id),
  INDEX(group_name)
)
;

-- THIS IS TEMPORARY DEV DATA
INSERT IGNORE INTO groups (id, group_name) VALUES
(1, 'Test Group')
;

CREATE TABLE IF NOT EXISTS user_groups (
  user_id INT NOT NULL,
  group_id INT NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (user_id, group_id)
)
;

-- THIS IS TEMPORARY DEV DATA
INSERT IGNORE INTO user_groups (user_id, group_id, is_admin) VALUES
(2, 1, 0),
(3, 1, 0),
(4, 1, 0),
(5, 1, 0),
(6, 1, 1)
;

CREATE TABLE IF NOT EXISTS user_checks (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  check_date DATE DEFAULT NULL,
  check_in TIME DEFAULT NULL,
  check_out TIME DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX(user_id),
  INDEX(check_date),
  INDEX(check_in),
  INDEX(check_out)
)
;

CREATE TABLE user_day_work_plan (
  id int(10) NOT NULL AUTO_INCREMENT,
  user_id int(4) NOT NULL,
  date date NOT NULL,
  status1 int(2) NOT NULL  DEFAULT 0,
  status2 int(2) DEFAULT NULL,
  time_start time NOT NULL,
  time_end time NOT NULL,
  time_exclude time DEFAULT NULL,
  group_id int(4) DEFAULT NULL,
   PRIMARY KEY (id),
   UNIQUE KEY (user_id, group_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

CREATE TABLE `status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `color_hex` varchar(10) NOT NULL,
  `editable` int(1) DEFAULT NULL,
  `edit_type` int(2) DEFAULT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

INSERT INTO `status`(`id`,`description`,`color`,`color_hex`,`editable`,`edit_type`) VALUES ( '0','White','White','FFFFFF',NULL,NULL);
INSERT INTO `status`(`id`,`description`,`color`,`color_hex`,`editable`,`edit_type`) VALUES ( '2','Werk','Green','32CD32','0',NULL);
INSERT INTO `status`(`id`,`description`,`color`,`color_hex`,`editable`,`edit_type`) VALUES ( '3','Vrij','Yellow','FFFF00','0',NULL);
INSERT INTO `status`(`id`,`description`,`color`,`color_hex`,`editable`,`edit_type`) VALUES ( '4','Ziekte','Red','E9967A','1','1');
INSERT INTO `status`(`id`,`description`,`color`,`color_hex`,`editable`,`edit_type`) VALUES ( '5','Dokter/overige',NULL,'00FFFF','1','0');
INSERT INTO `status`(`id`,`description`,`color`,`color_hex`,`editable`,`edit_type`) VALUES ( '6','Buitengewoon verlof','blue','0000FF','1','0');

CREATE TABLE `group_plannings` (
  id INT NOT NULL AUTO_INCREMENT,
  group_id INT NOT NULL,
  week_type ENUM('odd', 'even') NOT NULL,
  day_number INT NOT NULL,
  time_start TIME NOT NULL DEFAULT '00:00:00',
  time_end TIME NOT NULL DEFAULT '00:00:00',
  PRIMARY KEY (id),
  UNIQUE (group_id, week_type, day_number),
  INDEX (group_id)
)
;