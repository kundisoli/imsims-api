import { useEffect, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Search, MapPin, Phone, Mail, User, Package, TrendingUp, AlertTriangle, Plus, ArrowRight, Store } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Stores', href: '/stores' },
];

// Optional: Mock inventory and transfers (replace with API data if needed)
const mockInventory = [
  { id: 1, name: 'Premium T-Shirt', quantity: 45, location: 'Aisle 3, Shelf B', category: 'Clothing' },
  { id: 2, name: 'Designer Jeans', quantity: 23, location: 'Aisle 3, Shelf C', category: 'Clothing' },
  { id: 3, name: 'Running Shoes', quantity: 12, location: 'Aisle 4, Shelf A', category: 'Footwear' },
  { id: 4, name: 'Leather Wallet', quantity: 8, location: 'Aisle 2, Shelf D', category: 'Accessories' },
  { id: 5, name: 'Summer Dress', quantity: 3, location: 'Aisle 3, Shelf A', category: 'Clothing' },
];

const mockTransfers = [
  { id: 1, from: 'Downtown Flagship Store', to: 'Westside Mall Location', status: 'in_transit', items: 15, date: '2025-01-15' },
  { id: 2, from: 'Westside Mall Location', to: 'Downtown Flagship Store', status: 'received', items: 8, date: '2025-01-10' },
  { id: 3, from: 'Downtown Flagship Store', to: 'Airport Terminal Store', status: 'cancelled', items: 12, date: '2025-01-08' },
];

