import { LogOut, Plus, Skull } from "lucide-react";
import type { Sede } from "../../types";
import { Button } from "../ui/Button";

interface SedeSelectionScreenProps {
  sedes: Sede[];
  carregando: boolean;
  onEscolherSede: (clubeId: string) => void;
  onCriarNovaSede: () => void;
  onSair: () => Promise<void>;
}

/**
 * Tela exibida ao Super Admin (e somente a ele) depois do login: lista todas as
 * sedes existentes para escolher qual administrar nesta sessão, com a opção de
 * criar uma sede nova. Tesoureiros comuns (administradores de uma única sede)
 * nunca veem esta tela — entram direto na própria sede (ver App.tsx).
 */
export function SedeSelectionScreen({
  sedes,
  carregando,
  onEscolherSede,
  onCriarNovaSede,
  onSair,
}: SedeSelectionScreenProps) {
  return (
    <div className="flex min-h-screen flex-col bg-graphite-950">
      <header className="flex items-center justify-between border-b border-graphite-700 px-4 py-3">
        <div className="flex items-center gap-2.5">
          <Skull className="text-ember-500" size={24} strokeWidth={2} />
          <span className="font-display text-base font-bold uppercase tracking-wide text-chrome-50">
            Escolher Sede
          </span>
        </div>
        <button
          type="button"
          onClick={onSair}
          aria-label="Sair"
          className="rounded-sm p-2 text-graphite-400 hover:bg-graphite-800 hover:text-chrome-50"
        >
          <LogOut size={20} />
        </button>
      </header>

      <div className="flex flex-col gap-3 p-4">
        <Button variant="primary" fullWidth icon={<Plus size={16} />} onClick={onCriarNovaSede}>
          Nova Sede
        </Button>

        {carregando ? (
          <p className="py-8 text-center text-sm text-graphite-400">Carregando sedes...</p>
        ) : sedes.length === 0 ? (
          <p className="py-8 text-center text-sm text-graphite-400">
            Nenhuma sede cadastrada ainda. Use o botão acima para criar a primeira.
          </p>
        ) : (
          <ul className="flex flex-col border border-graphite-800">
            {sedes.map((sede) => (
              <li key={sede.id} className="border-b border-graphite-800 last:border-b-0">
                <button
                  type="button"
                  onClick={() => sede.id && onEscolherSede(sede.id)}
                  className="flex w-full items-center justify-between bg-graphite-900 px-4 py-3.5 text-left hover:bg-graphite-800"
                >
                  <span className="font-display text-sm font-semibold uppercase tracking-wide text-chrome-50">
                    {sede.nome}
                  </span>
                  <span className="text-xs text-graphite-400">{sede.id}</span>
                </button>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  );
}
