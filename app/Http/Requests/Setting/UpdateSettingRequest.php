<?php

namespace App\Http\Requests\Setting;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('setting'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'settings' => ['required', 'array:notifications'],
            'settings.notifications' => ['required', 'array:'.implode(',', Setting::NOTIFICATION_CHANNELS)],
        ];

        // Partial update: channel yang tidak dikirim tidak diubah.
        foreach (Setting::NOTIFICATION_CHANNELS as $channel) {
            $rules["settings.notifications.{$channel}"] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'settings.required' => 'Field settings wajib diisi.',
            'settings.notifications.required' => 'Pengaturan notifikasi wajib diisi.',
            'settings.notifications.array' => 'Channel notifikasi hanya boleh: '.implode(', ', Setting::NOTIFICATION_CHANNELS).'.',
            'settings.notifications.*.boolean' => 'Nilai :attribute harus true atau false.',
        ];
    }
}
