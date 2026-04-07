@extends('layouts.dashboard')

@section('title', 'View User')
@section('page-title', 'View User')

@section('content')
    @php
        $roleOptions = \App\Models\User::roleOptions();
        $roleDescriptions = \App\Support\RolePermissionRegistry::roleDescriptions();
        $accessGrantModules = \App\Support\RolePermissionRegistry::modules();
        $crudActionOptions = \App\Support\RolePermissionRegistry::actionOptions();
        $selectedRole = $user->normalizedRole();
        $unassignedRoleDescription = 'No role is assigned yet. An administrator must choose a role before this account receives role-based access.';
        $roleDescription = $selectedRole !== ''
            ? ($roleDescriptions[$selectedRole] ?? 'Role access follows the configured module scope for this classification.')
            : $unassignedRoleDescription;
        $permissionsByRole = collect(array_keys($roleOptions))
            ->mapWithKeys(function (string $role) {
                return [
                    $role => \App\Support\RolePermissionRegistry::permissionsForRole(
                        $role,
                        \App\Models\RolePermissionSetting::permissionsForRole($role),
                    ),
                ];
            })
            ->all();
        $selectedPermissions = $user->effectiveConcreteCrudPermissions();
        $userHasCustomPermissions = $user->hasCustomCrudPermissions();
        $totalPermissionCount = collect($accessGrantModules)
            ->sum(function (array $module) {
                return collect($module['items'] ?? [])
                    ->sum(fn (array $item) => count(\App\Support\RolePermissionRegistry::actionsForItem($item)));
            });
        $selectedPermissionCount = count($selectedPermissions);
    @endphp

    <div class="content-header">
        <h1>View User</h1>
        <p>Preview user information and account settings.</p>
    </div>

    @if (session('success'))
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
            {{ session('error') }}
        </div>
    @endif

    <section>
        <form action="{{ route('users.update', $user->idno) }}" method="POST" id="userPreviewForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="status" value="{{ $user->status }}">

            <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; gap: 16px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #002C76 0%, #003d99 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; margin-right: 20px;">
                            {{ strtoupper(substr($user->fname, 0, 1) . substr($user->lname, 0, 1)) }}
                        </div>
                        <div>
                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 4px;">
                                <h2 style="margin: 0; color: #002C76; font-size: 20px;">{{ $user->fname }} {{ $user->lname }}</h2>
                                <div class="user-status-pill-group" data-status-group aria-label="User status" data-edit-state="inactive">
                                    <span class="user-status-pill" data-status-value="{{ strtolower((string) $user->status) }}" data-active="true" style="
                                        padding-top: 2px;
                                        padding-right: 8px;
                                        padding-left: 8px;
                                        padding-bottom: 2px;
                                    ">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </div>
                            </div>
                            <p style="margin: 0; color: #6b7280; font-size: 14px;">{{ $user->username }}</p>
                            <p class="user-preview-meta-text">Registered At: {{ $user->created_at ? $user->created_at->format('Y-m-d h:i A') : '-' }}</p>
                            <p class="user-preview-meta-text">Email Verified At: {{ $user->email_verified_at ? $user->email_verified_at->format('Y-m-d h:i A') : '-' }}</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <a href="{{ route('users.index') }}" class="user-preview-secondary-btn">
                            <i data-feather="arrow-left" style="width: 16px; height: 16px;"></i> Back
                        </a>
                        <button type="button" id="userEditToggleBtn" class="user-preview-primary-btn">
                            <i data-feather="edit-3" style="width: 16px; height: 16px;"></i> Edit
                        </button>
                        <button type="button" id="userSaveBtn" class="user-preview-primary-btn" style="display: none;" disabled>
                            <i data-feather="save" style="width: 16px; height: 16px;"></i> Save
                        </button>
                        <button type="button" id="userCancelBtn" class="user-preview-secondary-btn !bg-red-400 !text-white" style="display: none;">
                            <i data-feather="x" style="width: 16px; height: 16px;"></i> Cancel
                        </button>
                    </div>
                </div>

                <div class="user-preview-tabs">
                    <div class="user-preview-tab-list" role="tablist" aria-label="User information sections">
                        <button
                            type="button"
                            class="user-preview-tab is-active"
                            id="user-preview-tab-personal"
                            role="tab"
                            aria-selected="true"
                            aria-controls="user-preview-panel-personal"
                            data-user-tab="personal"
                        >
                            Personal Information
                        </button>
                        <button
                            type="button"
                            class="user-preview-tab"
                            id="user-preview-tab-organization"
                            role="tab"
                            aria-selected="false"
                            aria-controls="user-preview-panel-organization"
                            data-user-tab="organization"
                        >
                            Organization Information
                        </button>
                        <button
                            type="button"
                            class="user-preview-tab"
                            id="user-preview-tab-system-access"
                            role="tab"
                            aria-selected="false"
                            aria-controls="user-preview-panel-system-access"
                            data-user-tab="system-access"
                        >
                            System Access
                        </button>
                        <button
                            type="button"
                            class="user-preview-tab"
                            id="user-preview-tab-account"
                            role="tab"
                            aria-selected="false"
                            aria-controls="user-preview-panel-account"
                            data-user-tab="account"
                        >
                            Account Information
                        </button>
                    </div>

                    <div
                        class="user-preview-tab-panel is-active"
                        id="user-preview-panel-personal"
                        role="tabpanel"
                        aria-labelledby="user-preview-tab-personal"
                        data-user-tab-panel="personal"
                    >
                        <div class="user-preview-grid user-preview-grid--wide">
                            <div>
                                <label class="user-preview-label">First Name <span class="user-preview-required">*</span></label>
                                <input type="text" name="fname" value="{{ $user->fname }}" required class="user-preview-input" data-editable>
                            </div>
                            <div>
                                <label class="user-preview-label">Last Name <span class="user-preview-required">*</span></label>
                                <input type="text" name="lname" value="{{ $user->lname }}" required class="user-preview-input" data-editable>
                            </div>
                            <div>
                                <label class="user-preview-label">Email Address <span class="user-preview-required">*</span></label>
                                <input type="email" name="emailaddress" value="{{ $user->emailaddress }}" required class="user-preview-input" data-editable>
                            </div>
                            <div>
                                <label class="user-preview-label">Mobile Number <span class="user-preview-required">*</span></label>
                                <input type="text" name="mobileno" value="{{ $user->mobileno }}" required maxlength="11" pattern="[0-9]{11}" inputmode="numeric" class="user-preview-input" data-editable>
                            </div>
                        </div>
                    </div>

                    <div
                        class="user-preview-tab-panel"
                        id="user-preview-panel-organization"
                        role="tabpanel"
                        aria-labelledby="user-preview-tab-organization"
                        data-user-tab-panel="organization"
                        hidden
                    >
                        <div class="user-preview-grid user-preview-grid--two-column">
                            <div>
                                <label class="user-preview-label">Agency/LGU <span class="user-preview-required">*</span></label>
                                <select id="agencySelect" name="agency" required class="user-preview-input" data-editable>
                                    <option value="">Select Agency/LGU</option>
                                    <option value="DILG" @selected($user->agency === 'DILG')>DILG</option>
                                    <option value="LGU" @selected($user->agency === 'LGU')>LGU</option>
                                </select>
                            </div>
                            <div>
                                <label class="user-preview-label">Position <span class="user-preview-required">*</span></label>
                                <select id="positionSelect" name="position" required class="user-preview-input" data-editable>
                                    <option value="{{ $user->position }}" selected>{{ $user->position }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="user-preview-label">Region <span class="user-preview-required">*</span></label>
                                <input type="text" name="region" value="{{ $user->region }}" required class="user-preview-input" data-editable>
                            </div>
                            <div>
                                <label class="user-preview-label">Province <span class="user-preview-required">*</span></label>
                                <select id="provinceSelect" name="province" required class="user-preview-input" data-editable>
                                    <option value="{{ $user->province }}" selected>{{ $user->province }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="user-preview-label">Office</label>
                                <select id="officeSelect" name="office" class="user-preview-input" data-editable>
                                    <option value="{{ $user->office }}" selected>{{ $user->office }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div
                        class="user-preview-tab-panel"
                        id="user-preview-panel-system-access"
                        role="tabpanel"
                        aria-labelledby="user-preview-tab-system-access"
                        data-user-tab-panel="system-access"
                        hidden
                    >
                        <div class="user-preview-access-tabs">
                            <div class="user-preview-access-tab-list" role="tablist" aria-label="System access tabs">
                                <button
                                    type="button"
                                    class="user-preview-access-tab is-active"
                                    id="user-preview-access-tab-role"
                                    role="tab"
                                    aria-selected="true"
                                    aria-controls="user-preview-access-panel-role"
                                    data-user-access-tab="role"
                                >
                                    Role
                                </button>
                                <button
                                    type="button"
                                    class="user-preview-access-tab"
                                    id="user-preview-access-tab-permissions"
                                    role="tab"
                                    aria-selected="false"
                                    aria-controls="user-preview-access-panel-permissions"
                                    data-user-access-tab="permissions"
                                >
                                    Permissions
                                </button>
                            </div>

                            <div
                                class="user-preview-access-panel is-active"
                                id="user-preview-access-panel-role"
                                role="tabpanel"
                                aria-labelledby="user-preview-access-tab-role"
                                data-user-access-panel="role"
                            >
                                <div class="user-preview-access-card">
                                    <div class="user-preview-role-picker-head">
                                        <label class="user-preview-label">Role Classification <span class="user-preview-required">*</span></label>
                                        <p>Select from the available role classifications. Changing the role updates access only and does not overwrite the saved agency or location fields.</p>
                                    </div>
                                    <select
                                        id="roleSelect"
                                        name="role"
                                        required
                                        class="user-preview-input user-preview-role-select-proxy"
                                        data-editable
                                        aria-hidden="true"
                                        tabindex="-1"
                                    >
                                        <option value="" @selected($selectedRole === '') disabled>Unassigned - select a role</option>
                                        @foreach($roleOptions as $roleValue => $roleLabel)
                                            <option value="{{ $roleValue }}" @selected($selectedRole === $roleValue)>{{ $roleLabel }}</option>
                                        @endforeach
                                    </select>
                                    <div class="user-preview-role-options" role="list" aria-label="Role classifications">
                                        @foreach($roleOptions as $roleValue => $roleLabel)
                                            <button
                                                type="button"
                                                class="user-preview-role-option @if($selectedRole === $roleValue) is-selected @endif"
                                                data-role-option="{{ $roleValue }}"
                                                aria-pressed="{{ $selectedRole === $roleValue ? 'true' : 'false' }}"
                                                aria-disabled="true"
                                                tabindex="-1"
                                            >
                                                <span class="user-preview-role-option-check" aria-hidden="true">
                                                    <i data-feather="check" style="width: 14px; height: 14px;"></i>
                                                </span>
                                                <span class="user-preview-role-option-copy">
                                                    <strong>{{ $roleLabel }}</strong>
                                                    <small>{{ $roleDescriptions[$roleValue] ?? 'Role access follows the configured module scope for this classification.' }}</small>
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                    <div class="user-preview-role-summary" id="userPreviewRoleSummary">
                                        <span>Selected Role Scope</span>
                                        <p>{{ $roleDescription }}</p>
                                    </div>
                                    <div
                                        id="userPreviewRoleDescriptions"
                                        data-role-descriptions='@json(array_merge(["" => $unassignedRoleDescription], $roleDescriptions))'
                                        hidden
                                    ></div>
                                    <div
                                        id="userPreviewRoleFallbackDescriptions"
                                        data-fallback-description="Role access follows the configured module scope for this classification."
                                        hidden
                                    ></div>
                                </div>
                            </div>

                            <div
                                class="user-preview-access-panel"
                                id="user-preview-access-panel-permissions"
                                role="tabpanel"
                                aria-labelledby="user-preview-access-tab-permissions"
                                data-user-access-panel="permissions"
                                hidden
                            >
                                <div class="user-preview-permission-table-card">
                                    <div class="user-preview-permission-table-head">
                                        <div>
                                            <h4>Role Configuration Permissions</h4>
                                            <p>Permission data starts from the configured role matrix. In edit mode, you can add or remove access for this specific user.</p>
                                        </div>
                                        <span class="user-preview-permission-count" id="userPreviewPermissionCount">
                                            {{ $selectedPermissionCount }} of {{ $totalPermissionCount }} permissions allowed
                                        </span>
                                    </div>

                                    <div class="user-preview-permission-table-wrap">
                                        <table class="user-preview-permission-table">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Module</th>
                                                    <th scope="col">Submodule</th>
                                                    <th scope="col">Description</th>
                                                    @foreach($crudActionOptions as $actionKey => $actionLabel)
                                                        <th scope="col">{{ $actionLabel }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($accessGrantModules as $module)
                                                    @php
                                                        $items = $module['items'] ?? [];
                                                        $rowspan = count($items);
                                                    @endphp
                                                    @foreach($items as $itemIndex => $item)
                                                        @php
                                                            $availableActions = \App\Support\RolePermissionRegistry::actionsForItem($item);
                                                            $itemPermissionKeys = collect($availableActions)
                                                                ->map(fn (string $actionKey) => $item['aspect'] . '.' . $actionKey)
                                                                ->all();
                                                            $allowedActionCount = count(array_intersect($itemPermissionKeys, $selectedPermissions));
                                                        @endphp
                                                        <tr
                                                            class="user-preview-permission-row {{ $allowedActionCount > 0 ? 'is-allowed' : '' }}"
                                                            data-permission-item-row
                                                        >
                                                            @if($itemIndex === 0)
                                                                <td rowspan="{{ $rowspan }}" class="user-preview-permission-module-cell">
                                                                    <div class="user-preview-permission-module-title">{{ $module['module'] }}</div>
                                                                    <div class="user-preview-permission-module-description">{{ $module['description'] }}</div>
                                                                </td>
                                                            @endif
                                                            <td class="user-preview-permission-submodule-cell" data-label="Submodule">
                                                                <div class="user-preview-permission-mobile-module">
                                                                    <div class="user-preview-permission-mobile-label">Module</div>
                                                                    <div class="user-preview-permission-module-title">{{ $module['module'] }}</div>
                                                                    <div class="user-preview-permission-module-description">{{ $module['description'] }}</div>
                                                                </div>
                                                                {{ $item['label'] }}
                                                            </td>
                                                            <td class="user-preview-permission-description-cell" data-label="Description">
                                                                {{ $item['description'] }}
                                                            </td>
                                                            @foreach($crudActionOptions as $actionKey => $actionLabel)
                                                                @if(in_array($actionKey, $availableActions, true))
                                                                    @php
                                                                        $permissionKey = $item['aspect'] . '.' . $actionKey;
                                                                        $hasPermission = in_array($permissionKey, $selectedPermissions, true);
                                                                    @endphp
                                                                    <td class="user-preview-permission-check-cell" data-label="{{ $actionLabel }}">
                                                                        <label class="user-preview-permission-check-item">
                                                                            <input
                                                                                type="checkbox"
                                                                                name="crud_permissions[]"
                                                                                class="user-preview-permission-check"
                                                                                data-permission-checkbox
                                                                                data-permission-key="{{ $permissionKey }}"
                                                                                value="{{ $permissionKey }}"
                                                                                @checked($hasPermission)
                                                                                disabled
                                                                            >
                                                                            <span>Allow</span>
                                                                        </label>
                                                                    </td>
                                                                @else
                                                                    <td class="user-preview-permission-check-cell user-preview-permission-check-cell--na" data-label="{{ $actionLabel }}">
                                                                        <span class="user-preview-permission-na">N/A</span>
                                                                    </td>
                                                                @endif
                                                            @endforeach
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div
                                    id="userPreviewPermissionsByRole"
                                    data-permissions-by-role='@json($permissionsByRole)'
                                    hidden
                                ></div>
                                <div
                                    id="userPreviewCurrentPermissions"
                                    data-current-permissions='@json($selectedPermissions)'
                                    data-has-custom-permissions="{{ $userHasCustomPermissions ? 'true' : 'false' }}"
                                    hidden
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="user-preview-tab-panel"
                        id="user-preview-panel-account"
                        role="tabpanel"
                        aria-labelledby="user-preview-tab-account"
                        data-user-tab-panel="account"
                        hidden
                    >
                        <div class="user-preview-grid user-preview-grid--two-column">
                            <div>
                                <label class="user-preview-label">Username <span class="user-preview-required">*</span></label>
                                <input type="text" name="username" value="{{ $user->username }}" required class="user-preview-input" data-editable>
                            </div>
                            <div>
                                <label class="user-preview-label">Password <span style="color: #999; font-size: 12px;">(Leave blank to keep current)</span></label>
                                <input type="password" name="password" value="" class="user-preview-input" data-editable autocomplete="new-password">
                            </div>
                            <div>
                                <label class="user-preview-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" value="" class="user-preview-input" data-editable autocomplete="new-password">
                            </div>
                            <div>
                                <label class="user-preview-label">Registered At</label>
                                <input type="text" value="{{ $user->created_at ? $user->created_at->format('Y-m-d h:i A') : '-' }}" class="user-preview-input user-preview-input--meta" disabled>
                                <p class="user-preview-meta-text">Recorded when the account was first created.</p>
                            </div>
                            <div>
                                <label class="user-preview-label">Registration IP Address</label>
                                <input type="text" value="{{ $user->registration_ip_address ?: 'Not captured' }}" class="user-preview-input user-preview-input--meta" disabled>
                                <p class="user-preview-meta-text">Best-effort client IP captured from the registration request.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <style>
        .user-preview-tabs {
            border-top: 1px solid #e5e7eb;
            padding-top: 24px;
        }

        .user-preview-tab-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 24px;
        }

        .user-preview-tab {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #475569;
            border-radius: 999px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .user-preview-tab:hover {
            border-color: #93c5fd;
            color: #1d4ed8;
        }

        .user-preview-tab.is-active {
            background: #002C76;
            border-color: #002C76;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(0, 44, 118, 0.14);
        }

        .user-preview-tab:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.16);
        }

        .user-preview-tab-panel {
            border-top: 1px solid #e5e7eb;
            padding-top: 30px;
        }

        .user-preview-access-tabs {
            display: grid;
            gap: 16px;
        }

        .user-preview-access-tab-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .user-preview-access-tab {
            border: 1px solid #dbe3ee;
            background: #ffffff;
            color: #475569;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .user-preview-access-tab:hover {
            border-color: #93c5fd;
            color: #1d4ed8;
        }

        .user-preview-access-tab.is-active {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .user-preview-access-tab:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.15);
        }

        .user-preview-access-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            padding: 16px;
        }

        .user-preview-role-picker-head {
            margin-bottom: 14px;
        }

        .user-preview-role-picker-head p {
            margin: 6px 0 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        .user-preview-role-select-proxy {
            display: none;
        }

        .user-preview-role-options {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        .user-preview-role-option {
            width: 100%;
            border: 1px solid #dbe3ee;
            border-radius: 14px;
            background: #ffffff;
            padding: 14px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            text-align: left;
            cursor: default;
            transition: all 0.2s ease;
        }

        .user-preview-role-option[aria-disabled="false"] {
            cursor: pointer;
        }

        .user-preview-role-option[aria-disabled="false"]:hover {
            border-color: #93c5fd;
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.08);
            transform: translateY(-1px);
        }

        .user-preview-role-option:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.15);
        }

        .user-preview-role-option.is-selected {
            border-color: #93c5fd;
            background: #eff6ff;
        }

        .user-preview-role-option-check {
            width: 22px;
            height: 22px;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: transparent;
            background: #ffffff;
            flex: 0 0 22px;
            margin-top: 1px;
        }

        .user-preview-role-option.is-selected .user-preview-role-option-check {
            border-color: #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .user-preview-role-option-copy strong {
            display: block;
            margin-bottom: 4px;
            color: #0f172a;
            font-size: 13px;
        }

        .user-preview-role-option-copy small {
            display: block;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        .user-preview-role-summary {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            padding: 14px 16px;
            margin-top: 14px;
        }

        .user-preview-role-summary span {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .user-preview-role-summary p {
            margin: 0;
            color: #334155;
            font-size: 13px;
            line-height: 1.5;
        }

        .user-preview-permission-table-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            overflow: hidden;
        }

        .user-preview-permission-table-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 18px 0;
            flex-wrap: wrap;
        }

        .user-preview-permission-table-head h4 {
            margin: 0 0 4px;
            color: #002C76;
            font-size: 14px;
        }

        .user-preview-permission-table-head p,
        .user-preview-permission-module-description,
        .user-preview-permission-description-cell {
            margin: 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        .user-preview-permission-count {
            display: inline-flex;
            align-items: center;
            border: 1px solid #dbeafe;
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .user-preview-permission-table-wrap {
            overflow-x: auto;
            margin-top: 16px;
        }

        .user-preview-permission-table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
        }

        .user-preview-permission-table thead th {
            padding: 14px 12px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #002C76;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-align: left;
        }

        .user-preview-permission-table tbody td {
            padding: 14px 12px;
            border-top: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .user-preview-permission-row {
            background: #ffffff;
            transition: background-color 0.2s ease;
        }

        .user-preview-permission-row.is-allowed td:not(.user-preview-permission-module-cell) {
            background: #fbfdff;
        }

        .user-preview-permission-module-cell {
            min-width: 200px;
            background: #f8fbff;
        }

        .user-preview-permission-module-title {
            color: #0f172a;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .user-preview-permission-mobile-module {
            display: none;
        }

        .user-preview-permission-mobile-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #1d4ed8;
            margin-bottom: 4px;
        }

        .user-preview-permission-submodule-cell {
            min-width: 180px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 600;
        }

        .user-preview-permission-description-cell {
            min-width: 320px;
        }

        .user-preview-permission-check-cell {
            min-width: 108px;
            text-align: center;
        }

        .user-preview-permission-check-cell--na {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
        }

        .user-preview-permission-na {
            display: inline-block;
            color: #94a3b8;
        }

        .user-preview-permission-check {
            width: 16px;
            height: 16px;
            accent-color: #2563eb;
            cursor: default;
            opacity: 1;
            pointer-events: none;
        }

        .user-preview-permission-check-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
        }

        .user-preview-permission-check-item span {
            color: #334155;
        }

        .user-preview-grid {
            display: grid;
            gap: 20px;
        }

        .user-preview-grid--wide {
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        }

        .user-preview-grid--two-column {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }

        .user-preview-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: #f8fafc;
            color: #111827;
            transition: all 0.2s ease;
        }

        .user-preview-input:disabled {
            background: #f8fafc;
            color: #111827;
            opacity: 1;
            cursor: default;
        }

        .user-preview-input[data-edit-state="active"] {
            background: #ffffff;
        }

        .user-preview-label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }

        .user-preview-required {
            color: #dc2626;
        }

        .user-preview-primary-btn,
        .user-preview-secondary-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .user-preview-primary-btn {
            background-color: #002C76;
            color: white;
        }

        .user-preview-primary-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .user-preview-secondary-btn {
            background-color: #e5e7eb;
            color: #374151;
        }

        .user-preview-primary-btn:hover:not(:disabled) {
            background-color: #001f59 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 44, 118, 0.2);
        }

        .user-preview-secondary-btn:hover {
            background-color: #d1d5db !important;
            transform: translateY(-2px);
        }

        .user-preview-input--meta {
            background: #f1f5f9;
        }

        .user-preview-meta-text {
            margin: 6px 0 0;
            color: #9ca3af;
            font-size: 11px;
            font-weight: 500;
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-status-pill-group {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            min-height: 46px;
        }

        .user-status-pill {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            border-radius: 999px;
            padding: 0;
            font-size: 14px;
            font-weight: 600;
            cursor: default;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }

        .user-status-pill[data-active="true"][data-status-value="active"] {
            background: #dcfce7;
            border-color: #86efac;
            color: #166534;
        }

        .user-status-pill[data-active="true"][data-status-value="inactive"] {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            .user-preview-grid {
                grid-template-columns: 1fr !important;
            }

            .user-preview-role-options {
                grid-template-columns: 1fr;
            }

            .user-preview-permission-table {
                min-width: 860px;
            }
        }
    </style>

    <script src="https://unpkg.com/feather-icons"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.feather) {
                feather.replace();
            }

            const form = document.getElementById('userPreviewForm');
            const tabButtons = Array.from(form.querySelectorAll('[data-user-tab]'));
            const tabPanels = Array.from(form.querySelectorAll('[data-user-tab-panel]'));
            const accessTabButtons = Array.from(form.querySelectorAll('[data-user-access-tab]'));
            const accessTabPanels = Array.from(form.querySelectorAll('[data-user-access-panel]'));
            const roleOptionButtons = Array.from(form.querySelectorAll('[data-role-option]'));
            const roleSummary = document.getElementById('userPreviewRoleSummary');
            const roleDescriptionsSource = document.getElementById('userPreviewRoleDescriptions');
            const roleFallbackDescriptionSource = document.getElementById('userPreviewRoleFallbackDescriptions');
            const permissionsByRoleSource = document.getElementById('userPreviewPermissionsByRole');
            const currentPermissionsSource = document.getElementById('userPreviewCurrentPermissions');
            const permissionItemRows = Array.from(form.querySelectorAll('[data-permission-item-row]'));
            const permissionCheckboxes = Array.from(form.querySelectorAll('[data-permission-checkbox]'));
            const permissionCount = document.getElementById('userPreviewPermissionCount');
            const editToggleBtn = document.getElementById('userEditToggleBtn');
            const saveBtn = document.getElementById('userSaveBtn');
            const cancelBtn = document.getElementById('userCancelBtn');
            const editableFields = Array.from(form.querySelectorAll('[data-editable]'));
            const agencySelect = document.getElementById('agencySelect');
            const roleSelect = document.getElementById('roleSelect');
            const positionSelect = document.getElementById('positionSelect');
            const provinceSelect = document.getElementById('provinceSelect');
            const officeSelect = document.getElementById('officeSelect');
            const roleDescriptions = roleDescriptionsSource ? JSON.parse(roleDescriptionsSource.dataset.roleDescriptions || '{}') : {};
            const roleFallbackDescription = roleFallbackDescriptionSource?.dataset.fallbackDescription || 'Role access follows the configured module scope for this classification.';
            const permissionsByRole = permissionsByRoleSource ? JSON.parse(permissionsByRoleSource.dataset.permissionsByRole || '{}') : {};
            const currentPermissions = currentPermissionsSource ? JSON.parse(currentPermissionsSource.dataset.currentPermissions || '[]') : [];
            let isEditMode = false;
            let initialSnapshot = '';

            const positions = {
                'DILG': [
                    'Engineer II',
                    'Engineer III',
                    'Unit Chief',
                    'Assistant Unit Chief',
                    'Financial Analyst II',
                    'Financial Analyst III',
                    'Project Evaluation Officer II',
                    'Project Evaluation Officer III',
                    'Information Systems Analyst III'
                ],
                'LGU': [
                    'Municipal Engineer I',
                    'Municipal Engineer II',
                    'Municipal Engineer III',
                    'Planning Officer II',
                    'Planning Officer III'
                ]
            };

            const provinces = [
                'Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'
            ];

            const offices = {
                'Abra': ['PLGU Abra', 'Bangued', 'Boliney', 'Bucay', 'Bucloc', 'Daguioman', 'Danglas', 'Dolores', 'La Paz', 'Lacub', 'Lagangilang', 'Lagayan', 'Langiden', 'Licuan-Baay', 'Luba', 'Malibcong', 'Manabo', 'Peñarrubia', 'Pidigan', 'Pilar', 'Sallapadan', 'San Isidro', 'San Juan', 'San Quintin', 'Tayum', 'Tineg', 'Tubo', 'Villaviciosa'],
                'Apayao': ['PLGU Apayao', 'Calanasan', 'Conner', 'Flora', 'Kabugao', 'Luna', 'Pudtol', 'Santa Marcela'],
                'Benguet': ['PLGU Benguet', 'Atok', 'Bakun', 'Bokod', 'Buguias', 'Itogon', 'Kabayan', 'Kapangan', 'Kibungan', 'La Trinidad', 'Mankayan', 'Sablan', 'Tuba', 'Tublay'],
                'City of Baguio': ['PLGU City of Baguio', 'City of Baguio'],
                'Ifugao': ['PLGU Ifugao', 'Aguinaldo', 'Alfonso Lista', 'Asipulo', 'Banaue', 'Hingyon', 'Hungduan', 'Kiangan', 'Lagawe', 'Lamut', 'Mayoyao', 'Tinoc'],
                'Kalinga': ['PLGU Kalinga', 'Balbalan', 'Lubuagan', 'Pasil', 'Pinukpuk', 'Rizal', 'Tabuk', 'Tanudan'],
                'Mountain Province': ['PLGU Mountain Province', 'Barlig', 'Bauko', 'Besao', 'Bontoc', 'Natonin', 'Paracelis', 'Sabangan', 'Sadanga', 'Sagada', 'Tadian']
            };

            function setActiveTab(tabName, shouldFocus = false) {
                tabButtons.forEach(function (button) {
                    const isActive = button.getAttribute('data-user-tab') === tabName;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    button.tabIndex = isActive ? 0 : -1;
                });

                tabPanels.forEach(function (panel) {
                    const isActive = panel.getAttribute('data-user-tab-panel') === tabName;
                    panel.classList.toggle('is-active', isActive);
                    panel.hidden = !isActive;
                });

                if (shouldFocus) {
                    const activeButton = tabButtons.find(function (button) {
                        return button.getAttribute('data-user-tab') === tabName;
                    });

                    activeButton?.focus();
                }
            }

            function setActiveAccessTab(tabName, shouldFocus = false) {
                accessTabButtons.forEach(function (button) {
                    const isActive = button.getAttribute('data-user-access-tab') === tabName;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    button.tabIndex = isActive ? 0 : -1;
                });

                accessTabPanels.forEach(function (panel) {
                    const isActive = panel.getAttribute('data-user-access-panel') === tabName;
                    panel.classList.toggle('is-active', isActive);
                    panel.hidden = !isActive;
                });

                if (shouldFocus) {
                    const activeButton = accessTabButtons.find(function (button) {
                        return button.getAttribute('data-user-access-tab') === tabName;
                    });

                    activeButton?.focus();
                }
            }

            function syncRoleOptionState() {
                const selectedRole = roleSelect?.value || '';

                roleOptionButtons.forEach(function (button) {
                    const isSelected = button.getAttribute('data-role-option') === selectedRole;
                    button.classList.toggle('is-selected', isSelected);
                    button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                    button.setAttribute('aria-disabled', isEditMode ? 'false' : 'true');
                    button.tabIndex = isEditMode ? 0 : -1;
                });

                if (roleSummary) {
                    const summaryText = roleDescriptions[selectedRole] || roleFallbackDescription;
                    const summaryParagraph = roleSummary.querySelector('p');
                    if (summaryParagraph) {
                        summaryParagraph.textContent = summaryText;
                    }
                }
            }

            function setPermissionCheckboxes(permissionKeys) {
                const nextPermissions = Array.isArray(permissionKeys) ? permissionKeys : [];
                const hasAllPermissions = nextPermissions.includes('*');
                const nextPermissionSet = new Set(nextPermissions);

                permissionCheckboxes.forEach(function (checkbox) {
                    const permissionKey = checkbox.getAttribute('data-permission-key') || '';
                    checkbox.checked = hasAllPermissions || nextPermissionSet.has(permissionKey);
                });

                syncPermissionTable();
            }

            function syncPermissionTable() {
                let allowedCount = 0;

                permissionCheckboxes.forEach(function (checkbox) {
                    if (checkbox.checked) {
                        allowedCount += 1;
                    }
                });

                permissionItemRows.forEach(function (row) {
                    const checkedActionCount = row.querySelectorAll('[data-permission-checkbox]:checked').length;
                    row.classList.toggle('is-allowed', checkedActionCount > 0);
                });

                if (permissionCount) {
                    permissionCount.textContent = allowedCount + ' of ' + permissionCheckboxes.length + ' permissions allowed';
                }
            }

            function focusActivePanelField() {
                const activePanel = tabPanels.find(function (panel) {
                    return !panel.hidden;
                });
                const activeAccessPanel = accessTabPanels.find(function (panel) {
                    return !panel.hidden;
                });
                let targetField = Array.from(activePanel?.querySelectorAll('[data-editable]') ?? []).find(function (field) {
                    return !field.closest('[hidden]') && field.offsetParent !== null;
                });

                if (activePanel?.getAttribute('data-user-tab-panel') === 'system-access') {
                    const activeAccessTabName = activeAccessPanel?.getAttribute('data-user-access-panel');

                    if (activeAccessTabName === 'permissions' && permissionCheckboxes.length) {
                        const firstEditablePermission = permissionCheckboxes.find(function (checkbox) {
                            return !checkbox.disabled;
                        });
                        firstEditablePermission?.focus();
                        return;
                    }

                    if ((activeAccessTabName === 'role' || !activeAccessTabName) && roleOptionButtons.length) {
                        const selectedRoleOption = roleOptionButtons.find(function (button) {
                            return button.classList.contains('is-selected');
                        });
                        selectedRoleOption?.focus();
                        return;
                    }
                }

                targetField?.focus();
            }

            function setEditMode(active) {
                isEditMode = active;

                editableFields.forEach(function (field) {
                    field.disabled = !active;
                    field.readOnly = !active && field.tagName !== 'SELECT';
                    field.dataset.editState = active ? 'active' : 'inactive';
                });

                permissionCheckboxes.forEach(function (checkbox) {
                    checkbox.disabled = !active;
                });

                editToggleBtn.style.display = active ? 'none' : '';
                saveBtn.style.display = active ? '' : 'none';
                cancelBtn.style.display = active ? '' : 'none';

                syncRoleOptionState();
                syncPermissionTable();

                if (active) {
                    focusActivePanelField();
                }

                refreshSaveState();
            }

            function snapshotForm() {
                const data = {};
                editableFields.forEach(function (field) {
                    data[field.name] = field.value ?? '';
                });

                data.__crud_permissions = permissionCheckboxes
                    .filter(function (checkbox) {
                        return checkbox.checked;
                    })
                    .map(function (checkbox) {
                        return checkbox.getAttribute('data-permission-key') || '';
                    })
                    .filter(Boolean)
                    .sort();

                return JSON.stringify(data);
            }

            function restoreInitialValues() {
                const values = JSON.parse(initialSnapshot);
                editableFields.forEach(function (field) {
                    if (Object.prototype.hasOwnProperty.call(values, field.name)) {
                        field.value = values[field.name];
                    }
                });

                syncDependentFields(true);
                setPermissionCheckboxes(values.__crud_permissions || []);
                syncRoleOptionState();
                refreshSaveState();
            }

            function isDirty() {
                return snapshotForm() !== initialSnapshot;
            }

            function refreshSaveState() {
                saveBtn.disabled = !isEditMode || !isDirty();
            }

            function updatePositionDropdown(preserveCurrent) {
                const selectedValue = agencySelect.value;
                const currentPosition = preserveCurrent ? positionSelect.value : '';
                positionSelect.innerHTML = '<option value="" disabled>Select Position</option>';

                if (positions[selectedValue]) {
                    positions[selectedValue].forEach(function (position) {
                        const option = document.createElement('option');
                        option.value = position;
                        option.textContent = position;
                        if (position === currentPosition) {
                            option.selected = true;
                        }
                        positionSelect.appendChild(option);
                    });
                }
            }

            function updateProvinceDropdown(preserveCurrent) {
                const selectedAgency = agencySelect.value;
                const currentProvince = preserveCurrent ? provinceSelect.value : '';
                provinceSelect.innerHTML = '<option value="" disabled>Select Province</option>';

                if (selectedAgency === 'DILG') {
                    const regionalOption = document.createElement('option');
                    regionalOption.value = 'Regional Office';
                    regionalOption.textContent = 'Regional Office';
                    if (currentProvince === 'Regional Office') {
                        regionalOption.selected = true;
                    }
                    provinceSelect.appendChild(regionalOption);
                }

                provinces.forEach(function (province) {
                    const option = document.createElement('option');
                    option.value = province;
                    option.textContent = province;
                    if (province === currentProvince) {
                        option.selected = true;
                    }
                    provinceSelect.appendChild(option);
                });
            }

            function updateOfficeDropdown(preserveCurrent) {
                const selectedAgency = agencySelect.value;
                const selectedProvince = provinceSelect.value;
                const currentOffice = preserveCurrent ? officeSelect.value : '';
                officeSelect.innerHTML = '<option value="">Select Office (Optional)</option>';

                if (selectedAgency === 'LGU' && offices[selectedProvince]) {
                    offices[selectedProvince].forEach(function (office) {
                        const option = document.createElement('option');
                        option.value = office;
                        option.textContent = office;
                        if (office === currentOffice) {
                            option.selected = true;
                        }
                        officeSelect.appendChild(option);
                    });
                } else if (currentOffice) {
                    const option = document.createElement('option');
                    option.value = currentOffice;
                    option.textContent = currentOffice;
                    option.selected = true;
                    officeSelect.appendChild(option);
                }
            }

            function syncDependentFields(preserveCurrent) {
                updatePositionDropdown(preserveCurrent);
                updateProvinceDropdown(preserveCurrent);
                updateOfficeDropdown(preserveCurrent);
            }

            tabButtons.forEach(function (button, index) {
                button.addEventListener('click', function () {
                    setActiveTab(button.getAttribute('data-user-tab'));
                });

                button.addEventListener('keydown', function (event) {
                    let nextIndex = index;

                    if (event.key === 'ArrowRight') {
                        nextIndex = (index + 1) % tabButtons.length;
                    } else if (event.key === 'ArrowLeft') {
                        nextIndex = (index - 1 + tabButtons.length) % tabButtons.length;
                    } else if (event.key === 'Home') {
                        nextIndex = 0;
                    } else if (event.key === 'End') {
                        nextIndex = tabButtons.length - 1;
                    } else {
                        return;
                    }

                    event.preventDefault();
                    const nextTab = tabButtons[nextIndex];
                    setActiveTab(nextTab.getAttribute('data-user-tab'), true);
                });
            });

            accessTabButtons.forEach(function (button, index) {
                button.addEventListener('click', function () {
                    setActiveAccessTab(button.getAttribute('data-user-access-tab'));
                });

                button.addEventListener('keydown', function (event) {
                    let nextIndex = index;

                    if (event.key === 'ArrowRight') {
                        nextIndex = (index + 1) % accessTabButtons.length;
                    } else if (event.key === 'ArrowLeft') {
                        nextIndex = (index - 1 + accessTabButtons.length) % accessTabButtons.length;
                    } else if (event.key === 'Home') {
                        nextIndex = 0;
                    } else if (event.key === 'End') {
                        nextIndex = accessTabButtons.length - 1;
                    } else {
                        return;
                    }

                    event.preventDefault();
                    const nextTab = accessTabButtons[nextIndex];
                    setActiveAccessTab(nextTab.getAttribute('data-user-access-tab'), true);
                });
            });

            roleOptionButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!isEditMode || !roleSelect) {
                        return;
                    }

                    const nextRole = button.getAttribute('data-role-option') || '';
                    if (nextRole === '' || roleSelect.value === nextRole) {
                        return;
                    }

                    roleSelect.value = nextRole;
                    roleSelect.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });

            editToggleBtn.addEventListener('click', function () {
                setEditMode(true);
            });

            cancelBtn.addEventListener('click', function () {
                if (!isDirty()) {
                    restoreInitialValues();
                    setEditMode(false);
                    return;
                }

                window.openConfirmationModal(
                    'Discard your unsaved changes to this user profile?',
                    function () {
                        restoreInitialValues();
                        setEditMode(false);
                    }
                );
            });

            saveBtn.addEventListener('click', function () {
                if (saveBtn.disabled) {
                    return;
                }

                window.openConfirmationModal(
                    'Save the changes you made to this user profile?',
                    function () {
                        HTMLFormElement.prototype.submit.call(form);
                    }
                );
            });

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                if (saveBtn.disabled) {
                    return;
                }

                window.openConfirmationModal(
                    'Save the changes you made to this user profile?',
                    function () {
                        HTMLFormElement.prototype.submit.call(form);
                    }
                );
            });

            agencySelect.addEventListener('change', function () {
                updatePositionDropdown(false);
                updateProvinceDropdown(false);
                updateOfficeDropdown(false);
                refreshSaveState();
            });

            roleSelect?.addEventListener('change', function () {
                syncRoleOptionState();
                if (isEditMode) {
                    setPermissionCheckboxes(Array.isArray(permissionsByRole[roleSelect.value]) ? permissionsByRole[roleSelect.value] : []);
                } else {
                    syncPermissionTable();
                }
                refreshSaveState();
            });

            provinceSelect.addEventListener('change', function () {
                updateOfficeDropdown(false);
                refreshSaveState();
            });

            editableFields.forEach(function (field) {
                field.addEventListener('input', refreshSaveState);
                field.addEventListener('change', refreshSaveState);
            });

            permissionCheckboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    syncPermissionTable();
                    refreshSaveState();
                });
            });

            syncDependentFields(true);
            setActiveTab('personal');
            setActiveAccessTab('role');
            syncRoleOptionState();
            setPermissionCheckboxes(currentPermissions);
            initialSnapshot = snapshotForm();
            setEditMode(false);
        });
    </script>
@endsection
