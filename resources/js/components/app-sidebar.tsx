import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, Store, Package, Boxes, Users2, CreditCard, ShoppingCart, Truck, BarChart3, Settings, HelpCircle, LogOut } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
     {
        title: 'Stores',
        href: '/stores',
        icon: Store,
    },
    { title: 'Products', href: '/product', icon: Package },
    { title: 'Category', href: '/category', icon: Boxes },
    { title: 'Suppliers', href: '/suppliers', icon: Users2 },
    { title: 'Billing', href: '/billing', icon: CreditCard },
    { title: 'Orders', href: '/orders', icon: ShoppingCart },
    { title: 'Delivery', href: '/delivery', icon: Truck },
    { title: 'Report', href: '/report', icon: BarChart3 },
    { title: 'Settings', href: '/settings', icon: Settings },
    { title: 'Help', href: '/help', icon: HelpCircle },
    { title: 'Logout', href: '/logout', icon: LogOut, method: 'post' as any },
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/react-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#react',
    //     icon: BookOpen,
    // },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
