<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\SettingsResource;

class SettingsController extends Controller
{
    /*
     * Display the settings.
     */
    public function index()
    {
        $settings = Settings::first();
        return response()->json([
            'status' => 'success',
            'message' => 'Paramètres récupérés avec succès',
            'data' => new SettingsResource($settings)
        ]);
    }

    /*
     * Update the settings.
     */
    public function update(Request $request, Settings $settings)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|file|mimes:png,jpg,jpeg,svg|max:2048',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        try {
            $data = $request->except(['logo', '_method']);

            if ($request->hasFile('logo')) {
                // Supprime l'ancien logo
                if ($settings->logo && Storage::disk('public')->exists($settings->logo)) {
                    Storage::disk('public')->delete($settings->logo);
                }

                $path = $request->file('logo')->store('logos', 'public');
                $data['logo'] = $path;
            }

            $settings->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Paramètres mis à jour avec succès',
                'data' => new SettingsResource($settings->fresh())
            ]);

        } catch (\Throwable $th) {
            \Log::error('Settings update error: ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour des paramètres',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(Settings $settings)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Paramètres récupérés avec succès',
            'data' => new SettingsResource($settings)
        ]);
    }

    public function destroy(Settings $settings)
    {
        $settings->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Paramètres supprimés avec succès',
        ]);
    }
}
