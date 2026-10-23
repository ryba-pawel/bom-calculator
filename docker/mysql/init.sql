-- Baza dla APP_ENV=test (Doctrine dodaje do nazwy bazy sufiks "_test")
CREATE DATABASE IF NOT EXISTS `bom_calculator_test`;
GRANT ALL PRIVILEGES ON `bom_calculator_test`.* TO 'app'@'%';
