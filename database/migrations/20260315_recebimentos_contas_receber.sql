-- Histórico de recebimentos para suportar juros, desconto e pagamento parcial em contas a receber
CREATE TABLE IF NOT EXISTS recebimentos_contas_receber (
  id INT AUTO_INCREMENT PRIMARY KEY,
  conta_receber_id INT NOT NULL,
  valor_pago DECIMAL(10,2) NOT NULL,
  juros DECIMAL(10,2) DEFAULT 0,
  desconto DECIMAL(10,2) DEFAULT 0,
  forma_pagamento VARCHAR(40) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conta_receber_id) REFERENCES contas_receber(id)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
