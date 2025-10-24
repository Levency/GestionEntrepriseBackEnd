<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FogetPassword extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        // Validate the email input
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            // Here you would typically generate a password reset token
            // and send an email to the user with the reset link.
            // For demonstration, we'll just return a success response.

            return response()->json([
                'message' => 'Password reset link sent to your email address.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send password reset link.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
