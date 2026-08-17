<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\UpdateCompanyProfileRequest;
use App\Models\CompanyProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Singleton settings screen — edit/update only, no index/create/destroy.
 * Every export document (invoice, bank docs, etc.) prints from
 * CompanyProfile::current() rather than hard-coded values.
 */
class CompanyProfileController extends Controller implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:company-profile.view', only: ['edit']),
            new Middleware('permission:company-profile.edit', only: ['update']),
        ];
    }

    public function edit(): View
    {
        return view('administration.company-profile.edit', [
            'profile' => CompanyProfile::current(),
        ]);
    }

    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        $profile = CompanyProfile::current();
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            if ($profile->hasLogo()) {
                Storage::disk('public')->delete($profile->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('company-profile', 'public');
        }

        $profile->update($data);

        return redirect()
            ->route('user-management.company-profile.edit')
            ->with('success', 'Company profile updated successfully.');
    }
}
