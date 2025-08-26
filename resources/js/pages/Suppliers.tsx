import { useState, useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Download } from 'lucide-react';

import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';
import { Bar, Line } from 'react-chartjs-2';

ChartJS.register(CategoryScale, LinearScale, BarElement, PointElement, LineElement, Title, Tooltip, Legend);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Suppliers', href: '/suppliers' },
];

interface Supplier {
  name: string;
  number: string;
  amount: number;
  pending: number;
  status: string;
  tex: string;
}

const initialSuppliers: Supplier[] = [
  { name: 'Supplier A', number: '12345', amount: 50000, pending: 10000, status: 'active', tex: 'included' },
  { name: 'Supplier B', number: '67890', amount: 70000, pending: 30000, status: 'inactive', tex: 'excluded' },
  { name: 'Supplier C', number: '11223', amount: 30000, pending: 5000, status: 'active', tex: 'included' },
];

export default function Suppliers() {
  const [filters, setFilters] = useState({
    buyer: '',
    number: '',
    abc: '',
    supplier: '',
    status: '',
    tex: '',
  });

  const handleFilterChange = (key: string, value: string) => {
    setFilters({ ...filters, [key]: value });
  };

  // Filter suppliers dynamically
  const filteredSuppliers = useMemo(() => {
    return initialSuppliers.filter((s) => {
      return (
        (!filters.supplier || s.name.toLowerCase().includes(filters.supplier.toLowerCase())) &&
        (!filters.number || s.number.includes(filters.number)) &&
        (!filters.status || s.status === filters.status) &&
        (!filters.tex || s.tex === filters.tex)
      );
    });
  }, [filters]);

  // Chart data
  const barChartData = useMemo(() => ({
    labels: filteredSuppliers.map((s) => s.name),
    datasets: [
      {
        label: 'Amount',
        data: filteredSuppliers.map((s) => s.amount),
        backgroundColor: 'rgba(99, 102, 241, 0.7)',
      },
      {
        label: 'Pending',
        data: filteredSuppliers.map((s) => s.pending),
        backgroundColor: 'rgba(16, 185, 129, 0.7)',
      },
    ],
  }), [filteredSuppliers]);

  const lineChartData = useMemo(() => ({
    labels: filteredSuppliers.map((s) => s.name),
    datasets: [
      {
        label: 'Pending Amount',
        data: filteredSuppliers.map((s) => s.pending),
        borderColor: 'rgba(220, 38, 38, 1)',
        backgroundColor: 'rgba(220, 38, 38, 0.2)',
        tension: 0.3,
      },
    ],
  }), [filteredSuppliers]);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Suppliers" />
      <div className="flex flex-col gap-4 p-4">
        {/* Filters Section */}
        <Card>
          <CardHeader>
            <CardTitle>Filters</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-6 gap-4">
              <Input
                placeholder="Supplier"
                value={filters.supplier}
                onChange={(e) => handleFilterChange('supplier', e.target.value)}
              />
              <Input
                placeholder="Number"
                value={filters.number}
                onChange={(e) => handleFilterChange('number', e.target.value)}
              />
              <Select onValueChange={(value) => handleFilterChange('status', value)} value={filters.status}>
                <SelectTrigger><SelectValue placeholder="Status" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="inactive">Inactive</SelectItem>
                </SelectContent>
              </Select>
              <Select onValueChange={(value) => handleFilterChange('tex', value)} value={filters.tex}>
                <SelectTrigger><SelectValue placeholder="Tex" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="included">Included</SelectItem>
                  <SelectItem value="excluded">Excluded</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        {/* Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardContent>
              <CardTitle>{filteredSuppliers.length}</CardTitle>
              <CardDescription>Suppliers</CardDescription>
            </CardContent>
          </Card>
          <Card>
            <CardContent>
              <CardTitle>${filteredSuppliers.reduce((sum, s) => sum + s.amount, 0)}</CardTitle>
              <CardDescription>Total Amount</CardDescription>
            </CardContent>
          </Card>
          <Card>
            <CardContent>
              <CardTitle>${filteredSuppliers.reduce((sum, s) => sum + s.pending, 0)}</CardTitle>
              <CardDescription>Total Pending</CardDescription>
            </CardContent>
          </Card>
        </div>

        {/* Charts Section */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Card>
            <CardHeader>
              <CardTitle>Capital vs Pending</CardTitle>
            </CardHeader>
            <CardContent>
              <Bar data={barChartData} />
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Pending Utilization</CardTitle>
            </CardHeader>
            <CardContent>
              <Line data={lineChartData} />
            </CardContent>
          </Card>
        </div>

        {/* Supplier Details Table */}
        <Card>
          <CardHeader className="flex items-center justify-between">
            <CardTitle>Supplier Details</CardTitle>
            <Button variant="outline" size="sm">
              <Download className="mr-2" /> Download
            </Button>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <table className="w-full table-auto border border-border rounded-lg">
                <thead className="bg-muted">
                  <tr>
                    <th className="p-2 text-left">Supplier Name</th>
                    <th className="p-2 text-left">Number</th>
                    <th className="p-2 text-left">Amount</th>
                    <th className="p-2 text-left">Pending Amount</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredSuppliers.map((s) => (
                    <tr key={s.number} className="border-t border-border">
                      <td className="p-2">{s.name}</td>
                      <td className="p-2">{s.number}</td>
                      <td className="p-2">${s.amount.toLocaleString()}</td>
                      <td className="p-2">${s.pending.toLocaleString()}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
