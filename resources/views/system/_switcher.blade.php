@if (! empty($viewAsSwitcherVisible))
    @php
        $__isLoginAs = ! empty($viewAsUserId);
        $__isRoleOnly = empty($viewAsUserId) && ! empty($viewAsRole);
        $__redirectTo = url()->previous() ?: url()->current();
    @endphp
    <div class="dropdown" data-view-as-switcher>
        <button type="button"
                class="btn btn-soft-primary btn-sm d-flex align-items-center gap-1"
                data-bs-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false">
            <i class="bx bx-analyse"></i>
            <span class="d-none d-md-inline">
                @if ($__isLoginAs)
                    Login As: <strong>{{ $viewAsRole ?? 'User' }}</strong>
                @elseif ($__isRoleOnly)
                    View As: <strong>{{ $viewAsRole }}</strong>
                @else
                    View As / Login As
                @endif
            </span>
        </button>

        <div class="dropdown-menu dropdown-menu-end p-3"
             style="min-width: 380px; max-width: 460px;">

            <ul class="nav nav-pills nav-sm mb-2" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-1 px-2 small" data-bs-toggle="tab"
                            data-bs-target="#vas-role" type="button">View As (Role)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-1 px-2 small" data-bs-toggle="tab"
                            data-bs-target="#vas-loginas" type="button">Login As (User)</button>
                </li>
            </ul>

            <div class="tab-content">
                {{-- TAB 1: View As role only --}}
                <div class="tab-pane fade show active" id="vas-role">
                    <h6 class="dropdown-header px-0 small">
                        <i class="bx bx-analyse text-primary"></i>
                        View-As Role (akan mengarahkan ke dashboard user dengan role ini)
                    </h6>

                    @if ($__isRoleOnly || $__isLoginAs)
                        <div class="alert alert-warning py-2 mb-2 small d-flex align-items-center justify-content-between gap-2">
                            <span>
                                Active:
                                <strong>{{ $viewAsRole }}</strong>
                                @if ($__isLoginAs) — Login As @endif
                            </span>
                            <form method="POST" action="{{ route('system.view-as.role') }}" class="m-0">
                                @csrf
                                <input type="hidden" name="role" value="">
                                <input type="hidden" name="redirect_to" value="{{ $__redirectTo }}">
                                <button type="submit" class="btn btn-sm btn-outline-warning">Reset</button>
                            </form>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('system.view-as.role') }}" class="m-0">
                        @csrf
                        <input type="hidden" name="redirect_to" value="{{ $__redirectTo }}">
                        <div class="mb-2">
                            <label class="form-label small">Role</label>
                            <select name="role" class="form-select form-select-sm">
                                <option value="">-- Off (System Admin mode) --</option>
                                @foreach (($systemRoles ?? collect()) as $role)
                                    <option value="{{ $role->name }}"
                                        {{ ($viewAsRole ?? '') === $role->name ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if (! empty($schools))
                            <div class="mb-2">
                                <label class="form-label small">School Context (optional)</label>
                                <select name="school_id" class="form-select form-select-sm">
                                    <option value="">-- none --</option>
                                    @foreach ($schools as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary btn-sm w-100">Apply Role</button>
                    </form>
                </div>

                {{-- TAB 2: Login As specific user --}}
                <div class="tab-pane fade" id="vas-loginas">
                    <h6 class="dropdown-header px-0 small">
                        <i class="bx bx-log-in text-success"></i>
                        Login As User (full identity swap)
                    </h6>
                    <p class="small text-muted mb-2">
                        Anda akan melihat halaman &amp; akses user tersebut. Klik <strong>Restore Admin</strong> untuk kembali.
                    </p>

                    @if ($__isLoginAs)
                        <div class="alert alert-success py-2 mb-2 small d-flex align-items-center justify-content-between gap-2">
                            <span>
                                Login As user id <code>{{ $viewAsUserId }}</code>
                                @if (! empty($viewAsRole)) (<strong>{{ $viewAsRole }}</strong>) @endif
                            </span>
                            <form method="POST" action="{{ route('system.view-as.restore') }}" class="m-0">
                                @csrf
                                <input type="hidden" name="redirect_to" value="{{ $__redirectTo }}">
                                <button type="submit" class="btn btn-sm btn-outline-success">Restore Admin</button>
                            </form>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('system.view-as.login-as') }}" class="m-0">
                        @csrf
                        <input type="hidden" name="redirect_to" value="{{ $__redirectTo }}">
                        <div class="mb-2">
                            <label class="form-label small">Search user</label>
                            <input type="text" class="form-control form-control-sm"
                                   placeholder="Cari nama atau email..."
                                   data-user-search
                                   data-endpoint="{{ route('system.view-as.users') }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">User</label>
                            <select name="user_id" class="form-select form-select-sm" required data-user-list>
                                <option value="">-- Pilih user --</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-100">Login As</button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    @once
        <script>
        (function() {
            const switcher = document.querySelector('[data-view-as-switcher]');
            if (!switcher) return;
            const search = switcher.querySelector('[data-user-search]');
            const list = switcher.querySelector('[data-user-list]');
            if (!search || !list) return;

            const debounce = (fn, ms) => {
                let t;
                return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
            };

            const fetchUsers = debounce(async (q) => {
                try {
                    const r = await fetch(`${search.dataset.endpoint}?q=${encodeURIComponent(q||'')}`, {
                        headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
                    });
                    const data = await r.json();
                    const current = list.value;
                    list.innerHTML = '<option value="">-- Pilih user --</option>';
                    (data.users || []).forEach(u => {
                        const opt = document.createElement('option');
                        opt.value = u.id;
                        opt.textContent = u.name + ' — ' + (u.email ? u.email + ' — ' : '') + (u.roles && u.roles.length ? u.roles.join(', ') : 'no role');
                        list.appendChild(opt);
                    });
                    if (current) list.value = current;
                } catch (e) { console.error(e); }
            }, 250);

            search.addEventListener('input', e => fetchUsers(e.target.value));
            // initial load
            fetchUsers('');
        })();
        </script>
    @endonce
@endif
