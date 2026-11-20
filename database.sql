CREATE DATABASE IF NOT EXISTS gerenciador_logistico
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gerenciador_logistico;

CREATE TABLE IF NOT EXISTS produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  descricao TEXT NULL,
  quantidade INT NOT NULL DEFAULT 1,
  peso DECIMAL(10,2) NOT NULL DEFAULT 0,
  valor DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enderecos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  identificacao VARCHAR(100) NOT NULL,
  logradouro VARCHAR(150) NOT NULL,
  numero VARCHAR(20) NULL,
  complemento VARCHAR(100) NULL,
  bairro VARCHAR(100) NULL,
  cidade VARCHAR(100) NOT NULL,
  estado CHAR(2) NOT NULL,
  cep VARCHAR(9) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transportadores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  documento VARCHAR(20) NULL,
  telefone VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  veiculo VARCHAR(100) NULL,
  placa VARCHAR(10) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fretes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  origem_id INT NOT NULL,
  destino_id INT NOT NULL,
  transportador_id INT NOT NULL,
  data_frete DATE NOT NULL,
  status ENUM('Pendente','Em trânsito','Concluído','Cancelado') NOT NULL DEFAULT 'Pendente',
  observacoes TEXT NULL,
  valor_frete DECIMAL(12,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_frete_origem FOREIGN KEY (origem_id) REFERENCES enderecos(id),
  CONSTRAINT fk_frete_destino FOREIGN KEY (destino_id) REFERENCES enderecos(id),
  CONSTRAINT fk_frete_transportador FOREIGN KEY (transportador_id) REFERENCES transportadores(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS frete_produtos (
  frete_id INT NOT NULL,
  produto_id INT NOT NULL,
  quantidade INT NOT NULL DEFAULT 1,
  PRIMARY KEY (frete_id, produto_id),
  CONSTRAINT fk_fp_frete FOREIGN KEY (frete_id) REFERENCES fretes(id) ON DELETE CASCADE,
  CONSTRAINT fk_fp_produto FOREIGN KEY (produto_id) REFERENCES produtos(id)
) ENGINE=InnoDB;
