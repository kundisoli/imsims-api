import { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppShell from '@/components/app-shell';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { 
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { 
    Plus, 
    Search, 
    Filter,
    Edit,
    Trash2,
    Package,
    AlertTriangle,
    DollarSign,
    Barcode
} from 'lucide-react';
import { productsApi, categoriesApi } from '@/lib/api';
import type { Product, Category, PaginatedResponse } from '@/types/inventory';

export default function ProductsPage() {
    const [products, setProducts] = useState<PaginatedResponse<Product> | null>(null);
    const [categories, setCategories] = useState<Category[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    
    // Filters
    const [search, setSearch] = useState('');
    const [categoryFilter, setCategoryFilter] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [lowStockFilter, setLowStockFilter] = useState(false);

    useEffect(() => {
        fetchCategories();
        fetchProducts();
    }, [search, categoryFilter, statusFilter, lowStockFilter]);

    const fetchProducts = async (page = 1) => {
        try {
            setLoading(true);
            const params: any = { page, per_page: 15 };
            
            if (search) params.search = search;
            if (categoryFilter) params.category_id = categoryFilter;
            if (statusFilter) params.is_active = statusFilter === 'active';
            if (lowStockFilter) params.low_stock = true;

            const response = await productsApi.getAll(params);
            setProducts(response.data);
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to fetch products');
        } finally {
            setLoading(false);
        }
    };

    const fetchCategories = async () => {
        try {
            const response = await categoriesApi.getTree();
            setCategories(response.data);
        } catch (err) {
            console.error('Failed to fetch categories:', err);
        }
    };

    const handleDeleteProduct = async (id: number) => {
        if (!confirm('Are you sure you want to delete this product?')) return;

        try {
            await productsApi.delete(id);
            fetchProducts();
        } catch (err: any) {
            alert(err.response?.data?.message || 'Failed to delete product');
        }
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    };

    const getStockStatus = (product: Product) => {
        const currentStock = product.current_stock || 0;
        
        if (currentStock === 0) {
            return { label: 'Out of Stock', variant: 'destructive' as const };
        } else if (currentStock <= product.reorder_point) {
            return { label: 'Low Stock', variant: 'secondary' as const };
        } else {
            return { label: 'In Stock', variant: 'default' as const };
        }
    };

    return (
        <AppShell>
            <Head title="Products" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Products</h1>
                        <p className="text-muted-foreground">
                            Manage your product catalog and inventory
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/inventory/products/create">
                            <Plus className="h-4 w-4 mr-2" />
                            Add Product
                        </Link>
                    </Button>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search products..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            
                            <Select value={categoryFilter} onValueChange={setCategoryFilter}>
                                <SelectTrigger>
                                    <SelectValue placeholder="All Categories" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All Categories</SelectItem>
                                    {categories.map((category) => (
                                        <SelectItem key={category.id} value={category.id.toString()}>
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select value={statusFilter} onValueChange={setStatusFilter}>
                                <SelectTrigger>
                                    <SelectValue placeholder="All Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All Status</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                </SelectContent>
                            </Select>

                            <Button
                                variant={lowStockFilter ? "default" : "outline"}
                                onClick={() => setLowStockFilter(!lowStockFilter)}
                                className="w-full"
                            >
                                <AlertTriangle className="h-4 w-4 mr-2" />
                                Low Stock Only
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Products Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Products List</CardTitle>
                        <CardDescription>
                            {products?.total || 0} products found
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {loading ? (
                            <div className="flex items-center justify-center h-32">
                                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                            </div>
                        ) : error ? (
                            <div className="text-center py-8">
                                <AlertTriangle className="h-12 w-12 text-destructive mx-auto mb-4" />
                                <p className="text-destructive">{error}</p>
                                <Button onClick={() => fetchProducts()} className="mt-4">
                                    Try Again
                                </Button>
                            </div>
                        ) : (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Product</TableHead>
                                            <TableHead>SKU</TableHead>
                                            <TableHead>Category</TableHead>
                                            <TableHead>Stock</TableHead>
                                            <TableHead>Cost Price</TableHead>
                                            <TableHead>Selling Price</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {products?.data?.map((product) => {
                                            const stockStatus = getStockStatus(product);
                                            return (
                                                <TableRow key={product.id}>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-3">
                                                            <Package className="h-8 w-8 text-muted-foreground bg-muted rounded p-1" />
                                                            <div>
                                                                <p className="font-medium">{product.name}</p>
                                                                {product.description && (
                                                                    <p className="text-sm text-muted-foreground truncate max-w-xs">
                                                                        {product.description}
                                                                    </p>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            <Barcode className="h-4 w-4 text-muted-foreground" />
                                                            <span className="font-mono text-sm">{product.sku}</span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        {product.category?.name || 'No Category'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-2">
                                                            <span className="font-medium">{product.current_stock || 0}</span>
                                                            <Badge variant={stockStatus.variant}>
                                                                {stockStatus.label}
                                                            </Badge>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-1">
                                                            <DollarSign className="h-3 w-3 text-muted-foreground" />
                                                            <span>{formatCurrency(product.cost_price)}</span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center space-x-1">
                                                            <DollarSign className="h-3 w-3 text-muted-foreground" />
                                                            <span>{formatCurrency(product.selling_price)}</span>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant={product.is_active ? "default" : "secondary"}>
                                                            {product.is_active ? 'Active' : 'Inactive'}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="flex items-center justify-end space-x-2">
                                                            <Button variant="ghost" size="sm" asChild>
                                                                <Link href={`/inventory/products/${product.id}/edit`}>
                                                                    <Edit className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleDeleteProduct(product.id)}
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>

                                {/* Pagination */}
                                {products && products.last_page > 1 && (
                                    <div className="flex items-center justify-between px-2 py-4">
                                        <div className="text-sm text-muted-foreground">
                                            Showing {products.from} to {products.to} of {products.total} products
                                        </div>
                                        <div className="flex items-center space-x-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => fetchProducts(products.current_page - 1)}
                                                disabled={products.current_page === 1}
                                            >
                                                Previous
                                            </Button>
                                            <div className="flex items-center space-x-1">
                                                {Array.from({ length: Math.min(5, products.last_page) }, (_, i) => {
                                                    const page = i + 1;
                                                    return (
                                                        <Button
                                                            key={page}
                                                            variant={products.current_page === page ? "default" : "outline"}
                                                            size="sm"
                                                            onClick={() => fetchProducts(page)}
                                                        >
                                                            {page}
                                                        </Button>
                                                    );
                                                })}
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => fetchProducts(products.current_page + 1)}
                                                disabled={products.current_page === products.last_page}
                                            >
                                                Next
                                            </Button>
                                        </div>
                                    </div>
                                )}

                                {(!products?.data || products.data.length === 0) && (
                                    <div className="text-center py-8">
                                        <Package className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                                        <p className="text-lg font-semibold">No products found</p>
                                        <p className="text-muted-foreground mb-4">
                                            Get started by adding your first product
                                        </p>
                                        <Button asChild>
                                            <Link href="/inventory/products/create">
                                                <Plus className="h-4 w-4 mr-2" />
                                                Add Product
                                            </Link>
                                        </Button>
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppShell>
    );
}