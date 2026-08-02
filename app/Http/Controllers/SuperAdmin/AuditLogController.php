<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');
        $query = AuditLog::with('user');

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('action', 'like', "%{$request->search}%")
                    ->orWhere('table_name', 'like', "%{$request->search}%")
                    ->orWhere('record_id', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        if ($request->has('table_name') && $request->table_name) {
            $query->where('table_name', $request->table_name);
        }

        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(30);
        $users = User::orderBy('name')->get();
        $actions = AuditLog::distinct()->pluck('action')->filter();
        $tables = AuditLog::distinct()->pluck('table_name')->filter();

        return view('super-admin.audit-logs.index', compact('logs', 'users', 'actions', 'tables', 'userId'));
    }

    public function show(Request $request, string $id)
    {
        $userId = $request->route('userId');
        $log = AuditLog::with('user')->findOrFail($id);

        return view('super-admin.audit-logs.show', compact('log', 'userId'));
    }

    public function export(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('action', 'like', "%{$request->search}%")
                    ->orWhere('table_name', 'like', "%{$request->search}%");
            });
        }
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->orderBy('created_at', 'desc')->limit(1000)->get();

        $filename = 'audit-log-'.now()->format('Y-m-d-His').'.csv';
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['ID', 'User', 'Action', 'Table', 'Record ID', 'IP Address', 'Timestamp']);

        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->id,
                $log->user?->name ?? 'System',
                $log->action,
                $log->table_name,
                $log->record_id,
                $log->ip_address,
                $log->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response()->streamDownload(fn () => $content, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
