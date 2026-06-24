/**
 * Metadados de uma sede (clube), usados pela tela de Super Admin para listar e
 * criar sedes — não confundir com `ConfigClube` (que vive dentro de
 * `clubes/{clubeId}` e contém nome exibido/valor/Pix). Este tipo descreve o
 * registro central de TODAS as sedes existentes, em `sedes/{clubeId}`.
 */
export interface Sede {
  /** ID da sede (mesmo valor usado como clubeId em clubes/{clubeId}). */
  id?: string;

  /** Nome curto da sede, para exibição na lista de sedes (ex: "Itajaí"). */
  nome: string;

  /** Timestamp de criação (epoch ms). */
  criadoEm: number;
}

export type NovaSedeInput = Pick<Sede, "nome"> & {
  /** ID escolhido para a sede (minúsculas, sem espaços/acentos — usado como caminho no Firestore). */
  id: string;
  /** Valor inicial da mensalidade para esta sede. */
  valorMensalidade: number;
  /** E-mail do tesoureiro responsável por esta sede, já vinculado como administrador na criação. */
  emailTesoureiro: string;
};

/**
 * Vínculo de um e-mail administrador a uma sede específica — ou a TODAS as sedes,
 * no caso do super admin (`clubeId: "*"`). Armazenado em
 * `administradores/{email}`, consultado pelas regras de segurança do Firestore
 * para decidir o que cada e-mail logado pode ver/editar.
 */
export interface Administrador {
  /** ID da sede que este e-mail administra, ou "*" para acesso de super admin (todas as sedes). */
  clubeId: string;
}
