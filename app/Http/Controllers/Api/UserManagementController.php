<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserManagementController extends Controller
{
    // Resend verification email
    public function resendVerificationEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'User not found.',
                'data' => null,
            ], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'type' => 'info',
                'message' => 'Email already verified.',
                'data' => null,
            ]);
        }

        $user->notify(new CustomVerifyEmail);

        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => 'Verification email resent.',
            'data' => null,
        ]);
    }

    // Register (Admin only)
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => ['required', Rule::in(['admin', 'warehouse_staff', 'project_manager'])],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => false,
        ]);

        // Send custom verification email
        $user->notify(new CustomVerifyEmail);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'type' => 'email_verification_required',
            'message' => 'User created successfully. Verification email sent.',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ]);
    }

    // Login
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Invalid credentials',
                'data' => null,
            ], 401);
        }

        $user = auth()->user();

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'type' => 'email_verification_required',
                'message' => 'Please verify your email before logging in.',
                'data' => null,
            ], 403);
        }

        // Set user as active
        $user->is_active = 1;
        $user->save();

        $isStaff = DB::table('warehouse_locations')->where('staff_id', $user->id)->exists();
        $isManager = DB::table('projects')->where('manager_id', $user->id)->exists();

        // Admins are exempt from admin approval
        $needsApproval = $user->role !== 'admin' && ! $isStaff && ! $isManager;

        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'admin_approval' => $needsApproval,
            ],
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        try {
            // Get the currently authenticated user
            $user = JWTAuth::parseToken()->authenticate();

            if ($user) {
                // Update is_active to false
                $user->update(['is_active' => false]);
            }

            // Invalidate the current token
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'type' => 'success',
                'message' => 'Logged out successfully',
                'data' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'Failed to logout or token already invalidated.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    // Profile
    public function profile()
    {
        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => 'User profile retrieved successfully',
            'data' => auth()->user(),
        ]);
    }

    // Admin-only CRUD
    public function index(Request $request)
    {
        $authUserId = auth()->id(); // get currently authenticated user ID
        $search = $request->query('search'); // optional search keyword
        $perPage = $request->query('per_page', 10); // default to 10 per page

        $query = User::where('id', '!=', $authUserId);

        // If there's a search term, filter by name or email
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Paginate results
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => 'Users retrieved successfully',
            'data' => $users,
        ]);
    }

    // manager
    public function manager(Request $request)
    {
        $authUserId = auth()->id(); // currently authenticated user ID
        $search = $request->query('search'); // optional search keyword
        $perPage = $request->query('per_page', 10); // default 10 per page

        $query = User::where('id', '!=', $authUserId)
            ->where('role', 'project_manager'); // only project managers

        // If there's a search term, filter by name or email
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Paginate results
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => 'Project managers retrieved successfully',
            'data' => $users,
        ]);
    }

    // Admin-only for staff
    public function staff(Request $request)
    {
        $authUserId = auth()->id(); // currently authenticated user ID
        $search = $request->query('search'); // optional search keyword
        $perPage = $request->query('per_page', 10); // default 10 per page

        $query = User::where('id', '!=', $authUserId)
            ->where('role', 'warehouse_staff'); // only project managers

        // If there's a search term, filter by name or email
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Paginate results
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => 'Project managers retrieved successfully',
            'data' => $users,
        ]);
    }

    public function show($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'User not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => 'User retrieved successfully',
            'data' => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'User not found',
                'data' => null,
            ], 404);
        }

        $user_data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => ['sometimes', Rule::in(['admin', 'warehouse_staff', 'project_manager'])],
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($user_data);

        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => 'User updated successfully',
            'data' => $user,
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'User not found',
                'data' => null,
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => 'User deleted successfully',
            'data' => null,
        ]);
    }

    public function updateInfo(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'User not found',
                'data' => null,
            ], 404);
        }

        // Validation rules
        $rules = [
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            // 'currentPassword' => 'sometimes|string',
            // 'newPassword' => 'sometimes|string|min:6',
            // 'confirmPassword' => 'sometimes|string|same:newPassword',
        ];

        $validated = $request->validate($rules);

        // // 🔐 If changing password, check current password first
        // if (! empty($validated['newPassword'])) {
        //     if (empty($validated['currentPassword']) || ! Hash::check($validated['currentPassword'], $user->password)) {
        //         return response()->json([
        //             'success' => false,
        //             'type' => 'error',
        //             'message' => 'Current password is incorrect',
        //             'data' => null,
        //         ], 422);
        //     }
        //     // Password is correct, update it
        //     $user->password = Hash::make($validated['newPassword']);
        // }

        // ✅ Now update name/email
        $user->fill([
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
        ]);

        $user->save();

        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => 'User info updated successfully',
            'data' => $user,
        ]);
    }

    public function updatePassword(Request $request, $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'type' => 'error',
                'message' => 'User not found',
                'data' => null,
            ], 404);
        }

        // Validation rules
        $rules = [
            'currentPassword' => 'sometimes|string',
            'newPassword' => 'sometimes|string|min:6',
            'confirmPassword' => 'sometimes|string|same:newPassword',
        ];

        $validated = $request->validate($rules);

        // 🔐 If changing password, check current password first
        if (! empty($validated['newPassword'])) {
            if (empty($validated['currentPassword']) || ! Hash::check($validated['currentPassword'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'type' => 'error',
                    'message' => 'Current password is incorrect',
                    'data' => null,
                ], 422);
            }
            // Password is correct, update it
            $user->password = Hash::make($validated['newPassword']);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'type' => 'success',
            'message' => 'User info updated successfully',
            'data' => $user,
        ]);
    }
}
