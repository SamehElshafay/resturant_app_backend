<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    public function __construct(private VoucherService $voucherService)
    {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Index
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $type = $request->get('type');   // RECEIPT, PAYMENT, TRANSFER
        $status = $request->get('status'); // DRAFT, POSTED, CANCELLED

        $vouchers = Voucher::with(['createdBy'])
            ->when($type, fn($q) => $q->where('voucher_type', $type))
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('vouchers.index', compact('vouchers', 'type', 'status'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Create
    // ─────────────────────────────────────────────────────────────────────────

    public function create()
    {
        // Eager load full account tree (flat, for JS dropdown building)
        $accounts = Account::orderBy('code')->get(['id', 'name', 'name_ar', 'name_en', 'code', 'parent_id']);
        return view('vouchers.create', compact('accounts'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Store (DRAFT)
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'voucher_type' => 'required|in:RECEIPT,PAYMENT,TRANSFER',
            'account_code' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'cash_amount' => 'nullable|numeric|min:0',
            'bank_amount' => 'nullable|numeric|min:0',
            'treasury_account_code' => 'nullable|string',
            'bank_account_code' => 'nullable|string',
            'recipient_account_code' => 'nullable|string',
            'expense_type' => 'nullable|in:ADMINISTRATIVE,OPERATIONAL,NONE',
            'note' => 'nullable|string',
            'reference_number' => 'nullable|string',
            'entity_type' => 'nullable|string',
        ]);

        $cash = (float) ($request->cash_amount ?? 0);
        $bank = (float) ($request->bank_amount ?? 0);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $createdVouchers = [];

            // Case 1: Cash Voucher
            if ($cash > 0) {
                $vCash = Voucher::create(array_merge($validated, [
                    'amount' => $cash,
                    'cash_amount' => $cash,
                    'bank_amount' => 0,
                    'bank_account_code' => null,
                    'status' => 'DRAFT',
                    'created_by' => auth()->id(),
                ]));
                $createdVouchers[] = $vCash;
            }

            // Case 2: Bank Voucher
            if ($bank > 0) {
                $vBank = Voucher::create(array_merge($validated, [
                    'amount' => $bank,
                    'cash_amount' => 0,
                    'bank_amount' => $bank,
                    'treasury_account_code' => null,
                    'status' => 'DRAFT',
                    'created_by' => auth()->id(),
                ]));
                $createdVouchers[] = $vBank;
            }

            \Illuminate\Support\Facades\DB::commit();

            $lastVoucher = end($createdVouchers);
            return response()->json([
                'success' => true,
                'message' => count($createdVouchers) > 1 ? 'Two vouchers created (Cash & Bank)' : 'Voucher created successfully',
                'id' => $lastVoucher ? $lastVoucher->id : null
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Show
    // ─────────────────────────────────────────────────────────────────────────

    public function show(Voucher $voucher)
    {
        $voucher->load(['createdBy', 'postedBy', 'journalEntries']);

        // Fetch account names for the codes used in the voucher
        $codes = array_filter([
            $voucher->account_code,
            $voucher->recipient_account_code,
            $voucher->treasury_account_code,
            $voucher->bank_account_code
        ]);

        $accountNames = Account::whereIn('code', $codes)
            ->get(['code', 'name', 'name_ar', 'name_en'])
            ->keyBy('code');

        return view('vouchers.show', compact('voucher', 'accountNames'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Post (DRAFT → POSTED)
    // ─────────────────────────────────────────────────────────────────────────

    public function post(Voucher $voucher)
    {
        try {
            $this->voucherService->post($voucher, Auth::id());

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => app()->getLocale() === 'ar' ? 'تم اعتماد السند بنجاح' : 'Voucher posted successfully.',
                ]);
            }

            return back()->with('success', 'Voucher posted and journal entries created.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to post voucher',
                    'details' => $e->getMessage(),
                ], 500);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cancel
    // ─────────────────────────────────────────────────────────────────────────

    public function cancel(Voucher $voucher)
    {
        if (!$voucher->isDraft()) {
            return back()->with('error', 'Only DRAFT vouchers can be cancelled.');
        }
        $voucher->update(['status' => 'CANCELLED']);
        return back()->with('success', 'Voucher cancelled.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // API: Account Tree (for filtered dropdown)
    // ─────────────────────────────────────────────────────────────────────────

    public function accountTree(Request $request)
    {
        $search = $request->get('search');
        $parentCode = $request->get('parent_code');

        $accounts = Account::query()
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%");
            }))
            ->when($parentCode, fn($q) => $q->where('code', 'like', "{$parentCode}-%"))
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'name_ar', 'parent_id']);

        return response()->json($accounts);
    }
}
