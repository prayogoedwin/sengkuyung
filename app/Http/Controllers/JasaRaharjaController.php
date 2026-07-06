<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class JasaRaharjaController extends Controller
{
    public const ROLE_NAME = 'jasa_raharja';

    public function index(): View
    {
        $this->ensureSuperAdmin();

        $users = User::query()
            ->role(self::ROLE_NAME, 'web')
            ->orderBy('id')
            ->get();

        return view('backend.jasa-raharja.index', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'api_key' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['username'],
            'whatsapp' => 'jr-' . Str::slug($validated['username']),
            'password' => Hash::make($validated['password']),
            'apikey' => $validated['api_key'],
        ]);

        $role = Role::findByName(self::ROLE_NAME, 'web');
        $user->syncRoles([$role]);

        return redirect()->route('jasa-raharja.index')->with('success', 'Akun Jasa Raharja berhasil ditambahkan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $user = User::query()->role(self::ROLE_NAME, 'web')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'api_key' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['username'];
        $user->apikey = $validated['api_key'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('jasa-raharja.index')->with('success', 'Akun Jasa Raharja berhasil diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $user = User::query()->role(self::ROLE_NAME, 'web')->findOrFail($id);
        $user->delete();

        return redirect()->route('jasa-raharja.index')->with('success', 'Akun Jasa Raharja berhasil dihapus.');
    }

    private function ensureSuperAdmin(): void
    {
        $user = auth()->user();
        $isSuperAdmin = $user && ($user->hasRole('super-admin') || $user->hasRole('superadmin'));

        abort_unless($isSuperAdmin, 403, 'Akses hanya untuk superadmin.');
    }
}
