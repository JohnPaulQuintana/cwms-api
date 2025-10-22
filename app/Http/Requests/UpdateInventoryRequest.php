<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => 'sometimes|string|max:255',
            'sku' => "sometimes|string|max:100|unique:inventories,sku,{$id}",
            'description' => 'nullable|string',
            'quantity' => 'sometimes|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'location_id' => 'sometimes|exists:warehouse_locations,id',
        ];
    }
}

