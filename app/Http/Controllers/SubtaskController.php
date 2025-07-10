<?php

namespace App\Http\Controllers;

use App\Models\Subtask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubtaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Pastikan task ini milik pengguna yang login
        $id = request()->query('task_id');

        if (!$id) {
            return response()->json([
                'message' => 'Task ID is required'
            ], 400);
        }

        $task = auth()->tasks()->findOrFail($id);
        if (auth()->id() !== $task->user_id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json($task->subtasks()->get());
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Pastikan task ini milik pengguna yang login\
        $id = $request->query('task_id');

        if (!$id) {
            return response()->json([
                'message' => 'Task ID is required'
            ], 400);
        }

        $task = auth()->tasks()->findOrFail($id);
        if (auth()->id() !== $task->user_id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Validasi jumlah subtask
        $user = auth()->user();
        $plan = $user->plan;
        $currentSubtaskCount = $task->subtasks()->count();
        if (
            $plan && $plan->task_limit > 0 &&
            $currentSubtaskCount >= $plan->task_limit
        ) { // Contoh sederhana
            return response()->json([
                'message' => 'You have reached the maximum number of subtasks allowed for this task plan.'
            ], 429);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $subtask = $task->subtasks()->create([
            'title' => $request->title,
            'description' => $request->description, // 'description' bisa ditambahkan jika ada di model/migration
        ]);

        return response()->json($subtask, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $subtask = Subtask::findOrFail($id);
        $task = $subtask->task;
        if (auth()->id() !== $task->user_id) {
            return response()->json(['message' => 'Unauthorized or invalid resource'], 403);
        }

        return response()->json($subtask);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $subtask = Subtask::findOrFail($id);
        $task = $subtask->task;
        if (auth()->id() !== $task->user_id) {
            return response()->json([
                'message' => 'Unauthorized or invalid resource'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'is_completed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $subtask->update($request->validated());
        return response()->json($subtask);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $subtask = Subtask::findOrFail($id);
        $task = $subtask->task;

        if (auth()->id() !== $task->user_id || $subtask->task_id !== $task->id) {
            return response()->json([
                'message' => 'Unauthorized or invalid resource'
            ], 403);
        }

        $subtask->delete();
        return response()->json([
            'message' => 'Subtask deleted successfully'
        ]);
    }
}
