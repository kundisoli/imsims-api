import { useEffect, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Search, Plus, X } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogClose } from '@/components/ui/dialog';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Products', href: '/products' },
];

// Mock products data (replace with API)
const mockProducts = [
  { id: 1, name: 'Premium T-Shirt', category: 'Clothing', quantity: 45, price: 25.99, store: 'Downtown', image: '/images/products/tshirt.jpg' },
  { id: 2, name: 'Designer Jeans', category: 'Clothing', quantity: 23, price: 59.99, store: 'Westside Mall', image: '/images/products/jeans.jpg' },
  { id: 3, name: 'Running Shoes', category: 'Footwear', quantity: 12, price: 89.99, store: 'Downtown', image: '/images/products/shoes.jpg' },
  { id: 4, name: 'Leather Wallet', category: 'Accessories', quantity: 8, price: 39.99, store: 'Airport Terminal', image: '/images/products/wallet.jpg' },
  { id: 5, name: 'Summer Dress', category: 'Clothing', quantity: 3, price: 45.0, store: 'Westside Mall', image: '/images/products/dress.jpg' },
];

export default function Products() {
  const [products, setProducts] = useState<any[]>([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [modalOpen, setModalOpen] = useState(false);

  const [newProduct, setNewProduct] = useState({
    name: '',
    category: '',
    quantity: 0,
    price: 0,
    store: '',
    image: '',
  });

  useEffect(() => {
    setProducts(mockProducts); // Replace with API call
  }, []);

  const filteredProducts = products.filter(
    (p) =>
      p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      p.category.toLowerCase().includes(searchTerm.toLowerCase()) ||
      p.store.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const getStockBadgeColor = (quantity: number) => {
    if (quantity === 0) return 'bg-red-500 text-white';
    if (quantity <= 5) return 'bg-yellow-500 text-white';
    return 'bg-green-500 text-white';
  };

  const handleAddProduct = () => {
    setProducts([...products, { id: products.length + 1, ...newProduct }]);
    setNewProduct({ name: '', category: '', quantity: 0, price: 0, store: '', image: '' });
    setModalOpen(false);
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Products" />

      <div className="flex flex-col gap-6 p-4 bg-card rounded-xl overflow-x-auto">

        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 className="text-2xl font-bold text-foreground">Products Management</h1>
            <p className="text-muted-foreground">Manage all products across your stores</p>
          </div>

          {/* Add Product Button */}
          <Button
            className="flex items-center gap-2 bg-primary/20 border-primary/30 hover:bg-primary/30"
            onClick={() => setModalOpen(true)}
          >
            <Plus className="size-4" /> Add New Product
          </Button>
        </div>

        {/* Products List */}
        <Card className="bg-card border-muted/20">
          <CardHeader className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <CardTitle>All Products</CardTitle>
            <div className="relative mt-2 md:mt-0">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
              <Input
                placeholder="Search products..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10 bg-muted text-foreground border-muted/30 placeholder:text-muted-foreground"
              />
            </div>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {filteredProducts.map((product) => (
                <Card key={product.id} className="bg-primary/10 border-primary/30 hover:bg-primary/20 transition p-3 flex flex-col">
                  <img
                    src={product.image}
                    alt={product.name}
                    className="h-40 w-full object-cover rounded-md mb-3"
                  />
                  <div className="flex flex-col gap-2 flex-1">
                    <div className="font-semibold text-lg text-foreground">{product.name}</div>
                    <div className="text-muted-foreground text-sm">{product.category} • {product.store}</div>
                    <div className="flex items-center justify-between mt-auto">
                      <Badge className={getStockBadgeColor(product.quantity)}>
                        {product.quantity === 0 ? 'Out of Stock' : `${product.quantity} in stock`}
                      </Badge>
                      <span className="font-medium text-foreground">${product.price.toFixed(2)}</span>
                    </div>
                    <Button variant="outline" size="sm" className="mt-2 border-muted/30 text-foreground hover:bg-muted/20">
                      Adjust Stock
                    </Button>
                  </div>
                </Card>
              ))}
              {filteredProducts.length === 0 && (
                <div className="text-center text-muted-foreground col-span-full p-6">No products found.</div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Add Product Modal */}
        <Dialog open={modalOpen} onOpenChange={setModalOpen}>
          <DialogContent className="bg-card border-muted/20 rounded-xl w-full max-w-md p-6">
            <DialogHeader>
              <DialogTitle className="text-foreground">Add New Product</DialogTitle>
              <DialogClose>
                <X className="cursor-pointer text-muted-foreground" />
              </DialogClose>
            </DialogHeader>

            <div className="flex flex-col gap-4 mt-4">
              <Input
                placeholder="Product Name"
                value={newProduct.name}
                onChange={(e) => setNewProduct({ ...newProduct, name: e.target.value })}
                className="bg-muted text-foreground border-muted/30"
              />
              <Input
                placeholder="Category"
                value={newProduct.category}
                onChange={(e) => setNewProduct({ ...newProduct, category: e.target.value })}
                className="bg-muted text-foreground border-muted/30"
              />
              <Input
                placeholder="Quantity"
                type="number"
                value={newProduct.quantity}
                onChange={(e) => setNewProduct({ ...newProduct, quantity: Number(e.target.value) })}
                className="bg-muted text-foreground border-muted/30"
              />
              <Input
                placeholder="Price"
                type="number"
                value={newProduct.price}
                onChange={(e) => setNewProduct({ ...newProduct, price: Number(e.target.value) })}
                className="bg-muted text-foreground border-muted/30"
              />
              <Input
                placeholder="Store"
                value={newProduct.store}
                onChange={(e) => setNewProduct({ ...newProduct, store: e.target.value })}
                className="bg-muted text-foreground border-muted/30"
              />
              <Input
                placeholder="Image URL"
                value={newProduct.image}
                onChange={(e) => setNewProduct({ ...newProduct, image: e.target.value })}
                className="bg-muted text-foreground border-muted/30"
              />

              <Button
                className="bg-primary/20 border-primary/30 hover:bg-primary/30 mt-2"
                onClick={handleAddProduct}
              >
                Add Product
              </Button>
            </div>
          </DialogContent>
        </Dialog>

      </div>
    </AppLayout>
  );
}
