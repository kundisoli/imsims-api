import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Download, CreditCard } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Billing', href: '/billing' },
];

interface Invoice {
  id: string;
  date: string;
  description: string;
  amount: number;
  status: string;
}

const sampleInvoices: Invoice[] = [
  { id: 'INV001', date: 'Aug 15, 2025', description: 'Standard subscription', amount: 20, status: 'Paid' },
  { id: 'INV002', date: 'Jul 15, 2025', description: 'Standard subscription', amount: 20, status: 'Paid' },
  { id: 'INV003', date: 'Jun 15, 2025', description: 'Standard subscription', amount: 20, status: 'Paid' },
];

export default function Billing() {
  const [paymentMethod, setPaymentMethod] = useState('**** 6521');

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Billing" />

      <div className="flex flex-col gap-4 p-4">
        {/* Current Plan & Next Invoice */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Card className="bg-primary/20 border-primary/30">
            <CardHeader>
              <CardTitle>Current Plan</CardTitle>
              <CardDescription>Standard</CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-2">
              <p>Your subscription allows 10 projects per month.</p>
              <Button size="sm">Upgrade Plan</Button>
            </CardContent>
          </Card>

          <Card className="bg-accent/20 border-accent/30">
            <CardHeader>
              <CardTitle>Next Invoice</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-2">
              <p>Amount: $20.00</p>
              <p>Due: Aug 15, 2025</p>
              <Button size="sm" variant="outline">Download Invoice</Button>
            </CardContent>
          </Card>
        </div>

        {/* Payment Method */}
        <Card className="bg-sidebar-foreground/10 border-sidebar-foreground/20">
          <CardHeader>
            <CardTitle>Payment Method</CardTitle>
          </CardHeader>
          <CardContent className="flex flex-col md:flex-row items-center justify-between gap-4">
            <div className="flex items-center gap-2">
              <CreditCard />
              <p>{paymentMethod}</p>
            </div>
            <Button size="sm" variant="outline">Change Card</Button>
          </CardContent>
        </Card>

        {/* Recent Invoices */}
        <Card className="bg-card border-muted/20">
          <CardHeader>
            <CardTitle>Recent Invoices</CardTitle>
          </CardHeader>
          <CardContent className="overflow-x-auto">
            <table className="w-full table-auto border-collapse text-muted-foreground">
              <thead className="border-b border-muted/30">
                <tr>
                  <th className="p-2 text-left">Date</th>
                  <th className="p-2 text-left">Description</th>
                  <th className="p-2 text-left">Amount</th>
                  <th className="p-2 text-left">Status</th>
                  <th className="p-2 text-left">Action</th>
                </tr>
              </thead>
              <tbody>
                {sampleInvoices.map((inv) => (
                  <tr key={inv.id} className="border-b border-muted/20 hover:bg-muted/10">
                    <td className="p-2">{inv.date}</td>
                    <td className="p-2">{inv.description}</td>
                    <td className="p-2">${inv.amount}</td>
                    <td className="p-2">{inv.status}</td>
                    <td className="p-2">
                    <Button size="sm" variant="outline" className="px-2 py-1 text-xs">
                        <Download className="mr-1" />Download
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
