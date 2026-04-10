<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Ingredient;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseInvoiceController extends Controller
{
    public function index()
    {
        $invoices = PurchaseInvoice::with(['supplier', 'branch'])->latest()->get();
        return view('purchase_invoices.index', compact('invoices'));
    }

    public function create()
    {
        $suppliers = \App\Models\Supplier::all();
        // $branches removed as we store in central warehouse by default (branch_id = null)
        $ingredients = Ingredient::all();
        return view('purchase_invoices.create', compact('suppliers', 'ingredients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            // 'branch_id' => 'required|exists:branches,id', // Removed: Central Warehouse
            'invoice_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $invoice = PurchaseInvoice::create([
                'invoice_number' => 'INV-' . time(),
                'supplier_id' => $request->supplier_id,
                'branch_id' => null, // Default to Central Warehouse/Null
                'status' => 'draft',
                'payment_status' => 'unpaid',
                'invoice_date' => $request->invoice_date,
                'notes' => $request->notes,
                'total_amount' => 0,
            ]);

            $totalAmount = 0;
            $mergedItems = [];

            // 1. Merge duplicate ingredients
            foreach ($request->items as $item) {
                $id = $item['ingredient_id'];
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];
                $lineTotal = $quantity * $unitPrice;

                if (isset($mergedItems[$id])) {
                    $mergedItems[$id]['quantity'] += $quantity;
                    $mergedItems[$id]['total_price'] += $lineTotal;
                    // Recalculate unit price (weighted average)
                    if ($mergedItems[$id]['quantity'] > 0) {
                        $mergedItems[$id]['unit_price'] = $mergedItems[$id]['total_price'] / $mergedItems[$id]['quantity'];
                    }
                } else {
                    $mergedItems[$id] = [
                        'ingredient_id' => $id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $lineTotal,
                    ];
                }
            }

            // 2. Save items
            foreach ($mergedItems as $item) {
                $totalAmount += $item['total_price'];

                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity' => $item['quantity'],
                    // remaining_quantity logic: initially equals quantity
                    // But we need to make sure the column exists first (migration done previously)
                    'remaining_quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);
            }

            $invoice->update(['total_amount' => $totalAmount]);

            DB::commit();
            return redirect()->route('purchase_invoices.index')->with('success', 'Purchase Invoice created successfully as Draft.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating invoice: ' . $e->getMessage())->withInput();
        }
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        // Load items with their ingredients
        $purchaseInvoice->load(['items.ingredient', 'supplier', 'branch', 'approver']);
        return view('purchase_invoices.show', compact('purchaseInvoice'));
    }

    public function approve(PurchaseInvoice $purchaseInvoice)
    {
        if ($purchaseInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be approved.');
        }

        try {
            DB::beginTransaction();

            $purchaseInvoice->load('items.ingredient');

            // 1. Initialize remaining quantity on items
            foreach ($purchaseInvoice->items as $item) {
                $item->remaining_quantity = $item->quantity;
                $item->save();
            }

            // 2. Mark as Approved
            $purchaseInvoice->status = 'approved';
            $purchaseInvoice->approved_by = Auth::id() ?? 1;
            $purchaseInvoice->approved_at = \Illuminate\Support\Carbon::now();
            $purchaseInvoice->save();

            // 3. Recalculate Weighted Average Cost based on all valid invoices
            $ingredientIds = $purchaseInvoice->items->pluck('ingredient_id')->unique();

            foreach ($ingredientIds as $id) {
                $ingredient = Ingredient::find($id);
                if ($ingredient) {
                    $ingredient->recalculateCost();
                }
            }

            // 4. Trigger Accounting Scenario
            $merchantBillDebitAccount = \App\Models\AccountingEntityConfig::where('entity_type', 'MERCHANT_BILL_DEBIT')->value('parent_account_code') ?? '001'; // Fallback to 001 if not set

            \App\Services\AccountingEngine::trigger('PURCHASE_INVOICE_APPROVE', [
                'total_amount' => $purchaseInvoice->total_amount,
                'supplier_account' => $purchaseInvoice->supplier->account_code ?? '003', 
                'merchant_bill_debit_account' => $merchantBillDebitAccount,
                'bill_id' => $purchaseInvoice->id,
            ], [
                'reference_type' => 'purchase_invoice',
                'reference_id' => $purchaseInvoice->id,
                'description' => "Approved Purchase Invoice #{$purchaseInvoice->invoice_number}"
            ]);

            DB::commit();
            return back()->with('success', 'Invoice approved. Ingredient costs updated based on weighted average of remaining reliable stock.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error approving invoice: ' . $e->getMessage());
        }
    }

    public function edit(PurchaseInvoice $purchaseInvoice)
    {
        if ($purchaseInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be edited.');
        }
        $suppliers = \App\Models\Supplier::all();
        $ingredients = Ingredient::all();
        $purchaseInvoice->load('items');
        return view('purchase_invoices.edit', compact('purchaseInvoice', 'suppliers', 'ingredients'));
    }

    public function update(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        if ($purchaseInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be updated.');
        }

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $purchaseInvoice->update([
                'supplier_id' => $request->supplier_id,
                'invoice_date' => $request->invoice_date,
                'notes' => $request->notes,
            ]);

            // Clear old items and add new ones (synced)
            $purchaseInvoice->items()->delete();

            $totalAmount = 0;
            $mergedItems = [];
            foreach ($request->items as $item) {
                $id = $item['ingredient_id'];
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];
                $lineTotal = $quantity * $unitPrice;

                if (isset($mergedItems[$id])) {
                    $mergedItems[$id]['quantity'] += $quantity;
                    $mergedItems[$id]['total_price'] += $lineTotal;
                    if ($mergedItems[$id]['quantity'] > 0) {
                        $mergedItems[$id]['unit_price'] = $mergedItems[$id]['total_price'] / $mergedItems[$id]['quantity'];
                    }
                } else {
                    $mergedItems[$id] = [
                        'ingredient_id' => $id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $lineTotal,
                    ];
                }
            }

            foreach ($mergedItems as $item) {
                $totalAmount += $item['total_price'];
                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $purchaseInvoice->id,
                    'ingredient_id' => $item['ingredient_id'],
                    'quantity' => $item['quantity'],
                    'remaining_quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);
            }

            $purchaseInvoice->update(['total_amount' => $totalAmount]);

            DB::commit();
            return redirect()->route('purchase_invoices.index')->with('success', 'Invoice updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating invoice: ' . $e->getMessage());
        }
    }

    public function destroy(PurchaseInvoice $purchaseInvoice)
    {
        if ($purchaseInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be deleted.');
        }
        $purchaseInvoice->delete();
        return back()->with('success', 'Invoice deleted.');
    }

    public function duplicate(PurchaseInvoice $purchaseInvoice)
    {
        $suppliers = \App\Models\Supplier::all();
        $ingredients = Ingredient::all();
        $purchaseInvoice->load('items');
        
        // We pass the invoice to a view that will pre-fill the creation form
        return view('purchase_invoices.create', [
            'suppliers' => $suppliers,
            'ingredients' => $ingredients,
            'duplicateFrom' => $purchaseInvoice
        ]);
    }
}
