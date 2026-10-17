@extends('layouts.master')

@section('title', 'System Configuration — Konsol Sistem')

@section('css')
    @include('dashboard._shared._css')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Konsol Sistem @endslot
        @slot('title') System Configuration @endslot
    @endcomponent

    <div class="dashboard-role" data-role="super-admin">
        <div class="alert alert-info d-flex align-items-start gap-2">
            <i class="ri-information-line mt-1"></i>
            <div>
                Nilai berikut dibaca dari konfigurasi aplikasi (<code>.env</code> + <code>config/*</code>) dan ditampilkan
                <strong>read-only</strong>. Untuk mengubahnya, edit file konfigurasi di server lalu jalankan
                <code>php artisan config:cache</code>.
            </div>
        </div>

        @foreach ($groups as $groupName => $items)
            <section class="dash-section">
                <div class="dash-section-header">
                    <span class="dash-section-icon"><i class="ri-settings-4-line"></i></span>
                    <h6 class="dash-section-title">{{ $groupName }}</h6>
                    <span class="dash-section-line"></span>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <tbody>
                                    @foreach ($items as $item)
                                        <tr>
                                            <td class="text-muted fs-13" style="width: 35%;">{{ $item['name'] }}</td>
                                            <td class="fw-medium">
                                                @if (! empty($item['badge']))
                                                    <span class="badge bg-{{ $item['badge'] }}-subtle text-{{ $item['badge'] }}">{{ $item['value'] }}</span>
                                                @else
                                                    {{ $item['value'] }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        @endforeach
    </div>
@endsection
