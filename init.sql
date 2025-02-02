DROP TABLE IF EXISTS swc_planets;
DROP TABLE IF EXISTS swc_systems;
DROP TABLE IF EXISTS swc_sectors;

CREATE TABLE swc_sectors (
  id INT PRIMARY KEY,
  uid VARCHAR(50),
  name VARCHAR(100),
  controlledby VARCHAR(50),
  population BIGINT,
  sector_edge_coordinates TEXT,
  last_updated DATETIME
);

CREATE TABLE swc_systems (
  id INT PRIMARY KEY,
  uid VARCHAR(50),
  name VARCHAR(100),
  sector VARCHAR(50),
  controlledby VARCHAR(50),
  population BIGINT,
  coordinates POINT,
  last_updated DATETIME
);

CREATE TABLE swc_planets (
  id INT PRIMARY KEY,
  uid VARCHAR(50),
  name VARCHAR(100),
  system VARCHAR(50),
  controlledby VARCHAR(50),  
  magistrate VARCHAR(50),      
  size INT,
  coordinates POINT,
  population BIGINT,
  civilisation_level INT,
  terrain_map TEXT,
  last_updated DATETIME
);


CREATE TABLE wp_tc_prospecting_changelog (
  id int(11) NOT NULL AUTO_INCREMENT,
  object_type varchar(100) DEFAULT NULL,
  object_id int(11) DEFAULT NULL,
  event_type varchar(100) DEFAULT NULL,
  event varchar(100) DEFAULT NULL,
  user varchar(100) DEFAULT NULL,
  datetime datetime DEFAULT NULL,
  planet_id int(11) DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE wp_tc_prospecting_planets (
  id int(20) NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  system varchar(255) NOT NULL,
  sector varchar(255) NOT NULL,
  location varchar(255) NOT NULL,
  size int(11) NOT NULL,
  PRIMARY KEY (id)
);

CREATE TABLE wp_tc_prospecting_grids (
  id int(11) NOT NULL AUTO_INCREMENT,
  planet_id int(20) NOT NULL,
  x int(11) NOT NULL,
  y int(11) NOT NULL,
  terrain_type varchar(50) NOT NULL,
  prospected tinyint(1) NOT NULL DEFAULT 0,
  prospect_time datetime DEFAULT NULL,
  prospector_skill int(11) NOT NULL DEFAULT 5,
  prospector_vehicle varchar(100) NOT NULL DEFAULT 'SX-65 Groundhog',
  PRIMARY KEY (id),
  UNIQUE KEY unique_planet_grid (planet_id,x,y),
  CONSTRAINT fk_grid_planet FOREIGN KEY (planet_id) REFERENCES wp_tc_prospecting_planets (id) ON DELETE CASCADE
);
CREATE TABLE wp_tc_prospecting_deposits (
  id int(20) NOT NULL AUTO_INCREMENT,
  planet_id int(20) NOT NULL,
  x int(11) NOT NULL,
  y int(11) NOT NULL,
  deposit_type varchar(50) NOT NULL,
  size int(11) NOT NULL,
  prospecting_time datetime DEFAULT NULL,
  prospector varchar(255) DEFAULT NULL,
  last_updated datetime DEFAULT NULL,
  last_updater varchar(255) DEFAULT NULL,
  prospector_skill int(11) DEFAULT NULL,
  prospector_vehicle varchar(100) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_planet_xy (planet_id,x,y),
  KEY idx_planet_coordinates (planet_id,x,y),
  CONSTRAINT fk_deposit_planet FOREIGN KEY (planet_id) REFERENCES wp_tc_prospecting_planets (id) ON DELETE CASCADE
);

INSERT INTO swc_sectors (id, uid, name, controlledby, population, sector_edge_coordinates, last_updated)
VALUES
(426, '25:276', 'Teraab', '20:584', 554965901975, '214,29;213,29;213,30;219,30;219,31;220,31;220,32;221,32;221,33;228,33;228,32;230,32;230,31;232,31;232,30;233,30;233,29;234,29;234,28;235,28;235,27;236,27;236,26;238,26;238,25;241,25;241,24;253,24;253,25;258,25;258,26;262,26;262,27;266,27;266,28;267,28;267,29;272,29;272,30;278,30;278,31;285,31;285,32;294,32;294,33;297,33;297,13;296,13;296,8;295,8;295,7;294,7;294,5;293,5;293,2;292,2;292,-1;291,-1;291,-3;290,-3;290,-16;291,-16;291,-21;292,-21;292,-31;291,-31;291,-32;290,-32;290,-33;285,-33;285,-32;275,-32;275,-33;272,-33;272,-34;269,-34;269,-35;266,-35;266,-36;265,-36;265,-37;263,-37;263,-38;261,-38;261,-39;260,-39;260,-40;259,-40;259,-41;258,-41;258,-42;257,-42;257,-43;256,-43;256,-44;254,-44;254,-45;251,-45;251,-46;237,-46;237,-45;233,-45;233,-44;229,-44;229,-43;226,-43;226,-37;225,-37;225,-32;226,-32;226,-13;225,-13;225,-4;224,-4;224,5;223,5;223,9;222,9;222,14;221,14;221,17;220,17;220,19;219,19;219,21;218,21;218,22;217,22;217,23;216,23;216,24;215,24;215,26;214,26', '2025-01-15 16:47:04.000');

INSERT INTO swc_systems (id, uid, name, sector, controlledby, population, coordinates, last_updated)
VALUES 
(880, '9:425', 'Drogheda', '25:276', '20:584', 64671878, ST_GeomFromText('POINT(236 23)'), '2025-01-15 18:47:23.000'),
(881, '9:305', 'Hoth`s Brand', '25:276', '20:584', 299646179329, ST_GeomFromText('POINT(223 32)'), '2025-01-15 18:47:24.000'),
(882, '9:383', 'Istic', '25:276', '20:584', 166899191528, ST_GeomFromText('POINT(253 4)'), '2025-01-15 18:47:24.000'),
(883, '9:1198', 'Nanth`ri', '25:276', '20:584', 66088896178, ST_GeomFromText('POINT(245 -24)'), '2025-01-15 18:47:25.000'),
(884, '9:1199', 'Nixor', '25:276', '20:584', 108983743, ST_GeomFromText('POINT(241 -40)'), '2025-01-15 18:47:25.000'),
(885, '9:390', 'Pesmenben', '25:276', '20:584', 22147168548, ST_GeomFromText('POINT(230 25)'), '2025-01-15 18:47:26.000'),
(886, '9:1214', 'Tyne`s Horky', '25:276', '20:584', 10810771, ST_GeomFromText('POINT(232 12)'), '2025-01-15 18:47:26.000');

INSERT INTO swc_planets (id, uid, name, system, controlledby, magistrate, size, coordinates, population, civilisation_level, terrain_map, last_updated)
VALUES
(8392, '8:5284', 'Nanth`ri Sun', '9:1198', NULL, NULL, 30, ST_GeomFromText('POINT(4 2)'), 0, 0, 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz', '2025-01-16 04:47:25.000'),

(8393, '8:580', 'Nanth`ri II', '9:1198', '20:584', '1:1259018', 16, ST_GeomFromText('POINT(5 12)'), 16881475646, 56, 'oooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo', '2025-01-16 04:47:26.000'),

(8394, '8:578', 'Nanth`ri', '9:1198', '20:584', '1:1259018', 14, ST_GeomFromText('POINT(8 8)'), 19652954, 4, 'kkkkkkkkkkkkkkgggkkkkgggkkkkgcgmkgggggggkggccmmmgggccgggcccccmgggccccggfffffgggggfffgfgfffgggggfggggggggggggggggdddfffhffffdddddfmffhdddddeeddmggfffffdmddggmkggggffmcgggkkkkkkgccggkggkkkkkkkkgggkg', '2025-01-16 04:47:27.000'),

(8395, '8:3541', 'Nanth`ri IV', '9:1198', '20:584', '1:1259018', 5, ST_GeomFromText('POINT(8 18)'), 1328360409, 66, 'jjjijjjjijjjjjjjjjjjjjjjj', '2025-01-16 04:47:27.000'),

(8396, '8:1250', 'Nanth`ri III', '9:1198', '20:584', '1:1259018', 20, ST_GeomFromText('POINT(14 14)'), 47859407169, 61, 'oooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo', '2025-01-16 04:47:28.000'),

(8397, '8:581', 'Nixor', '9:1199', '20:584', '1:1259018', 12, ST_GeomFromText('POINT(4 6)'), 27439852, 7, 'jjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjijjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjfjjjjjjjjjjjjjjjjjjjjjjjjjjjjjjj', '2025-01-16 04:47:28.000'),

(8398, '8:3587', 'Nixor Asteroid Belt', '9:1199', '20:584', '1:1259018', 2, ST_GeomFromText('POINT(4 10)'), 19167046, 60, 'ijij', '2025-01-16 04:47:28.000'),

(8399, '8:1945', 'Nixor IV', '9:1199', '20:584', '1:1259018', 13, ST_GeomFromText('POINT(5 16)'), 4861974, 2, 'kkkkjkjnjkkkkkkkkkkkkkkkkknkkkkkjkjkkcnkkkkjjkkkmcckkkkkjmmmmkcckkkkjkjmjiccckkkkkjkmmkkcckkkkkkjmmmcckkkkkkjmmjmjmkkkkkjkkjmmmmkkkkkkjkmmmmmkkkkkkjkmkkmjkkkkkkkkkjkkkfk', '2025-01-16 04:47:29.000'),

(8400, '8:5285', 'Nixor Sun', '9:1199', NULL, NULL, 30, ST_GeomFromText('POINT(10 10)'), 0, 0, 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz', '2025-01-16 04:47:30.000'),

(8401, '8:3540', 'Nixor II', '9:1199', '20:584', '1:1259018', 6, ST_GeomFromText('POINT(12 7)'), 5523994, 4, 'kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk', '2025-01-16 04:47:30.000'),

(8402, '8:1252', 'Nixor I', '9:1199', '20:584', '1:1259018', 13, ST_GeomFromText('POINT(12 11)'), 51990877, 5, 'kkkbjbjjbnkkkkkkjdbdbbmkkkkkkjdddjbnkkkkkknddjggbkkkkkknddggggkkkkkkjdggggekkkkkkbdejggnkkkkkkbbegebgnkkkkkbjbbjbckckkkcjbbbbbccckkkkccbbnjccckkkkccjbbbjcckkkcjbbbbbjckk', '2025-01-16 04:47:31.000'),

(8410, '8:582', 'Tyne`s Horky', '9:1214', '20:584', '1:1259018', 11, ST_GeomFromText('POINT(0 6)'), 3421608, 3, 'nbbbbbbbnbbmbbbnbbbmbbbbbbbmmbbbbbmmmmmbbbbbbbbbbbbbbibbbbbbbbbbbbbmibbbbmbbbbbbbbbbbbbbbbbbbbbbbbbbbbmmmmmmbbbmmkkkkkmmb', '2025-01-16 04:47:35.000'),

(8411, '8:5283', 'Tyne`s Horky Sun', '9:1214', NULL, NULL, 30, ST_GeomFromText('POINT(1 3)'), 0, 0, 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz', '2025-01-16 04:47:36.000'),

(8412, '8:1251', 'Tyne`s Horky III', '9:1214', '20:584', '1:1259018', 10, ST_GeomFromText('POINT(6 14)'), 2912405, 4, 'kkbbbnbbkkkkjbbbjbkkkkbbjddbkkkkjjjbdbkkkkbbbdjmmkkkbgjgebkkkkbgggebkkkkbgggggkkkkbgeeggkkkkjbebjgkk', '2025-01-16 04:47:36.000'),

(8413, '8:579', 'Tyne`s Horky II', '9:1214', '20:584', '1:1259018', 9, ST_GeomFromText('POINT(10 10)'), 4476758, 5, 'jjnmjjmjjjjjjjjnjjjjijjjjjjjjjmjjjijjjnnijiijjjjmjjjjmbjjijjjnjnnjjjnnjjjmmnmmjjj', '2025-01-16 04:47:37.000'),

(8387, '8:2731', 'Istic Sun', '9:383', NULL, NULL, 30, ST_GeomFromText('POINT(7 8)'), 0, 0, 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz', '2025-01-16 04:47:22.000'),

(8388, '8:1948', 'Istic I', '9:383', '20:584', '1:1259018', 15, ST_GeomFromText('POINT(8 13)'), 7346840302, 47, 'kkikkkkkkkkkkkkkkekkkeekkkbekkegggeggeeeegggegggggggfffgggddggffddgfgfehhhhhfdfhhhhfdhhfhehhfhdhhdhdhhfhfhfhfhdhehehfhdhfhdhhhhhgfdggggefgdggggggggggggggggggefddddddffeeedhhhhfeeffdkkgkkkgkkkfdkkkdfkkeeehhkkkkkkkkkkkkkkkkkkkk', '2025-01-16 04:47:23.000'),

(8389, '8:1944', 'Istic II', '9:383', '20:584', '1:1259018', 12, ST_GeomFromText('POINT(10 8)'), 4547703, 4, 'jjjbbjjjbjjnjjjjbbbjjjjjjjjjbmnmmjbjjjjbjmmbimjjjjjbbmmmmmjjjjjbbjjmbjjnjjfbjmmibmjjjjjjbmmibmjjjjjbjimmmmnjjjjbbmmmmmjjjnjbbmmmbmjjjjjbjijbbjjj', '2025-01-16 04:47:23.000'),

(8390, '8:1946', 'Istic III', '9:383', '20:584', '1:1259018', 19, ST_GeomFromText('POINT(12 4)'), 154538690039, 68, 'ooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo', '2025-01-16 04:47:24.000'),

(8391, '8:1927', 'Istic IV', '9:383', '20:584', '1:1259018', 13, ST_GeomFromText('POINT(13 14)'), 5009113484, 46, 'kkkkkkkkkkgggkkkkkkkkkggggkkkkkkkggggkkggkkkkgggkkkkgggggggkkkkkkgeeeeeeeegggggeeemmmbbbbbbffddmmmmbbbbjjjccccbbbbcccccccffffbbbjkkkkkkfffjjjkkkkkkkfffffffkkkkkkkkkkkkkk', '2025-01-16 04:47:24.000'),

(8403, '8:1978', 'Pesmenben VI', '9:390', '20:584', '1:1259018', 14, ST_GeomFromText('POINT(2 13)'), 5506551348, 32, 'kkknkkkkkkkkkkkkkmkkkkkkkkkkkkkkkkkkkkkmkkkkkkkkkkkkjkkkkkkckjkjnkkmkkkkcckmmjmmmkkkkkckkkmmmmmkkkkmcccmmmmmmkkkkkckckjmmmmkkkkkccccmmkmmkkkkkkcjkmmkkkkkkkkkccjkkkkjkkkkkkckkkkkkkkkkkikkkkjkkkjkkk', '2025-01-16 04:47:32.000'),

(8404, '8:1981', 'Pesmenben V', '9:390', '20:584', '1:1259018', 8, ST_GeomFromText('POINT(4 5)'), 57283937, 10, 'kkkkkkkkcfcffffcdccdcddddcccfffcccffffcdddddccffffcccccfkkkkkkkk', '2025-01-16 04:47:32.000'),

(8405, '8:1980', 'Pesmenben III', '9:390', '20:584', '1:1259018', 12, ST_GeomFromText('POINT(6 6)'), 15863701235, 54, 'kkkkkkkkkkkkfffffffffffcdcdccffffddccdccdddddcccfffcdddcffffdddccfffdccfcggggggggggfdfdggggggcffcccdcdcdffffffdddccccccjffddffccceeekkkkkkkkkkkk', '2025-01-16 04:47:32.000'),

(8406, '8:1977', 'Pesmenben I', '9:390', '20:584', '1:1259018', 7, ST_GeomFromText('POINT(9 11)'), 4476758, 6, 'iijiijjiijmiijnijjjiiijimjijjijnjiiijjjijjjjjiijj', '2025-01-16 04:47:33.000'),

(8407, '8:2738', 'Pesmenben', '9:390', NULL, NULL, 30, ST_GeomFromText('POINT(11 9)'), 0, 0, 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz', '2025-01-16 04:47:34.000'),

(8408, '8:1979', 'Pesmenben IV', '9:390', '20:584', '1:1259018', 9, ST_GeomFromText('POINT(12 16)'), 688265052, 41, 'kkkkkjkkkknknkjkkkkkmkjnjkknnkjkkkkkkkkjknkkkkkkkkkjkknkjkkmkkkkkjkmkkmkkkkkmmkmk', '2025-01-16 04:47:34.000'),

(8409, '8:1982', 'Pesmenben II', '9:390', '20:584', '1:1259018', 12, ST_GeomFromText('POINT(15 9)'), 26890218, 5, 'mmmmmmmmijjmmmmjjjiiiiiibmmjiiijjmmmmmjiijjmmmiiijjjjjjmmiijjjjiijjjiijjjmmmmmmmmijjjjjijjjijijjjiijmmmmmmijijimmmmmijmmjijmimjmiiimjmmmmmmmmmmm', '2025-01-16 04:47:34.000'),

(8358, '8:2089', 'Drogheda III', '9:425', '20:584', '1:1259018', 6, ST_GeomFromText('POINT(4 13)'), 1622081, 3, 'kkkjkkkknknkkkkkkkkkmkkkkkmjkkkkkkkk', '2025-01-16 03:50:57.000'),

(8359, '8:2141', 'Drogheda', '9:425', '20:584', '1:1259018', 10, ST_GeomFromText('POINT(8 10)'), 4583398, 2, 'kkbbbbbjkkkkbjbjbbkkknbjjjbbkkkkbbjmbjkkkkccbjdckkkggcngdjckkggggedcckkeggggbjikkgggggbbkkkeebjgjbkk', '2025-01-16 03:50:57.000'),

(8360, '8:2773', 'Drogheda Sun', '9:425', NULL, NULL, 30, ST_GeomFromText('POINT(10 9)'), 0, 0, 'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz', '2025-01-16 03:50:58.000'),

(8361, '8:1947', 'Drogheda II', '9:425', '20:584', '1:1259018', 8, ST_GeomFromText('POINT(16 8)'), 58466399, 15, 'kniijikkkkjiiikkkkinninkkkjjjnnkkkiijjkkkmiiijkkkkfijnkkkkjiijkk', '2025-01-16 03:50:58.000');
