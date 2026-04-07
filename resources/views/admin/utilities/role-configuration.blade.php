@extends('layouts.dashboard')

@section('title', 'Role Configuration')
@section('page-title', 'Role Configuration')

@section('content')
    @php
        $availableRoleKeys = collect($roleConfigurations)->pluck('role')->all();
        $requestedActiveRole = strtolower(trim((string) request()->query('role', $availableRoleKeys[0] ?? \App\Models\User::ROLE_REGIONAL)));
        $activeRole = in_array($requestedActiveRole, $availableRoleKeys, true)
            ? $requestedActiveRole
            : ($availableRoleKeys[0] ?? \App\Models\User::ROLE_REGIONAL);
    @endphp

    @if (session('success'))
        <div style="margin-bottom: 18px; padding: 12px 16px; border-radius: 10px; border: 1px solid #a7f3d0; background: #ecfdf5; color: #166534; font-size: 13px; font-weight: 600;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="margin-bottom: 18px; padding: 12px 16px; border-radius: 10px; border: 1px solid #fecaca; background: #fff1f2; color: #be123c; font-size: 13px; font-weight: 600;">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="margin-bottom: 18px; padding: 14px 16px; border-radius: 10px; border: 1px solid #fecaca; background: #fff7f7; color: #991b1b;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="role-config-page-header" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 12px; flex-wrap: wrap;">
        <div class="content-header" style="margin-bottom: 0;">
            <h1>Role Configuration</h1>
            <p>Manage centralized CRUD access for each hierarchy role used by the application.</p>
        </div>

        <div class="role-config-page-actions" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <button
                type="button"
                class="role-config-manage-users-btn"
                data-role-management-open
                style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 8px; background: #ffffff; color: #002c76; text-decoration: none; font-size: 13px; font-weight: 700; border: 1px solid #bfdbfe; box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08); cursor: pointer;"
            >
                <i class="fas fa-users-cog"></i>
                <span>Manage User Roles</span>
            </button>

            <a href="{{ route('utilities.system-setup.index') }}" class="role-config-back-link" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 8px; background: linear-gradient(180deg, #0a4cb3 0%, #002c76 100%); color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 600; border: 1px solid #002c76; box-shadow: 0 8px 18px rgba(0, 44, 118, 0.18);">
                <i class="fas fa-arrow-left"></i>
                <span>Back to System Setup</span>
            </a>
        </div>
    </div>

    <section class="role-config-shell" style="background: white; padding: 28px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);">
        <div class="role-config-intro" style="display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; flex-wrap: wrap; margin-bottom: 18px;">
            <div class="role-config-intro-copy" style="display: flex; align-items: flex-start; gap: 14px;">
                <div class="role-config-intro-icon" style="width: 52px; height: 52px; border-radius: 14px; background: #dbeafe; color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h2 style="margin: 0 0 6px; color: #002C76; font-size: 20px;">Role Configuration</h2>
                    <p style="margin: 0; color: #475569; font-size: 13px; line-height: 1.7; max-width: 820px;">
                        Configure CRUD access by role instead of by individual user. Changes save automatically and affect all users currently assigned to that hierarchy role.
                    </p>
                </div>
            </div>
            <div class="role-config-superadmin-note" style="padding: 10px 14px; border-radius: 10px; background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 700;">
                Superadmin remains full access by design
            </div>
        </div>

        <div class="role-config-superadmin-card" style="margin-bottom: 18px; padding: 16px 18px; border: 1px solid #dbeafe; border-radius: 12px; background: linear-gradient(180deg, #f8fbff 0%, #eff6ff 100%);">
            <div style="font-size: 14px; font-weight: 700; color: #002C76; margin-bottom: 6px;">Superadmin</div>
            <div style="font-size: 13px; color: #475569; line-height: 1.7;">
                {{ $roleDescriptions[\App\Models\User::ROLE_SUPERADMIN] ?? 'Superadmin keeps full access across all modules and utilities.' }}
            </div>
        </div>

        <div class="role-config-tabs" role="tablist" aria-label="Role configuration tabs">
            @foreach ($roleConfigurations as $roleConfiguration)
                @php
                    $roleKey = $roleConfiguration['role'];
                    $isActiveRole = $activeRole === $roleKey;
                @endphp
                <button
                    type="button"
                    class="role-config-tab{{ $isActiveRole ? ' is-active' : '' }}"
                    data-role-config-tab
                    data-target="role-panel-{{ $roleKey }}"
                    aria-selected="{{ $isActiveRole ? 'true' : 'false' }}"
                    role="tab"
                >
                    {{ $roleConfiguration['label'] }}
                </button>
            @endforeach
        </div>

        @foreach ($roleConfigurations as $roleConfiguration)
            @php
                $roleKey = $roleConfiguration['role'];
                $isActiveRole = $activeRole === $roleKey;
                $configuredPermissions = $roleConfiguration['permissions'] ?? [];
            @endphp
            <section
                id="role-panel-{{ $roleKey }}"
                class="role-config-panel{{ $isActiveRole ? ' is-active' : '' }}"
                data-role-config-panel
                role="tabpanel"
            >
                <div class="role-config-panel-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 18px; flex-wrap: wrap;">
                    <div>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 8px;">
                            <h3 style="margin: 0; color: #002C76; font-size: 18px;">{{ $roleConfiguration['label'] }}</h3>
                            <span
                                class="role-config-badge"
                                data-role-config-badge
                                data-default-bg="#dcfce7"
                                data-default-color="#166534"
                                data-custom-bg="#ede9fe"
                                data-custom-color="#5b21b6"
                                data-default-text="Using recommended baseline"
                                data-custom-text="Custom role configuration"
                                style="padding: 5px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; background: {{ $roleConfiguration['uses_recommended_defaults'] ? '#dcfce7' : '#ede9fe' }}; color: {{ $roleConfiguration['uses_recommended_defaults'] ? '#166534' : '#5b21b6' }};"
                            >
                                {{ $roleConfiguration['uses_recommended_defaults'] ? 'Using recommended baseline' : 'Custom role configuration' }}
                            </span>
                        </div>
                        <p style="margin: 0; color: #475569; font-size: 13px; line-height: 1.7; max-width: 860px;">
                            {{ $roleConfiguration['description'] }}
                        </p>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('utilities.role-configuration.roles.update', ['role' => $roleKey]) }}"
                    data-role-config-form
                    data-role-key="{{ $roleKey }}"
                    data-role-label="{{ $roleConfiguration['label'] }}"
                    data-reset-url="{{ route('utilities.role-configuration.roles.reset', ['role' => $roleKey]) }}"
                >
                    @csrf
                    @method('PUT')

                    <div class="role-config-status-row" style="margin-bottom: 18px;">
                        <div style="font-size: 12px; color: #64748b; line-height: 1.7;">
                            Tick or untick access items to save the permission matrix automatically for every user assigned to the <strong>{{ $roleConfiguration['label'] }}</strong> role.
                        </div>
                        <div class="role-config-save-status" data-role-config-save-status data-state="idle" aria-live="polite">
                            <span class="role-config-save-status__spinner" aria-hidden="true"></span>
                            <span class="role-config-save-status__text" data-role-config-save-status-text></span>
                        </div>
                    </div>

                    <div class="crud-permission-table-wrap">
                        <table class="crud-permission-table">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th>Submodule</th>
                                    <th>Description</th>
                                    @foreach($crudActionOptions as $actionKey => $actionLabel)
                                        <th>{{ $actionLabel }}</th>
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
                                        @endphp
                                        <tr class="crud-permission-row">
                                            @if($itemIndex === 0)
                                                <td rowspan="{{ $rowspan }}" class="crud-permission-module-cell">
                                                    <div class="crud-permission-module-title">{{ $module['module'] }}</div>
                                                    <div class="crud-permission-module-description">{{ $module['description'] }}</div>
                                                </td>
                                            @endif
                                            <td class="crud-permission-submodule-cell" data-label="Submodule">
                                                <div class="crud-permission-mobile-module">
                                                    <div class="crud-permission-mobile-label">Module</div>
                                                    <div class="crud-permission-module-title">{{ $module['module'] }}</div>
                                                    <div class="crud-permission-module-description">{{ $module['description'] }}</div>
                                                </div>
                                                {{ $item['label'] }}
                                            </td>
                                            <td class="crud-permission-description-cell" data-label="Description">{{ $item['description'] }}</td>
                                            @foreach($crudActionOptions as $actionKey => $actionLabel)
                                                @if(in_array($actionKey, $availableActions, true))
                                                    @php
                                                        $permissionKey = $item['aspect'] . '.' . $actionKey;
                                                    @endphp
                                                    <td class="crud-permission-check-cell" data-label="{{ $actionLabel }}">
                                                        <label class="crud-check-item">
                                                            <input
                                                                type="checkbox"
                                                                name="crud_permissions[]"
                                                                value="{{ $permissionKey }}"
                                                                @checked(in_array($permissionKey, $configuredPermissions, true))
                                                            >
                                                            <span>Allow</span>
                                                        </label>
                                                    </td>
                                                @else
                                                    <td class="crud-permission-check-cell crud-permission-check-cell--na" data-label="{{ $actionLabel }}">
                                                        <span class="crud-permission-na">N/A</span>
                                                    </td>
                                                @endif
                                            @endforeach
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div style="display: flex; justify-content: flex-end; align-items: center; gap: 16px; margin-top: 18px; flex-wrap: wrap;">
                        <button
                            type="button"
                            class="role-config-reset-btn"
                            data-role-config-reset
                            @disabled($roleConfiguration['uses_recommended_defaults'])
                        >
                            Reset Role
                        </button>
                    </div>
                </form>
            </section>
        @endforeach
    </section>

    <div class="role-management-modal" data-role-management-modal hidden>
        <div class="role-management-modal__backdrop" data-role-management-close></div>
        <div class="role-management-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="role-management-modal-title">
            <div class="role-management-modal__header">
                <div>
                    <p class="role-management-modal__eyebrow">Superadmin Controls</p>
                    <h2 id="role-management-modal-title" class="role-management-modal__title">Manage User Roles</h2>
                    <p class="role-management-modal__description">
                        Review role counts, open filtered users, and use the toolbar to manage the selected role.
                    </p>
                </div>
                <button type="button" class="role-management-modal__close" data-role-management-close aria-label="Close manage user roles modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="role-management-toolbar">
                <div class="role-management-toolbar__selection" data-role-toolbar-selection>
                    Select a role card to edit or review role actions.
                </div>
                <div class="role-management-toolbar__actions">
                    <button
                        type="button"
                        class="role-management-toolbar__button role-management-toolbar__button--secondary"
                        data-role-toolbar-add
                        data-confirm-skip="true"
                        title="Add an official role."
                    >
                        <i class="fas fa-plus"></i>
                        <span>Add Role</span>
                    </button>
                    <button
                        type="button"
                        class="role-management-toolbar__button role-management-toolbar__button--ghost"
                        data-role-toolbar-edit
                        data-confirm-skip="true"
                        title="Select a role to edit."
                    >
                        <i class="fas fa-pen"></i>
                        <span>Edit Role</span>
                    </button>
                    <button
                        type="button"
                        class="role-management-toolbar__button role-management-toolbar__button--danger"
                        data-role-toolbar-delete
                        data-confirm-skip="true"
                        title="Select a role to review deletion options."
                    >
                        <i class="fas fa-trash-alt"></i>
                        <span>Delete Role</span>
                    </button>
                </div>
            </div>

            <div class="role-management-modal__grid">
                @foreach ($roleManagementCards as $roleCard)
                    <article
                        class="role-management-card"
                        data-role-management-card
                        data-role-kind="{{ $roleCard['kind'] }}"
                        data-role-id="{{ $roleCard['role'] }}"
                        data-role-label="{{ $roleCard['label'] }}"
                        data-edit-url="{{ $roleCard['role_configuration_route'] ?? '' }}"
                        data-role-update-url="{{ $roleCard['role_definition_update_route'] ?? '' }}"
                        data-role-delete-url="{{ $roleCard['role_definition_delete_route'] ?? '' }}"
                        tabindex="0"
                    >
                        <div class="role-management-card__top">
                            <div>
                                <h3 class="role-management-card__title">{{ $roleCard['label'] }}</h3>
                                <p class="role-management-card__description">{{ $roleCard['description'] }}</p>
                            </div>
                            <span class="role-management-card__count">
                                {{ number_format($roleCard['total_users']) }} user{{ $roleCard['total_users'] === 1 ? '' : 's' }}
                            </span>
                        </div>

                        <div class="role-management-card__stats">
                            <div class="role-management-stat">
                                <span class="role-management-stat__label">Active</span>
                                <strong class="role-management-stat__value">{{ number_format($roleCard['active_users']) }}</strong>
                            </div>
                            <div class="role-management-stat">
                                <span class="role-management-stat__label">Inactive</span>
                                <strong class="role-management-stat__value">{{ number_format($roleCard['inactive_users']) }}</strong>
                            </div>
                        </div>

                        <div class="role-management-card__actions">
                            <a href="{{ $roleCard['users_route'] }}" class="role-management-action role-management-action--primary">
                                <i class="fas fa-users"></i>
                                <span>Users</span>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>

    <div class="role-entry-modal" data-role-entry-modal hidden>
        <div class="role-entry-modal__backdrop" data-role-entry-close></div>
        <div class="role-entry-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="role-entry-modal-title">
            <form
                data-role-entry-form
                data-confirm-skip="true"
                data-store-url="{{ route('utilities.role-configuration.role-definitions.store') }}"
                data-index-url="{{ route('utilities.role-configuration.index') }}"
            >
                @csrf
                <input type="hidden" data-role-entry-mode value="create">
                <input type="hidden" data-role-entry-id value="">
                <input type="hidden" data-role-entry-submit-url value="">

                <div class="role-entry-modal__header">
                    <div>
                        <p class="role-entry-modal__eyebrow">Official Role</p>
                        <h3 id="role-entry-modal-title" class="role-entry-modal__title">Add Role</h3>
                        <p class="role-entry-modal__description" data-role-entry-description>
                            Enter the role name. Saving creates an official role, then you can configure its access from the role matrix.
                        </p>
                    </div>
                    <button type="button" class="role-entry-modal__close" data-role-entry-close aria-label="Close role entry modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <label class="role-entry-modal__field">
                    <span class="role-entry-modal__label">Role Name</span>
                    <input
                        type="text"
                        class="role-entry-modal__input"
                        data-role-entry-name
                        maxlength="80"
                        placeholder="Example: Cluster Coordinator"
                        required
                    >
                </label>

                <p class="role-entry-modal__hint">
                    New roles start with no configured permissions until you set them on the Role Configuration page.
                </p>

                <div class="role-entry-modal__actions">
                    <button type="button" class="role-entry-modal__button role-entry-modal__button--secondary" data-role-entry-close>
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="role-entry-modal__button role-entry-modal__button--primary"
                        data-confirm-skip="true"
                    >
                        Save Role
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="role-config-toast-stack" data-role-config-toast-stack aria-live="polite" aria-atomic="true"></div>

    <style>
        body.role-management-modal-open {
            overflow: hidden;
        }

        .role-management-modal[hidden] {
            display: none;
        }

        .role-management-modal {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .role-management-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(2px);
            transition: background 0.2s ease;
        }

        .role-management-modal__dialog {
            position: relative;
            width: min(920px, 100%);
            max-height: calc(100vh - 32px);
            overflow: auto;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border: 1px solid #dbeafe;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
            padding: 18px;
            transition: filter 0.2s ease, transform 0.2s ease, opacity 0.2s ease;
        }

        .role-management-modal.is-backgrounded .role-management-modal__backdrop {
            background: rgba(15, 23, 42, 0.74);
        }

        .role-management-modal.is-backgrounded .role-management-modal__dialog {
            filter: blur(3px);
            transform: scale(0.99);
            opacity: 0.72;
            pointer-events: none;
            user-select: none;
        }

        .role-management-modal__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 14px;
        }

        .role-management-modal__eyebrow {
            margin: 0 0 4px;
            color: #1d4ed8;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .role-management-modal__title {
            margin: 0 0 4px;
            color: #002C76;
            font-size: 20px;
        }

        .role-management-modal__description {
            margin: 0;
            color: #475569;
            font-size: 12px;
            line-height: 1.55;
            max-width: 560px;
        }

        .role-management-modal__close {
            width: 34px;
            height: 34px;
            border: 1px solid #dbeafe;
            border-radius: 999px;
            background: #ffffff;
            color: #334155;
            cursor: pointer;
            font-size: 12px;
            flex: 0 0 auto;
        }

        .role-management-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            padding: 10px 12px;
            border-radius: 12px;
            border: 1px solid #dbeafe;
            background: #f8fbff;
        }

        .role-management-toolbar__selection {
            color: #475569;
            font-size: 11px;
            line-height: 1.5;
        }

        .role-management-toolbar__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .role-management-toolbar__button {
            appearance: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 7px 10px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .role-management-toolbar__button--secondary {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .role-management-toolbar__button--ghost {
            background: #ffffff;
            border-color: #dbe4f0;
            color: #475569;
        }

        .role-management-toolbar__button--danger {
            background: #fff1f2;
            border-color: #fecaca;
            color: #be123c;
        }

        .role-management-toolbar__button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .role-entry-modal[hidden] {
            display: none;
        }

        .role-entry-modal {
            position: fixed;
            inset: 0;
            z-index: 1250;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .role-entry-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.48);
        }

        .role-entry-modal__dialog {
            position: relative;
            width: min(420px, 100%);
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid #dbeafe;
            box-shadow: 0 24px 50px rgba(15, 23, 42, 0.22);
            padding: 18px;
        }

        .role-entry-modal__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .role-entry-modal__eyebrow {
            margin: 0 0 4px;
            color: #1d4ed8;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .role-entry-modal__title {
            margin: 0 0 4px;
            color: #002C76;
            font-size: 18px;
        }

        .role-entry-modal__description,
        .role-entry-modal__hint {
            margin: 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.55;
        }

        .role-entry-modal__close {
            width: 34px;
            height: 34px;
            border: 1px solid #dbeafe;
            border-radius: 999px;
            background: #ffffff;
            color: #334155;
            cursor: pointer;
            font-size: 12px;
            flex: 0 0 auto;
        }

        .role-entry-modal__field {
            display: block;
            margin-bottom: 10px;
        }

        .role-entry-modal__label {
            display: block;
            margin-bottom: 6px;
            color: #0f172a;
            font-size: 12px;
            font-weight: 700;
        }

        .role-entry-modal__input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 13px;
            color: #0f172a;
            outline: none;
        }

        .role-entry-modal__input:focus {
            border-color: #1d4ed8;
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
        }

        .role-entry-modal__hint {
            margin-top: 4px;
        }

        .role-entry-modal__actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 16px;
        }

        .role-entry-modal__button {
            appearance: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 12px;
            border-radius: 10px;
            border: 1px solid transparent;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .role-entry-modal__button--secondary {
            background: #ffffff;
            border-color: #dbe4f0;
            color: #475569;
        }

        .role-entry-modal__button--primary {
            background: #002C76;
            color: #ffffff;
        }

        .role-management-modal__grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 10px;
        }

        .role-management-card {
            border-radius: 12px;
            border: 1px solid #dbe4f0;
            background: #ffffff;
            padding: 10px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
            cursor: pointer;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .role-management-card:hover {
            border-color: #bfdbfe;
        }

        .role-management-card.is-selected {
            border-color: #1d4ed8;
            background: #f8fbff;
            box-shadow: 0 12px 22px rgba(29, 78, 216, 0.12);
        }

        .role-management-card--custom {
            border-style: dashed;
        }

        .role-management-card__top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 8px;
        }

        .role-management-card__top > div {
            min-width: 0;
        }

        .role-management-card__title {
            margin: 0 0 3px;
            color: #0f172a;
            font-size: 14px;
            line-height: 1.25;
        }

        .role-management-card__description {
            margin: 0;
            color: #64748b;
            font-size: 10px;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .role-management-card__count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 3px 6px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 9px;
            font-weight: 800;
            white-space: nowrap;
        }

        .role-management-card__stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
            margin-bottom: 8px;
        }

        .role-management-stat {
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 7px 8px;
        }

        .role-management-stat__label {
            display: block;
            color: #64748b;
            font-size: 9px;
            font-weight: 700;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .role-management-stat__value {
            color: #0f172a;
            font-size: 14px;
            line-height: 1.1;
        }

        .role-management-card__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .role-management-action {
            appearance: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 8px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            flex: 1 1 100%;
            cursor: pointer;
        }

        .role-management-action--primary {
            background: #002C76;
            color: #ffffff;
        }

        .role-management-action:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .role-management-action:hover,
        .role-config-manage-users-btn:hover {
            transform: translateY(-1px);
        }

        .role-config-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        .role-config-tab {
            padding: 10px 16px;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .role-config-tab:hover {
            background: #dbeafe;
            border-color: #93c5fd;
        }

        .role-config-tab.is-active {
            background: #002C76;
            border-color: #002C76;
            color: #ffffff;
            box-shadow: 0 10px 24px rgba(0, 44, 118, 0.18);
        }

        .role-config-back-link {
            justify-content: center;
        }

        .role-config-toast-stack {
            position: fixed;
            top: 88px;
            right: 24px;
            z-index: 1400;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
            width: min(360px, calc(100vw - 32px));
        }

        .role-config-toast {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid transparent;
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.18);
            background: #ffffff;
            color: #0f172a;
            pointer-events: auto;
            transform: translateY(-8px);
            opacity: 0;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .role-config-toast.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .role-config-toast--success {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .role-config-toast--info {
            border-color: #bfdbfe;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .role-config-toast--error {
            border-color: #fecaca;
            background: #fff1f2;
            color: #be123c;
        }

        .role-config-toast__icon {
            flex: 0 0 auto;
            font-size: 14px;
            line-height: 1.4;
            margin-top: 1px;
        }

        .role-config-toast__message {
            flex: 1 1 auto;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.45;
        }

        .role-config-panel {
            display: none;
        }

        .role-config-panel.is-active {
            display: block;
        }

        .crud-permission-table-wrap {
            overflow-x: auto;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
        }

        .crud-permission-table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
        }

        .crud-permission-table th,
        .crud-permission-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
            text-align: left;
            font-size: 13px;
        }

        .crud-permission-table th {
            background: #f8fafc;
            color: #002C76;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }

        .crud-permission-module-cell {
            min-width: 200px;
            background: #f8fbff;
        }

        .crud-permission-module-title {
            color: #0f172a;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .crud-permission-module-description,
        .crud-permission-description-cell {
            color: #64748b;
            line-height: 1.6;
        }

        .crud-permission-mobile-module {
            display: none;
        }

        .crud-permission-mobile-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #1d4ed8;
            margin-bottom: 4px;
        }

        .crud-permission-submodule-cell {
            min-width: 180px;
            color: #0f172a;
            font-weight: 600;
        }

        .crud-permission-check-cell {
            min-width: 108px;
            text-align: center;
        }

        .crud-permission-check-cell--na {
            color: #94a3b8;
            font-size: 12px;
            font-weight: 700;
        }

        .crud-permission-na {
            display: inline-block;
            color: #94a3b8;
        }

        .crud-check-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
        }

        .crud-check-item input[type="checkbox"] {
            width: 15px;
            height: 15px;
            accent-color: #002C76;
        }

        .role-config-status-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .role-config-status-row > :first-child {
            flex: 1 1 auto;
            min-width: 0;
        }

        .role-config-save-status {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            margin-left: auto;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-align: right;
            min-height: 18px;
        }

        .role-config-save-status[data-state="idle"] {
            visibility: hidden;
        }

        .role-config-save-status__spinner {
            width: 12px;
            height: 12px;
            border: 2px solid rgba(29, 78, 216, 0.18);
            border-top-color: currentColor;
            border-radius: 999px;
            animation: role-config-spinner 0.7s linear infinite;
            opacity: 0;
            visibility: hidden;
        }

        .role-config-save-status[data-state="saving"] .role-config-save-status__spinner {
            opacity: 1;
            visibility: visible;
        }

        .role-config-save-status[data-state="saving"] {
            color: #1d4ed8;
        }

        .role-config-save-status[data-state="saved"] {
            color: #166534;
        }

        .role-config-save-status[data-state="error"] {
            color: #be123c;
        }

        .role-config-reset-btn {
            padding: 10px 16px;
            background-color: #ffffff;
            color: #991b1b;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 13px;
        }

        .role-config-reset-btn:hover:not(:disabled) {
            background-color: #fff1f2;
        }

        .role-config-reset-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        @keyframes role-config-spinner {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .role-config-page-header {
                gap: 12px;
            }

            .role-config-page-actions {
                width: 100%;
            }

            .role-config-manage-users-btn,
            .role-config-back-link {
                width: 100%;
            }

            .role-config-shell {
                padding: 18px 14px !important;
                border-radius: 10px !important;
            }

            .role-config-intro {
                gap: 14px !important;
            }

            .role-config-intro-copy {
                align-items: flex-start !important;
            }

            .role-config-intro-icon {
                width: 44px !important;
                height: 44px !important;
                border-radius: 12px !important;
                font-size: 17px !important;
                flex: 0 0 auto;
            }

            .role-config-superadmin-note,
            .role-config-superadmin-card {
                width: 100%;
            }

            .role-management-modal {
                padding: 12px;
            }

            .role-management-modal__dialog {
                max-height: calc(100vh - 24px);
                padding: 18px 14px;
                border-radius: 16px;
            }

            .role-management-modal__header {
                gap: 12px;
            }

            .role-management-modal__title {
                font-size: 20px;
            }

            .role-management-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .role-management-toolbar__actions {
                width: 100%;
            }

            .role-management-toolbar__button {
                flex: 1 1 calc(50% - 4px);
            }

            .role-entry-modal__dialog {
                padding: 16px 14px;
            }

            .role-management-modal__grid {
                grid-template-columns: 1fr;
            }

            .role-config-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                overflow-y: hidden;
                padding-bottom: 4px;
                scrollbar-width: none;
                -webkit-overflow-scrolling: touch;
            }

            .role-config-tabs::-webkit-scrollbar {
                display: none;
            }

            .role-config-tab {
                flex: 0 0 auto;
                white-space: nowrap;
                width: auto;
            }

            .role-config-toast-stack {
                top: 76px;
                right: 12px;
                left: 12px;
                width: auto;
            }

            .role-config-panel-header {
                margin-bottom: 14px !important;
            }

            .role-config-status-row {
                flex-direction: column;
                gap: 10px;
            }

            .role-config-save-status {
                align-self: flex-start;
                margin-left: 0;
                justify-content: flex-start;
                text-align: left;
            }

            .crud-permission-table-wrap {
                overflow: visible;
                border: none;
                background: transparent;
            }

            .crud-permission-table {
                min-width: 0;
            }

            .crud-permission-table,
            .crud-permission-table thead,
            .crud-permission-table tbody,
            .crud-permission-table tr,
            .crud-permission-table td {
                display: block;
                width: 100%;
            }

            .crud-permission-table thead {
                display: none;
            }

            .crud-permission-row {
                border: 1px solid #dbe4f0;
                border-radius: 14px;
                background: #ffffff;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
                padding: 16px 14px;
                margin-bottom: 14px;
            }

            .crud-permission-table tbody tr:last-child {
                margin-bottom: 0;
            }

            .crud-permission-module-cell {
                display: none !important;
            }

            .crud-permission-submodule-cell,
            .crud-permission-description-cell,
            .crud-permission-check-cell {
                min-width: 0;
                border-bottom: none !important;
                padding: 0;
                text-align: left;
            }

            .crud-permission-submodule-cell {
                margin-bottom: 12px;
                font-size: 15px;
                line-height: 1.5;
            }

            .crud-permission-mobile-module {
                display: block;
                padding: 12px;
                margin-bottom: 10px;
                border-radius: 12px;
                background: linear-gradient(180deg, #f8fbff 0%, #eff6ff 100%);
                border: 1px solid #dbeafe;
            }

            .crud-permission-description-cell {
                margin-bottom: 14px;
                font-size: 13px;
                line-height: 1.6;
            }

            .crud-permission-description-cell::before,
            .crud-permission-check-cell::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 6px;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #1d4ed8;
            }

            .crud-permission-check-cell {
                padding: 10px 12px;
                margin-bottom: 10px;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                background: #f8fafc;
            }

            .crud-permission-check-cell:last-child {
                margin-bottom: 0;
            }

            .crud-check-item {
                width: 100%;
                justify-content: space-between;
                gap: 12px;
                font-size: 13px;
            }

            .role-config-reset-btn {
                width: 100%;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = Array.from(document.querySelectorAll('[data-role-config-tab]'));
            const panels = Array.from(document.querySelectorAll('[data-role-config-panel]'));
            const roleConfigForms = Array.from(document.querySelectorAll('[data-role-config-form]'));
            const roleManagementModal = document.querySelector('[data-role-management-modal]');
            const roleManagementGrid = roleManagementModal?.querySelector('.role-management-modal__grid');
            let roleManagementCards = roleManagementModal
                ? Array.from(roleManagementModal.querySelectorAll('[data-role-management-card]'))
                : [];
            const roleManagementOpenButtons = Array.from(document.querySelectorAll('[data-role-management-open]'));
            const roleManagementCloseButtons = roleManagementModal
                ? Array.from(roleManagementModal.querySelectorAll('[data-role-management-close]'))
                : [];
            const roleEntryModal = document.querySelector('[data-role-entry-modal]');
            const roleEntryCloseButtons = roleEntryModal
                ? Array.from(roleEntryModal.querySelectorAll('[data-role-entry-close]'))
                : [];
            const roleEntryForm = roleEntryModal?.querySelector('[data-role-entry-form]');
            const roleEntryModeInput = roleEntryModal?.querySelector('[data-role-entry-mode]');
            const roleEntryIdInput = roleEntryModal?.querySelector('[data-role-entry-id]');
            const roleEntrySubmitUrlInput = roleEntryModal?.querySelector('[data-role-entry-submit-url]');
            const roleEntryNameInput = roleEntryModal?.querySelector('[data-role-entry-name]');
            const roleEntryTitle = roleEntryModal?.querySelector('.role-entry-modal__title');
            const roleEntryDescription = roleEntryModal?.querySelector('[data-role-entry-description]');
            const roleToolbarAddButton = roleManagementModal?.querySelector('[data-role-toolbar-add]');
            const roleToolbarSelection = roleManagementModal?.querySelector('[data-role-toolbar-selection]');
            const roleToolbarEditButton = roleManagementModal?.querySelector('[data-role-toolbar-edit]');
            const roleToolbarDeleteButton = roleManagementModal?.querySelector('[data-role-toolbar-delete]');
            const roleConfigToastStack = document.querySelector('[data-role-config-toast-stack]');
            const roleSuccessToastStorageKey = 'pdmuoms-role-management-success-toast-v1';

            const showRoleConfigToast = (message, type = 'info') => {
                const normalizedMessage = String(message || '').trim();
                if (normalizedMessage === '' || !roleConfigToastStack) {
                    return;
                }

                const toast = document.createElement('div');
                const resolvedType = ['success', 'error', 'info'].includes(type) ? type : 'info';
                const iconClass = resolvedType === 'success'
                    ? 'fas fa-check-circle'
                    : (resolvedType === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle');

                toast.className = `role-config-toast role-config-toast--${resolvedType}`;
                toast.innerHTML = `
                    <span class="role-config-toast__icon" aria-hidden="true"><i class="${iconClass}"></i></span>
                    <div class="role-config-toast__message"></div>
                `;

                toast.querySelector('.role-config-toast__message').textContent = normalizedMessage;
                roleConfigToastStack.appendChild(toast);

                window.requestAnimationFrame(() => {
                    toast.classList.add('is-visible');
                });

                const removeToast = () => {
                    toast.classList.remove('is-visible');
                    window.setTimeout(() => {
                        toast.remove();
                    }, 220);
                };

                window.setTimeout(removeToast, 3200);
            };

            const showRoleToolbarMessage = (message) => showRoleConfigToast(message, 'info');

            const showRoleToolbarSuccess = (message) => showRoleConfigToast(message, 'success');

            const showRoleToolbarError = (message) => showRoleConfigToast(message, 'error');

            const queueRoleToolbarSuccess = (message) => {
                const normalizedMessage = String(message || '').trim();
                if (normalizedMessage === '') {
                    return;
                }

                try {
                    window.sessionStorage.setItem(roleSuccessToastStorageKey, normalizedMessage);
                } catch (error) {
                    showRoleToolbarSuccess(normalizedMessage);
                }
            };

            const flushQueuedRoleToolbarSuccess = () => {
                try {
                    const message = window.sessionStorage.getItem(roleSuccessToastStorageKey);
                    if (!message) {
                        return;
                    }

                    window.sessionStorage.removeItem(roleSuccessToastStorageKey);
                    showRoleToolbarSuccess(message);
                } catch (error) {
                    // Ignore storage access issues and continue without a queued toast.
                }
            };

            const readJsonErrorMessage = (data, fallbackMessage) => {
                if (data && typeof data.message === 'string' && data.message.trim() !== '') {
                    return data.message.trim();
                }

                if (data && typeof data.errors === 'object' && data.errors !== null) {
                    for (const fieldErrors of Object.values(data.errors)) {
                        if (Array.isArray(fieldErrors) && typeof fieldErrors[0] === 'string' && fieldErrors[0].trim() !== '') {
                            return fieldErrors[0].trim();
                        }
                    }
                }

                return fallbackMessage;
            };

            const attachRoleManagementCardEvents = (card) => {
                card.addEventListener('click', (event) => {
                    const interactiveTarget = event.target.closest('a, button');
                    if (interactiveTarget) {
                        return;
                    }

                    setSelectedRoleCard(card);
                });

                card.addEventListener('keydown', (event) => {
                    if (event.key !== 'Enter' && event.key !== ' ') {
                        return;
                    }

                    event.preventDefault();
                    setSelectedRoleCard(card);
                });
            };

            const refreshRoleManagementCards = () => {
                if (!roleManagementGrid) {
                    return;
                }

                roleManagementCards = Array.from(roleManagementGrid.querySelectorAll('[data-role-management-card]'));
                roleManagementCards.forEach((card) => {
                    if (card.dataset.roleEventsBound === 'true') {
                        return;
                    }

                    attachRoleManagementCardEvents(card);
                    card.dataset.roleEventsBound = 'true';
                });
            };

            const openRoleEntryModal = ({ mode = 'create', id = '', label = '', submitUrl = '' } = {}) => {
                if (!roleEntryModal || !roleEntryForm) {
                    return;
                }

                roleEntryModeInput.value = mode;
                roleEntryIdInput.value = id;
                roleEntrySubmitUrlInput.value = submitUrl;
                roleEntryNameInput.value = label;
                roleEntryTitle.textContent = mode === 'edit' ? 'Edit Role' : 'Add Role';
                if (roleEntryDescription) {
                    roleEntryDescription.textContent = mode === 'edit'
                        ? 'Update the official role name. Saving keeps the role available across user management and role configuration.'
                        : 'Enter the role name. Saving creates an official role, then you can configure its access from the role matrix.';
                }
                roleEntryModal.hidden = false;
                roleEntryNameInput.focus();
                roleEntryNameInput.select();
            };

            const closeRoleEntryModal = () => {
                if (!roleEntryModal || !roleEntryForm) {
                    return;
                }

                roleEntryModal.hidden = true;
                roleEntryForm.reset();
                roleEntryModeInput.value = 'create';
                roleEntryIdInput.value = '';
                roleEntrySubmitUrlInput.value = '';
            };

            const findRoleManagementCard = ({ roleKind = '', roleId = '', roleLabel = '' } = {}) => {
                return roleManagementCards.find((card) => {
                    const cardKind = (card.dataset.roleKind || '').trim();
                    const cardId = (card.dataset.roleId || '').trim();
                    const cardLabel = (card.dataset.roleLabel || '').trim();

                    return cardKind === roleKind
                        && (roleId === '' || cardId === roleId)
                        && (roleLabel === '' || cardLabel === roleLabel);
                }) ?? null;
            };

            const reopenRoleManagementModal = (selection = null) => {
                if (!roleManagementModal) {
                    return;
                }

                refreshRoleManagementCards();
                roleManagementModal.hidden = false;
                document.body.classList.add('role-management-modal-open');
                setSelectedRoleCard(selection ? findRoleManagementCard(selection) : null);
            };

            const queueRoleManagementModalReopen = (selection = null) => {
                window.setTimeout(() => {
                    reopenRoleManagementModal(selection);
                }, 0);
            };

            const setRoleManagementBackgrounded = (backgrounded) => {
                if (!roleManagementModal) {
                    return;
                }

                roleManagementModal.classList.toggle('is-backgrounded', backgrounded);
            };

            const setSelectedRoleCard = (selectedCard = null) => {
                roleManagementCards.forEach((card) => {
                    card.classList.toggle('is-selected', card === selectedCard);
                });

                const selectedRoleKind = selectedCard?.dataset.roleKind?.trim() || '';
                const selectedRoleId = selectedCard?.dataset.roleId?.trim() || '';
                const selectedRoleLabel = selectedCard?.dataset.roleLabel?.trim() || '';
                const editUrl = selectedCard?.dataset.editUrl?.trim() || '';
                const roleUpdateUrl = selectedCard?.dataset.roleUpdateUrl?.trim() || '';
                const roleDeleteUrl = selectedCard?.dataset.roleDeleteUrl?.trim() || '';

                if (roleToolbarSelection) {
                    roleToolbarSelection.textContent = selectedRoleLabel === ''
                        ? 'Select a role card to edit or review role actions.'
                        : `Selected role: ${selectedRoleLabel}${selectedRoleKind === 'custom' ? ' (Custom)' : ''}`;
                }

                if (roleToolbarAddButton) {
                    roleToolbarAddButton.title = selectedRoleLabel === ''
                        ? 'Add an official role.'
                        : `Add a role based on ${selectedRoleLabel}`;
                }

                if (roleToolbarEditButton) {
                    roleToolbarEditButton.dataset.roleKind = selectedRoleKind;
                    roleToolbarEditButton.dataset.roleId = selectedRoleId;
                    roleToolbarEditButton.dataset.roleLabel = selectedRoleLabel;
                    roleToolbarEditButton.dataset.updateUrl = roleUpdateUrl;
                    roleToolbarEditButton.dataset.url = editUrl;
                    roleToolbarEditButton.title = selectedRoleLabel === ''
                        ? 'Select a role to edit.'
                        : `Edit ${selectedRoleLabel}`;
                }

                if (roleToolbarDeleteButton) {
                    roleToolbarDeleteButton.dataset.roleKind = selectedRoleKind;
                    roleToolbarDeleteButton.dataset.roleId = selectedRoleId;
                    roleToolbarDeleteButton.dataset.roleLabel = selectedRoleLabel;
                    roleToolbarDeleteButton.dataset.deleteUrl = roleDeleteUrl;
                    roleToolbarDeleteButton.title = selectedRoleLabel === ''
                        ? 'Select a role to review deletion options.'
                        : `Delete ${selectedRoleLabel}`;
                }
            };

            const openRoleManagementModal = () => {
                if (!roleManagementModal) {
                    return;
                }

                setRoleManagementBackgrounded(false);
                reopenRoleManagementModal();
                roleManagementModal.querySelector('.role-management-modal__close')?.focus();
            };

            const closeRoleManagementModal = () => {
                if (!roleManagementModal) {
                    return;
                }

                setRoleManagementBackgrounded(false);
                closeRoleEntryModal();
                roleManagementModal.hidden = true;
                document.body.classList.remove('role-management-modal-open');
            };

            roleManagementOpenButtons.forEach((button) => {
                button.addEventListener('click', openRoleManagementModal);
            });

            roleManagementCloseButtons.forEach((button) => {
                button.addEventListener('click', closeRoleManagementModal);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && roleEntryModal && !roleEntryModal.hidden) {
                    closeRoleEntryModal();
                    return;
                }

                if (event.key === 'Escape' && roleManagementModal && !roleManagementModal.hidden) {
                    if (roleManagementModal.classList.contains('is-backgrounded')) {
                        return;
                    }

                    closeRoleManagementModal();
                }
            });

            roleEntryCloseButtons.forEach((button) => {
                button.addEventListener('click', closeRoleEntryModal);
            });

            refreshRoleManagementCards();
            flushQueuedRoleToolbarSuccess();

            roleToolbarEditButton?.addEventListener('click', () => {
                const roleKind = roleToolbarEditButton.dataset.roleKind || '';
                const selectedRoleId = roleToolbarEditButton.dataset.roleId || '';
                const selectedRoleLabel = roleToolbarEditButton.dataset.roleLabel || '';
                const updateUrl = roleToolbarEditButton.dataset.updateUrl || '';
                const editUrl = roleToolbarEditButton.dataset.url || '';

                if (updateUrl !== '') {
                    if (selectedRoleId === '' || selectedRoleLabel === '') {
                        showRoleToolbarMessage('Select a role first.');
                        return;
                    }

                    openRoleEntryModal({
                        mode: 'edit',
                        id: selectedRoleId,
                        label: selectedRoleLabel,
                        submitUrl: updateUrl,
                    });
                    return;
                }

                if (editUrl === '') {
                    showRoleToolbarMessage('Select an editable role first.');
                    return;
                }

                window.location.href = editUrl;
            });

            roleToolbarAddButton?.addEventListener('click', () => {
                openRoleEntryModal({ mode: 'create' });
            });

            roleToolbarDeleteButton?.addEventListener('click', () => {
                const selectedRoleKind = roleToolbarDeleteButton.dataset.roleKind || '';
                const selectedRoleId = roleToolbarDeleteButton.dataset.roleId || '';
                const selectedRoleLabel = roleToolbarDeleteButton.dataset.roleLabel || '';
                const deleteUrl = roleToolbarDeleteButton.dataset.deleteUrl || '';
                if (selectedRoleLabel === '') {
                    showRoleToolbarMessage('Select a role first.');
                    return;
                }

                if (selectedRoleId === '' || deleteUrl === '') {
                    showRoleToolbarMessage(`${selectedRoleLabel} cannot be deleted here.`);
                    return;
                }

                const selectedRoleState = {
                    roleKind: selectedRoleKind,
                    roleId: selectedRoleId,
                    roleLabel: selectedRoleLabel,
                };

                setRoleManagementBackgrounded(true);
                window.openConfirmationModal(
                    `Delete the role ${selectedRoleLabel}?`,
                    async () => {
                        const csrfToken = roleEntryForm?.querySelector('input[name="_token"]')?.value || '';

                        try {
                            const payload = new FormData();
                            payload.append('_token', csrfToken);
                            payload.append('_method', 'DELETE');

                            const response = await fetch(deleteUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: payload,
                            });
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                throw new Error(readJsonErrorMessage(data, 'Unable to delete role.'));
                            }

                            queueRoleToolbarSuccess(data.message || `Role ${selectedRoleLabel} deleted.`);
                            window.location.href = data.redirect_url || roleEntryForm?.dataset.indexUrl || window.location.href;
                        } catch (error) {
                            setRoleManagementBackgrounded(false);
                            setSelectedRoleCard(findRoleManagementCard(selectedRoleState));
                            showRoleToolbarMessage(error.message || 'Unable to delete role.');
                        }
                    },
                    () => {
                        setRoleManagementBackgrounded(false);
                        setSelectedRoleCard(findRoleManagementCard(selectedRoleState));
                        roleToolbarDeleteButton?.focus();
                    }
                );
            });

            roleEntryForm?.addEventListener('submit', async (event) => {
                event.preventDefault();

                const mode = roleEntryModeInput.value === 'edit' ? 'edit' : 'create';
                const roleLabel = roleEntryNameInput.value.trim().replace(/\s+/g, ' ');
                const submitUrl = mode === 'edit'
                    ? roleEntrySubmitUrlInput.value.trim()
                    : (roleEntryForm.dataset.storeUrl || '').trim();

                if (roleLabel === '') {
                    showRoleToolbarMessage('Enter a role name.');
                    roleEntryNameInput.focus();
                    return;
                }

                if (submitUrl === '') {
                    showRoleToolbarMessage('Role form is not configured correctly.');
                    return;
                }

                const csrfToken = roleEntryForm.querySelector('input[name="_token"]')?.value || '';
                const submitButton = roleEntryForm.querySelector('button[type="submit"]');
                const cancelButton = roleEntryForm.querySelector('[data-role-entry-close]');

                try {
                    submitButton?.setAttribute('disabled', 'disabled');
                    cancelButton?.setAttribute('disabled', 'disabled');

                    const payload = new FormData();
                    payload.append('_token', csrfToken);
                    payload.append('label', roleLabel);

                    if (mode === 'edit') {
                        payload.append('_method', 'PUT');
                    }

                    const response = await fetch(submitUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: payload,
                    });
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(readJsonErrorMessage(data, 'Unable to save role.'));
                    }

                    queueRoleToolbarSuccess(data.message || (mode === 'edit'
                        ? `Role ${roleLabel} updated.`
                        : `Role ${roleLabel} created.`));
                    window.location.href = data.redirect_url || roleEntryForm.dataset.indexUrl || window.location.href;
                } catch (error) {
                    showRoleToolbarMessage(error.message || 'Unable to save role.');
                    roleEntryNameInput.focus();
                    roleEntryNameInput.select();
                } finally {
                    submitButton?.removeAttribute('disabled');
                    cancelButton?.removeAttribute('disabled');
                }
            });

            const activatePanel = (panelId) => {
                tabs.forEach((tab) => {
                    const isActive = tab.dataset.target === panelId;
                    tab.classList.toggle('is-active', isActive);
                    tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                panels.forEach((panel) => {
                    panel.classList.toggle('is-active', panel.id === panelId);
                });
            };

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => activatePanel(tab.dataset.target));
            });

            const getCheckedPermissions = (form) => Array.from(form.querySelectorAll('input[name="crud_permissions[]"]:checked'))
                .map((input) => input.value)
                .sort();

            const setCheckedPermissions = (form, permissions) => {
                const selected = new Set(permissions);
                form.querySelectorAll('input[name="crud_permissions[]"]').forEach((input) => {
                    input.checked = selected.has(input.value);
                });
            };

            const setInputsDisabled = (form, disabled) => {
                form.querySelectorAll('input[name="crud_permissions[]"]').forEach((input) => {
                    input.disabled = disabled;
                });

                const resetButton = form.querySelector('[data-role-config-reset]');
                if (resetButton) {
                    if (disabled) {
                        resetButton.disabled = true;
                        return;
                    }

                    resetButton.disabled = resetButton.dataset.allowReset !== 'true';
                }
            };

            const updateBadgeState = (form, usesRecommendedDefaults) => {
                const badge = form.closest('[data-role-config-panel]')?.querySelector('[data-role-config-badge]');
                const resetButton = form.querySelector('[data-role-config-reset]');

                if (badge) {
                    badge.textContent = usesRecommendedDefaults ? badge.dataset.defaultText : badge.dataset.customText;
                    badge.style.background = usesRecommendedDefaults ? badge.dataset.defaultBg : badge.dataset.customBg;
                    badge.style.color = usesRecommendedDefaults ? badge.dataset.defaultColor : badge.dataset.customColor;
                }

                if (resetButton) {
                    resetButton.dataset.allowReset = usesRecommendedDefaults ? 'false' : 'true';
                    resetButton.disabled = usesRecommendedDefaults;
                }
            };

            const setSaveStatus = (form, state, message) => {
                const status = form.querySelector('[data-role-config-save-status]');
                if (!status) {
                    return;
                }

                status.dataset.state = state;
                const statusText = status.querySelector('[data-role-config-save-status-text]');
                if (statusText) {
                    statusText.textContent = message;
                }
            };

            const saveForm = async (form) => {
                const payload = new FormData();
                const checkedPermissions = getCheckedPermissions(form);

                payload.append('_token', form.querySelector('input[name="_token"]').value);
                payload.append('_method', 'PUT');
                checkedPermissions.forEach((permission) => payload.append('crud_permissions[]', permission));

                setSaveStatus(form, 'saving', 'Saving changes...');
                setInputsDisabled(form, true);

                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: payload,
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data.message || 'Unable to save role configuration.');
                }

                form.dataset.savedPermissions = JSON.stringify(data.permissions || checkedPermissions);
                updateBadgeState(form, Boolean(data.uses_recommended_defaults));
                setSaveStatus(form, 'idle', '');
                showRoleToolbarSuccess(data.message || 'Role configuration updated successfully.');
            };

            const resetForm = async (form) => {
                const payload = new FormData();
                payload.append('_token', form.querySelector('input[name="_token"]').value);
                payload.append('_method', 'DELETE');

                setSaveStatus(form, 'saving', 'Resetting role configuration...');
                setInputsDisabled(form, true);

                const response = await fetch(form.dataset.resetUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: payload,
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data.message || 'Unable to reset role configuration.');
                }

                const permissions = Array.isArray(data.permissions) ? data.permissions : [];
                setCheckedPermissions(form, permissions);
                form.dataset.savedPermissions = JSON.stringify([...permissions].sort());
                updateBadgeState(form, Boolean(data.uses_recommended_defaults));
                setSaveStatus(form, 'idle', '');
                showRoleToolbarSuccess(data.message || 'Role configuration reset.');
            };

            roleConfigForms.forEach((form) => {
                form.dataset.savedPermissions = JSON.stringify(getCheckedPermissions(form));
                updateBadgeState(form, form.querySelector('[data-role-config-reset]')?.disabled !== false);

                form.querySelectorAll('input[name="crud_permissions[]"]').forEach((input) => {
                    input.addEventListener('change', async () => {
                        const previousPermissions = JSON.parse(form.dataset.savedPermissions || '[]');

                        try {
                            await saveForm(form);
                        } catch (error) {
                            setCheckedPermissions(form, previousPermissions);
                            setSaveStatus(form, 'error', error.message || 'Unable to save role configuration.');
                            showRoleToolbarError(error.message || 'Unable to save role configuration.');
                        } finally {
                            setInputsDisabled(form, false);
                        }
                    });
                });

                const resetButton = form.querySelector('[data-role-config-reset]');
                if (!resetButton) {
                    return;
                }

                resetButton.dataset.allowReset = resetButton.disabled ? 'false' : 'true';
                resetButton.addEventListener('click', () => {
                    if (resetButton.disabled) {
                        return;
                    }

                    window.openConfirmationModal(
                        `Reset ${form.dataset.roleLabel} access back to the recommended default configuration?`,
                        async () => {
                            const previousPermissions = JSON.parse(form.dataset.savedPermissions || '[]');

                            try {
                                await resetForm(form);
                            } catch (error) {
                                setCheckedPermissions(form, previousPermissions);
                                setSaveStatus(form, 'error', error.message || 'Unable to reset role configuration.');
                                showRoleToolbarError(error.message || 'Unable to reset role configuration.');
                                setInputsDisabled(form, false);
                            } finally {
                                setInputsDisabled(form, false);
                            }
                        }
                    );
                });
            });
        });
    </script>
@endsection
