@extends('layouts.master')
@section('title') 添加学校 @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Super Admin @endslot
        @slot('li_2') <a href="{{ route('user.sa.schools.index', ['userId' => $userId]) }}" class="text-muted">学校管理</a> @endslot
        @slot('title') 添加学校 @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header border-bottom-dashed">
                    <h5 class="card-title mb-0">添加新学校</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.sa.schools.store', ['userId' => $userId]) }}" enctype="multipart/form-data">
                        @csrf

                        <h6 class="mb-3">基本信息</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">学校名称 <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">NPSN <span class="text-danger">*</span></label>
                                <input type="text" name="npsn" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">学校代码</label>
                                <input type="text" name="school_code" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">所属单位 <span class="text-danger">*</span></label>
                                <select name="work_unit_id" class="form-select" required>
                                    <option value="">选择单位...</option>
                                    @foreach($workUnits as $wu)
                                        <option value="{{ $wu->id }}">{{ $wu->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">学校级别</label>
                                <select name="school_level" class="form-select">
                                    <option value="">选择级别...</option>
                                    <option value="sd">小学</option>
                                    <option value="smp">初中</option>
                                    <option value="sma">高中</option>
                                    <option value="smk">职校</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">学校性质</label>
                                <select name="school_status" class="form-select">
                                    <option value="negeri">公立</option>
                                    <option value="swasta">私立</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">办学方向</label>
                                <select name="school_gender" class="form-select">
                                    <option value="putra">男校</option>
                                    <option value="putri">女校</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="mb-3">地址信息</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label">详细地址</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">省份</label>
                                <select name="province_code" class="form-select" onchange="loadCities(this.value, 'city_code')">
                                    <option value="">选择省份...</option>
                                    @foreach($provinces as $prov)
                                        <option value="{{ $prov->code }}">{{ $prov->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">城市</label>
                                <select name="city_code" class="form-select">
                                    <option value="">选择城市...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">区县</label>
                                <select name="district_code" class="form-select">
                                    <option value="">选择区县...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">村庄</label>
                                <select name="village_code" class="form-select">
                                    <option value="">选择村庄...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">邮政编码</label>
                                <input type="text" name="postal_code" class="form-control">
                            </div>
                        </div>

                        <h6 class="mb-3">联系方式</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">电话</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">邮箱</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">网站</label>
                                <input type="url" name="website" class="form-control">
                            </div>
                        </div>

                        <h6 class="mb-3">校长信息</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">校长姓名</label>
                                <input type="text" name="principal_name" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">校长工号</label>
                                <input type="text" name="principal_nip" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">校长用户</label>
                                <select name="principal_user_id" class="form-select">
                                    <option value="">不关联</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <h6 class="mb-3">其他信息</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">成立时间</label>
                                <input type="date" name="established_date" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">批准文号</label>
                                <input type="text" name="established_decree" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"> Accreditation</label>
                                <select name="accreditation" class="form-select">
                                    <option value="">未评定</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">运营时间</label>
                                <select name="operational_hours" class="form-select">
                                    <option value="pagi">上午</option>
                                    <option value="siang">下午</option>
                                    <option value="full_day">全天</option>
                                </select>
                            </div>
                        </div>

                        <h6 class="mb-3">文件上传</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">学校Logo</label>
                                <input type="file" name="logo_path" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">信头文件(KOP)</label>
                                <input type="file" name="kop_path" class="form-control" accept="image/*">
                            </div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                            <label class="form-check-label" for="is_active">启用</label>
                        </div>

                        <div class="float-end gap-2">
                            <a href="{{ route('user.sa.schools.index', ['userId' => $userId]) }}" class="btn btn-light">取消</a>
                            <button type="submit" class="btn btn-success"><i class="ri-save-line me-1"></i> 保存</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
    function loadCities(provinceCode, selectId) {
        // Simple implementation - in production would use AJAX
        const select = document.getElementById(selectId);
        select.innerHTML = '<option value="">选择城市...</option>';
    }
    </script>
@endsection
