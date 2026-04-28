-- ============================================================
-- Themis — seeds/templates.sql
-- Templates padrão de documentos jurídicos
-- Requer que já exista ao menos 1 tenant (id=1) e 1 user (id=1)
-- ============================================================

INSERT INTO doc_templates (tenant_id, nome, tipo, subtipo, conteudo_html, variaveis_json, papel_timbrado, ativo, created_by) VALUES

(1, 'Petição Inicial — Cível Padrão', 'peticao', 'inicial',
'<h2 style="text-align:center;text-transform:uppercase">EXCELENTÍSSIMO(A) SENHOR(A) DOUTOR(A) JUIZ(A) DE DIREITO DA {{processo_vara}}</h2>
<br>
<p><strong>{{cliente_nome}}</strong>, {{polo}}, {{cliente_doc}}, residente e domiciliado(a) à {{cliente_endereco}}, vem, por seu(sua) advogado(a) que esta subscreve, {{advogado_nome}}, inscrito(a) na {{advogado_oab}}, propor a presente</p>
<h3 style="text-align:center">AÇÃO {{processo_titulo}}</h3>
<p>em face de <strong>{{parte_contraria}}</strong>, pelos fatos e fundamentos a seguir expostos:</p>
<h3>I — DOS FATOS</h3>
<p>{{fatos}}</p>
<h3>II — DO DIREITO</h3>
<p>{{fundamentos_juridicos}}</p>
<h3>III — DO PEDIDO</h3>
<p>Ante o exposto, requer a Vossa Excelência se digne a:</p>
<p>{{pedidos}}</p>
<p>Dá-se à causa o valor de {{processo_valor_causa}}.</p>
<p>Nesses termos,<br>pede deferimento.</p>
<p>{{processo_comarca}}, {{data_hoje}}.</p>
<br>
<p style="text-align:center">______________________________________________<br><strong>{{advogado_nome}}</strong><br>{{advogado_oab}}</p>',
'["cliente_nome","polo","cliente_doc","cliente_endereco","advogado_nome","advogado_oab","processo_vara","processo_titulo","parte_contraria","fatos","fundamentos_juridicos","pedidos","processo_valor_causa","processo_comarca","data_hoje"]',
1, 1, 1),

(1, 'Contestação Padrão', 'peticao', 'contestacao',
'<h2 style="text-align:center;text-transform:uppercase">EXCELENTÍSSIMO(A) SENHOR(A) DOUTOR(A) JUIZ(A) DE DIREITO</h2>
<p style="text-align:center"><strong>{{processo_vara}}</strong></p>
<p style="text-align:center"><strong>Processo nº {{processo_numero}}</strong></p>
<br>
<p><strong>{{cliente_nome}}</strong>, {{polo}}, nos autos da ação em epígrafe movida por <strong>{{parte_contraria}}</strong>, vem, tempestivamente, por meio de seu(sua) advogado(a) {{advogado_nome}}, {{advogado_oab}}, apresentar</p>
<h3 style="text-align:center">CONTESTAÇÃO</h3>
<p>pelos motivos a seguir expostos:</p>
<h3>I — PRELIMINARMENTE</h3>
<p>{{preliminares}}</p>
<h3>II — NO MÉRITO</h3>
<p>{{merito}}</p>
<h3>III — DOS PEDIDOS</h3>
<p>Diante do exposto, requer sejam julgados improcedentes os pedidos formulados na inicial, com condenação do autor ao pagamento das custas e honorários advocatícios.</p>
<p>{{processo_comarca}}, {{data_hoje}}.</p>
<br>
<p style="text-align:center">______________________________________________<br><strong>{{advogado_nome}}</strong><br>{{advogado_oab}}</p>',
'["processo_numero","processo_vara","cliente_nome","polo","parte_contraria","advogado_nome","advogado_oab","preliminares","merito","processo_comarca","data_hoje"]',
1, 1, 1),

