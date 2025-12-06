-- Migration: Adiciona campos de estoque mínimo e ponto de corte em produtos
-- Data: 2025-01-XX
-- Descrição: Adiciona campos para controle de estoque mínimo e ponto de corte (reorder point)

-- Adiciona campo estoque_minimo (permite NULL inicialmente para produtos existentes)
ALTER TABLE prest_produtos 
ADD COLUMN IF NOT EXISTS estoque_minimo INTEGER;

-- Adiciona campo ponto_corte (permite NULL inicialmente para produtos existentes)
ALTER TABLE prest_produtos 
ADD COLUMN IF NOT EXISTS ponto_corte INTEGER;

-- Atualiza produtos existentes com valores padrão
UPDATE prest_produtos 
SET estoque_minimo = 10 
WHERE estoque_minimo IS NULL;

UPDATE prest_produtos 
SET ponto_corte = 5 
WHERE ponto_corte IS NULL;

-- Garante que ponto_corte <= estoque_minimo para produtos existentes
UPDATE prest_produtos 
SET ponto_corte = estoque_minimo 
WHERE ponto_corte > estoque_minimo;

-- Agora torna os campos NOT NULL com valores padrão
ALTER TABLE prest_produtos 
ALTER COLUMN estoque_minimo SET DEFAULT 10,
ALTER COLUMN estoque_minimo SET NOT NULL;

ALTER TABLE prest_produtos 
ALTER COLUMN ponto_corte SET DEFAULT 5,
ALTER COLUMN ponto_corte SET NOT NULL;

-- Comentários nas colunas
COMMENT ON COLUMN prest_produtos.estoque_minimo IS 'Estoque mínimo desejado para o produto. Quando o estoque atual ficar abaixo deste valor, será exibido alerta.';
COMMENT ON COLUMN prest_produtos.ponto_corte IS 'Ponto de corte (reorder point). Quando o estoque atual chegar neste valor, é recomendado fazer resuprimento urgente.';

-- Adiciona constraint para garantir que ponto_corte <= estoque_minimo
-- Remove constraint se já existir
ALTER TABLE prest_produtos 
DROP CONSTRAINT IF EXISTS check_ponto_corte_menor_igual_minimo;

ALTER TABLE prest_produtos 
ADD CONSTRAINT check_ponto_corte_menor_igual_minimo 
CHECK (ponto_corte <= estoque_minimo);

