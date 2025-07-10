<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Validasi limit task berdasarkan plan pengguna
        $user = auth()->user();
        $plan = $user->plan;

        if ($plan && $plan->task_limit > 0 && $user->tasks()->count() >= $plan->task_limit) {
            return response()->json([
                'message' => 'You have reached the maximum number of tasks allowed for your plan.'
            ], 403); // Forbidden
        }

        $tasks = auth()->tasks()->with('subtasks')->get();
        return response()->json($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $plan = $user->plan;
        // Validasi jumlah task
        if ($plan && $plan->task_limit > 0 && $user->tasks()->count() >= $plan->task_limit) {
            return response()->json([
                'message' => 'You have reached the maximum number of tasks allowed for your plan.'
            ], 429); // Too Many Requests
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $task = $user->tasks()->create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        return response()->json($task, 201); // 201 Created
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Pastikan task ini milik pengguna yang login
        $task = auth()->tasks()->findOrFail($id);
        if (auth()->id() !== $task->user_id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $task->load('subtasks'); // Eager load subtasks
        return response()->json($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Pastikan task ini milik pengguna yang login
        $task = auth()->tasks()->findOrFail($id);
        if (auth()->id() !== $task->user_id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'is_completed' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $task->update($request->validated()); // Use validated data
        return response()->json($task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Pastikan task ini milik pengguna yang login
        $task = auth()->tasks()->findOrFail($id);
        if (auth()->id() !== $task->user_id) {
            return response()->json([
                'message' =>
                    'Unauthorized'
            ], 403);
        }

        $task->delete();
        return response()->json(['message' => 'Task deleted successfully']);

    }
}
