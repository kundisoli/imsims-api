import AppLayout from '@/layouts/app-layout'; 
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Download, BarChart, Package, AlertCircle } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Reports', href: '/reports' },
];

export default function ReportsPage() {
  const reports = [
    {
      title: 'Stock Levels',
      description: 'Current stock levels of all products',
      icon: <Package className="h-5 w-5 mr-2" />,
      action: () => console.log('Download Stock Levels Report')
    },
    {
      title: 'Sales Report',
      description: 'Total sales and revenue',
      icon: <BarChart className="h-5 w-5 mr-2" />,
      action: () => console.log('Download Sales Report')
    },
    {
      title: 'Purchases Report',
      description: 'All purchases made from suppliers',
      icon: <Download className="h-5 w-5 mr-2" />,
      action: () => console.log('Download Purchases Report')
    },
    {
      title: 'Low Stock Alerts',
      description: 'Products that are low on stock',
      icon: <AlertCircle className="h-5 w-5 mr-2" />,
      action: () => console.log('Download Low Stock Alerts Report')
    },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Reports" />

      {/* Wrap content in a div and apply dashboard colors here */}
      <div className="bg-card text-foreground min-h-screen p-6 flex flex-col gap-6 rounded-xl">

        <h2 className="text-2xl font-semibold mb-4">Inventory Reports</h2>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {reports.map((report, idx) => (
            <Card key={idx} className="bg-primary/10 border-primary/30 hover:bg-primary/20 transition">
              <CardHeader className="flex items-center justify-between">
                <div className="flex items-center">
                  {report.icon}
                  <CardTitle className="text-foreground">{report.title}</CardTitle>
                </div>
              </CardHeader>
              <CardContent>
                <CardDescription className="text-muted-foreground">{report.description}</CardDescription>
                <div className="mt-4">
                  <Button size="sm" variant="outline" onClick={report.action}>
                    <Download className="h-4 w-4 mr-1" /> Download
                  </Button>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </AppLayout>
  );
}
