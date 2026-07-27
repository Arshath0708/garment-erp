<x-app-layout>
    <x-slot name="header">
        {{ __('Profile') }}
    </x-slot>

    <div class="row">
        <div class="col-md-6">
            <div class="card card-primary card-outline mb-4">
                <div class="card-header"><h3 class="card-title">Profile Information</h3></div>
                <div class="card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
            
            <div class="card card-danger card-outline mb-4">
                <div class="card-header"><h3 class="card-title">Delete Account</h3></div>
                <div class="card-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-success card-outline mb-4">
                <div class="card-header"><h3 class="card-title">Update Password</h3></div>
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
