import { ShieldAlert } from "lucide-react";
import { Button } from "../ui/Button";

interface AccessDeniedScreenProps {
  email: string | null;
  onSair: () => Promise<void>;
}

/**
 * Tela exibida quando o login com Google funciona, mas o e-mail autenticado
 * NÃO está na lista de pessoas autorizadas pelas regras de segurança do
 * Firestore (ver firestore.rules). Login e autorização são coisas diferentes:
 * qualquer conta Google consegue logar, mas só e-mails explicitamente
 * adicionados à lista conseguem de fato ler/escrever os dados do clube.
 */
export function AccessDeniedScreen({ email, onSair }: AccessDeniedScreenProps) {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-graphite-950 px-6">
      <div className="flex w-full max-w-sm flex-col items-center gap-5 text-center">
        <ShieldAlert className="text-alert-500" size={48} strokeWidth={1.5} />

        <div className="flex flex-col gap-1.5">
          <h1 className="font-display text-lg font-bold uppercase tracking-wide text-chrome-50">
            Acesso não autorizado
          </h1>
          <p className="text-sm text-graphite-400">
            {email ? (
              <>
                A conta <span className="text-chrome-50">{email}</span> ainda não tem
                permissão para acessar os dados deste clube.
              </>
            ) : (
              "Esta conta ainda não tem permissão para acessar os dados deste clube."
            )}
          </p>
        </div>

        <p className="text-xs text-graphite-400">
          Peça para um administrador adicionar seu e-mail à lista de pessoas autorizadas.
        </p>

        <Button variant="secondary" fullWidth onClick={onSair}>
          Tentar com outra conta
        </Button>
      </div>
    </div>
  );
}
