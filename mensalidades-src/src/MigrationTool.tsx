import { useState } from "react";
import {
  collection,
  doc,
  getDoc,
  getDocs,
  setDoc,
  writeBatch,
} from "firebase/firestore";
import { db } from "./firebase/config";
import { useAuth } from "./hooks/useAuth";

const ID_CLUBE_ANTIGO = "mutantes-mc";
const ID_SEDE_NOVA = "itajai";
const NOME_SEDE_NOVA = "Itajaí";
const EMAIL_SUPER_ADMIN = "tibabarcelos@gmail.com";

/**
 * FERRAMENTA DE MIGRAÇÃO TEMPORÁRIA — usar uma única vez para converter o clube
 * fixo antigo (clubes/mutantes-mc) para o modelo multi-sede (clubes/itajai +
 * sedes/itajai + administradores/{email}).
 *
 * Depois de confirmada a migração com sucesso, este arquivo e seu uso em
 * main.tsx devem ser REMOVIDOS — não faz parte do app de produção, é só uma
 * ferramenta de uma execução. Exige login com Google antes de mostrar o botão
 * de migração — as regras de segurança do Firestore continuam protegendo os
 * dados mesmo nesta tela (só o e-mail fixo em EMAIL_SUPER_ADMIN consegue
 * escrever, pela função ehSuperAdminInicial() em firestore.rules).
 */
