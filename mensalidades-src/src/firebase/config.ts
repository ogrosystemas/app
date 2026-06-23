import { initializeApp } from "firebase/app";
import { getAuth, GoogleAuthProvider } from "firebase/auth";
import {
  initializeFirestore,
  persistentLocalCache,
  persistentMultipleTabManager,
} from "firebase/firestore";

/**
 * Configuração do projeto Firebase (Mutantes Moto Clube).
 *
 * Estas chaves são PÚBLICAS por design — o apiKey do Firebase não é um segredo,
 * ele identifica o projeto, não autoriza acesso por si só. A segurança real dos
 * dados vem das regras do Firestore (ver firestore.rules na raiz do projeto),
 * que checam se o e-mail autenticado está na lista de pessoas autorizadas antes
 * de liberar qualquer leitura/escrita.
 *
 * Os valores vêm de variáveis de ambiente (prefixo VITE_, exigido pelo Vite para
 * expor a variável ao código do navegador) para facilitar trocar de projeto
 * Firebase no futuro sem precisar editar código — ver .env.example.
 */
const firebaseConfig = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
  storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
  appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

export const firebaseApp = initializeApp(firebaseConfig);

/**
 * Instância do Firestore com cache local persistente (IndexedDB) ativado.
 *
 * persistentLocalCache + persistentMultipleTabManager: o app funciona 100% offline
 * (lê e escreve no cache local imediatamente) e sincroniza automaticamente com o
 * servidor quando há conexão — inclusive lidando corretamente com múltiplas abas
 * abertas ao mesmo tempo. Esta é a API atual recomendada pelo Firebase (substitui
 * o antigo enableIndexedDbPersistence, que está em descontinuação).
 */
export const db = initializeFirestore(firebaseApp, {
  localCache: persistentLocalCache({
    tabManager: persistentMultipleTabManager(),
  }),
});

export const auth = getAuth(firebaseApp);
export const googleProvider = new GoogleAuthProvider();
