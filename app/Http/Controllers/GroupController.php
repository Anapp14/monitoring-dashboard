<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonitorGroup;
use App\Models\MonitorGroupItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    /**
     * Get all groups with their monitor IDs
     */
    public function index()
    {
        try {
            $groups = MonitorGroup::with('items')->orderBy('name')->get();

            $groupsData = $groups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'monitor_ids' => $group->monitor_ids,
                    'created_at' => $group->created_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $groupsData
            ]);

        } catch (\Exception $e) {
            Log::error('Get Groups Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch groups'
            ], 500);
        }
    }

    /**
     * Create new group
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:monitor_groups,name'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $group = MonitorGroup::create([
                'name' => $request->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Group created successfully',
                'data' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'monitor_ids' => [],
                    'created_at' => $group->created_at->toISOString(),
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Create Group Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create group'
            ], 500);
        }
    }

    /**
     * Update group name
     */
    public function update(Request $request, $id)
    {
        try {
            $group = MonitorGroup::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:monitor_groups,name,' . $id
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $group->update(['name' => $request->name]);

            return response()->json([
                'success' => true,
                'message' => 'Group updated successfully',
                'data' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'monitor_ids' => $group->monitor_ids,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Update Group Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update group'
            ], 500);
        }
    }

    /**
     * Delete group
     */
    public function destroy($id)
    {
        try {
            $group = MonitorGroup::findOrFail($id);
            $group->delete(); // Will also delete items due to cascade

            return response()->json([
                'success' => true,
                'message' => 'Group deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete Group Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete group'
            ], 500);
        }
    }

    /**
     * Assign monitor to group
     */
    public function assignMonitor(Request $request, $groupId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'monitor_id' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $group = MonitorGroup::findOrFail($groupId);

            // Check if monitor already in this group
            $exists = MonitorGroupItem::where('group_id', $groupId)
                ->where('monitor_id', $request->monitor_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Monitor already in this group'
                ], 422);
            }

            MonitorGroupItem::create([
                'group_id' => $groupId,
                'monitor_id' => $request->monitor_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Monitor assigned to group successfully',
                'data' => [
                    'group_id' => $group->id,
                    'monitor_ids' => $group->fresh()->monitor_ids
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Assign Monitor Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign monitor'
            ], 500);
        }
    }

    /**
     * Remove monitor from group
     */
    public function removeMonitor(Request $request, $groupId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'monitor_id' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $group = MonitorGroup::findOrFail($groupId);

            MonitorGroupItem::where('group_id', $groupId)
                ->where('monitor_id', $request->monitor_id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Monitor removed from group successfully',
                'data' => [
                    'group_id' => $group->id,
                    'monitor_ids' => $group->fresh()->monitor_ids
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Remove Monitor Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove monitor'
            ], 500);
        }
    }
}