<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $creator = null): User
    {
        return DB::transaction(function () use ($data, $creator) {
            $user = User::create([
                'name'       => $data['name'],
                'email'      => $data['email'],
                'phone'      => $data['phone'] ?? null,
                'password'   => Hash::make($data['password']),
                'status'     => $data['status'] ?? true,
                'created_by' => $creator?->id,
            ]);

            $user->syncRoles($data['roles']);

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'name'   => $data['name'],
                'email'  => $data['email'],
                'phone'  => $data['phone'] ?? null,
                'status' => $data['status'] ?? false,
            ]);

            // A blank password field means "leave it alone", not "clear it".
            if (filled($data['password'] ?? null)) {
                $user->update(['password' => Hash::make($data['password'])]);
            }

            $user->syncRoles($data['roles']);

            return $user->refresh();
        });
    }

    /**
     * @return array{allowed: bool, reason: ?string}
     */
    public function canDelete(User $user, User $actor): array
    {
        if ($user->isProtected()) {
            return ['allowed' => false, 'reason' => 'The Super Admin account cannot be deleted.'];
        }

        if ($user->is($actor)) {
            return ['allowed' => false, 'reason' => 'You cannot delete your own account.'];
        }

        return ['allowed' => true, 'reason' => null];
    }
}
