@extends('Personalia.master')
@section('title')
@lang('translation.customers')
@endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1')
Data GTK
@endslot
@slot('title')
Satuan Kerja
@endslot
@endcomponent
<div class="row">
    <div class="col-lg-12">
        <div class="card" id="customerList">
            <div class="card-header border-bottom-dashed">

                <div class="row g-4 align-items-center">
                    <div class="col-sm">
                        <div>
                            <h5 class="card-title mb-0">GTK List</h5>
                        </div>
                    </div>
                    <div class="col-sm-auto">
                        <div class="d-flex flex-wrap align-items-start gap-2">
                            <button class="btn btn-danger" id="remove-actions" onClick="deleteMultiple()"><i style="font-size: 18px" class="ri-delete-bin-2-line"></i></button>
                            <button type="button" class="btn btn-success add-btn" data-bs-toggle="modal" id="create-btn" data-bs-target="#showModal"><i style="font-size: 18px" class="bx bx-add-to-queue align-bottom me-1"></i> Add
                                GTK</button>
                            <button type="button" class="btn btn-info"><i style="font-size: 18px" class="bx bx-download align-bottom me-1"></i>
                                Import</button>
                            <button type="button" class="btn btn-primary"><i style="font-size: 18px" class="bx bx-export align-bottom me-1"></i>
                                Export</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border-bottom-dashed border-bottom">
                <form>
                    <div class="row g-3">
                        <div class="col-xl-6">
                            <div class="search-box">
                                <input type="text" class="form-control search" placeholder="Cari GTK berdasarkan Nama, Alamat, No HP, NUPY atau lainnya.">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <!--end col-->
                        <div class="col-xl-6">
                            <div class="row g-3">
                                <div class="col-sm-4">
                                </div>
                                <!--end col-->
                                <div class="col-sm-4">
                                    <div>
                                        <select class="form-control" data-plugin="choices" data-choices data-choices-search-false name="choices-single-default" id="idStatus">
                                            <option value="">Status</option>
                                            <option value="all" selected>All</option>
                                            <option value="Percobaan">Percobaan</option>
                                            <option value="Tetap">Tetap</option>
                                            <option value="Kontrak">Kontrak</option>
                                            <option value="Perjam">Perjam</option>
                                        </select>
                                    </div>
                                </div>
                                <!--end col-->

                                <div class="col-sm-4">
                                    <div>
                                        <button type="button" class="btn btn-primary w-100" onclick="SearchData();"> <i class="ri-equalizer-fill me-2 align-bottom"></i>Filters</button>
                                    </div>
                                </div>
                                <!--end col-->
                            </div>
                        </div>
                    </div>
                    <!--end row-->
                </form>
            </div>
            <div class="card-body">
                <div>
                    <div class="table-responsive table-card mb-1">
                        <table class="table table-hover align-middle" id="customerTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="sort" data-sort="name">Nama</th>
                                    <th class="sort" data-sort="nupy">NUPY</th>
                                    <th class="sort" data-sort="email">Email</th>
                                    <th class="sort" data-sort="no_hp">No HP</th>
                                    <th class="sort" data-sort="jabatan">Jabatan</th>
                                    <th class="sort" data-sort="status_gtk">Status GTK</th>
                                    <th class="sort" data-sort="tmt">TMT</th>
                                    <th class="sort" data-sort="ttl">TTL</th>
                                    <th class="sort" data-sort="alamat">Alamat</th>
                                    <th class="sort" data-sort="jk">Jenis Kelamin</th>
                                    <th class="sort" data-sort="status_pernikahan">Status Pernikahan</th>
                                    <th class="sort" data-sort="action">Action</th>
                                </tr>
                            </thead>
                            <tbody class="list">
                                <tr>
                                    <td class="name">Wawan Gunawan, S.Kom</td>
                                    <td class="nupy">20001220250822</td>
                                    <td class="email">gunawan.wawan531@gmail.com</td>
                                    <td class="no_hp">082146898982</td>
                                    <td class="jabatan">Jabatan</td>
                                    <td class="status_gtk">Status GTK</td>
                                    <td class="tmt">TMT</td>
                                    <td class="ttl">TTL</td>
                                    <td class="alamat">Jalan Soromandi No. 14 Lawata, Kelurahan Dasan Agung Baru, Kecamatan Selaparang, Kota Mataram. Telp. (0370) 6170501. </td>
                                    <td class="jk">Jenis Kelamin</td>
                                    <td class="status_pernikahan">Status Pernikahan</td>
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            <li class="list-inline-item edit" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" title="Edit">
                                                <a href="#showModal" data-bs-toggle="modal" class="text-primary d-inline-block edit-item-btn">
                                                    <i class="ri-pencil-fill fs-16"></i>
                                                </a>
                                            </li>
                                            <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" title="Remove">
                                                <a class="text-danger d-inline-block remove-item-btn" data-bs-toggle="modal" href="#deleteRecordModal">
                                                    <i class="ri-delete-bin-5-fill fs-16"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="noresult" style="display: none">
                            <div class="text-center">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#121331,secondary:#08a88a" style="width:75px;height:75px">
                                </lord-icon>
                                <h5 class="mt-2">Sorry! No Result Found</h5>
                                <p class="text-muted mb-0">We've searched more than 150+ customers
                                    We did not find any
                                    customers for you search.</p>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <div class="pagination-wrap hstack gap-2">
                            <a class="page-item pagination-prev disabled" href="#">
                                Previous
                            </a>
                            <ul class="pagination listjs-pagination mb-0"></ul>
                            <a class="page-item pagination-next" href="#">
                                Next
                            </a>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="showModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-light p-3">
                                <h5 class="modal-title" id="exampleModalLabel"></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
                            </div>
                            <form class="tablelist-form" autocomplete="off">
                                <div class="modal-body">
                                    <input type="hidden" id="id-field" />

                                    <div class="mb-3" id="modal-id" style="display: none;">
                                        <label for="id-field1" class="form-label">ID</label>
                                        <input type="text" id="id-field1" class="form-control" placeholder="ID" readonly />
                                    </div>

                                    <div class="mb-3">
                                        <label for="customername-field" class="form-label">Customer Name</label>
                                        <input type="text" id="customername-field" class="form-control" placeholder="Enter name" required />
                                        <div class="invalid-feedback">Please enter a customer name.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email-field" class="form-label">Email</label>
                                        <input type="email" id="email-field" class="form-control" placeholder="Enter email" required />
                                        <div class="invalid-feedback">Please enter an email.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone-field" class="form-label">Phone</label>
                                        <input type="text" id="phone-field" class="form-control" placeholder="Enter phone no." required />
                                        <div class="invalid-feedback">Please enter a phone.</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="date-field" class="form-label">Joining Date</label>
                                        <input type="date" id="date-field" class="form-control" data-provider="flatpickr" data-date-format="d M, Y" required placeholder="Select date" />
                                        <div class="invalid-feedback">Please select a date.</div>
                                    </div>

                                    <div>
                                        <label for="status-field" class="form-label">Status</label>
                                        <select class="form-control" data-choices data-choices-search-false name="status-field" id="status-field" required>
                                            <option value="">Status</option>
                                            <option value="Active">Active</option>
                                            <option value="Block">Block</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <div class="hstack gap-2 justify-content-end">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-success" id="add-btn">Add Customer</button>
                                        <!-- <button type="button" class="btn btn-success" id="edit-btn">Update</button> -->
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade zoomIn" id="deleteRecordModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="btn-close" id="deleteRecord-close" data-bs-dismiss="modal" aria-label="Close" id="btn-close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mt-2 text-center">
                                    <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#f7b84b,secondary:#f06548" style="width:100px;height:100px">
                                    </lord-icon>
                                    <div class="mt-4 pt-2 fs-15 mx-4 mx-sm-5">
                                        <h4>Are you sure ?</h4>
                                        <p class="text-muted mx-4 mb-0">Are you sure you want to
                                            remove this record ?</p>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 justify-content-center mt-4 mb-2">
                                    <button type="button" class="btn w-sm btn-light" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn w-sm btn-danger " id="delete-record">Yes, Delete
                                        It!</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end modal -->
            </div>
        </div>

    </div>
    <!--end col-->
</div>
<!--end row-->
@endsection
@section('script')
<script src="{{ URL::asset('build/libs/list.js/list.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/list.pagination.js/list.pagination.min.js') }}"></script>

<!--ecommerce-customer init js -->
<script src="{{ URL::asset('build/js/pages/ecommerce-customer-list.init.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>

<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
