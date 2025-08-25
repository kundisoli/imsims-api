import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { categoriesApi } from '@/lib/api';
import type { Product, Category } from '@/types/inventory';

interface ProductFormProps {
    product?: Product;
    onSubmit: (data: Partial<Product>) => void;
    loading?: boolean;
}

export default function ProductForm({ product, onSubmit, loading = false }: ProductFormProps) {
    const [categories, setCategories] = useState<Category[]>([]);
    const [formData, setFormData] = useState<Partial<Product>>({
        name: '',
        description: '',
        sku: '',
        barcode: '',
        category_id: undefined,
        supplier_id: undefined,
        cost_price: 0,
        selling_price: 0,
        min_stock_level: 0,
        max_stock_level: 0,
        reorder_point: 0,
        unit_of_measure: 'piece',
        weight: undefined,
        is_active: true,
        is_trackable: true,
        ...product
    });

    useEffect(() => {
        fetchCategories();
    }, []);

    const fetchCategories = async () => {
        try {
            const response = await categoriesApi.getTree();
            setCategories(response.data);
        } catch (err) {
            console.error('Failed to fetch categories:', err);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        onSubmit(formData);
    };

    const handleChange = (field: keyof Product, value: any) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
    };

    const flattenCategories = (categories: Category[], level = 0): Array<Category & { level: number }> => {
        let flattened: Array<Category & { level: number }> = [];
        
        categories.forEach(category => {
            flattened.push({ ...category, level });
            if (category.children && category.children.length > 0) {
                flattened = flattened.concat(flattenCategories(category.children, level + 1));
            }
        });
        
        return flattened;
    };

    const flatCategories = flattenCategories(categories);

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="grid gap-6 md:grid-cols-2">
                {/* Basic Information */}
                <Card>
                    <CardHeader>
                        <CardTitle>Basic Information</CardTitle>
                        <CardDescription>
                            Essential product details
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <Label htmlFor="name">Product Name *</Label>
                            <Input
                                id="name"
                                value={formData.name}
                                onChange={(e) => handleChange('name', e.target.value)}
                                placeholder="Enter product name"
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                value={formData.description}
                                onChange={(e) => handleChange('description', e.target.value)}
                                placeholder="Enter product description"
                                rows={3}
                            />
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="sku">SKU *</Label>
                                <Input
                                    id="sku"
                                    value={formData.sku}
                                    onChange={(e) => handleChange('sku', e.target.value)}
                                    placeholder="Product SKU"
                                    required
                                />
                            </div>

                            <div>
                                <Label htmlFor="barcode">Barcode</Label>
                                <Input
                                    id="barcode"
                                    value={formData.barcode}
                                    onChange={(e) => handleChange('barcode', e.target.value)}
                                    placeholder="Product barcode"
                                />
                            </div>
                        </div>

                        <div>
                            <Label htmlFor="category">Category</Label>
                            <Select
                                value={formData.category_id?.toString()}
                                onValueChange={(value) => handleChange('category_id', parseInt(value))}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">No Category</SelectItem>
                                    {flatCategories.map((category) => (
                                        <SelectItem key={category.id} value={category.id.toString()}>
                                            {'—'.repeat(category.level)} {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                {/* Pricing & Inventory */}
                <Card>
                    <CardHeader>
                        <CardTitle>Pricing & Inventory</CardTitle>
                        <CardDescription>
                            Pricing and stock management settings
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="cost_price">Cost Price *</Label>
                                <Input
                                    id="cost_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={formData.cost_price}
                                    onChange={(e) => handleChange('cost_price', parseFloat(e.target.value) || 0)}
                                    placeholder="0.00"
                                    required
                                />
                            </div>

                            <div>
                                <Label htmlFor="selling_price">Selling Price *</Label>
                                <Input
                                    id="selling_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={formData.selling_price}
                                    onChange={(e) => handleChange('selling_price', parseFloat(e.target.value) || 0)}
                                    placeholder="0.00"
                                    required
                                />
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-3">
                            <div>
                                <Label htmlFor="min_stock_level">Min Stock Level</Label>
                                <Input
                                    id="min_stock_level"
                                    type="number"
                                    min="0"
                                    value={formData.min_stock_level}
                                    onChange={(e) => handleChange('min_stock_level', parseInt(e.target.value) || 0)}
                                    placeholder="0"
                                />
                            </div>

                            <div>
                                <Label htmlFor="max_stock_level">Max Stock Level</Label>
                                <Input
                                    id="max_stock_level"
                                    type="number"
                                    min="0"
                                    value={formData.max_stock_level}
                                    onChange={(e) => handleChange('max_stock_level', parseInt(e.target.value) || 0)}
                                    placeholder="0"
                                />
                            </div>

                            <div>
                                <Label htmlFor="reorder_point">Reorder Point</Label>
                                <Input
                                    id="reorder_point"
                                    type="number"
                                    min="0"
                                    value={formData.reorder_point}
                                    onChange={(e) => handleChange('reorder_point', parseInt(e.target.value) || 0)}
                                    placeholder="0"
                                />
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label htmlFor="unit_of_measure">Unit of Measure</Label>
                                <Select
                                    value={formData.unit_of_measure}
                                    onValueChange={(value) => handleChange('unit_of_measure', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="piece">Piece</SelectItem>
                                        <SelectItem value="box">Box</SelectItem>
                                        <SelectItem value="pack">Pack</SelectItem>
                                        <SelectItem value="kg">Kilogram</SelectItem>
                                        <SelectItem value="g">Gram</SelectItem>
                                        <SelectItem value="lb">Pound</SelectItem>
                                        <SelectItem value="oz">Ounce</SelectItem>
                                        <SelectItem value="l">Liter</SelectItem>
                                        <SelectItem value="ml">Milliliter</SelectItem>
                                        <SelectItem value="m">Meter</SelectItem>
                                        <SelectItem value="cm">Centimeter</SelectItem>
                                        <SelectItem value="ft">Foot</SelectItem>
                                        <SelectItem value="in">Inch</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label htmlFor="weight">Weight (kg)</Label>
                                <Input
                                    id="weight"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={formData.weight || ''}
                                    onChange={(e) => handleChange('weight', parseFloat(e.target.value) || undefined)}
                                    placeholder="0.00"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Settings */}
            <Card>
                <CardHeader>
                    <CardTitle>Settings</CardTitle>
                    <CardDescription>
                        Product status and tracking settings
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="flex items-center justify-between">
                            <div className="space-y-0.5">
                                <Label htmlFor="is_active">Active Product</Label>
                                <p className="text-sm text-muted-foreground">
                                    Whether this product is active and available
                                </p>
                            </div>
                            <Switch
                                id="is_active"
                                checked={formData.is_active}
                                onCheckedChange={(checked) => handleChange('is_active', checked)}
                            />
                        </div>

                        <div className="flex items-center justify-between">
                            <div className="space-y-0.5">
                                <Label htmlFor="is_trackable">Track Inventory</Label>
                                <p className="text-sm text-muted-foreground">
                                    Whether to track inventory levels for this product
                                </p>
                            </div>
                            <Switch
                                id="is_trackable"
                                checked={formData.is_trackable}
                                onCheckedChange={(checked) => handleChange('is_trackable', checked)}
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Form Actions */}
            <div className="flex items-center justify-end space-x-4 pt-6 border-t">
                <Button type="button" variant="outline">
                    Cancel
                </Button>
                <Button type="submit" disabled={loading}>
                    {loading ? 'Saving...' : product ? 'Update Product' : 'Create Product'}
                </Button>
            </div>
        </form>
    );
}