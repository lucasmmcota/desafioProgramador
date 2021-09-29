-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema bank
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema bank
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `bank` DEFAULT CHARACTER SET utf8 ;
USE `bank` ;

-- -----------------------------------------------------
-- Table `bank`.`conta`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `bank`.`conta` (
  `numeroDaConta` INT NOT NULL AUTO_INCREMENT,
  `saldoTotalReais` DOUBLE NULL,
  `moedas` VARCHAR(45) NULL,
  `saldoMoedas` VARCHAR(45) NULL,
  PRIMARY KEY (`numeroDaConta`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `bank`.`transacao`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `bank`.`transacao` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `conta_numeroDaConta` INT NOT NULL,
  `tipo` VARCHAR(45) NOT NULL,
  `valor` DOUBLE NULL,
  `moeda` VARCHAR(45) NOT NULL,
  `data` DATE NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_transacao_conta`
    FOREIGN KEY (`conta_numeroDaConta`)
    REFERENCES `bank`.`conta` (`numeroDaConta`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
