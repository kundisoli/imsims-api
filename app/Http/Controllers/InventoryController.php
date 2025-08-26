namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index()
    {
        $products = Product::all();

        return Inertia::render('InventoryDashboard', [
            'products' => $products
        ]);
    }
}