export default function Stores() {
  const [stores, setStores] = useState<any[]>([]);
  const [selectedStore, setSelectedStore] = useState<any | null>(null);
  const [activeTab, setActiveTab] = useState('overview');
  const [searchTerm, setSearchTerm] = useState('');

  // Fetch stores dynamically
  useEffect(() => {
    fetch('/stores-data')
      .then((res) => res.json())
      .then((data) => {
        setStores(data);
        if (data.length > 0) {
          setSelectedStore(data[0]);
        }
      })
      .catch((err) => console.error('Error fetching stores:', err));
  }, []);

  const filteredStores = stores.filter(
    (store) =>
      store.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      store.address?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      store.manager?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'active': return 'bg-green-100 text-green-800';
      case 'inactive': return 'bg-red-100 text-red-800';
      case 'maintenance': return 'bg-yellow-100 text-yellow-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getTransferStatusColor = (status: string) => {
    switch (status) {
      case 'in_transit': return 'bg-blue-100 text-blue-800';
      case 'received': return 'bg-green-100 text-green-800';
      case 'cancelled': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  if (!selectedStore) {
    return (
      <AppLayout breadcrumbs={breadcrumbs}>
        <Head title="Stores" />
        <div className="p-6 text-center text-muted-foreground">Loading stores...</div>
      </AppLayout>
    );
  }

  // Safely access numeric fields with defaults
  const totalProducts = selectedStore.totalProducts ?? 0;
  const lowStockItems = selectedStore.lowStockItems ?? 0;
  const outOfStockItems = selectedStore.outOfStockItems ?? 0;
  const monthlyRevenue = selectedStore.monthlyRevenue ?? 0;

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Stores" />
      <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
        {/* Header */}
        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 className="text-2xl font-bold">Stores Management</h1>
            <p className="text-muted-foreground">Manage all your business locations and inventory</p>
          </div>
          <Button className="flex items-center gap-2">
            <Plus className="size-4" /> Add New Store
          </Button>
        </div>

        <div className="grid gap-4 lg:grid-cols-4">
          {/* Stores List */}
          <Card className="lg:col-span-1">
            <CardHeader>
              <CardTitle>All Stores</CardTitle>
              <div className="relative">
                <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  placeholder="Search stores..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-9"
                />
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                {filteredStores.map((store) => (
                  <div
                    key={store.id}
                    onClick={() => setSelectedStore(store)}
                    className={`p-3 rounded-lg border cursor-pointer transition-colors ${
                      selectedStore.id === store.id ? 'border-primary bg-primary/5' : 'border-border hover:bg-muted/50'
                    }`}
                  >
                    <div className="flex items-start gap-3">
                      <div className="mt-1">
                        <Store className="size-4 text-muted-foreground" />
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="font-medium truncate">{store.name}</div>
                        <div className="text-sm text-muted-foreground truncate">{store.address}</div>
                        <div className="flex items-center gap-2 mt-2">
                          <Badge className={getStatusColor(store.status)}>
                            {store.status?.charAt(0).toUpperCase() + store.status?.slice(1)}
                          </Badge>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Store Details */}
          <Card className="lg:col-span-3">
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>{selectedStore.name}</CardTitle>
                  <CardDescription>{selectedStore.address}</CardDescription>
                </div>
                <Badge className={getStatusColor(selectedStore.status)}>
                  {selectedStore.status?.charAt(0).toUpperCase() + selectedStore.status?.slice(1)}
                </Badge>
              </div>
            </CardHeader>
            <CardContent>
              <Tabs value={activeTab} onValueChange={setActiveTab}>
                <TabsList className="grid w-full grid-cols-4">
                  <TabsTrigger value="overview">Overview</TabsTrigger>
                  <TabsTrigger value="inventory">Inventory</TabsTrigger>
                  <TabsTrigger value="transfers">Transfers</TabsTrigger>
                  <TabsTrigger value="reports">Reports</TabsTrigger>
                </TabsList>

                <TabsContent value="overview" className="space-y-6">
                  {/* Store Stats */}
                  <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                      <CardContent className="p-4">
                        <div className="flex items-center gap-2">
                          <Package className="size-5 text-primary" />
                          <div>
                            <div className="text-2xl font-bold">{totalProducts}</div>
                            <div className="text-sm text-muted-foreground">Total Products</div>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                    <Card>
                      <CardContent className="p-4">
                        <div className="flex items-center gap-2">
                          <AlertTriangle className="size-5 text-yellow-600" />
                          <div>
                            <div className="text-2xl font-bold">{lowStockItems}</div>
                            <div className="text-sm text-muted-foreground">Low Stock</div>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                    <Card>
                      <CardContent className="p-4">
                        <div className="flex items-center gap-2">
                          <AlertTriangle className="size-5 text-red-600" />
                          <div>
                            <div className="text-2xl font-bold">{outOfStockItems}</div>
                            <div className="text-sm text-muted-foreground">Out of Stock</div>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                    <Card>
                      <CardContent className="p-4">
                        <div className="flex items-center gap-2">
                          <TrendingUp className="size-5 text-green-600" />
                          <div>
                            <div className="text-2xl font-bold">${monthlyRevenue.toLocaleString()}</div>
                            <div className="text-sm text-muted-foreground">Monthly Revenue</div>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  </div>

                  {/* Contact Information */}
                  <Card>
                    <CardHeader>
                      <CardTitle>Contact Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                      <div className="grid gap-4 md:grid-cols-2">
                        <div className="flex items-center gap-3">
                          <User className="size-5 text-muted-foreground" />
                          <div>
                            <div className="font-medium">Store Manager</div>
                            <div className="text-sm text-muted-foreground">{selectedStore.manager}</div>
                          </div>
                        </div>
                        <div className="flex items-center gap-3">
                          <Phone className="size-5 text-muted-foreground" />
                          <div>
                            <div className="font-medium">Phone</div>
                            <div className="text-sm text-muted-foreground">{selectedStore.phone}</div>
                          </div>
                        </div>
                        <div className="flex items-center gap-3">
                          <Mail className="size-5 text-muted-foreground" />
                          <div>
                            <div className="font-medium">Email</div>
                            <div className="text-sm text-muted-foreground">{selectedStore.email}</div>
                          </div>
                        </div>
                        <div className="flex items-center gap-3">
                          <MapPin className="size-5 text-muted-foreground" />
                          <div>
                            <div className="font-medium">Address</div>
                            <div className="text-sm text-muted-foreground">{selectedStore.address}</div>
                          </div>
                        </div>
                      </div>
                    </CardContent>
                  </Card>

                  {/* Notes */}
                  <Card>
                    <CardHeader>
                      <CardTitle>Notes</CardTitle>
                    </CardHeader>
                    <CardContent>
                      <p className="text-muted-foreground">{selectedStore.notes}</p>
                    </CardContent>
                  </Card>
                </TabsContent>

                {/* Inventory, Transfers, Reports Tabs */}
                <TabsContent value="inventory" className="space-y-4">
                  <div className="flex items-center justify-between">
                    <h3 className="text-lg font-semibold">Current Inventory</h3>
                    <Button variant="outline" size="sm">Export Inventory</Button>
                  </div>
                  <div className="space-y-3">
                    {mockInventory.map((item) => (
                      <div key={item.id} className="flex items-center justify-between p-3 border rounded-lg">
                        <div className="flex-1">
                          <div className="font-medium">{item.name}</div>
                          <div className="text-sm text-muted-foreground">{item.category} • {item.location}</div>
                        </div>
                        <div className="flex items-center gap-4">
                          <div className="text-right">
                            <div className="font-medium">{item.quantity}</div>
                            <div className="text-sm text-muted-foreground">in stock</div>
                          </div>
                          <Button variant="outline" size="sm">Adjust Stock</Button>
                        </div>
                      </div>
                    ))}
                  </div>
                </TabsContent>

                <TabsContent value="transfers" className="space-y-4">
                  <div className="flex items-center justify-between">
                    <h3 className="text-lg font-semibold">Transfer Management</h3>
                    <Button><Plus className="size-4 mr-2" /> Create Transfer</Button>
                  </div>
                  <div className="space-y-3">
                    {mockTransfers.map((transfer) => (
                      <div key={transfer.id} className="flex items-center justify-between p-3 border rounded-lg">
                        <div className="flex-1">
                          <div className="font-medium">Transfer #{transfer.id}</div>
                          <div className="text-sm text-muted-foreground">{transfer.from} <ArrowRight className="inline size-4" /> {transfer.to}</div>
                          <div className="text-sm text-muted-foreground">{transfer.items} items • {transfer.date}</div>
                        </div>
                        <div className="flex items-center gap-2">
                          <Badge className={getTransferStatusColor(transfer.status)}>
                            {transfer.status.replace('_', ' ').charAt(0).toUpperCase() + transfer.status.slice(1).replace('_', ' ')}
                          </Badge>
                          <Button variant="outline" size="sm">View Details</Button>
                        </div>
                      </div>
                    ))}
                  </div>
                </TabsContent>

                <TabsContent value="reports" className="space-y-4">
                  <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                      <CardHeader>
                        <CardTitle>Sales Performance</CardTitle>
                        <CardDescription>Last 30 days</CardDescription>
                      </CardHeader>
                      <CardContent>
                        <div className="space-y-3">
                          <div className="flex justify-between"><span>Total Sales</span><span className="font-medium">${monthlyRevenue.toLocaleString()}</span></div>
                          <div className="flex justify-between"><span>Orders</span><span className="font-medium">1,247</span></div>
                          <div className="flex justify-between"><span>Average Order Value</span><span className="font-medium">$36.12</span></div>
                        </div>
                      </CardContent>
                    </Card>
                    <Card>
                      <CardHeader>
                        <CardTitle>Inventory Turnover</CardTitle>
                        <CardDescription>This month</CardDescription>
                      </CardHeader>
                      <CardContent>
                        <div className="space-y-3">
                          <div className="flex justify-between"><span>Turnover Rate</span><span className="font-medium">2.4x</span></div>
                          <div className="flex justify-between"><span>Days to Sell</span><span className="font-medium">12.5</span></div>
                          <div className="flex justify-between"><span>Stock Efficiency</span><span className="font-medium">85%</span></div>
                        </div>
                      </CardContent>
                    </Card>
                  </div>
                </TabsContent>
              </Tabs>
            </CardContent>
          </Card>
        </div>
      </div>
    </AppLayout>
  );
}
