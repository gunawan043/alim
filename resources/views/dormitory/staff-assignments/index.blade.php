@extends('layouts.app')

@section('title', 'Staf Perizinan Scope Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Staf Perizinan Scope Management</h4>
                    <p class="text-muted">Configure which dormitories {{ $user->name }} can access as Staf Perizinan</p>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('user.staf-assignments.update', ['userId' => $user->id]) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">User</label>
                            <input type="text" class="form-control" value="{{ $user->name }} ({{ $user->email }})" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Select Dormitories to Access</label>
                            <div class="row">
                                @foreach($dormitories as $dorm)
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="dormitory_ids[]"
                                               value="{{ $dorm->id }}" id="dorm_{{ $dorm->id }}"
                                               {{ $existingAssignments->has($dorm->id) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="dorm_{{ $dorm->id }}">
                                            {{ $dorm->name }} ({{ $dorm->code }})
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date"
                                       value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">End Date (Optional)</label>
                                <input type="date" class="form-control" name="end_date"
                                       value="{{ $existingAssignments->first()?->end_date ?? '' }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"
                                      placeholder="Optional notes about this assignment...">{{ $existingAssignments->first()?->notes ?? '' }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line"></i> Save Scope
                        </button>
                    </form>

                    <hr class="my-4">

                    <h5>Current Active Assignments</h5>
                    @if($existingAssignments->isEmpty())
                        <p class="text-muted">No active assignments</p>
                    @else
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Dormitory</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($existingAssignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->dormitory->name }} ({{ $assignment->dormitory->code }})</td>
                                    <td>{{ $assignment->start_date->format('Y-m-d') }}</td>
                                    <td>{{ $assignment->end_date?->format('Y-m-d') ?? 'Ongoing' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $assignment->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($assignment->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($assignment->status === 'active')
                                        <form method="POST"
                                              action="{{ route('user.staf-assignments.destroy', ['userId' => $user->id, 'dormitoryId' => $assignment->dormitory_id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('End this assignment?')">
                                                <i class="ri-delete-bin-line"></i> End
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
