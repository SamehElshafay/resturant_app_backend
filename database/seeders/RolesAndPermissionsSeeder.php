<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء Permissions
        $permissions = [
            // Branches
            ['name' => 'view_branches', 'display_name' => 'عرض الفروع', 'group' => 'branches'],
            ['name' => 'create_branches', 'display_name' => 'إضافة فروع', 'group' => 'branches'],
            ['name' => 'edit_branches', 'display_name' => 'تعديل الفروع', 'group' => 'branches'],
            ['name' => 'delete_branches', 'display_name' => 'حذف الفروع', 'group' => 'branches'],

            // Products
            ['name' => 'view_products', 'display_name' => 'عرض المنتجات', 'group' => 'products'],
            ['name' => 'create_products', 'display_name' => 'إضافة منتجات', 'group' => 'products'],
            ['name' => 'edit_products', 'display_name' => 'تعديل المنتجات', 'group' => 'products'],
            ['name' => 'delete_products', 'display_name' => 'حذف المنتجات', 'group' => 'products'],

            // Orders
            ['name' => 'view_orders', 'display_name' => 'عرض الطلبات', 'group' => 'orders'],
            ['name' => 'create_orders', 'display_name' => 'إنشاء طلبات', 'group' => 'orders'],
            ['name' => 'edit_orders', 'display_name' => 'تعديل الطلبات', 'group' => 'orders'],
            ['name' => 'cancel_orders', 'display_name' => 'إلغاء الطلبات', 'group' => 'orders'],

            // Accounting
            ['name' => 'view_accounts', 'display_name' => 'عرض الحسابات', 'group' => 'accounting'],
            ['name' => 'create_accounts', 'display_name' => 'إنشاء حسابات', 'group' => 'accounting'],
            ['name' => 'view_reports', 'display_name' => 'عرض التقارير', 'group' => 'accounting'],

            // Purchase Invoices
            ['name' => 'view_purchases', 'display_name' => 'عرض فواتير الشراء', 'group' => 'purchases'],
            ['name' => 'create_purchases', 'display_name' => 'إنشاء فواتير الشراء', 'group' => 'purchases'],
            ['name' => 'approve_purchases', 'display_name' => 'اعتماد فواتير الشراء', 'group' => 'purchases'],

            // Expenses
            ['name' => 'view_expenses', 'display_name' => 'عرض المصروفات', 'group' => 'expenses'],
            ['name' => 'create_expenses', 'display_name' => 'إضافة مصروفات', 'group' => 'expenses'],

            // Users & Roles
            ['name' => 'manage_users', 'display_name' => 'إدارة المستخدمين', 'group' => 'users'],
            ['name' => 'manage_roles', 'display_name' => 'إدارة الصلاحيات', 'group' => 'users'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                array_merge($permission, ['guard_name' => 'web'])
            );
        }

        // إنشاء Roles
        $admin = Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'مدير النظام',
                'description' => 'صلاحيات كاملة',
                'guard_name' => 'web'
            ]
        );

        $manager = Role::updateOrCreate(
            ['name' => 'manager'],
            [
                'display_name' => 'مدير',
                'description' => 'إدارة الفرع والموظفين',
                'guard_name' => 'web'
            ]
        );

        $cashier = Role::updateOrCreate(
            ['name' => 'cashier'],
            [
                'display_name' => 'كاشير',
                'description' => 'إنشاء الطلبات فقط',
                'guard_name' => 'web'
            ]
        );

        $accountant = Role::updateOrCreate(
            ['name' => 'accountant'],
            [
                'display_name' => 'محاسب',
                'description' => 'إدارة الحسابات والتقارير',
                'guard_name' => 'web'
            ]
        );

        // ربط الصلاحيات بالـ Roles
        $allPermissions = Permission::all()->pluck('id');

        // Admin: كل الصلاحيات
        $admin->permissions()->sync($allPermissions);

        // Manager: معظم الصلاحيات إلا إدارة المستخدمين
        $managerPermissions = Permission::whereNotIn('name', ['manage_users', 'manage_roles'])->pluck('id');
        $manager->permissions()->sync($managerPermissions);

        // Cashier: الطلبات فقط
        $cashierPermissions = Permission::whereIn('name', ['view_products', 'view_orders', 'create_orders'])->pluck('id');
        $cashier->permissions()->sync($cashierPermissions);

        // Accountant: الحسابات والتقارير
        $accountantPermissions = Permission::where('group', 'accounting')->pluck('id');
        $accountant->permissions()->sync($accountantPermissions);
    }
}
