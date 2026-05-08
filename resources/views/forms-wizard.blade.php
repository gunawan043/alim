@extends('layouts.master')
@section('title')
    @lang('translation.wizard')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Forms
        @endslot
        @slot('title')
            Wizard
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Vertical nav Steps</h4>
                </div><!-- end card header -->
                <div class="card-body form-steps">
                    <form class="vertical-navs-step">
                        <div class="row gy-5">
                            <div class="col-lg-3">
                                <div class="nav flex-column custom-nav nav-pills" role="tablist"
                                    aria-orientation="vertical">
                                    <button class="nav-link done" id="v-pills-bill-info-tab" data-bs-toggle="pill"
                                        data-bs-target="#v-pills-bill-info" type="button" role="tab"
                                        aria-controls="v-pills-bill-info" aria-selected="true">
                                        <span class="step-title me-2">
                                            <i class="ri-close-circle-fill step-icon me-2"></i> Step 1
                                        </span>
                                        Billing Info
                                    </button>
                                    <button class="nav-link active" id="v-pills-bill-address-tab" data-bs-toggle="pill"
                                        data-bs-target="#v-pills-bill-address" type="button" role="tab"
                                        aria-controls="v-pills-bill-address" aria-selected="false">
                                        <span class="step-title me-2">
                                            <i class="ri-close-circle-fill step-icon me-2"></i> Step 2
                                        </span>
                                        Address
                                    </button>
                                    <button class="nav-link" id="v-pills-payment-tab" data-bs-toggle="pill"
                                        data-bs-target="#v-pills-payment" type="button" role="tab"
                                        aria-controls="v-pills-payment" aria-selected="false">
                                        <span class="step-title me-2">
                                            <i class="ri-close-circle-fill step-icon me-2"></i> Step 3
                                        </span>
                                        Payment
                                    </button>
                                    <button class="nav-link" id="v-pills-finish-tab" data-bs-toggle="pill"
                                        data-bs-target="#v-pills-finish" type="button" role="tab"
                                        aria-controls="v-pills-finish" aria-selected="false">
                                        <span class="step-title me-2">
                                            <i class="ri-close-circle-fill step-icon me-2"></i> Step 4
                                        </span>
                                        Finish
                                    </button>
                                </div>
                                <!-- end nav -->
                            </div> <!-- end col-->
                            <div class="col-lg-6">
                                <div class="px-lg-4">
                                    <div class="tab-content">
                                        <div class="tab-pane fade" id="v-pills-bill-info" role="tabpanel"
                                            aria-labelledby="v-pills-bill-info-tab">
                                            <div>
                                                <h5>Billing Info</h5>
                                                <p class="text-muted">Fill all information below</p>
                                            </div>

                                            <div>
                                                <div class="row g-3">
                                                    <div class="col-sm-6">
                                                        <label for="firstName" class="form-label">First name</label>
                                                        <input type="text" class="form-control" id="firstName"
                                                            placeholder="Enter first name" value="" required>
                                                        <div class="invalid-feedback">Please enter a first name</div>
                                                    </div>

                                                    <div class="col-sm-6">
                                                        <label for="lastName" class="form-label">Last name</label>
                                                        <input type="text" class="form-control" id="lastName"
                                                            placeholder="Enter last name" value="" required>
                                                        <div class="invalid-feedback">Please enter a last name</div>
                                                    </div>

                                                    <div class="col-12">
                                                        <label for="username" class="form-label">Username</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">@</span>
                                                            <input type="text" class="form-control" id="username"
                                                                placeholder="Username" required>
                                                            <div class="invalid-feedback">Please enter a user name</div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12">
                                                        <label for="email" class="form-label">Email <span
                                                                class="text-muted">(Optional)</span></label>
                                                        <input type="email" class="form-control" id="email"
                                                            placeholder="Enter Email" />
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="button"
                                                    class="btn btn-success btn-label right ms-auto nexttab nexttab"
                                                    data-nexttab="v-pills-bill-address-tab"><i
                                                        class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>Go
                                                    to Shipping</button>
                                            </div>
                                        </div>
                                        <!-- end tab pane -->
                                        <div class="tab-pane fade show active" id="v-pills-bill-address" role="tabpanel"
                                            aria-labelledby="v-pills-bill-address-tab">
                                            <div>
                                                <h5>Shipping Address</h5>
                                                <p class="text-muted">Fill all information below</p>
                                            </div>

                                            <div>
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label for="address" class="form-label">Address</label>
                                                        <input type="text" class="form-control" id="address"
                                                            placeholder="1234 Main St" required>
                                                        <div class="invalid-feedback">Please enter a address</div>
                                                    </div>

                                                    <div class="col-12">
                                                        <label for="address2" class="form-label">Address 2 <span
                                                                class="text-muted">(Optional)</span></label>
                                                        <input type="text" class="form-control" id="address2"
                                                            placeholder="Apartment or suite" />
                                                    </div>

                                                    <div class="col-md-5">
                                                        <label for="country" class="form-label">Country</label>
                                                        <select class="form-select" id="country" required>
                                                            <option value="">Choose...</option>
                                                            <option>United States</option>
                                                        </select>
                                                        <div class="invalid-feedback">Please select a country</div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <label for="state" class="form-label">State</label>
                                                        <select class="form-select" id="state">
                                                            <option value="">Choose...</option>
                                                            <option>California</option>
                                                        </select>
                                                        <div class="invalid-feedback">Please select a state</div>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label for="zip" class="form-label">Zip</label>
                                                        <input type="text" class="form-control" id="zip"
                                                            placeholder="" />
                                                    </div>
                                                </div>

                                                <hr class="my-4 text-muted">

                                                <div class="form-check mb-2">
                                                    <input type="checkbox" class="form-check-input" id="same-address">
                                                    <label class="form-check-label" for="same-address">Shipping address is
                                                        the same as my billing address</label>
                                                </div>

                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="save-info">
                                                    <label class="form-check-label" for="save-info">Save this information
                                                        for next time</label>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="button" class="btn btn-light btn-label previestab"
                                                    data-previous="v-pills-bill-info-tab"><i
                                                        class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                                    Back to Billing Info</button>
                                                <button type="button"
                                                    class="btn btn-success btn-label right ms-auto nexttab nexttab"
                                                    data-nexttab="v-pills-payment-tab"><i
                                                        class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>Go
                                                    to Payment</button>
                                            </div>
                                        </div>
                                        <!-- end tab pane -->
                                        <div class="tab-pane fade" id="v-pills-payment" role="tabpanel"
                                            aria-labelledby="v-pills-payment-tab">
                                            <div>
                                                <h5>Payment</h5>
                                                <p class="text-muted">Fill all information below</p>
                                            </div>

                                            <div>
                                                <div class="my-3">
                                                    <div class="form-check form-check-inline">
                                                        <input id="credit" name="paymentMethod" type="radio"
                                                            class="form-check-input" checked required>
                                                        <label class="form-check-label" for="credit">Credit card</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input id="debit" name="paymentMethod" type="radio"
                                                            class="form-check-input" required>
                                                        <label class="form-check-label" for="debit">Debit card</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input id="paypal" name="paymentMethod" type="radio"
                                                            class="form-check-input" required>
                                                        <label class="form-check-label" for="paypal">PayPal</label>
                                                    </div>
                                                </div>

                                                <div class="row gy-3">
                                                    <div class="col-md-12">
                                                        <label for="cc-name" class="form-label">Name on card</label>
                                                        <input type="text" class="form-control" id="cc-name"
                                                            placeholder="" required>
                                                        <small class="text-muted">Full name as displayed on card</small>
                                                        <div class="invalid-feedback">
                                                            Name on card is required
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label for="cc-number" class="form-label">Credit card
                                                            number</label>
                                                        <input type="text" class="form-control" id="cc-number"
                                                            placeholder="" required>
                                                        <div class="invalid-feedback">
                                                            Credit card number is required
                                                        </div>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label for="cc-expiration" class="form-label">Expiration</label>
                                                        <input type="text" class="form-control" id="cc-expiration"
                                                            placeholder="" required>
                                                        <div class="invalid-feedback">
                                                            Expiration date required
                                                        </div>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label for="cc-cvv" class="form-label">CVV</label>
                                                        <input type="text" class="form-control" id="cc-cvv"
                                                            placeholder="" required>
                                                        <div class="invalid-feedback">
                                                            Security code required
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="button" class="btn btn-light btn-label previestab"
                                                    data-previous="v-pills-bill-address-tab"><i
                                                        class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                                    Back to Shipping Info</button>
                                                <button type="button"
                                                    class="btn btn-success btn-label right ms-auto nexttab nexttab"
                                                    data-nexttab="v-pills-finish-tab"><i
                                                        class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                    Order Complete</button>
                                            </div>
                                        </div>
                                        <!-- end tab pane -->
                                        <div class="tab-pane fade" id="v-pills-finish" role="tabpanel"
                                            aria-labelledby="v-pills-finish-tab">
                                            <div class="text-center pt-4 pb-2">

                                                <div class="mb-4">
                                                    <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop"
                                                        colors="primary:#0ab39c,secondary:#005981"
                                                        style="width:120px;height:120px"></lord-icon>
                                                </div>
                                                <h5>Your Order is Completed !</h5>
                                                <p class="text-muted">You Will receive an order confirmation email with
                                                    details of your order.</p>
                                            </div>
                                        </div>
                                        <!-- end tab pane -->
                                    </div>
                                    <!-- end tab content -->
                                </div>
                            </div>
                            <!-- end col -->

                            <div class="col-lg-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="fs-14 text-primary mb-0"><i
                                            class="ri-shopping-cart-fill align-middle me-2"></i> Your cart</h5>
                                    <span class="badge bg-danger rounded-pill">3</span>
                                </div>
                                <ul class="list-group mb-3">
                                    <li class="list-group-item d-flex justify-content-between lh-sm">
                                        <div>
                                            <h6 class="my-0">Product name</h6>
                                            <small class="text-muted">Brief description</small>
                                        </div>
                                        <span class="text-muted">$12</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between lh-sm">
                                        <div>
                                            <h6 class="my-0">Second product</h6>
                                            <small class="text-muted">Brief description</small>
                                        </div>
                                        <span class="text-muted">$8</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between lh-sm">
                                        <div>
                                            <h6 class="my-0">Third item</h6>
                                            <small class="text-muted">Brief description</small>
                                        </div>
                                        <span class="text-muted">$5</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between bg-light">
                                        <div class="text-success">
                                            <h6 class="my-0">Discount code</h6>
                                            <small>−$5 Discount</small>
                                        </div>
                                        <span class="text-success">−$5</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>Total (USD)</span>
                                        <strong>$20</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <!-- end row -->
                    </form>
                </div>
            </div>
            <!-- end -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ URL::asset('build/js/pages/form-wizard.init.js') }}"></script>
    
    <script>
        // SweetAlert2 fallback function
        function showAlert(config) {
            if (typeof Swal !== 'undefined' && Swal) {
                return Swal.fire(config);
            } else {
                // Fallback to native alert
                if (config.icon === 'error') {
                    alert('Error: ' + config.text);
                } else if (config.icon === 'warning') {
                    alert('Warning: ' + config.text);
                } else if (config.icon === 'success') {
                    alert('Success: ' + config.text);
                } else {
                    alert(config.text || config.title);
                }
                
                // Return a mock promise for consistency
                return Promise.resolve({ isConfirmed: true });
            }
        }
        
        // Loading alert
        function showLoading(title = 'Menyimpan Data', text = 'Harap tunggu...') {
            if (typeof Swal !== 'undefined' && Swal) {
                Swal.fire({
                    title: title,
                    text: text,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            } else {
                // Fallback
                console.log(title + ': ' + text);
            }
        }
        
        // Close loading
        function closeLoading() {
            if (typeof Swal !== 'undefined' && Swal && Swal.isLoading()) {
                Swal.close();
            }
        }
        
        let familyMemberCount = 0;
        let totalFields = 0;
        let filledFields = 0;
        let currentStep = 1;
        
        // Define all required fields for validation
        const requiredFields = [
            // Data Dasar
            'name', 'email', 'nik', 'tempat_lahir', 'tanggal_lahir',
            'jenis_kelamin', 'golongan_darah', 'status_perkawinan', 'nama_ibu_kandung',
            
            // Alamat Domisili
            'provinsi_domisili', 'kabupaten_domisili', 'kecamatan_domisili', 'desa_domisili',
            'jalan_domisili', 'rt_rw_domisili',
            
            // Kontak
            'no_hp', 'kontak_darurat',
            
            // Kepegawaian
            'nupy', 'jenis_gtk', 'jabatan', 'status_kepegawaian', 'tmt',
            'nomor_sk', 'tanggal_sk', 'work_unit_id'
        ];
        
        // Define optional fields
        const optionalFields = [
            'no_kk', 'npwp',
            'dusun_domisili',
            'provinsi_ktp', 'kabupaten_ktp', 'kecamatan_ktp', 'desa_ktp',
            'jalan_ktp', 'rt_rw_ktp', 'dusun_ktp',
            'no_whatsapp', 'instagram', 'facebook', 'twitter',
            'pangkat_golongan'
        ];
        
        // Calculate total fields
        totalFields = requiredFields.length + optionalFields.length;
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            updateProgress();
            updateNavigationPills();
            setupNavigation();
            setupTabListeners();
        });
        
        // Setup navigation
        function setupNavigation() {
            // Next step buttons
            document.querySelectorAll('.next-step').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const nextTab = this.getAttribute('data-next');
                    if (validateCurrentStep()) {
                        switchToTab(nextTab);
                    }
                });
            });
            
            // Previous step buttons
            document.querySelectorAll('.prev-step').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const prevTab = this.getAttribute('data-prev');
                    switchToTab(prevTab);
                });
            });
        }
        
        // Setup tab listeners
        function setupTabListeners() {
            const tabButtons = document.querySelectorAll('[data-bs-toggle="pill"]');
            tabButtons.forEach(button => {
                button.addEventListener('show.bs.tab', function(event) {
                    const targetTab = event.target.getAttribute('aria-controls');
                    currentStep = getStepNumber(targetTab);
                    updateNavigationPills();
                    
                    // Show review data when review tab is activated
                    if (targetTab === 'v-pills-review') {
                        setTimeout(() => {
                            showReviewData();
                        }, 300);
                    }
                });
            });
        }
        
        // Switch tab programmatically
        function switchToTab(tabId) {
            const tabElement = document.getElementById(tabId);
            if (tabElement) {
                const tab = new bootstrap.Tab(tabElement);
                tab.show();
            }
        }
        
        // Get step number from tab ID
        function getStepNumber(tabContentId) {
            const stepMap = {
                'v-pills-data-dasar': 1,
                'v-pills-alamat': 2,
                'v-pills-kontak': 3,
                'v-pills-kepegawaian': 4,
                'v-pills-keluarga': 5,
                'v-pills-review': 6
            };
            return stepMap[tabContentId] || 1;
        }
        
        // Update progress based on filled fields
        function updateProgress() {
            calculateFilledFields();
            const percentage = totalFields > 0 ? Math.round((filledFields / totalFields) * 100) : 0;
            
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            
            if (progressBar && progressText) {
                progressBar.style.width = `${percentage}%`;
                progressBar.setAttribute('aria-valuenow', percentage);
                progressText.textContent = `${percentage}% Lengkap`;
                
                // Update progress color
                if (percentage < 30) {
                    progressBar.className = 'progress-bar bg-danger';
                } else if (percentage < 70) {
                    progressBar.className = 'progress-bar bg-warning';
                } else {
                    progressBar.className = 'progress-bar bg-success';
                }
            }
        }
        
        // Calculate filled fields
        function calculateFilledFields() {
            filledFields = 0;
            
            // Check required fields
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    if (field.type === 'select-one') {
                        if (field.value && field.value !== '') {
                            filledFields++;
                        }
                    } else {
                        if (field.value && field.value.trim() !== '') {
                            filledFields++;
                        }
                    }
                }
            });
            
            // Check optional fields
            optionalFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && field.value && field.value.trim() !== '') {
                    filledFields++;
                }
            });
            
            // Add family members (each with at least one field filled counts as 1)
            const familyMembers = document.querySelectorAll('.family-member');
            familyMembers.forEach(member => {
                const memberFields = member.querySelectorAll('input, select, textarea');
                let memberHasValue = false;
                
                memberFields.forEach(field => {
                    if (field.value && field.value.trim() !== '') {
                        memberHasValue = true;
                    }
                });
                
                if (memberHasValue) {
                    filledFields++;
                }
            });
        }
        
        // Update navigation pills
        function updateNavigationPills() {
            const steps = [
                'v-pills-data-dasar-tab',
                'v-pills-alamat-tab',
                'v-pills-kontak-tab',
                'v-pills-kepegawaian-tab',
                'v-pills-keluarga-tab',
                'v-pills-review-tab'
            ];
            
            steps.forEach(stepId => {
                const button = document.getElementById(stepId);
                if (button) {
                    const stepNumber = steps.indexOf(stepId) + 1;
                    
                    button.classList.remove('active', 'done');
                    
                    if (stepNumber === currentStep) {
                        button.classList.add('active');
                    } else if (stepNumber < currentStep) {
                        button.classList.add('done');
                    }
                }
            });
        }
        
        // Validate current step
        function validateCurrentStep() {
            const currentTab = document.querySelector('.tab-pane.active');
            if (!currentTab) return true;
            
            const inputs = currentTab.querySelectorAll('[required]');
            let isValid = true;
            let firstInvalidField = null;
            
            inputs.forEach(input => {
                input.classList.remove('is-invalid');
                
                let fieldValid = true;
                
                if (!input.value.trim()) {
                    fieldValid = false;
                } else if (input.name === 'nik' && (input.value.length !== 16 || !/^\d+$/.test(input.value))) {
                    fieldValid = false;
                } else if (input.name === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
                    fieldValid = false;
                } else if (input.name === 'no_kk' && input.value !== '' && (input.value.length !== 16 || !/^\d+$/.test(input.value))) {
                    fieldValid = false;
                }
                
                if (!fieldValid) {
                    input.classList.add('is-invalid');
                    isValid = false;
                    
                    if (!firstInvalidField) {
                        firstInvalidField = input;
                    }
                }
            });
            
            if (!isValid && firstInvalidField) {
                // Scroll to first invalid field
                firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalidField.focus();
                
                showAlert({
                    icon: 'warning',
                    title: 'Data Belum Lengkap',
                    text: 'Harap isi semua field yang wajib diisi dengan benar'
                });
            }
            
            return isValid;
        }
        
        // Wilayah functions
        async function loadCities(provinceCode, targetId, type) {
            if (!provinceCode) {
                const target = document.getElementById(targetId);
                if (target) {
                    target.innerHTML = '<option value="">Pilih Kabupaten/Kota</option>';
                    updateProgress();
                }
                return;
            }
            
            try {
                const response = await fetch(`/wilayah/regencies/${provinceCode}`);
                const result = await response.json();
                
                if (result.success && Array.isArray(result.data)) {
                    const target = document.getElementById(targetId);
                    if (target) {
                        let options = '<option value="">Pilih Kabupaten/Kota</option>';
                        result.data.forEach(city => {
                            options += `<option value="${city.code}">${city.name}</option>`;
                        });
                        target.innerHTML = options;
                        
                        // Reset dependent dropdowns
                        if (type === 'domisili') {
                            resetDropdown('kecamatan_domisili', 'Pilih Kecamatan');
                            resetDropdown('desa_domisili', 'Pilih Desa');
                            document.getElementById('kode_pos_domisili').value = '';
                        } else {
                            resetDropdown('kecamatan_ktp', 'Pilih Kecamatan');
                            resetDropdown('desa_ktp', 'Pilih Desa');
                            document.getElementById('kode_pos_ktp').value = '';
                        }
                        
                        updateProgress();
                    }
                } else {
                    throw new Error(result.message || 'Data tidak ditemukan');
                }
            } catch (error) {
                console.error('Error loading cities:', error);
                showAlert({
                    icon: 'error',
                    title: 'Error',
                    text: 'Gagal memuat data kabupaten/kota'
                });
            }
        }
        
        async function loadDistricts(cityCode, targetId, type) {
            if (!cityCode) {
                const target = document.getElementById(targetId);
                if (target) {
                    target.innerHTML = '<option value="">Pilih Kecamatan</option>';
                    updateProgress();
                }
                return;
            }
            
            try {
                const response = await fetch(`/wilayah/districts/${cityCode}`);
                const result = await response.json();
                
                if (result.success && Array.isArray(result.data)) {
                    const target = document.getElementById(targetId);
                    if (target) {
                        let options = '<option value="">Pilih Kecamatan</option>';
                        result.data.forEach(district => {
                            options += `<option value="${district.code}">${district.name}</option>`;
                        });
                        target.innerHTML = options;
                        
                        // Reset dependent dropdown
                        if (type === 'domisili') {
                            resetDropdown('desa_domisili', 'Pilih Desa');
                            document.getElementById('kode_pos_domisili').value = '';
                        } else {
                            resetDropdown('desa_ktp', 'Pilih Desa');
                            document.getElementById('kode_pos_ktp').value = '';
                        }
                        
                        updateProgress();
                    }
                } else {
                    throw new Error(result.message || 'Data tidak ditemukan');
                }
            } catch (error) {
                console.error('Error loading districts:', error);
                showAlert({
                    icon: 'error',
                    title: 'Error',
                    text: 'Gagal memuat data kecamatan'
                });
            }
        }
        
        async function loadVillages(districtCode, targetId, type) {
            if (!districtCode) {
                const target = document.getElementById(targetId);
                if (target) {
                    target.innerHTML = '<option value="">Pilih Desa</option>';
                    updateProgress();
                }
                return;
            }
            
            try {
                const response = await fetch(`/wilayah/villages/${districtCode}`);
                const result = await response.json();
                
                if (result.success && Array.isArray(result.data)) {
                    const target = document.getElementById(targetId);
                    if (target) {
                        let options = '<option value="">Pilih Desa</option>';
                        result.data.forEach(village => {
                            options += `<option value="${village.code}">${village.name}</option>`;
                        });
                        target.innerHTML = options;
                        
                        // Reset postal code
                        if (type === 'domisili') {
                            document.getElementById('kode_pos_domisili').value = '';
                        } else {
                            document.getElementById('kode_pos_ktp').value = '';
                        }
                        
                        updateProgress();
                    }
                } else {
                    throw new Error(result.message || 'Data tidak ditemukan');
                }
            } catch (error) {
                console.error('Error loading villages:', error);
                showAlert({
                    icon: 'error',
                    title: 'Error',
                    text: 'Gagal memuat data desa'
                });
            }
        }
        
        async function updatePostalCode(villageCode, targetId) {
            if (!villageCode) {
                const target = document.getElementById(targetId);
                if (target) {
                    target.value = '';
                }
                return;
            }
            
            try {
                const response = await fetch(`/wilayah/villages/${villageCode}`);
                const result = await response.json();
                
                if (result.success && Array.isArray(result.data)) {
                    const village = result.data.find(v => v.code == villageCode);
                    if (village && village.postal_code) {
                        const target = document.getElementById(targetId);
                        if (target) {
                            target.value = village.postal_code;
                        }
                    }
                }
            } catch (error) {
                console.error('Error getting postal code:', error);
            }
        }
        
        // Helper function to reset dropdown
        function resetDropdown(elementId, placeholder) {
            const element = document.getElementById(elementId);
            if (element) {
                element.innerHTML = `<option value="">${placeholder}</option>`;
            }
        }
        
        function copyAddressToKTP() {
            const sameAddressCheckbox = document.getElementById('sameAddress');
            if (!sameAddressCheckbox) return;
            
            if (sameAddressCheckbox.checked) {
                // Copy province
                const provinsiDomisili = document.getElementById('provinsi_domisili');
                const provinsiKtp = document.getElementById('provinsi_ktp');
                if (provinsiDomisili && provinsiKtp && provinsiDomisili.value) {
                    provinsiKtp.value = provinsiDomisili.value;
                    // Trigger change event to load cities
                    provinsiKtp.dispatchEvent(new Event('change'));
                }
                
                // Copy other values with delay for dropdown loading
                setTimeout(() => {
                    const kabupatenDomisili = document.getElementById('kabupaten_domisili');
                    const kabupatenKtp = document.getElementById('kabupaten_ktp');
                    if (kabupatenDomisili && kabupatenKtp && kabupatenDomisili.value) {
                        kabupatenKtp.value = kabupatenDomisili.value;
                        kabupatenKtp.dispatchEvent(new Event('change'));
                    }
                    
                    setTimeout(() => {
                        const kecamatanDomisili = document.getElementById('kecamatan_domisili');
                        const kecamatanKtp = document.getElementById('kecamatan_ktp');
                        if (kecamatanDomisili && kecamatanKtp && kecamatanDomisili.value) {
                            kecamatanKtp.value = kecamatanDomisili.value;
                            kecamatanKtp.dispatchEvent(new Event('change'));
                        }
                        
                        setTimeout(() => {
                            const desaDomisili = document.getElementById('desa_domisili');
                            const desaKtp = document.getElementById('desa_ktp');
                            if (desaDomisili && desaKtp && desaDomisili.value) {
                                desaKtp.value = desaDomisili.value;
                                desaKtp.dispatchEvent(new Event('change'));
                            }
                            
                            // Copy text fields
                            copyFieldValue('jalan_domisili', 'jalan_ktp');
                            copyFieldValue('rt_rw_domisili', 'rt_rw_ktp');
                            copyFieldValue('dusun_domisili', 'dusun_ktp');
                            copyFieldValue('kode_pos_domisili', 'kode_pos_ktp');
                            
                            updateProgress();
                        }, 500);
                    }, 500);
                }, 500);
            } else {
                // Clear KTP fields
                clearField('provinsi_ktp');
                resetDropdown('kabupaten_ktp', 'Pilih Kabupaten/Kota');
                resetDropdown('kecamatan_ktp', 'Pilih Kecamatan');
                resetDropdown('desa_ktp', 'Pilih Desa');
                clearField('jalan_ktp');
                clearField('rt_rw_ktp');
                clearField('dusun_ktp');
                clearField('kode_pos_ktp');
                
                updateProgress();
            }
        }
        
        function copyFieldValue(sourceId, targetId) {
            const source = document.getElementById(sourceId);
            const target = document.getElementById(targetId);
            if (source && target) {
                target.value = source.value;
            }
        }
        
        function clearField(fieldId) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = '';
            }
        }
        
        function addFamilyMember() {
            familyMemberCount++;
            const container = document.getElementById('familyMembersContainer');
            const template = `
                <div class="family-member border p-3 mb-3 rounded">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Anggota Keluarga #${familyMemberCount}</h6>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeFamilyMember(this)">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Hubungan</label>
                            <select class="form-select" name="anggota_keluarga[${familyMemberCount-1}][relationship]" onchange="updateProgress()">
                                <option value="">Pilih...</option>
                                <option value="Suami">Suami</option>
                                <option value="Istri">Istri</option>
                                <option value="Anak">Anak</option>
                                <option value="Ayah">Ayah</option>
                                <option value="Ibu">Ibu</option>
                                <option value="Saudara">Saudara</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="anggota_keluarga[${familyMemberCount-1}][nama]" oninput="updateProgress()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jenis Kelamin</label>
                            <select class="form-select" name="anggota_keluarga[${familyMemberCount-1}][jenis_kelamin]" onchange="updateProgress()">
                                <option value="">Pilih...</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pekerjaan</label>
                            <input type="text" class="form-control" name="anggota_keluarga[${familyMemberCount-1}][pekerjaan]" oninput="updateProgress()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pendidikan Terakhir</label>
                            <input type="text" class="form-control" name="anggota_keluarga[${familyMemberCount-1}][pendidikan_terakhir]" oninput="updateProgress()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" name="anggota_keluarga[${familyMemberCount-1}][tanggal_lahir]" onchange="updateProgress()">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="anggota_keluarga[${familyMemberCount-1}][alamat]" rows="2" oninput="updateProgress()"></textarea>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', template);
            updateProgress();
        }
        
        function removeFamilyMember(button) {
            const member = button.closest('.family-member');
            if (member) {
                member.remove();
                familyMemberCount--;
                
                // Renumber remaining members
                const members = document.querySelectorAll('.family-member');
                members.forEach((member, index) => {
                    const title = member.querySelector('h6');
                    if (title) {
                        title.textContent = `Anggota Keluarga #${index + 1}`;
                    }
                });
                
                updateProgress();
            }
        }
        
        // Collect form data
        function collectFormData() {
            const form = document.getElementById('gtkWizardForm');
            const formData = new FormData(form);
            
            const data = {};
            
            // Process form data
            formData.forEach((value, key) => {
                // Handle nested arrays and objects
                if (key.includes('[') && key.includes(']')) {
                    const match = key.match(/(\w+)\[(\w+)\](?:\[(\w+)\])?/);
                    if (match) {
                        const [, parent, child, grandchild] = match;
                        
                        if (!data[parent]) {
                            if (parent === 'anggota_keluarga') {
                                data[parent] = [];
                            } else {
                                data[parent] = {};
                            }
                        }
                        
                        if (parent === 'anggota_keluarga') {
                            const index = parseInt(child);
                            if (!data[parent][index]) data[parent][index] = {};
                            data[parent][index][grandchild] = value;
                        } else {
                            if (grandchild) {
                                if (!data[parent][child]) data[parent][child] = {};
                                data[parent][child][grandchild] = value;
                            } else {
                                data[parent][child] = value;
                            }
                        }
                    }
                } else {
                    data[key] = value;
                }
            });
            
            // Process family members
            const familyMembers = [];
            document.querySelectorAll('.family-member').forEach((member, index) => {
                const memberData = {};
                const inputs = member.querySelectorAll('input, select, textarea');
                
                inputs.forEach(input => {
                    const name = input.name;
                    const match = name.match(/anggota_keluarga\[(\d+)\]\[(\w+)\]/);
                    if (match) {
                        const [, , field] = match;
                        if (input.value.trim()) {
                            memberData[field] = input.value;
                        }
                    }
                });
                
                // Only add if there's at least a name
                if (memberData.nama && memberData.nama.trim()) {
                    familyMembers.push(memberData);
                }
            });
            
            if (familyMembers.length > 0) {
                data.anggota_keluarga = familyMembers;
            }
            
            return data;
        }
        
        // Show validation errors
        function showValidationErrors(errors) {
            const alertDiv = document.getElementById('validationAlert');
            const messageSpan = document.getElementById('validationMessage');
            
            if (errors && Object.keys(errors).length > 0) {
                let errorMessage = 'Mohon perbaiki data berikut:<br><ul class="mb-0">';
                
                Object.keys(errors).forEach(field => {
                    errorMessage += `<li>${errors[field][0]}</li>`;
                });
                
                errorMessage += '</ul>';
                
                if (messageSpan) {
                    messageSpan.innerHTML = errorMessage;
                }
                if (alertDiv) {
                    alertDiv.style.display = 'block';
                }
                
                // Scroll to validation alert
                alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                return false;
            }
            
            return true;
        }
        
        // Show review data
        function showReviewData() {
            const data = collectFormData();
            const reviewContainer = document.getElementById('reviewData');
            
            if (!reviewContainer) return;
            
            // Hide validation alert
            const alertDiv = document.getElementById('validationAlert');
            if (alertDiv) {
                alertDiv.style.display = 'none';
            }
            
            let html = `
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th width="30%">Nama Lengkap</th>
                                <td>${data.name || '-'}</td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td>${data.email || '-'}</td>
                            </tr>
                            <tr>
                                <th>NIK</th>
                                <td>${data.nik || '-'}</td>
                            </tr>
                            <tr>
                                <th>Tempat/Tanggal Lahir</th>
                                <td>${data.tempat_lahir || '-'} / ${data.tanggal_lahir || '-'}</td>
                            </tr>
                            <tr>
                                <th>Jenis Kelamin</th>
                                <td>${data.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</td>
                            </tr>
                            <tr>
                                <th>NUPY</th>
                                <td>${data.kepegawaian?.nupy || '-'}</td>
                            </tr>
                            <tr>
                                <th>Jenis GTK</th>
                                <td>${data.kepegawaian?.jenis_gtk || '-'}</td>
                            </tr>
                            <tr>
                                <th>Unit Kerja</th>
                                <td>${getWorkUnitName(data.work_unit_id)}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
            
            reviewContainer.innerHTML = html;
        }
        
        function getWorkUnitName(workUnitId) {
            const select = document.getElementById('work_unit_id');
            if (select && select.options[select.selectedIndex]) {
                return select.options[select.selectedIndex].text;
            }
            return '-';
        }
        
        // Validate all required fields before submit
        function validateAllRequiredFields() {
            let allValid = true;
            const invalidFields = [];
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.classList.remove('is-invalid');
                    
                    let isValid = true;
                    
                    if (!field.value.trim()) {
                        isValid = false;
                    } else if (fieldId === 'nik' && (field.value.length !== 16 || !/^\d+$/.test(field.value))) {
                        isValid = false;
                    } else if (fieldId === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        field.classList.add('is-invalid');
                        allValid = false;
                        invalidFields.push(fieldId);
                    }
                }
            });
            
            return { allValid, invalidFields };
        }
        
        // Submit form
        async function submitForm() {
            // Validate all required fields
            const validation = validateAllRequiredFields();
            if (!validation.allValid) {
                // Show error message using our custom function
                await showAlert({
                    icon: 'error',
                    title: 'Data Belum Lengkap',
                    html: `
                        <p>Masih ada data yang wajib diisi atau tidak valid:</p>
                        <ul class="text-start">
                            ${validation.invalidFields.map(field => {
                                const fieldNames = {
                                    'name': 'Nama Lengkap',
                                    'email': 'Email',
                                    'nik': 'NIK',
                                    'tempat_lahir': 'Tempat Lahir',
                                    'tanggal_lahir': 'Tanggal Lahir',
                                    'jenis_kelamin': 'Jenis Kelamin',
                                    'golongan_darah': 'Golongan Darah',
                                    'status_perkawinan': 'Status Perkawinan',
                                    'nama_ibu_kandung': 'Nama Ibu Kandung',
                                    'provinsi_domisili': 'Provinsi Domisili',
                                    'kabupaten_domisili': 'Kabupaten/Kota Domisili',
                                    'kecamatan_domisili': 'Kecamatan Domisili',
                                    'desa_domisili': 'Desa Domisili',
                                    'jalan_domisili': 'Jalan Domisili',
                                    'rt_rw_domisili': 'RT/RW Domisili',
                                    'no_hp': 'No. HP',
                                    'kontak_darurat': 'Kontak Darurat',
                                    'nupy': 'NUPY',
                                    'jenis_gtk': 'Jenis GTK',
                                    'jabatan': 'Jabatan',
                                    'status_kepegawaian': 'Status Kepegawaian',
                                    'tmt': 'TMT',
                                    'nomor_sk': 'Nomor SK',
                                    'tanggal_sk': 'Tanggal SK',
                                    'work_unit_id': 'Unit Kerja'
                                };
                                return `<li>${fieldNames[field] || field}</li>`;
                            }).join('')}
                        </ul>
                        <p class="mt-3">Silakan lengkapi data tersebut terlebih dahulu.</p>
                    `,
                    confirmButtonText: 'OK'
                });
                
                // Switch to first tab with errors
                if (validation.invalidFields.length > 0) {
                    const firstInvalidField = document.getElementById(validation.invalidFields[0]);
                    if (firstInvalidField) {
                        // Find which tab contains this field
                        const tabContainers = document.querySelectorAll('.tab-pane');
                        tabContainers.forEach(tab => {
                            if (tab.contains(firstInvalidField) && !tab.classList.contains('active')) {
                                const tabId = tab.id + '-tab';
                                switchToTab(tabId);
                            }
                        });
                        
                        // Scroll to and focus on first invalid field
                        setTimeout(() => {
                            firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            firstInvalidField.focus();
                        }, 300);
                    }
                }
                
                return;
            }
            
            // Collect form data
            const data = collectFormData();
            
            // Show loading
            const submitButton = document.getElementById('submitButton');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="ri-loader-4-line label-icon align-middle fs-16 me-2"></i> Menyimpan...';
            }
            
            showLoading('Menyimpan Data', 'Harap tunggu...');
            
            try {
                const response = await fetch('{{ route("gtk.wizard.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                closeLoading();
                
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="ri-save-line label-icon align-middle fs-16 me-2"></i> Simpan Data GTK';
                }
                
                if (result.success) {
                    // Show success message with password info
                    await showAlert({
                        icon: 'success',
                        title: 'Berhasil!',
                        html: `
                            <div class="text-start">
                                <p>Data GTK berhasil disimpan</p>
                                <p><strong>Nama:</strong> ${data.name}</p>
                                <p><strong>Email:</strong> ${data.email}</p>
                                <p><strong>Password:</strong> ${result.data.password}</p>
                                <p class="text-muted"><small>Password di-generate dari NUPY</small></p>
                            </div>
                        `,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = '{{ route("gtk.index") }}';
                    });
                } else {
                    // Show validation errors if available
                    if (result.errors) {
                        showValidationErrors(result.errors);
                        await showAlert({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            text: 'Mohon perbaiki data yang ditandai'
                        });
                    } else {
                        await showAlert({
                            icon: 'error',
                            title: 'Gagal!',
                            text: result.message || 'Terjadi kesalahan saat menyimpan data'
                        });
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                closeLoading();
                
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="ri-save-line label-icon align-middle fs-16 me-2"></i> Simpan Data GTK';
                }
                
                await showAlert({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Terjadi kesalahan jaringan. Silakan coba lagi.'
                });
            }
        }
        
        // Make functions globally available
        window.addFamilyMember = addFamilyMember;
        window.removeFamilyMember = removeFamilyMember;
        window.copyAddressToKTP = copyAddressToKTP;
        window.submitForm = submitForm;
    </script>
@endpush
@section('script')
    <script src="{{ URL::asset('build/js/pages/form-wizard.init.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
