import { onSnapshot } from "firebase/firestore";
import { useEffect, useState } from "react";
import { refSede } from "../db/refs";
import type { Sede } from "../types";

/**
 * Hook de leitura dos metadados de UMA sede (nome curto + tipo Matriz/Subsede),
 * usado pelo header do app para exibir o badge correto. Diferente de useConfig
 * (que lê `clubes/{clubeId}`, com o nome completo editável e o valor da
 * mensalidade), este hook lê `sedes/{clubeId}` — o registro central usado pela
 * tela de Super Admin para listar/criar sedes.
 *
 * Reativo via onSnapshot — qualquer alteração (ex: super admin reclassificando
 * Matriz/Subsede direto no Firestore) reflete aqui automaticamente.
 */
export function useSede(clubeId: string): { sede: Sede | undefined; carregando: boolean } {
  const [sede, setSede] = useState<Sede | undefined>(undefined);
  const [carregando, setCarregando] = useState(true);

  useEffect(() => {
    setSede(undefined);
    setCarregando(true);
    const cancelarInscricao = onSnapshot(
      refSede(clubeId),
      (snapshot) => {
        setSede(snapshot.exists() ? { ...snapshot.data(), id: snapshot.id } : undefined);
        setCarregando(false);
      },
      () => {
        // Falha de permissão ou de rede: trata como "sede sem metadados disponíveis"
        // em vez de travar a tela em carregamento — o header tem um fallback visual
        // para esse caso (ver AppHeader.tsx).
        setSede(undefined);
        setCarregando(false);
      },
    );
    return cancelarInscricao;
  }, [clubeId]);

  return { sede, carregando };
}
