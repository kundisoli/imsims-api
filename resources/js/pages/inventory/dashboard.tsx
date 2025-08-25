import { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AppShell from '@/components/app-shell';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
    Package, 
    TrendingDown, 
    TrendingUp, 
    Warehouse, 
    AlertTriangle,
    DollarSign,
    ShoppingCart,
    BarChart3
} from 'lucide-react';
import { inventoryApi } from '@/lib/api';
import type { InventoryOverview } from '@/types/inventory';

export default function InventoryDashboard() {
    const [overview, setOverview] = useState<InventoryOverview | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        fetchOverview();
    }, []);

    const fetchOverview = async () => {
        try {
            setLoading(true);
            const response = await inventoryApi.getOverview();
            setOverview(response.data);
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to fetch inventory overview');
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <AppShell>
                <Head title="Inventory Dashboard" />
                <div className="flex items-center justify-center h-64">
                    <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-primary"></div>
                </div>
            </AppShell>
        );
    }

    if (error) {
        return (
            <AppShell>
                <Head title="Inventory Dashboard" />
                <div className="flex items-center justify-center h-64">
                    <div className="text-center">
                        <AlertTriangle className="h-16 w-16 text-destructive mx-auto mb-4" />
                        <p className="text-lg font-semibold text-destructive">{error}</p>
                        <Button onClick={fetchOverview} className="mt-4">
                            Try Again
                        </Button>
                    </div>
                </div>
            </AppShell>
        );
    }

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    };

    const formatNumber = (number: number) => {
        return new Intl.NumberFormat('en-US').format(number);
    };

    return (
        <AppShell>
            <Head title="Inventory Dashboard" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Inventory Dashboard</h1>
                        <p className="text-muted-foreground">
                            Overview of your inventory management system
                        </p>
                    </div>
                    <Button onClick={fetchOverview}>
                        <BarChart3 className="h-4 w-4 mr-2" />
                        Refresh Data
                    </Button>
                </div>

                {/* Key Metrics */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Products</CardTitle>
                            <Package className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {formatNumber(overview?.total_products || 0)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Active products in inventory
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Value</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {formatCurrency(overview?.total_inventory_value || 0)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Total inventory value at cost
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Low Stock Items</CardTitle>
                            <TrendingDown className="h-4 w-4 text-orange-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-orange-500">
                                {formatNumber(overview?.low_stock_products || 0)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Products below reorder point
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Out of Stock</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-destructive" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-destructive">
                                {formatNumber(overview?.out_of_stock_products || 0)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Products with zero stock
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Charts and Lists */}
                <div className="grid gap-6 md:grid-cols-2">
                    {/* Recent Movements */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Stock Movements</CardTitle>
                            <CardDescription>
                                Latest 10 inventory movements
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {overview?.recent_movements?.map((movement) => (
                                    <div key={movement.id} className="flex items-center justify-between border-b pb-2">
                                        <div className="flex items-center space-x-3">
                                            <div className={`w-2 h-2 rounded-full ${
                                                movement.type === 'in' ? 'bg-green-500' : 'bg-red-500'
                                            }`} />
                                            <div>
                                                <p className="font-medium text-sm">
                                                    {movement.product?.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {movement.warehouse?.name}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <p className={`font-medium text-sm ${
                                                movement.type === 'in' ? 'text-green-600' : 'text-red-600'
                                            }`}>
                                                {movement.type === 'in' ? '+' : '-'}{movement.quantity}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {new Date(movement.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                                {(!overview?.recent_movements || overview.recent_movements.length === 0) && (
                                    <p className="text-center text-muted-foreground py-4">
                                        No recent movements
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Top Products by Value */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Top Products by Value</CardTitle>
                            <CardDescription>
                                Highest value products in inventory
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {overview?.top_products_by_value?.map((product, index) => (
                                    <div key={index} className="flex items-center justify-between border-b pb-2">
                                        <div className="flex items-center space-x-3">
                                            <Badge variant="outline" className="w-6 h-6 rounded-full p-0 flex items-center justify-center">
                                                {index + 1}
                                            </Badge>
                                            <div>
                                                <p className="font-medium text-sm">
                                                    {product.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    SKU: {product.sku}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-medium text-sm">
                                                {formatCurrency(product.total_value)}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                                {(!overview?.top_products_by_value || overview.top_products_by_value.length === 0) && (
                                    <p className="text-center text-muted-foreground py-4">
                                        No products found
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Warehouse Summary */}
                <Card>
                    <CardHeader>
                        <CardTitle>Warehouse Summary</CardTitle>
                        <CardDescription>
                            Overview of inventory across all warehouses
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {overview?.warehouse_summary?.map((warehouse) => (
                                <Card key={warehouse.id} className="p-4">
                                    <div className="flex items-center space-x-3 mb-3">
                                        <Warehouse className="h-5 w-5 text-muted-foreground" />
                                        <h3 className="font-semibold">{warehouse.name}</h3>
                                    </div>
                                    <div className="space-y-2 text-sm">
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Products:</span>
                                            <span className="font-medium">{formatNumber(warehouse.product_count)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Quantity:</span>
                                            <span className="font-medium">{formatNumber(warehouse.total_quantity)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-muted-foreground">Value:</span>
                                            <span className="font-medium">{formatCurrency(warehouse.total_value)}</span>
                                        </div>
                                    </div>
                                </Card>
                            ))}
                            {(!overview?.warehouse_summary || overview.warehouse_summary.length === 0) && (
                                <p className="text-center text-muted-foreground py-4 col-span-full">
                                    No warehouses found
                                </p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
}