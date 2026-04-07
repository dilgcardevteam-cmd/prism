<?php

namespace App\Http\Controllers;

use App\Models\RolePermissionSetting;
use App\Models\User;
use App\Support\InputSanitizer;
use App\Support\RolePermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('superadmin');
    }

    public function index(Request $request)
    {
        $roleOptions = User::roleOptions();
        $statusOptions = [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
        $provinceOptions = $this->provinceOptions();
        $selectedRole = strtolower(trim((string) $request->query('role', '')));
        $selectedStatus = strtolower(trim((string) $request->query('status', '')));
        $selectedProvince = trim((string) $request->query('province', ''));
        $selectedLgu = trim((string) $request->query('lgu', ''));
        $search = trim((string) $request->query('search', ''));
        $lguOptions = $this->lguOptions($selectedProvince);

        $usersQuery = User::query()
            ->orderByRaw("CASE WHEN LOWER(TRIM(COALESCE(status, ''))) = 'inactive' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->orderByDesc('idno');

        if ($search !== '') {
            $searchKeyword = '%' . strtolower($search) . '%';

            $usersQuery->where(function ($query) use ($searchKeyword) {
                $query
                    ->whereRaw("LOWER(TRIM(COALESCE(CONCAT_WS(' ', fname, lname), ''))) LIKE ?", [$searchKeyword])
                    ->orWhereRaw("LOWER(TRIM(COALESCE(emailaddress, ''))) LIKE ?", [$searchKeyword])
                    ->orWhereRaw("LOWER(TRIM(COALESCE(username, ''))) LIKE ?", [$searchKeyword])
                    ->orWhereRaw("LOWER(TRIM(COALESCE(agency, ''))) LIKE ?", [$searchKeyword])
                    ->orWhereRaw("LOWER(TRIM(COALESCE(position, ''))) LIKE ?", [$searchKeyword])
                    ->orWhereRaw("LOWER(TRIM(COALESCE(region, ''))) LIKE ?", [$searchKeyword])
                    ->orWhereRaw("LOWER(TRIM(COALESCE(province, ''))) LIKE ?", [$searchKeyword])
                    ->orWhereRaw("LOWER(TRIM(COALESCE(office, ''))) LIKE ?", [$searchKeyword]);
            });
        }

        if ($selectedRole !== '' && array_key_exists($selectedRole, $roleOptions)) {
            $usersQuery->whereRaw('LOWER(TRIM(COALESCE(role, ""))) = ?', [$selectedRole]);
        } else {
            $selectedRole = '';
        }

        if ($selectedStatus !== '' && array_key_exists($selectedStatus, $statusOptions)) {
            $usersQuery->whereRaw('LOWER(TRIM(COALESCE(status, ""))) = ?', [$selectedStatus]);
        } else {
            $selectedStatus = '';
        }

        if ($selectedProvince !== '') {
            $usersQuery->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [strtolower($selectedProvince)]);
        }

        if ($selectedLgu !== '') {
            $usersQuery
                ->whereRaw('LOWER(TRIM(COALESCE(agency, ""))) = ?', ['lgu'])
                ->whereRaw('LOWER(TRIM(COALESCE(office, ""))) = ?', [strtolower($selectedLgu)]);
        }

        $users = $usersQuery->paginate(15)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'accessGrantModules' => $this->accessGrantModules(),
            'crudActionOptions' => RolePermissionRegistry::actionOptions(),
            'roleOptions' => $roleOptions,
            'statusOptions' => $statusOptions,
            'provinceOptions' => $provinceOptions,
            'lguOptions' => $lguOptions,
            'filters' => [
                'search' => $search,
                'role' => $selectedRole,
                'status' => $selectedStatus,
                'province' => $selectedProvince,
                'lgu' => $selectedLgu,
            ],
        ]);
    }

    private function provinceOptions(): array
    {
        return User::query()
            ->selectRaw("TRIM(COALESCE(province, '')) as value")
            ->whereRaw("TRIM(COALESCE(province, '')) <> ''")
            ->distinct()
            ->orderBy('value')
            ->pluck('value')
            ->all();
    }

    private function lguOptions(string $selectedProvince = ''): array
    {
        $query = User::query()
            ->selectRaw("TRIM(COALESCE(office, '')) as value")
            ->whereRaw("LOWER(TRIM(COALESCE(agency, ''))) = ?", ['lgu'])
            ->whereRaw("TRIM(COALESCE(office, '')) <> ''");

        if ($selectedProvince !== '') {
            $query->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [strtolower($selectedProvince)]);
        }

        return $query
            ->distinct()
            ->orderBy('value')
            ->pluck('value')
            ->all();
    }

    public function create()
    {
        return view('admin.users.create');
    }

    private function roleValidationRule(): array
    {
        return ['required', 'in:' . implode(',', array_keys(User::roleOptions()))];
    }

    private function sanitizeUserPayload(array $validated): array
    {
        $validated = InputSanitizer::sanitizeTextFields($validated, [
            'fname',
            'lname',
            'agency',
            'position',
            'region',
            'province',
            'username',
        ]);

        $validated = InputSanitizer::sanitizeTextFields($validated, ['office'], false, true);

        if (array_key_exists('emailaddress', $validated)) {
            $validated['emailaddress'] = strtolower(trim((string) $validated['emailaddress']));
        }

        if (array_key_exists('mobileno', $validated)) {
            $validated['mobileno'] = preg_replace('/\D+/', '', (string) $validated['mobileno']);
        }

        return $validated;
    }

    private function concretePermissionSet(array $permissions): array
    {
        $normalizedPermissions = RolePermissionRegistry::normalizePermissions($permissions);

        if (in_array('*', $normalizedPermissions, true)) {
            $normalizedPermissions = RolePermissionRegistry::validPermissionKeys();
        }

        sort($normalizedPermissions);

        return array_values($normalizedPermissions);
    }

    private function resolveUserAccessValue(string $role, array $permissions): ?string
    {
        $selectedPermissions = $this->concretePermissionSet($permissions);
        $defaultPermissions = $this->concretePermissionSet(
            RolePermissionRegistry::permissionsForRole(
                $role,
                RolePermissionSetting::permissionsForRole($role),
            ),
        );
        $allPermissions = RolePermissionRegistry::validPermissionKeys();

        sort($allPermissions);

        if ($selectedPermissions === $defaultPermissions) {
            return null;
        }

        if ($selectedPermissions === []) {
            return User::ACCESS_SCOPE_NONE;
        }

        if ($selectedPermissions === array_values($allPermissions)) {
            return User::ACCESS_SCOPE_ALL;
        }

        return User::ACCESS_PERMISSION_PREFIX . implode(',', $selectedPermissions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fname' => ['required', 'string', 'max:255'],
            'lname' => ['required', 'string', 'max:255'],
            'emailaddress' => ['required', 'email', 'unique:tbusers,emailaddress'],
            'username' => ['required', 'string', 'unique:tbusers,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'agency' => ['required', 'string'],
            'position' => ['required', 'string'],
            'region' => ['required', 'string'],
            'province' => ['required', 'string'],
            'office' => ['nullable', 'string'],
            'mobileno' => ['required', 'digits:11'],
            'role' => $this->roleValidationRule(),
            'status' => ['required', 'in:active,inactive'],
        ]);

        $validated = $this->sanitizeUserPayload($validated);
        $validated['password'] = Hash::make($validated['password']);
        $validated['email_verified_at'] = now();

        User::create($validated);

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    private function accessGrantModules(): array
    {
        return RolePermissionRegistry::modules();
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'fname' => ['required', 'string', 'max:255'],
            'lname' => ['required', 'string', 'max:255'],
            'emailaddress' => ['required', 'email', "unique:tbusers,emailaddress,{$user->idno},idno"],
            'username' => ['required', 'string', "unique:tbusers,username,{$user->idno},idno"],
            'agency' => ['required', 'string'],
            'position' => ['required', 'string'],
            'region' => ['required', 'string'],
            'province' => ['required', 'string'],
            'office' => ['nullable', 'string'],
            'mobileno' => ['required', 'digits:11'],
            'role' => $this->roleValidationRule(),
            'crud_permissions' => ['nullable', 'array'],
            'crud_permissions.*' => ['string'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
            $validated['password'] = Hash::make($request->password);
        }

        $validated = $this->sanitizeUserPayload($validated);
        $validated['access'] = $this->resolveUserAccessValue(
            $validated['role'],
            $validated['crud_permissions'] ?? [],
        );
        unset($validated['crud_permissions']);
        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        if ($user->idno === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }

    public function block(User $user)
    {
        if ($user->idno === Auth::id()) {
            return back()->with('error', 'You cannot change the status of your own account.');
        }

        $isInactive = strtolower(trim((string) $user->status)) === 'inactive';

        $user->update([
            'status' => $isInactive ? 'active' : 'inactive',
        ]);

        if ($isInactive) {
            return redirect()->route('users.index')->with('success', 'User activated successfully.');
        }

        return redirect()->route('users.index')->with('success', 'User deactivated successfully.');
    }

    public function updateAccess(Request $request, User $user)
    {
        $redirectTo = route('users.index', ['tab' => 'access-grants']);

        if ($request->filled('redirect_to')) {
            $safeRedirect = InputSanitizer::sanitizeInternalRedirect($request->input('redirect_to'));
            if ($safeRedirect) {
                $redirectTo = $safeRedirect;
            }
        }

        return redirect()
            ->to($redirectTo)
            ->with('error', 'User-specific access grants are now managed through the Role Configuration page.');
    }
}
