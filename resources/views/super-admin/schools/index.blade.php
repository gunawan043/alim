@extends('layouts.master')
@section('title') 管理学校 @endsection
@section('css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('title') 管理学校 @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <div class="row g-4 align-items-center">
                        <div class="col-sm">
                            <h5 class="card-title mb-0">学校管理</h5>
                            <p class="text-muted mb-0">管理系统中的所有学校。</p>
                        </div>
                        <div class="col-sm-auto">
                            <a href="{{ route('user.sa.schools.create', ['userId' => $userId]) }}" class="btn btn-success">
                                <i class="ri-add-line align-bottom me-1"></i> 添加学校
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filter --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="搜索学校名称/NPSN..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="level" class="form-control">
                                <option value="">全部级别</option>
                                <option value="sd" {{ request('level') == 'sd' ? 'selected' : '' }}>小学</option>
                                <option value="smp" {{ request('level') == 'smp' ? 'selected' : '' }}>初中</option>
                                <option value="sma" {{ request('level') == 'sma' ? 'selected' : '' }}>高中</option>
                                <option value="smk" {{ request('level') == 'smk' ? 'selected' : '' }}>职校</option>
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
                            <a href="{{ route('user.sa.schools.index', ['userId' => $userId]) }}" class="btn btn-light w-100">重置</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>学校名称</th>
                                    <th>级别</th>
                                    <th>NPSN</th>
                                    <th>所属单位</th>
                                    <th>校长</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                    <th class="text-center">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schools as $school)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ $school->logo_url }}" alt="logo" class="rounded" width="40" height="40" style="object-fit:cover;">
                                                <div>
                                                    <div class="fw-bold">{{ $school->name }}</div>
                                                    <small class="text-muted">{{ $school->school_code ?? '-' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-primary-subtle text-primary">{{ $school->level_text }}</span></td>
                                        <td><small>{{ $school->npsn ?? '-' }}</small></td>
                                        <td><small>{{ $school->workUnit?->name ?? '-' }}</small></td>
                                        <td><small>{{ $school->principalUser?->name ?? '-' }}</small></td>
                                        <td>
                                            <span class="badge {{ $school->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                {{ $school->is_active ? '启用' : '停用' }}
                                            </span>
                                        </td>
                                        <td><small class="text-muted">{{ $school->created_at?->format('Y-m-d') }}</small></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-soft-secondary" data-bs-toggle="dropdown">
                                                    <i class="ri-more-2-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('user.sa.schools.edit', ['userId' => $userId, 'id' => $school->id]) }}">
                                                            <i class="ri-pencil-line text-primary me-2"></i>编辑
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item toggle-status-btn" data-id="{{ $school->id }}" data-active="{{ $school->is_active }}">
                                                            <i class="ri-{{ $school->is_active ? 'pause-circle' : 'play-circle' }} me-2"></i>
                                                            {{ $school->is_active ? '停用' : '启用' }}
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger delete-school" data-id="{{ $school->id }}" data-name="{{ $school->name }}">
                                                            <i class="ri-delete-bin-line me-2"></i>删除
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">暂无学校数据。</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($schools->hasPages())
                        @include('shared._pagination', ['paginator' => $schools])
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <div class="modal fade zoomIn" id="deleteSchoolModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:80px;height:80px"></lord-icon>
                    <h4 class="mt-3">删除学校?</h4>
                    <p class="text-muted">学校 <strong id="deleteSchoolName"></strong> 将被永久删除。</p>
                </div>
                <div class="modal-footer justify-content-center gap-2">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">取消</button>
                    <form id="deleteSchoolForm" method="POST" style="display:inline">
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
                const isActive = this.dataset.active === 'true';
                fetch(`/{{ $userId }}/sa/schools/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                }).then(r => r.json()).then(data => {
                    if (data.success) location.reload();
                });
            });
        });

        // Delete school
        document.querySelectorAll('.delete-school').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('deleteSchoolName').textContent = this.dataset.name;
                document.getElementById('deleteSchoolForm').action = `/{{ $userId }}/sa/schools/${this.dataset.id}`;
                new bootstrap.Modal(document.getElementById('deleteSchoolModal')).show();
            });
        });
    });
    </script>
@endsection
