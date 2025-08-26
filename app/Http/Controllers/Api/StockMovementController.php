<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Stock;
use App\Models\Product;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StockMovementController extends Controller
{
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Display a listing of stock movements.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);
        $productId = $request->get('product_id');
        $type = $request->get('type');
        $reason = $request->get('reason');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = StockMovement::with(['product', 'user', 'stock'])
            ->orderBy('performed_at', 'desc');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($reason) {
            $query->where('reason', $reason);
        }

        if ($dateFrom) {
            $query->whereDate('performed_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('performed_at', '<=', $dateTo);
        }

        $movements = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $movements,
        ]);
    }

    /**
     * Store a newly created stock movement.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'type' => ['required', Rule::in(array_keys(StockMovement::getTypes()))],
            'quantity' => 'required|integer|not_in:0',
            'reason' => ['required', Rule::in(array_keys(StockMovement::getReasons()))],
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'cost_per_unit' => 'nullable|numeric|min:0',
            'location_from' => 'nullable|string|max:100',
            'location_to' => 'required_if:type,transfer|string|max:100',
            'batch_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            $product = Product::findOrFail($data['product_id']);

            // Set defaults
            $data['user_id'] = auth()->id();
            $data['performed_at'] = now();
            $data['cost_per_unit'] = $data['cost_per_unit'] ?? $product->cost_price;
            $data['total_cost'] = abs($data['quantity']) * $data['cost_per_unit'];

            // Handle different movement types
            switch ($data['type']) {
                case StockMovement::TYPE_IN:
                    $this->handleStockIn($data, $product);
                    break;
                case StockMovement::TYPE_OUT:
                    $this->handleStockOut($data, $product);
                    break;
                case StockMovement::TYPE_ADJUSTMENT:
                    $this->handleStockAdjustment($data, $product);
                    break;
                case StockMovement::TYPE_TRANSFER:
                    $this->handleStockTransfer($data, $product);
                    break;
            }

            $movement = StockMovement::create($data);
            $movement->load(['product', 'user', 'stock']);

            // Log the movement
            AuditLog::logStockMovement(
                $product->id,
                $data['type'],
                $data['quantity'],
                $data['reason'],
                ['movement_id' => $movement->id]
            );

            // Clear cache
            $this->clearStockCache($product->id);

            return response()->json([
                'success' => true,
                'message' => 'Stock movement recorded successfully',
                'data' => $movement,
            ], 201);
        });
    }

    /**
     * Display the specified stock movement.
     */
    public function show(StockMovement $stockMovement): JsonResponse
    {
        $stockMovement->load(['product', 'user', 'stock']);

        return response()->json([
            'success' => true,
            'data' => $stockMovement,
        ]);
    }

    /**
     * Update the specified stock movement (limited updates allowed).
     */
    public function update(Request $request, StockMovement $stockMovement): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
            'reference_number' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldValues = $stockMovement->toArray();
        $stockMovement->update($request->validated());

        // Log the update
        AuditLog::logUpdate('StockMovement', $stockMovement->id, $oldValues, $stockMovement->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Stock movement updated successfully',
            'data' => $stockMovement,
        ]);
    }

    /**
     * Remove the specified stock movement (reversal).
     */
    public function destroy(StockMovement $stockMovement): JsonResponse
    {
        return DB::transaction(function () use ($stockMovement) {
            // Create a reversal movement
            $reversalData = [
                'product_id' => $stockMovement->product_id,
                'stock_id' => $stockMovement->stock_id,
                'user_id' => auth()->id(),
                'type' => $this->getReversalType($stockMovement->type),
                'quantity' => -$stockMovement->quantity,
                'reason' => 'adjustment',
                'reference_number' => 'REVERSAL-' . $stockMovement->id,
                'notes' => "Reversal of movement #{$stockMovement->id}",
                'cost_per_unit' => $stockMovement->cost_per_unit,
                'total_cost' => $stockMovement->total_cost,
                'performed_at' => now(),
            ];

            $reversalMovement = StockMovement::create($reversalData);

            // Update stock quantities
            $this->reverseStockMovement($stockMovement);

            // Log the reversal
            AuditLog::logStockMovement(
                $stockMovement->product_id,
                'reversal',
                $stockMovement->quantity,
                'reversal',
                ['original_movement_id' => $stockMovement->id, 'reversal_movement_id' => $reversalMovement->id]
            );

            // Clear cache
            $this->clearStockCache($stockMovement->product_id);

            return response()->json([
                'success' => true,
                'message' => 'Stock movement reversed successfully',
                'data' => $reversalMovement,
            ]);
        });
    }

    /**
     * Get stock movement statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $productId = $request->get('product_id');
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $cacheKey = "stock_movements:stats:" . md5("{$productId}:{$dateFrom}:{$dateTo}");

        $stats = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($productId, $dateFrom, $dateTo) {
            $query = StockMovement::whereBetween('performed_at', [$dateFrom, $dateTo]);

            if ($productId) {
                $query->where('product_id', $productId);
            }

            return [
                'total_movements' => $query->count(),
                'movements_by_type' => $query->groupBy('type')
                    ->selectRaw('type, COUNT(*) as count, SUM(ABS(quantity)) as total_quantity')
                    ->get()
                    ->keyBy('type'),
                'movements_by_reason' => $query->groupBy('reason')
                    ->selectRaw('reason, COUNT(*) as count, SUM(ABS(quantity)) as total_quantity')
                    ->get()
                    ->keyBy('reason'),
                'total_value' => $query->sum('total_cost'),
                'date_range' => [$dateFrom, $dateTo],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Handle stock in movement.
     */
    private function handleStockIn(array &$data, Product $product): void
    {
        $location = $data['location_to'] ?? 'main';
        $batchNumber = $data['batch_number'] ?? null;

        $stock = Stock::firstOrCreate([
            'product_id' => $product->id,
            'location' => $location,
            'batch_number' => $batchNumber,
        ], [
            'quantity' => 0,
            'cost_per_unit' => $data['cost_per_unit'],
            'expiry_date' => $data['expiry_date'] ?? null,
        ]);

        $stock->increment('quantity', abs($data['quantity']));
        $data['stock_id'] = $stock->id;
        $data['quantity'] = abs($data['quantity']); // Ensure positive for stock in
    }

    /**
     * Handle stock out movement.
     */
    private function handleStockOut(array &$data, Product $product): void
    {
        $location = $data['location_from'] ?? 'main';
        $quantity = abs($data['quantity']);

        $availableStock = Stock::where('product_id', $product->id)
            ->where('location', $location)
            ->sum('quantity');

        if ($availableStock < $quantity) {
            throw new \Exception("Insufficient stock. Available: {$availableStock}, Requested: {$quantity}");
        }

        // Use FIFO method to deduct stock
        $stocks = Stock::where('product_id', $product->id)
            ->where('location', $location)
            ->where('quantity', '>', 0)
            ->orderBy('created_at')
            ->get();

        $remainingQuantity = $quantity;
        foreach ($stocks as $stock) {
            if ($remainingQuantity <= 0) break;

            $deductQuantity = min($stock->quantity, $remainingQuantity);
            $stock->decrement('quantity', $deductQuantity);
            $remainingQuantity -= $deductQuantity;

            if (!isset($data['stock_id'])) {
                $data['stock_id'] = $stock->id;
            }
        }

        $data['quantity'] = -$quantity; // Negative for stock out
    }

    /**
     * Handle stock adjustment movement.
     */
    private function handleStockAdjustment(array &$data, Product $product): void
    {
        $location = $data['location_to'] ?? $data['location_from'] ?? 'main';
        $batchNumber = $data['batch_number'] ?? null;

        $stock = Stock::firstOrCreate([
            'product_id' => $product->id,
            'location' => $location,
            'batch_number' => $batchNumber,
        ], [
            'quantity' => 0,
            'cost_per_unit' => $data['cost_per_unit'],
        ]);

        $stock->increment('quantity', $data['quantity']);
        $data['stock_id'] = $stock->id;
    }

    /**
     * Handle stock transfer movement.
     */
    private function handleStockTransfer(array &$data, Product $product): void
    {
        $quantity = abs($data['quantity']);

        // Check stock in source location
        $availableStock = Stock::where('product_id', $product->id)
            ->where('location', $data['location_from'])
            ->sum('quantity');

        if ($availableStock < $quantity) {
            throw new \Exception("Insufficient stock in source location. Available: {$availableStock}, Requested: {$quantity}");
        }

        // Deduct from source location
        $data['quantity'] = -$quantity;
        $this->handleStockOut($data, $product);

        // Add to destination location
        $destinationData = $data;
        $destinationData['quantity'] = $quantity;
        $destinationData['location_to'] = $data['location_to'];
        unset($destinationData['location_from']);
        $this->handleStockIn($destinationData, $product);
    }

    /**
     * Get reversal type for a movement type.
     */
    private function getReversalType(string $type): string
    {
        return match ($type) {
            StockMovement::TYPE_IN => StockMovement::TYPE_OUT,
            StockMovement::TYPE_OUT => StockMovement::TYPE_IN,
            default => StockMovement::TYPE_ADJUSTMENT,
        };
    }

    /**
     * Reverse a stock movement.
     */
    private function reverseStockMovement(StockMovement $movement): void
    {
        if ($movement->stock_id) {
            $stock = Stock::find($movement->stock_id);
            if ($stock) {
                $stock->decrement('quantity', $movement->quantity);
            }
        }
    }

    /**
     * Clear stock-related cache.
     */
    private function clearStockCache(int $productId): void
    {
        Cache::forget("product:{$productId}:stock_levels");
        Cache::forget("product:{$productId}:details");
        Cache::forget('products:low_stock');
        Cache::forget('stock_movements:stats:*');
    }
}
