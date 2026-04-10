-- Correção pontual: divide por 2 o estoque de produtos que foram duplicados
-- Execute APENAS se o estoque está o dobro do esperado
-- Substitua o ID correto do produto afetado

-- Para identificar produtos possivelmente duplicados:
SELECT p.id, p.nome, p.tipo, p.estoque_atual,
       ROUND(p.capacidade_ml * (p.rendimento_pct/100) / p.ml_por_dose) AS doses_por_barril,
       ROUND(p.estoque_atual / NULLIF(ROUND(p.capacidade_ml * (p.rendimento_pct/100) / p.ml_por_dose),0), 2) AS barris_equivalentes
FROM produtos p
WHERE p.tipo IN ('chopp_barril','dose','garrafa')
  AND p.capacidade_ml > 0
  AND p.ml_por_dose > 0
ORDER BY barris_equivalentes DESC;

-- Após identificar, corrija manualmente:
-- UPDATE produtos SET estoque_atual = [valor_correto] WHERE id = [id_do_produto];

-- Exemplo: Chopp 500 estava com 112 mas deveria ter 56:
-- UPDATE produtos SET estoque_atual = 56 WHERE nome LIKE '%chopp%500%';
