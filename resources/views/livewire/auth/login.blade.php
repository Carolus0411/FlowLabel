<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function mount()
    {
        // It is logged in
        if (auth()->check()) {
            return redirect()->route('order-label.index');
        }

        if (app()->environment('local')) {
            $this->email = 'admin@admin.com';
            $this->password = 'q1w2e3r4';
            $this->remember = true;
        }
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        logger('Login attempt for email: ' . $this->email);
        
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            logger('Login failed for: ' . $this->email);
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        logger('Login successful for: ' . $this->email);
        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectIntended(default: route('order-label.index', absolute: false), navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div>
    <div class="px-10 lg:px-0 flex items-center justify-center min-h-dvh" style="background: linear-gradient(135deg, #5F489C 0%, #3d2f6b 50%, #23B2D2 100%);">
        <div class="w-md shadow">
            <div class="bg-white dark:bg-neutral-800 p-8 space-y-6">

                <!-- Logo -->
                <div class="text-center">
                    <svg style="width: 200px; height: auto; display: block; margin: 0 auto 30px;" viewBox="0 0 123 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M122.697 2.62525C122.576 1.93822 122.235 1.38082 121.669 0.95737C121.103 0.533918 120.135 0.317871 118.761 0.317871C117.417 0.317871 116.466 0.538239 115.9 0.978974C115.334 1.41971 114.989 1.99439 114.872 2.69438C114.751 3.39869 114.695 4.10733 114.695 4.82892V15.735L106.541 3.84375C106.42 3.67955 106.243 3.43326 106.006 3.10919C105.768 2.7808 105.422 2.48698 104.977 2.22772C104.528 1.96414 103.884 1.83019 103.08 1.83019C102.277 1.83019 101.65 1.95982 101.201 2.2234C100.751 2.48698 100.41 2.7808 100.172 3.10487C99.9346 3.43326 99.7531 3.67523 99.6365 3.83943L91.2927 15.8992V4.99744C91.2927 4.27584 91.2322 3.56721 91.1156 2.8629C90.9989 2.15859 90.6532 1.58822 90.0872 1.14749C89.5212 0.706755 88.5662 0.486388 87.2267 0.486388C85.857 0.486388 84.8848 0.698113 84.3187 1.12588C83.7527 1.54933 83.4113 2.10673 83.2904 2.79376C83.1737 3.48079 83.1132 4.1851 83.1132 4.90238V27.1941C83.1132 27.9459 83.1737 28.6675 83.2904 29.3546C83.4113 30.0416 83.7527 30.6076 84.3187 31.0484C84.8848 31.4891 85.8397 31.7095 87.1792 31.7095C87.9829 31.7095 88.6051 31.6619 89.0372 31.5626C89.4693 31.4632 89.815 31.3163 90.0656 31.1218C90.3205 30.9274 90.5971 30.6465 90.8909 30.2879L103.072 12.9048L115.101 30.128C115.399 30.4866 115.676 30.7675 115.926 30.962C116.181 31.1564 116.523 31.3076 116.955 31.4027C117.387 31.5021 118.005 31.5496 118.813 31.5496C120.157 31.5496 121.107 31.3292 121.673 30.8885C122.239 30.4478 122.581 29.8817 122.702 29.1947C122.818 28.5077 122.879 27.7861 122.879 27.0342V4.73386C122.874 4.01227 122.818 3.31228 122.697 2.62525Z" fill="#5F489C"/>
                        <path d="M63.898 31.9903C60.4672 32.0163 57.516 31.3984 54.8456 29.7521C51.4407 27.6608 49.4488 24.5583 48.5155 20.7257C48.0445 18.7899 47.8976 16.8152 48.0099 14.8405C48.2044 11.3881 49.0988 8.15605 51.2852 5.38201C53.2382 2.89748 55.8092 1.38515 58.8252 0.568491C60.977 -0.0148355 63.185 -0.0839695 65.3801 0.0715842C68.7029 0.309236 71.7491 1.37651 74.2942 3.60179C76.3812 5.42522 77.7163 7.74125 78.4811 10.39C79.1077 12.5548 79.3151 14.7757 79.2027 17.014C79.0342 20.3929 78.196 23.5645 76.1219 26.3256C74.1213 28.9873 71.464 30.6336 68.2708 31.4589C66.7325 31.8564 65.1683 32.0465 63.898 31.9903ZM56.103 15.9726C56.2024 17.0313 56.2802 18.0942 56.4055 19.1485C56.4573 19.5849 56.5827 20.017 56.7123 20.4405C57.987 24.6361 61.0764 26.1052 64.8616 25.6342C67.5751 25.2929 69.3769 23.6552 70.293 21.1102C71.3732 18.1028 71.3732 15.0263 70.63 11.9455C70.1936 10.135 69.3165 8.59247 67.7566 7.50791C66.2356 6.4536 64.5418 6.1641 62.7141 6.35422C60.1258 6.62212 58.311 7.92272 57.2049 10.2517C56.3493 12.0665 56.1462 13.9936 56.103 15.9726Z" fill="#5F489C"/>
                        <path d="M37.6267 23.4522C39.3292 23.4522 40.9495 23.4608 42.5742 23.4478C43.4989 23.4392 44.3371 23.6423 45.0501 24.2731C45.6766 24.8305 46.0957 25.5133 46.1735 26.3472C46.3291 28.0064 46.4414 29.6786 44.9118 30.8755C44.376 31.299 43.7754 31.5712 43.0754 31.5712C40.9322 31.5755 38.7934 31.5712 36.6502 31.5885C35.5483 31.5971 34.4422 31.6879 33.3447 31.6576C32.2126 31.6317 31.1367 31.3638 30.3027 30.5083C29.741 29.9292 29.4472 29.2293 29.4429 28.4212C29.4385 20.0948 29.4385 11.7684 29.4429 3.44194C29.4429 2.22344 30.2465 1.04815 31.4607 0.594451C32.8391 0.0802592 34.2477 0.071617 35.6261 0.616055C36.8878 1.11296 37.6267 2.27097 37.631 3.64071L37.6267 23.4522Z" fill="#5F489C"/>
                        <path d="M106.26 22.0349H103.534V25.6213H107.12V22.8948C107.12 22.4151 106.736 22.0349 106.26 22.0349Z" fill="#23B2D2"/>
                        <path d="M99.1352 22.8903V25.6169H102.722V22.0305H99.9951C99.5198 22.0348 99.1352 22.415 99.1352 22.8903Z" fill="#23B2D2"/>
                        <path d="M106.26 30.0201C106.736 30.0201 107.116 29.6355 107.116 29.1645V26.438H103.53V30.0201H106.26Z" fill="#23B2D2"/>
                        <path d="M99.1352 29.16C99.1352 29.6353 99.5198 30.0156 99.9908 30.0156H102.717V26.4292H99.1309V29.16H99.1352Z" fill="#23B2D2"/>
                        <path d="M25.5583 3.8955C25.5583 5.24363 25.3553 6.19856 24.9534 6.76892C24.5472 7.33929 24.033 7.68064 23.4065 7.80163C22.78 7.92261 22.1189 7.98311 21.4318 7.98311H8.21842V12.4121H17.3442C18.0313 12.4121 18.6535 12.4639 19.2066 12.5676C19.7596 12.6713 20.1961 12.9738 20.5072 13.4664C20.8226 13.959 20.9781 14.8059 20.9781 16.0028C20.9781 17.1392 20.8139 17.9472 20.4856 18.4268C20.1572 18.9064 19.7164 19.1916 19.159 19.278C18.606 19.3688 17.9837 19.412 17.2967 19.412H8.21842V27.5397C8.21842 28.1964 8.15793 28.8403 8.03694 29.4711C7.91595 30.0977 7.5746 30.6162 7.00424 31.018C6.43387 31.4199 5.46166 31.623 4.0876 31.623C2.73947 31.623 1.78023 31.4199 1.21418 31.018C0.64382 30.6119 0.302466 30.089 0.18148 29.4452C0.060494 28.8014 0 28.1489 0 27.4921V4.28439C0 2.72885 0.306787 1.65726 0.92036 1.07393C1.53393 0.4906 2.63145 0.196777 4.22155 0.196777H24.9361C25.2732 0.196777 25.5497 0.468996 25.5497 0.806029L25.5583 3.8955Z" fill="#5F489C"/>
                    </svg>
                    <h1 class="text-2xl font-bold text-purple-700 mb-2">Selamat Datang</h1>
                    <p class="text-gray-600 text-sm">Masukkan akun Anda untuk melanjutkan</p>
                </div>

                <x-auth-alert :status="session('status')" class="alert-success" />

                <x-form wire:submit="login">
                    <x-input label="Email" wire:model="email" icon="o-envelope" placeholder="masukkan@email.com" wire:loading.attr="disabled" wire:target="login" />
                    <x-input label="Password" wire:model="password" type="password" icon="o-key" placeholder="••••••••" wire:loading.attr="disabled" wire:target="login" />

                    <div class="space-y-4 my-4">
                        <x-checkbox label="Ingat saya" wire:model="remember" />
                        <x-button label="Masuk" type="submit" icon="o-paper-airplane" class="btn-primary w-full" spinner="login" />
                    </div>

                </x-form>
            </div>
        </div>
    </div>
</div>
