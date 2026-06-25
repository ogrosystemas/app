/**
 * Papel do dono do token no momento em que o token foi registrado — usado pelo
 * script de disparo (rodando fora do app, no VPS) para decidir QUE TIPO de
 * lembrete enviar para cada token, sem precisar reconsultar `administradores`/
 * `acessos` a cada envio:
 *
 * - "integrante": recebe lembrete pessoal (pendência do próprio membro vinculado).
 * - "admin-sede": recebe lembrete pessoal (se também tiver pendência como membro,
 *   caso o e-mail do tesoureiro esteja vinculado a um Membro) + resumo da sede
 *   (quantos membros estão pendentes) — o Super Admin nunca recebe automático.
 */
export type PapelTokenNotificacao = "integrante" | "admin-sede";

/**
 * Um token de notificação push (Firebase Cloud Messaging) de UM dispositivo de
 * UMA pessoa. Guardado com o PRÓPRIO TOKEN como ID do documento (não o e-mail)
 * porque a mesma pessoa pode ter múltiplos dispositivos/instalações do PWA —
 * cada um gera seu próprio token, e cada um recebe a notificação separadamente.
 *
 * Armazenado na raiz do banco (`tokensNotificacao/{token}`), fora do caminho de
 * qualquer sede especifica, porque o script de disparo (VPS) precisa fazer UMA
 * leitura central de todos os tokens de todas as sedes, em vez de 11 leituras
 * separadas por subcoleção.
 */
export interface TokenNotificacao {
  /** Token do dispositivo, gerado pelo FCM no navegador. Também é o ID do documento. */
  token: string;

  /** E-mail (normalizado, minúsculas) da pessoa autenticada que gerou este token. */
  email: string;

  /** Sede a que este token pertence (a mesma sede da pessoa logada quando o token foi salvo). */
  clubeId: string;

  /** Papel da pessoa no momento do registro — define o tipo de lembrete enviado. */
  papel: PapelTokenNotificacao;

  /**
   * ID do membro vinculado a este e-mail nesta sede, quando existir — usado para
   * calcular a pendência PESSOAL tanto de integrantes quanto de tesoureiros que
   * também sejam membros cadastrados. Ausente se o e-mail não tiver vínculo de
   * membro (ex: tesoureiro que só administra, sem ser ele mesmo um membro).
   */
  membroId?: string;

  /** Timestamp de criação do registro (epoch ms). */
  criadoEm: number;

  /** Timestamp da última vez que este token foi confirmado/atualizado (epoch ms). */
  atualizadoEm: number;
}
