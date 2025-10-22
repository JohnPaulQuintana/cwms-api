<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    /**
     * Verify user's email via API link
     */
    public function verify($id, $hash)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'User not found.',
                'data' => null
            ], 404);
        }

        // Check if hash matches email
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Invalid verification link.',
                'data' => null
            ], 400);
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'type' => 'info',
                'message' => 'Email already verified.',
                'data' => null
            ]);
        }

        // Mark email as verified
        $user->markEmailAsVerified();

        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => 'Email verified successfully. You can now log in.',
            'data' => null
        ]);
    }
}
