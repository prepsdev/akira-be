<?php

namespace App\Http\Controllers;

use App\Models\GlobalSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class GlobalSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = GlobalSetting::all();
        return response()->json($settings);
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_name' => 'required|string|exists:global_settings,setting_name',
            'is_enabled' => 'required|boolean',
        ]);

        $setting = GlobalSetting::where('setting_name', $validated['setting_name'])->first();

        if ($setting) {
            $setting->update(['is_enabled' => $validated['is_enabled']]);

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'setting' => $setting
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Setting not found'
        ], 404);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'setting_name' => 'required|string|exists:global_settings,setting_name',
            'setting_value' => 'nullable|string',
            'is_enabled' => 'nullable|boolean',
        ]);

        $setting = GlobalSetting::where('setting_name', $validated['setting_name'])->first();

        if ($setting) {
            $updateData = [];
            if (isset($validated['setting_value'])) {
                $updateData['setting_value'] = $validated['setting_value'];
            }
            if (isset($validated['is_enabled'])) {
                $updateData['is_enabled'] = $validated['is_enabled'];
            }

            $setting->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'setting' => $setting
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Setting not found'
        ], 404);
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        try {
            $setting = GlobalSetting::where('setting_name', 'app_logo')->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Logo setting not found'
                ], 404);
            }

            // Delete old logo if exists
            if ($setting->setting_value && Storage::disk('public')->exists($setting->setting_value)) {
                Storage::disk('public')->delete($setting->setting_value);
            }

            // Store new logo
            $path = $request->file('logo')->store('logos', 'public');

            // Update setting
            $setting->update(['setting_value' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Logo uploaded successfully',
                'logo_url' => Storage::url($path),
                'setting' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload logo: ' . $e->getMessage()
            ], 500);
        }
    }
}
