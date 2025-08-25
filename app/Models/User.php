<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_EMPLOYEE = 'employee';
    const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'permissions',
        'phone',
        'address'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'permissions' => 'array',
        'password' => 'hashed',
    ];

    /**
     * Get user's managed warehouses
     */
    public function managedWarehouses()
    {
        return $this->hasMany(\App\Models\PostgreSQL\Warehouse::class, 'manager_id');
    }

    /**
     * Get user's stock movements
     */
    public function stockMovements()
    {
        return $this->hasMany(\App\Models\PostgreSQL\StockMovement::class);
    }

    /**
     * Get user's purchase orders
     */
    public function purchaseOrders()
    {
        return $this->hasMany(\App\Models\PostgreSQL\PurchaseOrder::class);
    }

    /**
     * Check if user has role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->role === self::ROLE_ADMIN) {
            return true; // Admin has all permissions
        }

        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * Check if user can access inventory
     */
    public function canAccessInventory(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_EMPLOYEE
        ]) || $this->hasPermission('access_inventory');
    }

    /**
     * Check if user can manage products
     */
    public function canManageProducts(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_MANAGER
        ]) || $this->hasPermission('manage_products');
    }

    /**
     * Check if user can view reports
     */
    public function canViewReports(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_MANAGER
        ]) || $this->hasPermission('view_reports');
    }

    /**
     * Check if user can adjust stock
     */
    public function canAdjustStock(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_EMPLOYEE
        ]) || $this->hasPermission('adjust_stock');
    }

    /**
     * Check if user can manage warehouses
     */
    public function canManageWarehouses(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_MANAGER
        ]) || $this->hasPermission('manage_warehouses');
    }

    /**
     * Check if user can manage categories
     */
    public function canManageCategories(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_MANAGER
        ]) || $this->hasPermission('manage_categories');
    }

    /**
     * Check if user can manage suppliers
     */
    public function canManageSuppliers(): bool
    {
        return $this->hasAnyRole([
            self::ROLE_ADMIN,
            self::ROLE_MANAGER
        ]) || $this->hasPermission('manage_suppliers');
    }

    /**
     * Check if user can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN) || $this->hasPermission('manage_users');
    }

    /**
     * Get available roles
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_EMPLOYEE => 'Employee',
            self::ROLE_USER => 'User'
        ];
    }

    /**
     * Get available permissions
     */
    public static function getPermissions(): array
    {
        return [
            'access_inventory' => 'Access Inventory',
            'manage_products' => 'Manage Products',
            'adjust_stock' => 'Adjust Stock',
            'view_reports' => 'View Reports',
            'manage_warehouses' => 'Manage Warehouses',
            'manage_categories' => 'Manage Categories',
            'manage_suppliers' => 'Manage Suppliers',
            'manage_users' => 'Manage Users',
            'create_purchase_orders' => 'Create Purchase Orders',
            'approve_purchase_orders' => 'Approve Purchase Orders',
            'receive_inventory' => 'Receive Inventory',
            'transfer_stock' => 'Transfer Stock',
            'perform_audits' => 'Perform Audits',
            'export_data' => 'Export Data'
        ];
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for users with specific role
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Get full name attribute
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Check if user is manager
     */
    public function isManager(): bool
    {
        return $this->hasRole(self::ROLE_MANAGER);
    }

    /**
     * Check if user is employee
     */
    public function isEmployee(): bool
    {
        return $this->hasRole(self::ROLE_EMPLOYEE);
    }
}