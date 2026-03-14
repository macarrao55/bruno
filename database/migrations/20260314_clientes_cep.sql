-- Compatibilidade: adicionar CEP no cadastro de clientes
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS cep VARCHAR(15) NULL AFTER telefone;
