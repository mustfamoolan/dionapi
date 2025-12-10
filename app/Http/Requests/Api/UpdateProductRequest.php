<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $clientId = $this->user()->id;
        $productId = $this->route('id');

        return [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,' . $productId . ',id,client_id,' . $clientId,
            'purchase_price' => 'required|numeric|min:0',
            'wholesale_price' => 'required|numeric|min:0',
            'retail_price' => 'required|numeric|min:0',
            'unit_type' => 'required|in:weight,piece,carton',
            'weight' => 'required_if:unit_type,weight|nullable|numeric|min:0',
            'weight_unit' => 'required_if:unit_type,weight|nullable|in:kg,g',
            'pieces_per_carton' => 'required_if:unit_type,carton|nullable|integer|min:1',
            'piece_price_in_carton' => 'required_if:unit_type,carton|nullable|numeric|min:0',
            'total_quantity' => 'required|numeric|min:0',
            'remaining_quantity' => 'required|numeric|min:0',
            'min_quantity' => 'required|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم المنتج مطلوب.',
            'name.string' => 'اسم المنتج يجب أن يكون نصاً.',
            'name.max' => 'اسم المنتج يجب ألا يتجاوز 255 حرفاً.',

            'sku.required' => 'كود المنتج (SKU) مطلوب.',
            'sku.string' => 'كود المنتج يجب أن يكون نصاً.',
            'sku.unique' => 'كود المنتج مستخدم مسبقاً. يرجى استخدام كود آخر.',

            'purchase_price.required' => 'سعر الشراء مطلوب.',
            'purchase_price.numeric' => 'سعر الشراء يجب أن يكون رقماً.',
            'purchase_price.min' => 'سعر الشراء يجب أن يكون أكبر من أو يساوي صفر.',

            'wholesale_price.required' => 'سعر البيع بالجملة مطلوب.',
            'wholesale_price.numeric' => 'سعر البيع بالجملة يجب أن يكون رقماً.',
            'wholesale_price.min' => 'سعر البيع بالجملة يجب أن يكون أكبر من أو يساوي صفر.',

            'retail_price.required' => 'سعر البيع بالمفرد مطلوب.',
            'retail_price.numeric' => 'سعر البيع بالمفرد يجب أن يكون رقماً.',
            'retail_price.min' => 'سعر البيع بالمفرد يجب أن يكون أكبر من أو يساوي صفر.',

            'unit_type.required' => 'نوع الوحدة مطلوب.',
            'unit_type.in' => 'نوع الوحدة يجب أن يكون: وزن، قطعة، أو كارتون.',

            'weight.required_if' => 'الوزن مطلوب عند اختيار نوع الوحدة "وزن".',
            'weight.numeric' => 'الوزن يجب أن يكون رقماً.',
            'weight.min' => 'الوزن يجب أن يكون أكبر من أو يساوي صفر.',

            'weight_unit.required_if' => 'وحدة الوزن مطلوبة عند اختيار نوع الوحدة "وزن".',
            'weight_unit.in' => 'وحدة الوزن يجب أن تكون: كيلو (kg) أو غرام (g).',

            'pieces_per_carton.required_if' => 'عدد القطع في الكارتون مطلوب عند اختيار نوع الوحدة "كارتون".',
            'pieces_per_carton.integer' => 'عدد القطع في الكارتون يجب أن يكون رقماً صحيحاً.',
            'pieces_per_carton.min' => 'عدد القطع في الكارتون يجب أن يكون على الأقل 1.',

            'piece_price_in_carton.required_if' => 'سعر القطعة داخل الكارتون مطلوب عند اختيار نوع الوحدة "كارتون".',
            'piece_price_in_carton.numeric' => 'سعر القطعة داخل الكارتون يجب أن يكون رقماً.',
            'piece_price_in_carton.min' => 'سعر القطعة داخل الكارتون يجب أن يكون أكبر من أو يساوي صفر.',

            'total_quantity.required' => 'الكمية الكلية مطلوبة.',
            'total_quantity.numeric' => 'الكمية الكلية يجب أن تكون رقماً.',
            'total_quantity.min' => 'الكمية الكلية يجب أن تكون أكبر من أو تساوي صفر.',

            'remaining_quantity.required' => 'الكمية المتبقية مطلوبة.',
            'remaining_quantity.numeric' => 'الكمية المتبقية يجب أن تكون رقماً.',
            'remaining_quantity.min' => 'الكمية المتبقية يجب أن تكون أكبر من أو تساوي صفر.',

            'min_quantity.required' => 'الحد الأدنى للتنبيه مطلوب.',
            'min_quantity.numeric' => 'الحد الأدنى للتنبيه يجب أن يكون رقماً.',
            'min_quantity.min' => 'الحد الأدنى للتنبيه يجب أن يكون أكبر من أو يساوي صفر.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate remaining_quantity doesn't exceed total_quantity
            if ($this->has('total_quantity') && $this->has('remaining_quantity')) {
                if ($this->remaining_quantity > $this->total_quantity) {
                    $validator->errors()->add('remaining_quantity', 'الكمية المتبقية لا يمكن أن تكون أكبر من الكمية الكلية.');
                }
            }
        });
    }
}

