import { CalendarDays, ClipboardList, FileText, Settings } from 'lucide-react'

const items = [
  { id: 'agenda', label: 'Agenda', icon: CalendarDays },
  { id: 'inspections', label: 'Vistorias', icon: ClipboardList },
  { id: 'reports', label: 'Relatórios', icon: FileText },
  { id: 'settings', label: 'Ajustes', icon: Settings },
]

export function BottomNav({ active, onNavigate }) {
  return <nav className="bottom-nav" aria-label="Navegação principal">
    {items.map(item => {
      const Icon = item.icon
      return <button key={item.id} className={active === item.id ? 'active' : ''} aria-current={active === item.id ? 'page' : undefined} onClick={() => onNavigate(item.id)}>
        <Icon /><span>{item.label}</span>
      </button>
    })}
  </nav>
}
