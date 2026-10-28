-- ============================================================
-- biblioteca.sql  (banco completo, já com todos os ajustes)
-- Importe no phpMyAdmin (aba "Importar" ou "SQL").
-- ATENÇÃO: este script apaga e recria as tabelas. Se você já
-- tem dados que quer manter, use o alter_tabelas.sql no lugar.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `biblioteca`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `biblioteca`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `emprestimos`;
DROP TABLE IF EXISTS `prateleira`;
DROP TABLE IF EXISTS `redefinicao_senha`;
DROP TABLE IF EXISTS `livros`;
DROP TABLE IF EXISTS `leitores`;
DROP TABLE IF EXISTS `funcionario`;
DROP TABLE IF EXISTS `bibliotecario`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- bibliotecario
-- --------------------------------------------------------
CREATE TABLE `bibliotecario` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(30) NOT NULL,
  `senha` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `bibliotecario` (`nome`, `senha`) VALUES
('alysson', '$2y$10$cCfzNLLy5EcT/4wlzkPETOvfnNtsaYGIG48hv.1FN8Pca7nFMK5/2');

-- --------------------------------------------------------
-- funcionario
-- --------------------------------------------------------
CREATE TABLE `funcionario` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(30) NOT NULL,
  `senha` VARCHAR(100) NOT NULL,
  `telefone` VARCHAR(15) NOT NULL,
  `cpf` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- leitores
-- --------------------------------------------------------
CREATE TABLE `leitores` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(30) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `senha` VARCHAR(100) NOT NULL,
  `telefone` VARCHAR(15) NOT NULL,
  `cpf` VARCHAR(100) NOT NULL,
  `endereco` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_leitores_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `redefinicao_senha` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `leitor_id` INT NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expira_em` DATETIME NOT NULL,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_redefinicao_token_hash` (`token_hash`),
  KEY `idx_redefinicao_leitor` (`leitor_id`),
  CONSTRAINT `fk_redefinicao_leitor` FOREIGN KEY (`leitor_id`)
    REFERENCES `leitores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- livros
-- --------------------------------------------------------
CREATE TABLE `livros` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `status` VARCHAR(5) NOT NULL DEFAULT 'ativo',
  `titulo` VARCHAR(30) NOT NULL,
  `autor` VARCHAR(30) NOT NULL,
  `genero` VARCHAR(15) NOT NULL,
  `quantidade` INT NOT NULL DEFAULT 1,
  `descricao` VARCHAR(350) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- prateleira
-- --------------------------------------------------------
CREATE TABLE `prateleira` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- emprestimos (status: 'emprestado' ou 'devolvido')
-- --------------------------------------------------------
CREATE TABLE `emprestimos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `id_livro` INT NOT NULL,
  `id_leitor` INT NOT NULL,
  `data_emprestimo` DATE NOT NULL,
  `data_prevista` DATE NOT NULL,
  `data_devolucao` DATE DEFAULT NULL,
  `multa` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `status` VARCHAR(15) NOT NULL DEFAULT 'emprestado',
  PRIMARY KEY (`id`),
  KEY `id_livro` (`id_livro`),
  KEY `id_leitor` (`id_leitor`),
  CONSTRAINT `fk_emprestimo_livro` FOREIGN KEY (`id_livro`) REFERENCES `livros` (`id`),
  CONSTRAINT `fk_emprestimo_leitor` FOREIGN KEY (`id_leitor`) REFERENCES `leitores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
