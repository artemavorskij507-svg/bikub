<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrganizationController extends Controller
{
    /**
     * Display a listing of organizations
     */
    public function index(Request $request)
    {
        $query = Organization::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $organizations = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $organizations,
            'count' => $organizations->count(),
        ]);
    }

    /**
     * Store a newly created organization
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:organizations,slug',
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $organization = Organization::create($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $organization,
            'message' => 'Organization created successfully',
        ], 201);
    }

    /**
     * Display the specified organization
     */
    public function show($id)
    {
        $organization = Organization::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $organization,
        ]);
    }

    /**
     * Update the specified organization
     */
    public function update(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:organizations,slug,'.$id,
            'email' => 'sometimes|email|max:255',
            'phone' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $organization->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $organization->fresh(),
            'message' => 'Organization updated successfully',
        ]);
    }

    /**
     * Remove the specified organization
     */
    public function destroy($id)
    {
        $organization = Organization::findOrFail($id);
        $organization->delete();

        return response()->json([
            'success' => true,
            'message' => 'Organization deleted successfully',
        ]);
    }

    /**
     * Add user to organization
     */
    public function addUser(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Attach user to organization (assuming pivot table exists)
        $organization->users()->attach($request->user_id, [
            'role' => $request->role ?? 'member',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User added to organization successfully',
        ]);
    }

    /**
     * Remove user from organization
     */
    public function removeUser($id, $userId)
    {
        $organization = Organization::findOrFail($id);
        $organization->users()->detach($userId);

        return response()->json([
            'success' => true,
            'message' => 'User removed from organization successfully',
        ]);
    }

    /**
     * Alias for store method (for backward compatibility)
     */
    public function create(Request $request)
    {
        return $this->store($request);
    }
}
