<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use App\Models\Account;
use App\Models\Category;
use App\Models\Product;
use App\Models\BranchProduct;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Main Branch
        $branch = Branch::create([
            'name' => 'Main Branch',
            'address' => 'Cairo, Egypt',
            'phone' => '0123456789',
        ]);

        // 2. Create Accounts for Branch
        $parentAccount = Account::create([
            'branch_id' => $branch->id,
            'name' => 'Main Branch Assets',
            'code' => '1000',
            'type' => 1, // Asset
        ]);

        $branch->update(['parent_account_id' => $parentAccount->id]);

        // 3. Create Admin User
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@pos.com',
            'password' => Hash::make('password'),
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        // 4. Create Cashier User
        $cashier = User::create([
            'name' => 'John Cashier',
            'email' => 'cashier@pos.com',
            'password' => Hash::make('password'),
            'branch_id' => $branch->id,
            'salary' => 5000,
            'is_active' => true,
        ]);
        $cashier->assignRole('cashier');

        // 5. Some Categories & Products
        $categories = [
            ['name' => 'Appetizers', 'printer_ip' => '192.168.1.50'],
            ['name' => 'Main Course', 'printer_ip' => '192.168.1.51'],
            ['name' => 'Desserts', 'printer_ip' => '192.168.1.52'],
        ];

        foreach ($categories as $catData) {
            $cat = Category::create($catData);

            // Add 2 products per category
            for ($i = 1; $i <= 2; $i++) {
                $product = Product::create([
                    'category_id' => $cat->id,
                    'name' => $cat->name . " Item " . $i,
                    'base_purchase_price' => rand(10, 50),
                ]);

                BranchProduct::create([
                    'branch_id' => $branch->id,
                    'product_id' => $product->id,
                    'price' => $product->base_purchase_price * 2,
                ]);
            }
        }
    }
}
