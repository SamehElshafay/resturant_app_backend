<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * List all employees for admin.
     */
    public function index()
    {
        return User::with(['roles', 'branch', 'documents.documentType'])->get();
    }

    /**
     * Store a new employee (Cashier, Driver, etc.)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'branch_id' => 'required|exists:branches,id',
            'role' => 'required|string|exists:roles,name',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'commission_rate' => 'nullable|numeric',
            'image' => 'nullable|image',
        ]);

        $userData = $validated;
        $userData['password'] = Hash::make($request->password);

        if ($request->hasFile('image')) {
            $userData['image'] = $request->file('image')->store('users', 'public');
        }

        $user = User::create($userData);
        $user->assignRole($request->role);

        return response()->json($user->load('roles'), 201);
    }

    /**
     * Upload dynamic documents for an employee.
     */
    public function uploadDocument(Request $request, User $user)
    {
        $request->validate([
            'document_type_id' => 'required|exists:document_types,id',
            'file' => 'required|file|max:5120', // 5MB max
        ]);

        $path = $request->file('file')->store('documents/' . $user->id, 'public');

        $doc = UserDocument::create([
            'user_id' => $user->id,
            'document_type_id' => $request->document_type_id,
            'file_path' => $path,
        ]);

        return response()->json($doc, 201);
    }
}
