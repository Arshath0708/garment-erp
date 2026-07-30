<x-guest-layout>
    <div class="mb-4">
        <h2 class="auth-brand">Create Account</h2>
        <p class="text-muted">Start managing your ERP efficiently today.</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="mb-3">
            <label class="form-label fw-bold text-secondary small">Full Name</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="John Doe" value="{{ old('name') }}" required autofocus autocomplete="name">
            </div>
            @error('name')
                <div class="text-danger small mt-1 d-block">{{ $message }}</div>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="mb-3">
            <label class="form-label fw-bold text-secondary small">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="name@example.com" value="{{ old('email') }}" required autocomplete="username">
            </div>
            @error('email')
                <div class="text-danger small mt-1 d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <!-- Password -->
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold text-secondary small">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control border-end-0 @error('password') is-invalid @enderror" placeholder="••••••••" required autocomplete="new-password">
                    <button class="input-group-text toggle-password" type="button" aria-label="Toggle password visibility" style="cursor: pointer; border-left: none;">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('password')
                    <div class="text-danger small mt-1 d-block">{{ $message }}</div>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="col-md-6 mb-4">
                <label class="form-label fw-bold text-secondary small">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
                    <input type="password" name="password_confirmation" class="form-control border-end-0 @error('password_confirmation') is-invalid @enderror" placeholder="••••••••" required autocomplete="new-password">
                    <button class="input-group-text toggle-password" type="button" aria-label="Toggle password visibility" style="cursor: pointer; border-left: none;">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-modern w-100 mb-4">
            Create Account
        </button>

        <p class="text-center text-muted small">
            Already have an account? <a href="{{ route('login') }}" class="text-primary fw-bold text-decoration-none">Sign in here</a>
        </p>

    </form>
</x-guest-layout>
