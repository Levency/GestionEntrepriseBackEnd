<?php

namespace App\Http\Controllers;

use App\Models\Settings;
use Illuminate\Http\Request;
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
            'logo' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);
        try {
        $settings->update($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Paramètres mis à jour avec succès',
            'data' => new SettingsResource($settings)
        ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour des paramètres',
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
