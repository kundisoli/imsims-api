import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Eye, Truck, X } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Orders', href: '/orders' },
];

interface Order {
  id: string;
  date: string;
  customer: string;
  location: string;
  amount: string;
  status: 'On Delivery' | 'Delivered' | 'Canceled';
}

const sampleOrders: Order[] = [
  { id: '#00002456', date: 'Nov 21 2020 09:21 AM', customer: 'James Silepu', location: 'Corner One St. Manchester Park London', amount: '$2.14', status: 'On Delivery' },
  { id: '#00002457', date: 'Nov 21 2020 09:21 AM', customer: 'Marquez Sibaban', location: 'Center Park, Orange St. London', amount: '$4.31', status: 'Canceled' },
  { id: '#00002458', date: 'Nov 21 2020 09:21 AM', customer: 'James Silepu', location: 'Sweet Mango Residence, Corner St. London', amount: '$4.31', status: 'Delivered' },
];

export default function OrderList() {
  const [statusFilter, setStatusFilter] = useState<'All' | 'On Delivery' | 'Delivered' | 'Canceled'>('All');
  const [viewOrder, setViewOrder] = useState<Order | null>(null);
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [newOrder, setNewOrder] = useState({
    id: '',
    date: '',
    customer: '',
    location: '',
    amount: '',
    status: 'On Delivery' as Order['status'],
  });

  const filteredOrders = statusFilter === 'All' 
    ? sampleOrders 
    : sampleOrders.filter(order => order.status === statusFilter);

  const getStatusClass = (status: string) => {
    switch (status) {
      case 'On Delivery': return 'bg-yellow-100 text-yellow-800';
      case 'Delivered': return 'bg-green-100 text-green-800';
      case 'Canceled': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const handleCreateOrder = () => {
    console.log('New Order Submitted:', newOrder);
    setIsAddModalOpen(false);
    // TODO: send newOrder to backend (Postgres / MongoDB)
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Orders" />
      <div className="p-4 flex flex-col gap-6">

        {/* Filter Header + Add New Order */}
        <Card className="bg-primary/20 border-primary/30 flex flex-col md:flex-row md:items-center md:justify-between">
          <CardHeader>
            <CardTitle>Order List</CardTitle>
            <CardDescription>Filter by Status</CardDescription>
          </CardHeader>
          <CardContent className="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
            {/* Status Filters */}
            <div className="flex flex-wrap gap-2">
              {(['All', 'On Delivery', 'Delivered', 'Canceled'] as const).map(status => (
                <Button
                  key={status}
                  size="sm"
                  variant={statusFilter === status ? "default" : "outline"}
                  onClick={() => setStatusFilter(status)}
                >
                  {status}
                </Button>
              ))}
            </div>

            {/* Add New Order */}
            <div className="mt-2 md:mt-0">
              <Button size="sm" variant="default" onClick={() => setIsAddModalOpen(true)}>
                + New Order
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* Orders Table */}
        <Card className="bg-card border-muted/20">
          <CardHeader>
            <CardTitle>Orders</CardTitle>
            <CardDescription>Showing {filteredOrders.length} of {sampleOrders.length} orders</CardDescription>
          </CardHeader>
          <CardContent className="overflow-x-auto">
            <table className="w-full table-auto text-muted-foreground border-collapse">
              <thead className="border-b border-muted/30">
                <tr>
                  <th className="p-2 text-left">Order ID</th>
                  <th className="p-2 text-left">Date</th>
                  <th className="p-2 text-left">Customer</th>
                  <th className="p-2 text-left">Location</th>
                  <th className="p-2 text-left">Amount</th>
                  <th className="p-2 text-left">Status</th>
                  <th className="p-2 text-left">Action</th>
                </tr>
              </thead>
              <tbody>
                {filteredOrders.map(order => (
                  <tr key={order.id} className="border-b border-muted/20 hover:bg-muted/10">
                    <td className="p-2">{order.id}</td>
                    <td className="p-2">{order.date}</td>
                    <td className="p-2">{order.customer}</td>
                    <td className="p-2 max-w-[250px] truncate">{order.location}</td>
                    <td className="p-2">{order.amount}</td>
                    <td className="p-2">
                      <span className={`inline-block max-w-[90px] truncate text-center px-2 py-1 text-xs rounded-full ${getStatusClass(order.status)}`} title={order.status}>
                        {order.status}
                      </span>
                    </td>
                    <td className="p-2">
                      <Button
                        size="sm"
                        variant="outline"
                        className="px-2 py-1"
                        onClick={() => setViewOrder(order)}
                      >
                        <Eye className="h-4 w-4 mr-1" /> View Details
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </CardContent>
        </Card>

        {/* View Details Modal */}
        <Dialog open={!!viewOrder} onOpenChange={() => setViewOrder(null)}>
          <DialogContent className="sm:max-w-lg">
            <DialogHeader>
              <DialogTitle>Order Details</DialogTitle>
            </DialogHeader>
            {viewOrder && (
              <div className="grid gap-2 py-2 text-sm">
                <p><strong>Order ID:</strong> {viewOrder.id}</p>
                <p><strong>Date:</strong> {viewOrder.date}</p>
                <p><strong>Customer:</strong> {viewOrder.customer}</p>
                <p><strong>Location:</strong> {viewOrder.location}</p>
                <p><strong>Amount:</strong> {viewOrder.amount}</p>
                <p><strong>Status:</strong> {viewOrder.status}</p>
              </div>
            )}
            <DialogFooter className="mt-4 flex justify-end">
              <Button variant="default" onClick={() => setViewOrder(null)}>Close</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        {/* Add New Order Modal */}
        <Dialog open={isAddModalOpen} onOpenChange={setIsAddModalOpen}>
          <DialogContent className="sm:max-w-lg">
            <DialogHeader>
              <DialogTitle>New Order</DialogTitle>
            </DialogHeader>
            <div className="grid gap-4 py-2">
              <Input placeholder="Order ID" value={newOrder.id} onChange={(e) => setNewOrder({...newOrder, id: e.target.value})} />
              <Input placeholder="Date" type="date" value={newOrder.date} onChange={(e) => setNewOrder({...newOrder, date: e.target.value})} />
              <Input placeholder="Customer" value={newOrder.customer} onChange={(e) => setNewOrder({...newOrder, customer: e.target.value})} />
              <Input placeholder="Location" value={newOrder.location} onChange={(e) => setNewOrder({...newOrder, location: e.target.value})} />
              <Input placeholder="Amount" type="number" value={newOrder.amount} onChange={(e) => setNewOrder({...newOrder, amount: e.target.value})} />
              <select value={newOrder.status} onChange={(e) => setNewOrder({...newOrder, status: e.target.value as Order['status']})} className="border rounded px-2 py-1">
                <option>On Delivery</option>
                <option>Delivered</option>
                <option>Canceled</option>
              </select>
            </div>
            <DialogFooter className="mt-4 flex justify-end gap-2">
              <Button variant="outline" onClick={() => setIsAddModalOpen(false)}>Cancel</Button>
              <Button onClick={handleCreateOrder}>Create Order</Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

      </div>
    </AppLayout>
  );
}
