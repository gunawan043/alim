@extends('layouts.master')
@section('title') 管理宿舍 @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('title') 管理宿舍 @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">宿舍管理</h5>
                            <p class="text-muted mb-0">管理系统中的所有宿舍。</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.sa.dormitories.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> 添加宿舍
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="搜索宿舍名称/代码..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="gender" class="form-control">
                                <option value="">全部性别</option>
                                <option value="putra" {{ request('gender') == 'putra' ? 'selected' : '' }}>男宿</option>
                                <option value="putri" {{ request('gender') == 'putri' ? 'selected' : '' }}>女宿</option>
                                <option value="campuran" {{ request('gender') == 'campuran' ? 'selected' : '' }}>混合</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-control">
                                <option value="">全部状态</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>启用</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>停用</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="ri-search-line me-1"></i> 筛选</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('user.sa.dormitories.index', ['userId' => $userId]) }}" class="btn btn-light w-100">重置</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>宿舍名称</th>
                                    <th>代码</th>
                                    <th>性别</th>
                                    <th>所属学校</th>
                                    <th>容量</th>
                                    <th>主管</th>
                                    <th>状态</th>
                                    <th class="text-center">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dormitories as $dorm)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar-xs">
                                                    <div class="avatar-title bg-{{ $dorm->gender == 'putra' ? 'primary' : ($dorm->gender == 'putri' ? 'danger' : 'info') }}-subtle text-{{ $dorm->gender == 'putra' ? 'primary' : ($dorm->gender == 'putri' ? 'danger' : 'info') }} rounded-circle">
                                                        {{ $dorm->gender == 'putra' ? '男' : ($dorm->gender == 'putri' ? '女' : '混') }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $dorm->name }}</div>
                                                    <small class="text-muted">{{ $dorm->address ?? '' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-secondary">{{ $dorm->code }}</span></td>
                                        <td>
                                            <span class="badge {{ $dorm->gender == 'putra' ? 'bg-primary-subtle text-primary' : ($dorm->gender == 'putri' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info') }}">
                                                {{ $dorm->gender == 'putra' ? '男宿' : ($dorm->gender == 'putri' ? '女宿' : '混合') }}
                                            </span>
                                        </td>
                                        <td><small>{{ $dorm->school?->name ?? '-' }}</small></td>
                                        <td>
                                            <small>{{ $dorm->capacity ?? 0 }}人</small>
                                            @if($dorm->total_rooms)
                                                <br><small class="text-muted">{{ $dorm->total_rooms }}间房</small>
                                            @endif
                                        </td>
                                        <td><small>{{ $dorm->head?->name ?? '-' }}</small></td>
                                        <td>
                                            <span class="badge {{ $dorm->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                {{ $dorm->is_active ? '启用' : '停用' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.sa.dormitories.edit', ['userId' => $userId, 'id' => $dorm->id]) }}">
                                                            <i class="ri-pencil-line text-primary me-2"></i>编辑
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item toggle-status-btn" data-id="{{ $dorm->id }}" data-active="{{ $dorm->is_active }}">
                                                            <i class="ri-{{ $dorm->is_active ? 'pause-circle' : 'play-circle' }} me-2"></i>
                                                            {{ $dorm->is_active ? '停用' : '启用' }}
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger delete-dorm" data-id="{{ $dorm->id }}" data-name="{{ $dorm->name }}">
                                                            <i class="ri-delete-bin-line me-2"></i>删除
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">暂无宿舍数据。</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($dormitories->hasPages())
                        @include('shared._pagination', ['paginator' => $dormitories])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade zoomIn" id="deleteDormModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                    <h4 class="mt-3">删除宿舍?</h4>
                    <p class="text-muted">宿舍 <strong id="deleteDormName"></strong> 将被永久删除。</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">取消</button>
                    <form id="deleteDormForm" method="POST" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger">确认删除</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle status
        document.querySelectorAll('.toggle-status-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                fetch(`/{{ $userId }}/sa/dormitories/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                }).then(r => r.json()).then(data => {
                    if (data.success) location.reload();
                });
            });
        });

        // Delete dorm
        document.querySelectorAll('.delete-dorm').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('deleteDormName').textContent = this.dataset.name;
                document.getElementById('deleteDormForm').action = `/{{ $userId }}/sa/dormitories/${this.dataset.id}`;
                new bootstrap.Modal(document.getElementById('deleteDormModal')).show();
            });
        });
    });
    </script>
@endsection
