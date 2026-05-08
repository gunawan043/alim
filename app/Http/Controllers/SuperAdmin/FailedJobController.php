<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FailedJobController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->route('userId');

        if (!config('queue..failed')) {
            return view('super-admin.failed-jobs.index', [
                'jobs' => collect([]),
                'hasTable' => false,
                'userId' => $userId,
            ]);
        }

        try {
            $query = DB::table('failed_jobs');

            if ($request->has('search') && $request->search) {
                $query->where('displayName', 'like', "%{$request->search}%");
            }

            if ($request->has('from_date') && $request->from_date) {
                $query->whereDate('failed_at', '>=', $request->from_date);
            }

            if ($request->has('to_date') && $request->to_date) {
                $query->whereDate('failed_at', '<=', $request->to_date);
            }

            $jobs = $query->orderBy('failed_at', 'desc')->paginate(20);
            $hasTable = true;

            return view('super-admin.failed-jobs.index', compact('jobs', 'hasTable', 'userId'));
        } catch (\Exception $e) {
            return view('super-admin.failed-jobs.index', [
                'jobs'    => collect([]),
                'hasTable' => false,
                'error'  => 'Tabel failed_jobs tidak ditemukan.',
                'userId' => $userId,
            ]);
        }
    }

    public function retry(string $id)
    {
        if (!config('queue..failed')) {
            return back()->with('error', 'Failed job table tidak dikonfigurasi.');
        }

        try {
            $job = DB::table('failed_jobs')->where('id', $id)->first();

            if (!$job) {
                return back()->with('error', 'Job tidak ditemukan.');
            }

            // Hapus dari failed_jobs
            DB::table('failed_jobs')->where('id', $id)->delete();

            // Dispatch ulang job
            $jobData = json_decode($job->payload, true);
            $connection = $job->connection ?? config('queue.default');
            \Illuminate\Support\Facades\Queue::connection($connection)->pushRaw(
                $job->payload,
                $job->queue ?? 'default'
            );

            return redirect()->route('super-admin.failed-jobs.index')
                ->with('success', 'Job berhasil di-retry dan dipindahkan ke queue.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal me-retry job: ' . $e->getMessage());
        }
    }

    public function retryAll()
    {
        if (!config('queue._failed')) {
            return back()->with('error', 'Failed job table tidak dikonfigurasi.');
        }

        try {
            $count = DB::table('failed_jobs')->count();
            \Illuminate\Support\Facades\Artisan::call('queue:retry', ['all']);

            return redirect()->route('super-admin.failed-jobs.index')
                ->with('success', "{$count} job berhasil di-retry semua.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal me-retry semua: ' . $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        if (!config('queue.failed')) {
            return back()->with('error', 'Failed job table tidak dikonfigurasi.');
        }

        DB::table('failed_jobs')->where('id', $id)->delete();

        return redirect()->route('super-admin.failed-jobs.index')
            ->with('success', 'Failed job berhasil dihapus.');
    }

    public function flush()
    {
        if (!config('queue.failed')) {
            return back()->with('error', 'Failed job table tidak dikonfigurasi.');
        }

        DB::table('failed_jobs')->truncate();

        return redirect()->route('super-admin.failed-jobs.index')
            ->with('success', 'Semua failed job berhasil dihapus.');
    }
}
