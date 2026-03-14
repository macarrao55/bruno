-- MAPA DE ENTREGAS - ajuste incremental para tabela existente
ALTER TABLE entregas
  MODIFY COLUMN status ENUM('preparando','em rota','entregue','cancelado','atrasado') DEFAULT 'preparando',
  ADD COLUMN IF NOT EXISTS latitude DECIMAL(10,7) NULL,
  ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) NULL,
  ADD COLUMN IF NOT EXISTS started_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS delivered_at DATETIME NULL;
