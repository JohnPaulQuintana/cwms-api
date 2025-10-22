<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;

class ProjectController extends Controller
{
    // 🔒 Require authentication
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * List all projects
     * Roles: Admin, Project Manager, Warehouse Staff
     */
    public function index()
    {
        $search = request('search'); // get search term from query params
        $perPage = request('per_page', 10); // optional per-page param

        $projects = Project::with('manager')
            ->latest()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }


    /**
     * Show specific project
     * Roles: Admin, Project Manager
     */
    public function show($id)
    {
        $project = Project::with('manager')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $project]);
    }

    /**
     * Create a new project
     * Roles: Admin only
     */
    public function store(Request $request)
    {
        $this->authorizeRole(['admin']);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $project = Project::create($validated);

        return response()->json(['success' => true, 'data' => $project], 201);
    }

    /**
     * Update an existing project
     * Roles: Admin or Project Manager
     */
    public function update(Request $request, $id)
    {
        $this->authorizeRole(['admin', 'project_manager']);

        $project = Project::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location' => 'nullable|string|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $project->update($validated);
        return response()->json(['success' => true, 'data' => $project]);
    }

    /**
     * Delete a project
     * Role: Admin only
     */
    public function destroy($id)
    {
        $this->authorizeRole(['admin']);

        $project = Project::findOrFail($id);
        $project->delete();

        return response()->json(['success' => true, 'message' => 'Project deleted']);
    }

    // 🧩 Utility function for role-based restrictions
    private function authorizeRole(array $roles)
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, $roles)) {
            abort(403, 'Access denied');
        }
    }
}