export function MigrationTool() {
  const { usuario, carregando, entrarComGoogle, erro } = useAuth();
  const [log, setLog] = useState<string[]>([]);
  const [executando, setExecutando] = useState(false);
  const [concluido, setConcluido] = useState(false);

  function adicionarLog(linha: string) {
    setLog((atual) => [...atual, linha]);
  }

  async function executarMigracao() {
    setExecutando(true);
    setLog([]);
    try {
      adicionarLog("Iniciando migração...");

      // 1. Cria o vínculo de super admin (necessário ANTES de qualquer outra
      // escrita, já que as regras de segurança exigem ser admin para escrever).
      adicionarLog(`Criando vínculo de super admin para ${EMAIL_SUPER_ADMIN}...`);
      await setDoc(doc(db, "administradores", EMAIL_SUPER_ADMIN.toLowerCase()), {
        clubeId: "*",
      });
      adicionarLog("✅ Super admin criado.");

      // 2. Lê a config do clube antigo.
      adicionarLog(`Lendo config de clubes/${ID_CLUBE_ANTIGO}...`);
      const configAntigaSnapshot = await getDoc(doc(db, "clubes", ID_CLUBE_ANTIGO));
      if (!configAntigaSnapshot.exists()) {
        throw new Error(`clubes/${ID_CLUBE_ANTIGO} não encontrado — nada para migrar.`);
      }
      const configAntiga = configAntigaSnapshot.data();
      adicionarLog(`✅ Config encontrada: ${JSON.stringify(configAntiga)}`);

      // 3. Cria os metadados da sede nova e a config da sede nova (cópia da antiga).
      adicionarLog(`Criando sedes/${ID_SEDE_NOVA}...`);
      await setDoc(doc(db, "sedes", ID_SEDE_NOVA), {
        nome: NOME_SEDE_NOVA,
        criadoEm: Date.now(),
      });
      adicionarLog(`Criando clubes/${ID_SEDE_NOVA} (cópia da config antiga)...`);
      await setDoc(doc(db, "clubes", ID_SEDE_NOVA), configAntiga);
      adicionarLog("✅ Sede e config criadas.");

      // 4. Copia todos os membros, mantendo o MESMO ID de documento (importante:
      // os documentos de "acessos" antigos referenciam membroId — manter o mesmo
      // ID evita ter que reescrever também a coleção acessos).
      adicionarLog("Copiando membros...");
      const membrosAntigos = await getDocs(collection(db, "clubes", ID_CLUBE_ANTIGO, "membros"));
      const loteMembros = writeBatch(db);
      let totalMembros = 0;
      for (const docMembro of membrosAntigos.docs) {
        loteMembros.set(doc(db, "clubes", ID_SEDE_NOVA, "membros", docMembro.id), docMembro.data());
        totalMembros++;
      }
      if (totalMembros > 0) await loteMembros.commit();
      adicionarLog(`✅ ${totalMembros} membro(s) copiado(s).`);

      // 5. Copia todos os pagamentos, mantendo o mesmo ID determinístico.
      adicionarLog("Copiando pagamentos...");
      const pagamentosAntigos = await getDocs(collection(db, "clubes", ID_CLUBE_ANTIGO, "pagamentos"));
      const lotePagamentos = writeBatch(db);
      let totalPagamentos = 0;
      for (const docPagamento of pagamentosAntigos.docs) {
        lotePagamentos.set(
          doc(db, "clubes", ID_SEDE_NOVA, "pagamentos", docPagamento.id),
          docPagamento.data(),
        );
        totalPagamentos++;
      }
      if (totalPagamentos > 0) await lotePagamentos.commit();
      adicionarLog(`✅ ${totalPagamentos} pagamento(s) copiado(s).`);

      // 6. Copia avisos, se houver.
      adicionarLog("Copiando avisos...");
      const avisosAntigos = await getDocs(collection(db, "clubes", ID_CLUBE_ANTIGO, "avisos"));
      const loteAvisos = writeBatch(db);
      let totalAvisos = 0;
      for (const docAviso of avisosAntigos.docs) {
        loteAvisos.set(doc(db, "clubes", ID_SEDE_NOVA, "avisos", docAviso.id), docAviso.data());
        totalAvisos++;
      }
      if (totalAvisos > 0) await loteAvisos.commit();
      adicionarLog(`✅ ${totalAvisos} aviso(s) copiado(s).`);

      // 7. Atualiza os documentos de "acessos" existentes (integrantes vinculados),
      // adicionando o campo clubeId que não existia no formato antigo.
      adicionarLog("Atualizando vínculos de acesso (acessos/*) com o novo clubeId...");
      const acessosSnapshot = await getDocs(collection(db, "acessos"));
      const loteAcessos = writeBatch(db);
      let totalAcessos = 0;
      for (const docAcesso of acessosSnapshot.docs) {
        const dados = docAcesso.data();
        if (!("clubeId" in dados)) {
          loteAcessos.set(doc(db, "acessos", docAcesso.id), { ...dados, clubeId: ID_SEDE_NOVA });
          totalAcessos++;
        }
      }
      if (totalAcessos > 0) await loteAcessos.commit();
      adicionarLog(`✅ ${totalAcessos} vínculo(s) de acesso atualizado(s).`);

      adicionarLog("");
      adicionarLog("🎉 MIGRAÇÃO CONCLUÍDA COM SUCESSO.");
      adicionarLog(`Os dados antigos em clubes/${ID_CLUBE_ANTIGO} NÃO foram apagados —`);
      adicionarLog("você pode confirmar tudo certo na sede nova antes de remover o antigo manualmente.");
      setConcluido(true);
    } catch (erro) {
      adicionarLog(`❌ ERRO: ${erro instanceof Error ? erro.message : String(erro)}`);
    } finally {
      setExecutando(false);
    }
  }

  return (
    <div className="flex min-h-screen flex-col gap-4 bg-graphite-950 p-4 text-chrome-50">
      <h1 className="font-display text-lg font-bold uppercase">Ferramenta de Migração — uso único</h1>

      {carregando && <p className="text-sm text-graphite-400">Verificando sessão...</p>}

      {!carregando && !usuario && (
        <div className="flex flex-col gap-3">
          <p className="text-sm text-graphite-400">Faça login para continuar.</p>
          {erro && <p className="text-sm text-alert-400">{erro}</p>}
          <button
            type="button"
            onClick={() => entrarComGoogle()}
            className="bg-ember-600 px-4 py-2 font-display text-sm font-semibold uppercase text-chrome-50"
          >
            Entrar com Google
          </button>
        </div>
      )}

      {!carregando && usuario && usuario.email?.toLowerCase() !== EMAIL_SUPER_ADMIN.toLowerCase() && (
        <p className="text-sm text-alert-400">
          Logado como {usuario.email}, mas esta ferramenta só pode ser executada por{" "}
          {EMAIL_SUPER_ADMIN}.
        </p>
      )}

      {!carregando && usuario && usuario.email?.toLowerCase() === EMAIL_SUPER_ADMIN.toLowerCase() && (
        <>
          <p className="text-sm text-graphite-400">
            Migra clubes/{ID_CLUBE_ANTIGO} para clubes/{ID_SEDE_NOVA}, cria sedes/{ID_SEDE_NOVA} e o
            vínculo de super admin para {EMAIL_SUPER_ADMIN}. Os dados antigos NÃO são apagados.
          </p>

          <button
            type="button"
            onClick={executarMigracao}
            disabled={executando || concluido}
            className="bg-ember-600 px-4 py-2 font-display text-sm font-semibold uppercase text-chrome-50 disabled:opacity-50"
          >
            {executando ? "Migrando..." : concluido ? "Migração concluída" : "Executar migração"}
          </button>

          <pre className="flex-1 overflow-auto border border-graphite-700 bg-graphite-900 p-3 text-xs">
            {log.join("\n")}
          </pre>
        </>
      )}
    </div>
  );
}