(1, 'Parecer Técnico Divergente — Padrão', 'parecer', 'divergente',
'<h2 style="text-align:center;text-transform:uppercase">PARECER TÉCNICO DIVERGENTE</h2>
<p style="text-align:center"><strong>Processo nº {{processo_numero}}</strong></p>
<p style="text-align:center">{{processo_vara}} — {{processo_tribunal}}</p>
<br>
<h3>1. OBJETO</h3>
<p>O presente Parecer Técnico Divergente tem por objeto a análise crítica do Laudo Pericial apresentado pelo perito oficial, apontando as divergências técnicas identificadas pelo Assistente Técnico da parte {{polo}}.</p>
<h3>2. IDENTIFICAÇÃO DAS PARTES</h3>
<p>
<strong>Requerente:</strong> {{parte_contraria}}<br>
<strong>Requerido:</strong> {{cliente_nome}}<br>
<strong>Assistente Técnico:</strong> {{advogado_nome}} — {{advogado_oab}}<br>
<strong>Data:</strong> {{data_hoje}}
</p>
<h3>3. DIVERGÊNCIAS IDENTIFICADAS</h3>
<p>{{divergencias_texto}}</p>
<h3>4. CONCLUSÃO</h3>
<p>{{conclusao}}</p>
<h3>5. DOCUMENTOS CONSULTADOS</h3>
<p>{{documentos_consultados}}</p>
<br>
<p>{{processo_comarca}}, {{data_hoje}}.</p>
<br>
<p style="text-align:center">______________________________________________<br><strong>{{advogado_nome}}</strong><br>{{advogado_oab}}</p>',
'["processo_numero","processo_vara","processo_tribunal","polo","parte_contraria","cliente_nome","advogado_nome","advogado_oab","divergencias_texto","conclusao","documentos_consultados","processo_comarca","data_hoje"]',
1, 1, 1),

