import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Users, Package, Wallet2 } from 'lucide-react';
import { cn } from '@/lib/utils';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

export default function InventoryDashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inventory Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                {/* Header with tabs and search */}
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <Tabs defaultValue="week">
                        <TabsList>
                            <TabsTrigger value="day">Today</TabsTrigger>
                            <TabsTrigger value="week">This Week</TabsTrigger>
                            <TabsTrigger value="month">This Month</TabsTrigger>
                            <TabsTrigger value="year">This Year</TabsTrigger>
                        </TabsList>
                    </Tabs>
                    <div className="w-full md:w-80">
                        <div className="rounded-lg border bg-card px-3 py-2 text-sm text-muted-foreground">
                            Search item, SKU, order...
                        </div>
                    </div>
                </div>

                {/* Top inventory stats */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card className="bg-primary/20 border-primary/30">
                        <CardHeader className="flex-row items-center justify-between">
                            <CardTitle>Total Products</CardTitle>
                            <Package className="size-5 opacity-70" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-semibold">1,250</div>
                            <CardDescription className="mt-1">Products in stock</CardDescription>
                        </CardContent>
                    </Card>
                    <Card className="bg-accent/20 border-accent/30">
                        <CardHeader className="flex-row items-center justify-between">
                            <CardTitle>Low Stock</CardTitle>
                            <Wallet2 className="size-5 opacity-70" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-semibold">120</div>
                            <CardDescription className="mt-1">Items below threshold</CardDescription>
                        </CardContent>
                    </Card>
                    <Card className="bg-sidebar-foreground/10 border-sidebar-foreground/20">
                        <CardHeader className="flex-row items-center justify-between">
                            <CardTitle>Suppliers</CardTitle>
                            <Users className="size-5 opacity-70" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-semibold">25</div>
                            <CardDescription className="mt-1">Active suppliers</CardDescription>
                        </CardContent>
                    </Card>
                </div>

                {/* Middle: Profit by Category and traffic */}
                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="col-span-2">
                        <CardHeader>
                            <CardTitle>Profit by Category</CardTitle>
                            <CardDescription>This Year</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-8">
                                {/* Donut chart placeholder */}
                                <div className="mx-auto h-56 w-56 rounded-full bg-muted p-6">
                                    <div className="relative h-full w-full rounded-full border-[18px] border-primary/70">
                                        <div className="absolute inset-4 rounded-full border-[18px] border-accent/70" />
                                        <div className="absolute inset-12 rounded-full border-[18px] border-sidebar-foreground/20" />
                                        <div className="absolute inset-16 rounded-full bg-card" />
                                    </div>
                                </div>
                                <div className="grid gap-2 text-sm">
                                    <div className="flex items-center gap-2"><span className="size-3 rounded-sm bg-primary" /> Electronics (40%)</div>
                                    <div className="flex items-center gap-2"><span className="size-3 rounded-sm bg-accent" /> Apparel (25%)</div>
                                    <div className="flex items-center gap-2"><span className="size-3 rounded-sm bg-sidebar-foreground/50" /> Home Goods (20%)</div>
                                    <div className="flex items-center gap-2"><span className="size-3 rounded-sm bg-muted-foreground/30" /> Accessories (10%)</div>
                                    <div className="flex items-center gap-2"><span className="size-3 rounded-sm bg-secondary" /> Misc (5%)</div>
                                </div>
                                <div className="ml-auto text-right">
                                    <div className="text-sm text-muted-foreground">Total Annual Profit</div>
                                    <div className="text-2xl font-semibold">$1,200,000</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Traffic Source</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {[ 
                                    { name: 'Online Store', val: 50, color: 'bg-primary' },
                                    { name: 'Marketplace', val: 30, color: 'bg-red-400' },
                                    { name: 'Retail', val: 20, color: 'bg-yellow-300' },
                                ].map((s) => (
                                    <div key={s.name} className="grid grid-cols-3 items-center gap-3 text-sm">
                                        <div className="col-span-1">{s.name}</div>
                                        <div className="col-span-2 flex items-center gap-3">
                                            <div className="h-2 flex-1 rounded-full bg-muted">
                                                <div className={cn("h-2 rounded-full", s.color)} style={{ width: `${s.val}%` }} />
                                            </div>
                                            <div className="w-10 text-right">{s.val}%</div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Bottom: Stock levels and order summary */}
                <div className="grid gap-4 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle>Stock Levels</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3 text-sm">
                                {[
                                    { name: 'Laptop', left: 50, total: 80 },
                                    { name: 'Desk Chair', left: 20, total: 50 },
                                    { name: 'Smartphone', left: 70, total: 100 },
                                    { name: 'Headphones', left: 35, total: 60 },
                                ].map((i) => (
                                    <div key={i.name}>
                                        <div className="mb-1 flex items-center justify-between">
                                            <div>{i.name}</div>
                                            <div className="text-muted-foreground">{i.left} of {i.total} remaining</div>
                                        </div>
                                        <div className="h-2 w-full rounded bg-muted">
                                            <div className="h-2 rounded bg-accent" style={{ width: `${Math.round((i.left / i.total) * 100)}%` }} />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="col-span-2">
                        <CardHeader>
                            <CardTitle>Order Summary</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="relative h-48">
                                <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/10" />
                                <svg viewBox="0 0 400 160" className="absolute inset-0 h-full w-full">
                                    <path d="M0 110 C 60 90, 100 130, 160 110 S 260 70, 320 90 S 380 140, 400 120" className="fill-none stroke-accent" strokeWidth="3" />
                                    <path d="M0 120 C 60 130, 100 80, 160 95 S 260 120, 320 70 S 380 100, 400 85" className="fill-none stroke-primary" strokeWidth="3" />
                                </svg>
                                <div className="absolute right-6 top-6 rounded-full bg-secondary px-3 py-1 text-xs">$45,670 Total Orders</div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
