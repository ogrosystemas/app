/**
 * Lembra qual sede o Super Admin escolheu pela última vez, para reabrir direto
 * nela em sessões futuras (sem precisar passar pela tela de escolha de sede a
 * cada reload do app instalado) — só usado para Super Admin; tesoureiros de
 * sede já entram direto na própria sede de qualquer forma, sem precisar disso.
 *
 * Usa localStorage (não Firestore): essa preferência é só de conveniência de
 * navegação neste dispositivo específico, não é um dado que precise sincronizar
 * entre dispositivos nem que tenha qualquer implicação de segurança — quem
 * decide o que cada e-mail pode acessar continua sendo só firestore.rules.
 */
const CHAVE_ULTIMA_SEDE = "mutantes-mc:ultima-sede-super-admin";

export function lerUltimaSedeEscolhida(): string | null {
  try {
    return localStorage.getItem(CHAVE_ULTIMA_SEDE);
  } catch {
    // Navegadores com armazenamento bloqueado (modo privado restrito, etc.) —
    // simplesmente não lembra a escolha; a tela de seleção volta a aparecer.
    return null;
  }
}

export function salvarUltimaSedeEscolhida(clubeId: string): void {
  try {
    localStorage.setItem(CHAVE_ULTIMA_SEDE, clubeId);
  } catch {
    // Falha silenciosa — mesma razão do comentário acima.
  }
}

export function limparUltimaSedeEscolhida(): void {
  try {
    localStorage.removeItem(CHAVE_ULTIMA_SEDE);
  } catch {
    // Falha silenciosa — mesma razão do comentário acima.
  }
}