(1, 'Laudo Pericial — Estrutura Padrão', 'laudo', 'judicial',
'<h1 style="text-align:center">LAUDO PERICIAL</h1>
<p style="text-align:center"><strong>Processo nº {{processo_numero}}</strong></p>
<p style="text-align:center">{{processo_vara}} — {{processo_tribunal}}</p>
<br>
<h2>1. QUESITOS</h2>
<p>{{quesitos}}</p>
<h2>2. OBJETO DA PERÍCIA</h2>
<p>{{objeto_pericia}}</p>
<h2>3. METODOLOGIA</h2>
<p>{{metodologia}}</p>
<h2>4. ANÁLISE TÉCNICA</h2>
<p>{{analise_tecnica}}</p>
{{#if ibutg_registrado}}
<h2>5. REGISTRO DE IBUTG — NR-15</h2>
<p>
<strong>Índice de Bulbo Úmido Termômetro de Globo (IBUTG):</strong> {{ibutg_registrado}} °C<br>
<strong>Data/Hora da Medição:</strong> {{ibutg_data}}<br>
<strong>Local:</strong> {{ibutg_local}}<br>
<strong>Limite NR-15:</strong> {{ibutg_limite}} °C ({{ibutg_regime}})
</p>
{{/if}}
<h2>6. RESPOSTAS AOS QUESITOS</h2>
<p>{{respostas_quesitos}}</p>
<h2>7. CONCLUSÃO</h2>
<p>{{conclusao}}</p>
<h2>8. MEMÓRIA DE CÁLCULO</h2>
<p>{{memoria_calculo}}</p>
<br>
<p>{{processo_comarca}}, {{data_hoje}}.</p>
<br>
<p style="text-align:center">______________________________________________<br><strong>{{advogado_nome}}</strong><br>{{advogado_oab}}</p>',
'["processo_numero","processo_vara","processo_tribunal","quesitos","objeto_pericia","metodologia","analise_tecnica","respostas_quesitos","conclusao","memoria_calculo","ibutg_registrado","ibutg_data","ibutg_local","ibutg_limite","ibutg_regime","processo_comarca","data_hoje","advogado_nome","advogado_oab"]',
1, 1, 1),

(1, 'Notificação Extrajudicial', 'notificacao', 'extrajudicial',
'<h2 style="text-align:center;text-transform:uppercase">NOTIFICAÇÃO EXTRAJUDICIAL</h2>
<br>
<p><strong>{{notificante_nome}}</strong>, {{notificante_qualificacao}}, vem, por meio de seu(sua) advogado(a) {{advogado_nome}}, {{advogado_oab}}, NOTIFICAR {{notificado_nome}}, {{notificado_qualificacao}}, nos seguintes termos:</p>
<h3>DOS FATOS E FUNDAMENTOS</h3>
<p>{{fatos}}</p>
<h3>DO PEDIDO</h3>
<p>Pelo exposto, NOTIFICA-SE o destinatário para que, no prazo de <strong>{{prazo_dias}} ({{prazo_dias_extenso}}) dias</strong> úteis a contar do recebimento desta, {{pedido_notificacao}}.</p>
<p>Caso não seja atendida a presente notificação no prazo acima estipulado, o notificante tomará as medidas judiciais cabíveis, sem prejuízo de eventuais perdas e danos.</p>
<p>{{processo_comarca}}, {{data_hoje}}.</p>
<br>
<p style="text-align:center">______________________________________________<br><strong>{{advogado_nome}}</strong><br>{{advogado_oab}}</p>',
'["notificante_nome","notificante_qualificacao","notificado_nome","notificado_qualificacao","advogado_nome","advogado_oab","fatos","prazo_dias","prazo_dias_extenso","pedido_notificacao","processo_comarca","data_hoje"]',
1, 1, 1),

(1, 'Contrato de Honorários Advocatícios', 'contrato', 'honorarios',
'<h2 style="text-align:center;text-transform:uppercase">CONTRATO DE PRESTAÇÃO DE SERVIÇOS ADVOCATÍCIOS</h2>
<br>
<p><strong>CONTRATANTE:</strong> {{cliente_nome}}, {{cliente_qualificacao}}, {{cliente_doc}}, residente e domiciliado(a) à {{cliente_endereco}}.</p>
<p><strong>CONTRATADO(A):</strong> {{advogado_nome}}, inscrito(a) na {{advogado_oab}}, com escritório na {{escritorio_endereco}}.</p>
<br>
<h3>CLÁUSULA 1ª — DO OBJETO</h3>
<p>O(A) CONTRATADO(A) compromete-se a prestar serviços advocatícios ao(à) CONTRATANTE na seguinte causa: <strong>{{objeto_causa}}</strong>, perante {{processo_vara}}, {{processo_tribunal}}.</p>
<h3>CLÁUSULA 2ª — DOS HONORÁRIOS</h3>
<p>Pelos serviços ora contratados, o(a) CONTRATANTE pagará ao(à) CONTRATADO(A) a título de honorários advocatícios {{descricao_honorarios}}.</p>
<h3>CLÁUSULA 3ª — DAS DESPESAS</h3>
<p>As despesas processuais, custas judiciais, emolumentos e outras despesas necessárias ao bom andamento da causa correrão por conta do(a) CONTRATANTE, devendo ser reembolsadas ao(à) CONTRATADO(A) quando adiantadas por este(a).</p>
<h3>CLÁUSULA 4ª — DA VIGÊNCIA</h3>
<p>O presente contrato vigorará até o trânsito em julgado da decisão final, incluindo eventuais recursos e fase de execução.</p>
<h3>CLÁUSULA 5ª — DO FORO</h3>
<p>Fica eleito o Foro da Comarca de {{processo_comarca}} para dirimir quaisquer dúvidas decorrentes do presente instrumento.</p>
<br>
<p>Por estarem assim justos e contratados, assinam o presente instrumento em 2 (duas) vias de igual teor e forma.</p>
<p>{{processo_comarca}}, {{data_hoje}}.</p>
<br>
<div style="display:flex;justify-content:space-between;margin-top:40px">
<div style="text-align:center">______________________________<br><strong>{{cliente_nome}}</strong><br>CONTRATANTE</div>
<div style="text-align:center">______________________________<br><strong>{{advogado_nome}}</strong><br>{{advogado_oab}}<br>CONTRATADO(A)</div>
</div>',
'["cliente_nome","cliente_qualificacao","cliente_doc","cliente_endereco","advogado_nome","advogado_oab","escritorio_endereco","objeto_causa","processo_vara","processo_tribunal","descricao_honorarios","processo_comarca","data_hoje"]',
1, 1, 1)

ON DUPLICATE KEY UPDATE nome = VALUES(nome);
