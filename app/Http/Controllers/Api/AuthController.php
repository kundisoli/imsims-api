<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MongoDB\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact administrator.'
            ], 403);
        }

        // Create token
        $deviceName = $request->device_name ?? $request->userAgent();
        $token = $user->createToken($deviceName)->plainTextToken;

        // Update last login
        $user->updateLastLogin();

        // Log the login
        AuditLog::logEvent([
            'user_id' => $user->id,
            'action' => 'login',
            'model_type' => User::class,
            'model_id' => $user->id,
            'metadata' => [
                'device_name' => $deviceName,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]
        ]);

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $this->getUserPermissions($user),
                'last_login_at' => $user->last_login_at
            ]
        ]);
    }

    /**
     * Register new user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'string|in:admin,manager,employee,user',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if current user can create users
        if (auth()->check() && !auth()->user()->canManageUsers()) {
            return response()->json(['message' => 'Unauthorized to create users'], 403);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? User::ROLE_USER,
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => true
        ]);

        // Log the registration
        AuditLog::logCreated($user);

        $token = $user->createToken($request->device_name ?? 'default')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $this->getUserPermissions($user)
            ]
        ], 201);
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Log the logout
        AuditLog::logEvent([
            'user_id' => $user->id,
            'action' => 'logout',
            'model_type' => User::class,
            'model_id' => $user->id
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Logout from all devices (revoke all tokens)
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Log the logout from all devices
        AuditLog::logEvent([
            'user_id' => $user->id,
            'action' => 'logout_all_devices',
            'model_type' => User::class,
            'model_id' => $user->id
        ]);

        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all devices successfully'
        ]);
    }

    /**
     * Get current user information
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'address' => $user->address,
                'is_active' => $user->is_active,
                'permissions' => $this->getUserPermissions($user),
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at
            ]
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'current_password' => 'required_with:password',
            'password' => 'nullable|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify current password if changing password
        if ($request->has('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'errors' => ['current_password' => ['Current password is incorrect']]
                ], 422);
            }
        }

        $oldValues = $user->getOriginal();
        
        $updateData = $request->only(['name', 'email', 'phone', 'address']);
        
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Log the update
        AuditLog::logUpdated($user, $oldValues, $user->getChanges());

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'address' => $user->address,
                'permissions' => $this->getUserPermissions($user)
            ]
        ]);
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'errors' => ['current_password' => ['Current password is incorrect']]
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Log the password change
        AuditLog::logEvent([
            'user_id' => $user->id,
            'action' => 'password_changed',
            'model_type' => User::class,
            'model_id' => $user->id
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Get user permissions based on role
     */
    private function getUserPermissions(User $user): array
    {
        $basePermissions = $user->permissions ?? [];

        // Add role-based permissions
        $rolePermissions = match ($user->role) {
            User::ROLE_ADMIN => array_keys(User::getPermissions()),
            User::ROLE_MANAGER => [
                'access_inventory', 'manage_products', 'adjust_stock', 'view_reports',
                'manage_warehouses', 'manage_categories', 'manage_suppliers',
                'create_purchase_orders', 'approve_purchase_orders', 'receive_inventory',
                'transfer_stock', 'perform_audits', 'export_data'
            ],
            User::ROLE_EMPLOYEE => [
                'access_inventory', 'adjust_stock', 'receive_inventory', 'transfer_stock'
            ],
            User::ROLE_USER => ['access_inventory'],
            default => []
        };

        return array_unique(array_merge($basePermissions, $rolePermissions));
    }
}