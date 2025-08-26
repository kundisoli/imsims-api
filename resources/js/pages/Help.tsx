import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Help', href: '/help' },
];

export default function Help() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Help" />
            <div className="flex flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Help & Support</CardTitle>
                    </CardHeader>
                    <CardContent>
                        Coming soon. This page will follow the dashboard styling.
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}


