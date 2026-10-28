USE `biblioteca`;

ALTER TABLE `leitores`
  ADD COLUMN `email` VARCHAR(190) NULL AFTER `nome`;

CREATE UNIQUE INDEX `uq_leitores_email` ON `leitores` (`email`);

CREATE TABLE IF NOT EXISTS `redefinicao_senha` (
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