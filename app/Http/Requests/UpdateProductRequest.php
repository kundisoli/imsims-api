<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        $productId = $this->route('product')?->id ?? $this->route('product');

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($productId)
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->ignore($productId)
            ],
            'price' => 'required|numeric|min:0|max:9999999.99',
            'cost_price' => 'required|numeric|min:0|max:9999999.99|lte:price',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'minimum_stock' => 'required|integer|min:0|max:999999',
            'maximum_stock' => 'required|integer|min:1|max:999999|gte:minimum_stock',
            'unit_of_measure' => 'required|string|max:50',
            'weight' => 'nullable|numeric|min:0|max:99999.999',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'dimensions.unit' => 'nullable|string|in:mm,cm,m,in,ft',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'name.max' => 'Product name cannot exceed 255 characters.',
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU already exists.',
            'sku.max' => 'SKU cannot exceed 100 characters.',
            'barcode.unique' => 'This barcode already exists.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price cannot be negative.',
            'cost_price.required' => 'Cost price is required.',
            'cost_price.numeric' => 'Cost price must be a valid number.',
            'cost_price.min' => 'Cost price cannot be negative.',
            'cost_price.lte' => 'Cost price cannot be higher than selling price.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'supplier_id.required' => 'Supplier is required.',
            'supplier_id.exists' => 'Selected supplier does not exist.',
            'minimum_stock.required' => 'Minimum stock is required.',
            'minimum_stock.integer' => 'Minimum stock must be a whole number.',
            'minimum_stock.min' => 'Minimum stock cannot be negative.',
            'maximum_stock.required' => 'Maximum stock is required.',
            'maximum_stock.integer' => 'Maximum stock must be a whole number.',
            'maximum_stock.min' => 'Maximum stock must be at least 1.',
            'maximum_stock.gte' => 'Maximum stock must be greater than or equal to minimum stock.',
            'unit_of_measure.required' => 'Unit of measure is required.',
            'weight.numeric' => 'Weight must be a valid number.',
            'weight.min' => 'Weight cannot be negative.',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'name' => 'product name',
            'sku' => 'SKU',
            'category_id' => 'category',
            'supplier_id' => 'supplier',
            'minimum_stock' => 'minimum stock',
            'maximum_stock' => 'maximum stock',
            'unit_of_measure' => 'unit of measure',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => $this->boolean('is_active'),
            ]);
        }
    }
}
