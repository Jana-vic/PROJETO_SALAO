-- =====================================================
-- banco.sql  —  cria o banco de dados do Studio Bem Garota
-- Rode UMA vez só (phpMyAdmin > Importar, ou linha de comando)
-- =====================================================

-- 1) Cria o banco (a "gaveteira" inteira)
CREATE DATABASE IF NOT EXISTS agendamento_bemgarota
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

-- 2) Entra no banco ("daqui pra frente, falo deste")
USE agendamento_bemgarota;

SET NAMES utf8mb4;

-- =====================================================
-- TABELA 1: clientes — quem marca horário
-- =====================================================
CREATE TABLE IF NOT EXISTS clientes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  whatsapp VARCHAR(20) NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY whatsapp_unico (whatsapp)
) ENGINE=InnoDB;

-- =====================================================
-- TABELA 2: agendamentos — quem, quando e o status
--   pendente   = amarelo (aguardando o Pix)
--   confirmado = vermelho (Pix pago, horário garantido)
--   expirado   = prazo do Pix venceu, horário liberado
--   cancelado  = studio cancelou
-- =====================================================
CREATE TABLE IF NOT EXISTS agendamentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT UNSIGNED NOT NULL,
  `data` DATE NOT NULL,
  `hora` TIME NOT NULL,
  status ENUM('pendente','confirmado','cancelado','expirado') NOT NULL DEFAULT 'pendente',
  valor_servico DECIMAL(10,2) NOT NULL,
  valor_sinal DECIMAL(10,2) NOT NULL,
  valor_restante DECIMAL(10,2) NOT NULL,
  expira_em DATETIME NULL,
  pago_em DATETIME NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX busca_data_hora (`data`, `hora`),
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABELA 3: recadinhos — mensagens das clientes
-- =====================================================
CREATE TABLE IF NOT EXISTS recadinhos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT UNSIGNED NOT NULL,
  mensagem TEXT NOT NULL,
  lida ENUM('nao','sim') NOT NULL DEFAULT 'nao',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TABELA 4: bloqueios — horários fechados pela nail designer
-- =====================================================
CREATE TABLE IF NOT EXISTS bloqueios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `data` DATE NOT NULL,
  `hora` TIME NOT NULL,
  motivo VARCHAR(200) NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY dia_e_hora (`data`, `hora`)
) ENGINE=InnoDB;

-- =====================================================
-- TABELA 5: usuarios — login da nail designer
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(50) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  UNIQUE KEY usuario_unico (usuario)
) ENGINE=InnoDB;

-- Senha inicial: admin123 (guardada CRIPTOGRAFADA, nunca em texto puro)
-- Para trocar depois: gere um hash novo (ex.: php -r "echo password_hash('suasenha', PASSWORD_DEFAULT);")
-- e atualize esta linha, ou peça para trocarmos juntos na etapa do login.
INSERT INTO usuarios (usuario, senha)
VALUES ('admin', '$2y$10$FPrCitp8Vwsv7cJv685yHeLcNnpHO2AK4vaj2c2WR65tfOhzvAHF.');
