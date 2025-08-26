import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Star, MapPin, Clock, Package, Truck, Download, FileText } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Delivery', href: '/delivery' },
];

export default function Delivery() {
  const [selectedDriver, setSelectedDriver] = useState('James Lubin');
  const [selectedVehicle, setSelectedVehicle] = useState('Volkswagen Transporter');

  // Sample data
  const favorites = [
    { name: 'Nolan Dokidis', vehicle: 'Mercedes-Benz Sprinter' },
    { name: 'Ahmad Mango', vehicle: 'Volkswagen Transporter' },
    { name: 'James Lubin', vehicle: 'Volkswagen Transporter' },
    { name: 'Talan Dorwart', vehicle: 'Mercedes-Benz Metris' },
    { name: 'TRIJCKS', driver: 'Jakob Vetrovs', vehicle: 'Volvo-FL' },
    { name: 'Zain Vetrovs', vehicle: 'Mercedes-Benz Alego' },
    { name: 'Jaylon Rinel Madsen', vehicle: 'Volvo-FL' },
    { name: 'Gustavo Torff', vehicle: 'Volvo-FH' },
    { name: 'Jaylon Botosh', vehicle: 'Han TOM 13:30-0.4x2 BL CH' },
    { name: 'Marcus Dokidis', vehicle: 'Han TOL 8:30-0.4x2 BL CH' },
    { name: 'Tiana Westervelt', vehicle: 'Volkswagen Transporter' },
    { name: 'Zain Korsgaard', vehicle: 'Mercedes-Benz Sprinter' },
    { name: 'Wilson Dokidis', vehicle: 'Mercedes-Benz Metris' },
    { name: 'Jaxson Donin', vehicle: 'Volkswagen Transporter' },
    { name: 'Adri New Vehicle', vehicle: '' },
  ];

  const routes = [
    { id: '107-591', packages: 138, from: '2972 Westheimer Rd, Santa Ana', to: '270 Ruder Ave' },
    { id: '109-270', packages: 107, from: '8900 Murray Ave', to: '168 V1/00h St., Clivey, CA 95020' },
    { id: '112-791', packages: 86, from: '230 Mayock Rd', to: '8825 Arroyo Cir Suite 21, Clivey, CA 95020' },
    { id: '128-612', packages: 129, from: '6215 Engle Way', to: '905 Lit St., Clivey, CA 95020' },
  ];

  const stats = [
    { category: 'On the Way', time: '3 hr 10 min', percentage: '39.7%' },
    { category: 'Unloading', time: '2 hr 15 min', percentage: '28.3%' },
    { category: 'Loading', time: '1 hr 23 min', percentage: '17.4%' },
    { category: 'Waiting', time: '1 hr 10 min', percentage: '14.6%' },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Delivery" />

      <div className="bg-card text-foreground min-h-screen p-6 flex flex-col gap-6 rounded-xl">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Favorites Section */}
          <Card className="bg-gray-800 lg:col-span-1">
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Star className="h-5 w-5 text-yellow-400" />
                FAVORITES
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {favorites.map((item, index) => (
                  <div 
                    key={index} 
                    className={`p-3 rounded-lg cursor-pointer transition-colors ${
                      selectedDriver === item.name ? 'bg-blue-600' : 'bg-gray-700 hover:bg-gray-600'
                    }`}
                    onClick={() => setSelectedDriver(item.name)}
                  >
                    <div className="font-medium">{item.name}</div>
                    {item.driver && <div className="text-sm text-gray-300">{item.driver}</div>}
                    <div className="text-sm text-gray-400">{item.vehicle}</div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Driver & Vehicle Details */}
          <Card className="bg-gray-800 lg:col-span-2">
            <CardHeader>
              <CardTitle>{selectedDriver}</CardTitle>
              <CardDescription>ID: 236-542-097</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="mb-6">
                <h3 className="text-lg font-semibold mb-4">{selectedVehicle}</h3>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <p className="text-gray-400">Payload</p>
                    <p className="font-medium">2,885 lbs</p>
                  </div>
                  <div>
                    <p className="text-gray-400">Load Volume</p>
                    <p className="font-medium">353,937 in³</p>
                  </div>
                  <div>
                    <p className="text-gray-400">Load Length</p>
                    <p className="font-medium">117 in</p>
                  </div>
                  <div>
                    <p className="text-gray-400">Load Width</p>
                    <p className="font-medium">67 in</p>
                  </div>
                </div>
              </div>

              <div className="mb-6">
                <h3 className="text-lg font-semibold mb-2">DRR244</h3>
                <div className="flex gap-2 mb-4">
                  <Button size="sm" variant="outline">
                    <FileText className="h-4 w-4 mr-1" /> Documents
                  </Button>
                  <Button size="sm" variant="outline">
                    <MapPin className="h-4 w-4 mr-1" /> Routes
                  </Button>
                </div>
              </div>

              {/* Current Route */}
              <div className="mb-6">
                <h4 className="font-semibold mb-2 flex items-center gap-2">
                  <Truck className="h-4 w-4" /> NOW ON THE WAY
                </h4>
                <Card className="bg-gray-700 border-gray-600">
                  <CardContent className="p-4">
                    <div className="font-medium mb-2">ID: 107-591 - 138 packages</div>
                    <div className="text-sm mb-2">2972 Westheimer Rd, Santa Ana → 270 Ruder Ave</div>
                    <div className="grid grid-cols-2 gap-2 text-sm">
                      <div>
                        <p className="text-gray-400">Distance</p>
                        <p>0.62 ml</p>
                      </div>
                      <div>
                        <p className="text-gray-400">Time</p>
                        <p>10 min</p>
                      </div>
                      <div>
                        <p className="text-gray-400">Weight</p>
                        <p>2,160 lbs</p>
                      </div>
                      <div>
                        <p className="text-gray-400">Volume</p>
                        <p>3,357 in³</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </div>

              {/* Other Routes */}
              <div className="mb-6">
                <h4 className="font-semibold mb-2">Other Routes</h4>
                <div className="space-y-3">
                  {routes.slice(1).map((route, index) => (
                    <div key={index} className="p-3 bg-gray-700 rounded-lg">
                      <div className="font-medium">ID: {route.id} - {route.packages} packages</div>
                      <div className="text-sm">{route.from} → {route.to}</div>
                    </div>
                  ))}
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Driver Statistics */}
        <Card className="bg-gray-800">
          <CardHeader>
            <CardTitle>Driver Statistics</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h4 className="font-semibold mb-4">AVERAGE TIME PER DAY BY CATEGORY</h4>
                <div className="h-6 bg-gray-700 rounded-full mb-2 flex">
                  <div className="bg-blue-600 rounded-l-full" style={{ width: '39.7%' }}></div>
                  <div className="bg-green-600" style={{ width: '28.3%' }}></div>
                  <div className="bg-yellow-600" style={{ width: '17.4%' }}></div>
                  <div className="bg-red-600 rounded-r-full" style={{ width: '14.6%' }}></div>
                </div>
                <div className="flex justify-between text-xs mb-6">
                  <span>W</span>
                  <span>N</span>
                  <span>GH</span>
                  <span>Y</span>
                </div>
                
                <div className="space-y-3">
                  {stats.map((stat, index) => (
                    <div key={index} className="flex justify-between items-center">
                      <span>{stat.category}</span>
                      <div className="flex items-center gap-4">
                        <span>{stat.time}</span>
                        <span className="font-semibold">{stat.percentage}</span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
              
              <div>
                <h4 className="font-semibold mb-4">WORKING TIME PER DAY</h4>
                <div className="bg-gray-700 p-4 rounded-lg mb-4">
                  <div className="text-center text-2xl font-bold mb-2">8 hr 58 min</div>
                  <div className="text-center text-gray-400">Average Working Time</div>
                </div>
                <div className="flex justify-center">
                  <Button>
                    <Download className="h-4 w-4 mr-2" /> Download Report
                  </Button>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}