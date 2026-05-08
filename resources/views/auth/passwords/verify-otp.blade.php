@extends('layouts.master-without-nav')
@section('title')
@lang('translation.two-step-verification')
@endsection
@section('content')

        <div class="auth-page-wrapper pt-5">
            <!-- auth page bg -->
            <div class="auth-one-bg-position auth-one-bg"  id="auth-particles">
                <div class="bg-overlay"></div>

                <div class="shape">
                    <svg xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 1440 120">
                        <path d="M 0,36 C 144,53.6 432,123.2 720,124 C 1008,124.8 1296,56.8 1440,40L1440 140L0 140z"></path>
                    </svg>
                </div>
            </div>

            <!-- auth page content -->
            <div class="auth-page-content">
                <div class="container">
                    
                    <div class="row justify-content-center">
                        <div class="col-md-8 col-lg-6 col-xl-5">
                            <div class="card mt-4">

                                <div class="card-body p-4">
                                    <div class="col-lg-12">
                                        <div class="text-center  mb-4 text-white-50">
                                            <div>
                                                <a href="index" class="d-inline-block auth-logo">
                                                    <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="" height="70">
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="p-2 mt-4">

                                        <div class="text-muted text-center mb-4">
                                            <h4>Verifikasi Email</h4>
                                            <p>Masukkan 6 digit kode OTP yang dikirim ke email Anda</p>
                                        </div>

                                        <form method="POST" action="{{ route('password.otp.verify') }}" id="otp-form" autocomplete="off">
                                            @csrf

                                            <input type="hidden" name="otp" id="otp">

                                            <div class="row justify-content-center gap-1">
                                                @for ($i = 1; $i <= 6; $i++)
                                                    <div class="col-lg-1" style="width: 50px; padding: 0;">
                                                        <input
                                                            type="text"
                                                            class="form-control form-control-lg text-center otp-input"
                                                            maxlength="1"
                                                            data-index="{{ $i }}"
                                                            inputmode="numeric"
                                                            pattern="[0-9]*"
                                                            required
                                                        >
                                                    </div>
                                                @endfor
                                            </div>

                                            @error('otp')
                                                <div class="alert alert-danger mt-3 text-center">
                                                    {{ $message }}
                                                </div>
                                            @enderror

                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-success w-100">
                                                    Verifikasi OTP
                                                </button>
                                            </div>

                                        </form>
                                    </div>

                                </div>
                                <!-- end card body -->
                            </div>
                            <!-- end card -->
                            <script>
                                document.addEventListener('DOMContentLoaded', () => {
                                    const inputs = document.querySelectorAll('.otp-input');
                                    const otpHidden = document.getElementById('otp');

                                    inputs.forEach((input, index) => {

                                        input.addEventListener('input', (e) => {
                                            input.value = input.value.replace(/[^0-9]/g, '');

                                            if (input.value && index < inputs.length - 1) {
                                                inputs[index + 1].focus();
                                            }

                                            updateOtp();
                                        });

                                        input.addEventListener('keydown', (e) => {
                                            if (e.key === 'Backspace' && !input.value && index > 0) {
                                                inputs[index - 1].focus();
                                            }
                                        });

                                        input.addEventListener('paste', (e) => {
                                            e.preventDefault();
                                            const data = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                                            data.split('').forEach((num, i) => {
                                                if (inputs[i]) inputs[i].value = num;
                                            });
                                            updateOtp();
                                            inputs[Math.min(data.length, 5)].focus();
                                        });
                                    });

                                    function updateOtp() {
                                        otpHidden.value = Array.from(inputs).map(i => i.value).join('');
                                    }
                                });
                            </script>


                            <div class="mt-4 text-center">
                                OTP berlaku <span id="timer">10:00</span>
                            </div>

                            <script>
                                let time = 600;
                                setInterval(() => {
                                    let m = Math.floor(time/60);
                                    let s = time%60;
                                    document.getElementById('timer').innerText =
                                        m+":"+(s<10?'0':'')+s;
                                    time--;
                                },1000);
                            </script>

                        </div>
                    </div>
                    <!-- end row -->
                </div>
                <!-- end container -->
            </div>
            <!-- end auth page content -->

            <!-- footer -->
            <footer class="footer start-0">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="text-center">
                                &copy; <script>
                                    document.write(new Date().getFullYear())
                                </script> Alim. by Ponpes Abu Hurairah Mataram</p>
                        </div>
                        </div>
                    </div>
                </div>
            </footer>
            <!-- end Footer -->
        </div>
        <!-- end auth-page-wrapper -->


@endsection
@section('script')
    <script src="{{ URL::asset('build/libs/particles.js/particles.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/particles.app.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/two-step-verification.init.js') }}"></script>
@endsection
