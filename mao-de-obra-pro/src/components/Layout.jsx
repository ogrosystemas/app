import { Home, Users, ClipboardList, Settings, Wallet } from 'lucide-react';

export const Layout = ({ children, activeTab, setActiveTab }) => {
  const menuItems = [
    { id: 'dashboard', icon: Home, label: 'Início' },
    { id: 'clientes', icon: Users, label: 'Clientes' },
    { id: 'servicos', icon: ClipboardList, label: 'Catálogo' },
    { id: 'financeiro', icon: Settings, label: 'Ajustes' },
  ];

  return (
    <div className="min-h-screen bg-slate-50 pb-24">
      <header className="bg-blue-600 text-white p-4 shadow-md sticky top-0 z-10">
        <h1 className="text-xl font-bold italic">Mão de Obra PRO</h1>
      </header>
      
      <main className="p-4 max-w-md mx-auto">
        {children}
      </main>

      <nav className="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 flex justify-around p-2 shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">
        {menuItems.map((item) => (
          <button
            key={item.id}
            onClick={() => setActiveTab(item.id)}
            className={`flex flex-col items-center p-2 rounded-lg transition-colors ${
              activeTab === item.id ? 'text-blue-600' : 'text-slate-400'
            }`}
          >
            <item.icon size={24} />
            <span className="text-xs mt-1 font-medium">{item.label}</span>
          </button>
        ))}
      </nav>
    </div>
  );
};