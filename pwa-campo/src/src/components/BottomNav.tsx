import React, { useEffect, useState } from 'react';
import { Calendar, ClipboardList, FileText, Settings } from 'lucide-react';
import { subscribePendingCount } from '../sync';

export type TabType = 'agenda' | 'vistorias' | 'relatorios' | 'configuracoes';

interface BottomNavProps {
  activeTab: TabType;
  onTabChange: (tab: TabType) => void;
}

export const BottomNav: React.FC<BottomNavProps> = ({ activeTab, onTabChange }) => {
  const [pendingCount, setPendingCount] = useState<number>(0);

  useEffect(() => {
    const unsubscribe = subscribePendingCount(count => setPendingCount(count));
    return () => unsubscribe();
  }, []);

  const navItems = [
    {
      id: 'agenda' as TabType,
      label: 'Agenda',
      icon: Calendar,
    },
    {
      id: 'vistorias' as TabType,
      label: 'Vistorias',
      icon: ClipboardList,
      badge: pendingCount,
    },
    {
      id: 'relatorios' as TabType,
      label: 'Relatórios',
      icon: FileText,
    },
    {
      id: 'configuracoes' as TabType,
      label: 'Ajustes',
      icon: Settings,
    },
  ];

  return (
    <nav className="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-slate-200 shadow-lg pb-[env(safe-area-inset-bottom,0.5rem)] pt-2 px-4">
      <div className="max-w-md mx-auto grid grid-cols-4 gap-1">
        {navItems.map((item) => {
          const Icon = item.icon;
          const isActive = activeTab === item.id;

          return (
            <button
              key={item.id}
              onClick={() => onTabChange(item.id)}
              className={`relative flex flex-col items-center justify-center py-2 px-1 rounded-xl transition active:scale-95 ${
                isActive
                  ? 'text-[#1a365d] font-bold'
                  : 'text-slate-400 hover:text-slate-600 font-medium'
              }`}
            >
              <div className="relative">
                <Icon className={`w-5 h-5 transition-transform ${isActive ? 'scale-110 text-[#1a365d]' : ''}`} />
                {item.badge ? item.badge > 0 ? (
                  <span className="absolute -top-1.5 -right-2.5 min-w-[16px] h-4 px-1 bg-amber-500 text-white font-black text-[9px] rounded-full flex items-center justify-center border border-white ring-2 ring-amber-500/20">
                    {item.badge}
                  </span>
                ) : null : null}
              </div>
              <span className="text-[10px] mt-1 font-bold uppercase tracking-wider">{item.label}</span>
              
              {isActive && (
                <span className="w-1.5 h-1.5 bg-[#1a365d] rounded-full mt-0.5"></span>
              )}
            </button>
          );
        })}
      </div>
    </nav>
  );
};
