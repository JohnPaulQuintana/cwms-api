<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:inventories,sku',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'location_id' => 'required|exists:warehouse_locations,id',
        ];
    }
}

