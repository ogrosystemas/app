import { Chrome, Skull } from "lucide-react";
import { Button } from "../ui/Button";

interface LoginScreenProps {
  onEntrar: () => Promise<void>;
  erro: string | null;
  entrando: boolean;
}

/**
 * Tela exibida quando ninguém está autenticado. Bloqueia o acesso ao restante
 * do app até o login ser concluído — os dados do clube vivem na nuvem e exigem
 * uma conta autorizada (ver firestore.rules) para serem lidos ou alterados.
 */
export function LoginScreen({ onEntrar, erro, entrando }: LoginScreenProps) {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-graphite-950 px-6">
      <div className="flex w-full max-w-sm flex-col items-center gap-6 text-center">
        <Skull className="text-ember-500" size={56} strokeWidth={1.5} />

        <div className="flex flex-col gap-1.5">
          <h1 className="font-display text-xl font-bold uppercase tracking-wide text-chrome-50">
            Mutantes Moto Clube
          </h1>
          <p className="text-sm text-graphite-400">Conferência de Mensalidades</p>
        </div>

        {erro && (
          <p className="w-full border border-alert-600 bg-alert-950 px-3 py-2 text-sm text-alert-400">
            {erro}
          </p>
        )}

        <Button
          variant="primary"
          fullWidth
          size="lg"
          icon={<Chrome size={18} />}
          onClick={onEntrar}
          disabled={entrando}
        >
          {entrando ? "Entrando..." : "Entrar com Google"}
        </Button>

        <p className="text-xs text-graphite-400">
          Apenas contas autorizadas pelo clube têm acesso aos dados.
        </p>
      </div>
    </div>
  );
}
