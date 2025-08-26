<?php

namespace App\Http\Requests;

use App\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // You can implement authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'product_id' => 'required|exists:products,id',
            'type' => ['required', Rule::in(array_keys(StockMovement::getTypes()))],
            'quantity' => 'required|integer|not_in:0',
            'reason' => ['required', Rule::in(array_keys(StockMovement::getReasons()))],
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'cost_per_unit' => 'nullable|numeric|min:0|max:9999999.99',
            'batch_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date|after:today',
        ];

        // Add conditional rules based on movement type
        if ($this->input('type') === StockMovement::TYPE_TRANSFER) {
            $rules['location_from'] = 'required|string|max:100';
            $rules['location_to'] = 'required|string|max:100|different:location_from';
        } elseif ($this->input('type') === StockMovement::TYPE_IN) {
            $rules['location_to'] = 'required|string|max:100';
            $rules['quantity'] = 'required|integer|min:1';
        } elseif ($this->input('type') === StockMovement::TYPE_OUT) {
            $rules['location_from'] = 'required|string|max:100';
            $rules['quantity'] = 'required|integer|min:1';
        } else {
            // For adjustments, we can have any location
            $rules['location_from'] = 'nullable|string|max:100';
            $rules['location_to'] = 'nullable|string|max:100';
        }

        // Add validation for stock availability on outbound movements
        if (in_array($this->input('type'), [StockMovement::TYPE_OUT, StockMovement::TYPE_TRANSFER])) {
            $rules['quantity'] = array_merge(
                (array) $rules['quantity'],
                ['stock_available:' . $this->input('product_id') . ',' . $this->input('location_from')]
            );
        }

        return $rules;
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required.',
            'product_id.exists' => 'Selected product does not exist.',
            'type.required' => 'Movement type is required.',
            'type.in' => 'Invalid movement type selected.',
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.not_in' => 'Quantity cannot be zero.',
            'quantity.min' => 'Quantity must be positive for this movement type.',
            'quantity.stock_available' => 'Insufficient stock available for this movement.',
            'reason.required' => 'Reason is required.',
            'reason.in' => 'Invalid reason selected.',
            'reference_number.max' => 'Reference number cannot exceed 100 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'cost_per_unit.numeric' => 'Cost per unit must be a valid number.',
            'cost_per_unit.min' => 'Cost per unit cannot be negative.',
            'batch_number.max' => 'Batch number cannot exceed 100 characters.',
            'expiry_date.date' => 'Expiry date must be a valid date.',
            'expiry_date.after' => 'Expiry date must be in the future.',
            'location_from.required' => 'Source location is required for this movement type.',
            'location_from.max' => 'Source location cannot exceed 100 characters.',
            'location_to.required' => 'Destination location is required for this movement type.',
            'location_to.max' => 'Destination location cannot exceed 100 characters.',
            'location_to.different' => 'Destination location must be different from source location.',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'location_from' => 'source location',
            'location_to' => 'destination location',
            'cost_per_unit' => 'cost per unit',
            'batch_number' => 'batch number',
            'expiry_date' => 'expiry date',
            'reference_number' => 'reference number',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize quantity based on movement type
        if ($this->has('type') && $this->has('quantity')) {
            $quantity = abs((int) $this->input('quantity'));
            
            // For outbound movements, make quantity negative for internal processing
            if ($this->input('type') === StockMovement::TYPE_OUT) {
                $this->merge(['quantity' => $quantity]);
            } else {
                $this->merge(['quantity' => $quantity]);
            }
        }

        // Set default location if not provided
        if (!$this->has('location_from') && !$this->has('location_to')) {
            $this->merge(['location_to' => 'main']);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->addExtension('stock_available', function ($attribute, $value, $parameters, $validator) {
            if (count($parameters) !== 2) {
                return false;
            }

            $productId = $parameters[0];
            $location = $parameters[1];

            if (!$productId || !$location) {
                return true; // Let other validation rules handle missing values
            }

            $availableStock = \App\Models\Stock::where('product_id', $productId)
                ->where('location', $location)
                ->sum('quantity');

            return $availableStock >= $value;
        });
    }
}
