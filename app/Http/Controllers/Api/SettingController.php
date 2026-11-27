<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\StoreSettingRequest;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class SettingController extends Controller
{
    public function __construct(
        private SettingService $settingService
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSettingRequest $request): JsonResponse
    {
        $setting = $this->settingService->create($request->user(), $request->validated());

        return (new SettingResource($setting))
            ->additional(['message' => 'Pengaturan notifikasi berhasil dibuat.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $setting): SettingResource
    {
        Gate::authorize('view', $setting);

        return new SettingResource($setting);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSettingRequest $request, Setting $setting): SettingResource
    {
        $setting = $this->settingService->update($setting, $request->validated());

        return (new SettingResource($setting))
            ->additional(['message' => 'Pengaturan notifikasi berhasil diperbarui.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting): JsonResponse
    {
        Gate::authorize('delete', $setting);

        $this->settingService->delete($setting);

        return ResponseHelper::success(null, "Pengaturan berhasil dihapus");
    }
}
