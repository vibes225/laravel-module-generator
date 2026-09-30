import {
    BarChart3,
    Bell,
    Building2,
    Calendar,
    Circle,
    CreditCard,
    FileText,
    Folder,
    Home,
    Inbox,
    Layers,
    LayoutDashboard,
    Mail,
    Package,
    Settings,
    ShoppingCart,
    Star,
    Tag,
    Users,
} from 'lucide-react';

// Table d'icônes du menu : le champ `icon` d'un module désigne une clé de cette table.
export const icons = {
    'bar-chart': BarChart3,
    bell: Bell,
    building: Building2,
    calendar: Calendar,
    'credit-card': CreditCard,
    'file-text': FileText,
    folder: Folder,
    home: Home,
    inbox: Inbox,
    layers: Layers,
    dashboard: LayoutDashboard,
    mail: Mail,
    package: Package,
    settings: Settings,
    'shopping-cart': ShoppingCart,
    star: Star,
    tag: Tag,
    users: Users,
};

export default function Icon({ name, className = 'h-4 w-4' }) {
    const Component = icons[name] ?? Circle;

    return <Component className={className} aria-hidden="true" />;
}
