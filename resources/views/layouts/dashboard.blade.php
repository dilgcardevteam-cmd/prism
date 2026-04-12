<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - DILG-CAR PDMU</title>
    <link rel="icon" type="image/png" href="/DILG-Logo.png">
    @include('partials.google-sans-font')
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Vite (Tailwind + app JS) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html,
        body {
            height: 100%;
        }

        body {
            font-family: var(--app-font-sans);
            background-image: url('/background.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            overflow-x: hidden;
            overflow-y: hidden;
        }

        body.sidebar-open {
            overflow: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            --sidebar-start: #00163f;
            --sidebar-mid: #002c76;
            --sidebar-end: #0b4fb3;
            --sidebar-glow: rgba(125, 211, 252, 0.18);
            --sidebar-highlight: rgba(255, 255, 255, 0.16);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 250px;
            background:
                radial-gradient(circle at top left, var(--sidebar-highlight) 0%, transparent 34%),
                radial-gradient(circle at bottom right, var(--sidebar-glow) 0%, transparent 30%),
                linear-gradient(180deg, var(--sidebar-start) 0%, var(--sidebar-mid) 48%, var(--sidebar-end) 100%);
            padding: 20px;
            overflow-y: auto;
            transition: transform 280ms cubic-bezier(0.2, 0.8, 0.2, 1), width 280ms cubic-bezier(0.2, 0.8, 0.2, 1), padding 280ms cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 280ms cubic-bezier(0.2, 0.8, 0.2, 1);
            will-change: transform;
            z-index: 1000;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
            transform: translateX(0);
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .sidebar::-webkit-scrollbar {
            display: none;
        }
        
        .sidebar.collapsed {
            transform: translateX(-100%);
            box-shadow: none;
            pointer-events: none;
        }

        .sidebar.icon-collapsed {
            width: 78px;
            padding: 20px 10px;
            transform: translateX(0);
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.15);
            pointer-events: auto;
        }
        
        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            transition: margin-bottom 220ms ease, padding-bottom 220ms ease;
        }

        .sidebar-brand-link {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            min-width: 0;
            width: 100%;
            padding-right: 0;
            color: inherit;
            text-decoration: none;
            transition: padding-right 220ms ease;
        }
        
        .sidebar-logo {
            width: 100%;
            max-width: 195px;
            height: auto;
            margin-right: 0;
            transition: width 220ms ease, max-width 220ms ease, height 220ms ease, margin-right 220ms ease;
        }

        .sidebar-logo--collapsed {
            display: none;
        }
        
        .sidebar-title {
            color: white;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
            max-width: 220px;
            overflow: hidden;
            transition: opacity 200ms ease, max-width 220ms ease, transform 220ms ease;
        }
        
        .sidebar-title small {
            display: block;
            font-size: 12px;
            font-weight: 400;
            opacity: 0.8;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 8px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 6px;
            position: relative;
            transform: translateY(0);
            box-shadow: 0 0 0 rgba(0, 0, 0, 0);
            transition: background-color 0.22s ease, color 0.22s ease, padding-left 0.22s ease, transform 0.22s ease, box-shadow 0.22s ease;
            font-size: 14px;
        }
        
        .sidebar-menu a:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            padding-left: 20px;
            transform: translateY(-2px);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.22);
        }
        
        .sidebar-menu a.active {
            background-color: #ffffff;
            color: #002C76;
            font-weight: 700;
            box-shadow: none;
        }

        .sidebar-menu a.sidebar-float-hover:hover,
        .sidebar-menu a.sidebar-float-hover:focus-visible {
            transform: translateY(-4px);
            box-shadow: 0 14px 24px rgba(15, 23, 42, 0.24);
        }

        .sidebar-menu a.sidebar-float-hover.active:hover,
        .sidebar-menu a.sidebar-float-hover.active:focus-visible {
            background-color: #ffffff;
            color: #002C76;
        }
        
        .sidebar-menu i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
            transition: margin-right 220ms ease, transform 220ms ease;
        }

        .sidebar-menu a span {
            display: inline-block;
            overflow: hidden;
            max-width: 1000px;
            opacity: 1;
            transform: translateX(0);
            transition: opacity 180ms ease, max-width 220ms ease, transform 220ms ease;
        }

        .sidebar-menu a .sidebar-menu-badge {
            margin-left: auto;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #dc2626;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            flex-shrink: 0;
            max-width: none;
            opacity: 1;
            transform: none;
        }

        /* Submenu Styles */
        .submenu {
            list-style: none;
            background: linear-gradient(180deg, rgba(0, 20, 58, 0.46) 0%, rgba(0, 44, 118, 0.28) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 6px;
            overflow: hidden;
            margin-top: 8px;
        }

        .submenu li {
            margin: 0;
        }

        .submenu a {
            display: flex;
            align-items: center;
            padding: 10px 16px 10px 48px !important;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 13px;
            transition: background-color 0.22s ease, color 0.22s ease, padding-left 0.22s ease, transform 0.22s ease, box-shadow 0.22s ease;
        }

        .submenu a:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            padding-left: 52px !important;
            transform: translateY(-3px);
            box-shadow: 0 12px 20px rgba(15, 23, 42, 0.24);
        }

        .submenu a.active {
            background-color: #ffffff;
            color: #002C76;
            font-weight: 700;
            box-shadow: none;
        }

        .submenu .submenu {
            margin-top: 0;
            border-radius: 0;
            background: linear-gradient(180deg, rgba(0, 16, 46, 0.4) 0%, rgba(0, 35, 92, 0.24) 100%);
        }

        .submenu .submenu a {
            padding-left: 64px !important;
            font-size: 12px;
        }

        .submenu .submenu a:hover {
            padding-left: 68px !important;
        }

        .submenu-empty {
            display: block;
            padding: 10px 16px 10px 64px;
            color: rgba(255, 255, 255, 0.65);
            font-size: 12px;
            font-style: italic;
        }

        .sidebar-menu a.submenu-toggle {
            cursor: pointer;
        }

        .submenu-chevron {
            transition: transform 0.2s ease;
        }

        .sidebar-menu a.submenu-toggle[aria-expanded="true"] .submenu-chevron {
            transform: rotate(180deg);
        }
        
        /* Topbar Styles */
        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            z-index: 999;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: padding-left 280ms cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        
        .topbar.with-sidebar {
            padding-left: 280px;
        }

        .sidebar.icon-collapsed ~ .topbar {
            padding-left: 108px;
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .toggle-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 24px;
            color: #002C76;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            z-index: 1001;
            position: relative;
        }
        
        .toggle-btn:hover {
            background-color: #f0f0f0;
            color: #003d99;
        }
        
        .toggle-btn:active {
            transform: scale(0.95);
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .pagasa-clock {
            font-size: 12px;
            font-weight: 600;
            color: #002C76;
            white-space: nowrap;
            min-width: 250px;
            text-align: right;
        }
        
        /* Profile Dropdown */
        .profile-dropdown {
            position: relative;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-direction: row-reverse;
        }
        
        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #002C76 0%, #003d99 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .profile-icon:hover {
            border-color: #002C76;
            box-shadow: 0 4px 12px rgba(0, 44, 118, 0.2);
        }

        /* Notification Bell */
        .notification-bell {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: transparent;
            color: #002C76;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s ease;
            border: none;
            padding: 0;
        }

        .notification-bell:hover {
            color: #003d99;
            transform: scale(1.1);
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc2626;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            border: 2px solid white;
        }

        .notification-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
        }

        .notification-menu {
            position: absolute;
            top: 48px;
            right: 0;
            width: min(380px, calc(100vw - 24px));
            max-height: 420px;
            overflow-y: auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
            display: none;
            z-index: 1100;
        }

        .notification-menu.show {
            display: block;
            animation: slideDown 0.2s ease;
        }

        .notification-menu-header {
            padding: 10px 14px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .notification-menu-title {
            font-size: 13px;
            font-weight: 700;
            color: #002C76;
        }

        .notification-clear-btn {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1;
            cursor: pointer;
        }

        .notification-clear-btn:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        .notification-menu-empty {
            padding: 14px;
            font-size: 13px;
            color: #6b7280;
        }

        .notification-menu-footer {
            padding: 10px 14px;
            border-top: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .notification-menu-footer-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .notification-menu-action-form {
            margin: 0;
        }

        .notification-menu-view-all {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .notification-menu-view-all:hover {
            color: #1e3a8a;
        }

        .notification-menu-clear-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            background: transparent;
            color: #b91c1c;
            font-size: 12px;
            font-weight: 700;
            line-height: 1;
            padding: 0;
            cursor: pointer;
        }

        .notification-menu-clear-action:hover {
            color: #991b1b;
        }

        .notification-menu-item {
            display: block;
            text-decoration: none;
            color: inherit;
            padding: 10px 14px;
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.16s ease;
        }

        .notification-menu-item:hover {
            background: #f8fafc;
        }

        .notification-menu-item.unread {
            background: #eff6ff;
        }

        .notification-menu-item:last-child {
            border-bottom: 0;
        }

        .notification-menu-message {
            font-size: 12px;
            line-height: 1.4;
            color: #1f2937;
            margin-bottom: 3px;
        }

        .notification-menu-message-row {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .notification-unread-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #dc2626;
            margin-top: 5px;
            flex-shrink: 0;
        }

        .notification-menu-item.unread .notification-menu-message {
            font-weight: 600;
        }

        .notification-menu-time {
            font-size: 11px;
            color: #64748b;
        }

        .profile-menu {
            position: absolute;
            top: 50px;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            display: none;
            z-index: 1001;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        .profile-menu.show {
            display: block;
            animation: slideDown 0.2s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .profile-menu-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            background-color: #f9fafb;
        }
        
        .profile-menu-name {
            font-weight: 600;
            color: #002C76;
            font-size: 14px;
        }
        
        .profile-menu-email {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }

        .profile-menu-role {
            font-size: 12px;
            color: #1d4ed8;
            margin-top: 6px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        
        .profile-menu-item {
            padding: 12px 20px;
            color: #374151;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            border: none;
            background: none;
            cursor: pointer;
            width: 100%;
            text-align: left;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .profile-menu-item:hover {
            background-color: #f3f4f6;
            color: #002C76;
        }
        
        .profile-menu-item.logout {
            color: #dc2626;
            border-top: 1px solid #e5e7eb;
        }
        
        .profile-menu-item.logout:hover {
            background-color: #fef2f2;
        }
        
        /* Main Content */
        .main-content {
            margin-top: 70px;
            margin-left: 250px;
            padding: 30px;
            height: calc(100vh - 70px);
            min-height: calc(100vh - 70px);
            overflow-y: auto;
            overflow-x: hidden;
            transition: none;
            will-change: margin-left;
        }

        .main-content.sidebar-transition-enabled {
            transition: margin-left 280ms cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        
        .main-content.with-sidebar {
            margin-left: 250px;
        }
        
        .main-content:not(.with-sidebar) {
            margin-left: 0;
        }

        .sidebar.icon-collapsed ~ .main-content {
            margin-left: 78px;
        }

        .main-content.is-shifting-left {
            animation: mainContentShiftLeft 320ms cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .main-content.is-shifting-right {
            animation: mainContentShiftRight 320ms cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes mainContentShiftLeft {
            from {
                transform: translateX(16px);
                opacity: 0.94;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes mainContentShiftRight {
            from {
                transform: translateX(-16px);
                opacity: 0.94;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .sidebar.icon-collapsed .sidebar-header {
            justify-content: center;
            margin-bottom: 18px;
            padding-bottom: 16px;
        }

        .sidebar.icon-collapsed .sidebar-brand-link {
            justify-content: center;
            padding-right: 0;
        }

        .sidebar.icon-collapsed .sidebar-logo {
            width: 56px;
            max-width: 56px;
            height: auto;
            margin-right: 0;
        }

        .sidebar.icon-collapsed .sidebar-logo--expanded {
            display: none;
        }

        .sidebar.icon-collapsed .sidebar-logo--collapsed {
            display: block;
        }

        .sidebar.icon-collapsed .submenu,
        .sidebar.icon-collapsed .submenu-empty,
        .sidebar.icon-collapsed .submenu-chevron {
            display: none !important;
        }

        .sidebar.icon-collapsed .sidebar-menu a {
            justify-content: center;
            padding: 11px 0 !important;
        }

        .sidebar.icon-collapsed .sidebar-menu a:hover {
            padding-left: 0 !important;
        }

        .sidebar.icon-collapsed .sidebar-menu a span {
            opacity: 0;
            max-width: 0;
            transform: translateX(-6px);
            white-space: nowrap;
        }

        .sidebar.icon-collapsed .sidebar-menu a .sidebar-menu-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            margin-left: 0;
            max-width: none;
            opacity: 1;
            transform: none;
        }

        .sidebar.icon-collapsed .sidebar-menu i {
            margin-right: 0;
            transform: translateX(0);
        }

        .sidebar.icon-collapsed .sidebar-title {
            opacity: 0;
            max-width: 0;
            transform: translateX(-8px);
            margin-right: 0;
        }
        
        .content-header {
            margin-bottom: 30px;
        }
        
        .content-header h1 {
            color: #002C76;
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .content-header p {
            color: #6b7280;
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 220px;
                transition: transform 320ms cubic-bezier(0.22, 1, 0.36, 1), opacity 220ms ease, box-shadow 320ms ease;
                opacity: 1;
            }
            
            .sidebar.collapsed {
                transform: translateX(-100%);
                opacity: 0;
            }

            .sidebar.icon-collapsed {
                width: 220px;
                padding: 20px;
            }
            
            .topbar {
                padding: 0 15px;
            }
            
            .topbar.with-sidebar {
                padding-left: 15px;
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px 15px;
            }
            
            .main-content.with-sidebar {
                margin-left: 220px;
            }
            
            .sidebar-title {
                font-size: 14px;
            }
            
            .content-header h1 {
                font-size: 24px;
            }

            .content-header {
                flex-wrap: wrap;
                gap: 12px;
            }

            .main-content div[style*="display: flex"][style*="justify-content: space-between"] {
                flex-wrap: wrap;
                gap: 12px;
            }

            .main-content div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }

            .main-content div[style*="grid-template-columns: repeat(3"] {
                grid-template-columns: 1fr !important;
            }

            .main-content div[style*="grid-template-columns: repeat(2"] {
                grid-template-columns: 1fr !important;
            }

            .main-content div[style*="grid-template-columns: repeat(auto-fit"] {
                grid-template-columns: 1fr !important;
            }

            .main-content div[style*="grid-template-columns: minmax"] {
                grid-template-columns: 1fr !important;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .sidebar,
            .topbar,
            .main-content,
            .sidebar-title,
            .sidebar-logo,
            .sidebar-menu i,
            .sidebar-menu a span {
                transition: none !important;
            }
        }
        
        @media (max-width: 480px) {
            .sidebar {
                width: 80%;
                z-index: 1100;
                max-width: 320px;
                transition: transform 320ms cubic-bezier(0.22, 1, 0.36, 1), opacity 220ms ease, box-shadow 320ms ease;
            }
            
            .sidebar.collapsed {
                transform: translateX(-100%);
                opacity: 0;
            }

            .sidebar.icon-collapsed {
                width: 80%;
                max-width: 320px;
                padding: 20px;
            }
            
            .topbar {
                padding: 0 12px;
                height: 60px;
            }
            
            .topbar.with-sidebar {
                padding-left: 12px;
            }
            
            .topbar-left {
                gap: 10px;
            }
            
            .toggle-btn {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
            
            .topbar-right {
                gap: 15px;
            }

            .pagasa-clock {
                display: none;
            }
            
            .main-content {
                margin-top: 60px;
                margin-left: 0;
                padding: 15px 12px;
                height: calc(100vh - 60px);
                min-height: calc(100vh - 60px);
                overflow-y: auto;
            }
            
            .main-content.with-sidebar {
                margin-left: 0;
            }
            
            .content-header {
                margin-bottom: 20px;
            }
            
            .content-header h1 {
                font-size: 20px;
                margin-bottom: 6px;
            }
            
            .content-header p {
                font-size: 13px;
            }
            
            .sidebar-header {
                margin-bottom: 20px;
                padding-bottom: 15px;
            }
            
            .sidebar-logo {
                width: 100%;
                max-width: 165px;
                height: auto;
                margin-right: 0;
            }
            
            .sidebar-title {
                font-size: 12px;
            }
            
            .sidebar-title small {
                font-size: 10px;
            }
            
            .sidebar-menu a {
                padding: 10px 12px;
                font-size: 13px;
            }
            
            .sidebar-menu i {
                width: 18px;
                margin-right: 10px;
            }
            
            .profile-icon {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }
            
            .profile-menu {
                right: -5px;
                min-width: 170px;
                font-size: 13px;
            }

            .notification-menu {
                right: -8px;
                width: min(320px, calc(100vw - 24px));
            }
            
            .profile-menu-item {
                padding: 10px 16px;
                font-size: 13px;
            }
            
            .profile-menu-header {
                padding: 12px 16px;
            }
            
            .profile-menu-name {
                font-size: 13px;
            }
            
            .profile-menu-email {
                font-size: 11px;
            }

            .profile-menu-role {
                font-size: 11px;
            }
        }

        @media (max-width: 768px) {
            .main-content.is-shifting-left,
            .main-content.is-shifting-right {
                animation: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .main-content {
                transition: none;
            }

            .main-content.is-shifting-left,
            .main-content.is-shifting-right {
                animation: none;
            }
        }

        /* Shared report-table polish */
        .report-table-card,
        .report-table-shell {
            position: relative;
            border: 1px solid #dbe4f0 !important;
            border-radius: 14px !important;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%) !important;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08) !important;
        }

        .report-table-card::before,
        .report-table-shell::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            background: linear-gradient(90deg, #002C76 0%, #1d4ed8 55%, #0284c7 100%);
            pointer-events: none;
        }

        .report-table-scroll,
        .report-table-shell {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .report-table-scroll table,
        .report-table-shell table {
            border-collapse: separate !important;
            border-spacing: 0 !important;
            background: #ffffff;
        }

        .report-table-scroll table thead th,
        .report-table-shell table thead th {
            background: #edf3ff !important;
            color: #1f2a44 !important;
            border-bottom: 1px solid #d9e3f0 !important;
            font-size: 12px !important;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .report-table-scroll table tbody td,
        .report-table-shell table tbody td {
            border-bottom: 1px solid #e8edf5 !important;
            color: #0f172a !important;
            vertical-align: middle;
        }

        .report-table-scroll table tbody tr:nth-child(even),
        .report-table-shell table tbody tr:nth-child(even) {
            background-color: #fbfdff;
        }

        .report-table-scroll table tbody tr:hover,
        .report-table-shell table tbody tr:hover {
            background-color: #eef4ff !important;
        }

        .report-table-scroll table tbody td:last-child a,
        .report-table-shell table tbody td:last-child a {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 14px !important;
            border-radius: 999px !important;
            border: 1px solid #1d4ed8 !important;
            background: #ffffff !important;
            color: #1d4ed8 !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, color 0.2s ease !important;
        }

        .report-table-scroll table tbody td:last-child a:hover,
        .report-table-shell table tbody td:last-child a:hover {
            background: #1d4ed8 !important;
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(29, 78, 216, 0.22);
        }

        .table-pagination-row {
            margin-top: 18px !important;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
        }

        .table-pagination-row a,
        .table-pagination-row span {
            border-radius: 999px !important;
            font-weight: 700 !important;
            letter-spacing: 0.01em;
        }

        .table-pagination-row a:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.14);
        }

        .table-pagination-row select {
            border: 1px solid #c7d5e8 !important;
            border-radius: 999px !important;
            background: #ffffff !important;
            color: #334155;
            font-weight: 600;
            padding: 6px 12px !important;
        }

        .table-pagination-row select:focus {
            outline: none;
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
        }

        /* Scoped detail/edit page refinement */
        .ops-detail-page .content-header {
            background: linear-gradient(135deg, #ffffff 0%, #f7faff 100%);
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .ops-detail-page .content-header h1 {
            color: #0f2a5d;
            letter-spacing: 0.01em;
        }

        .ops-detail-page .content-header p {
            color: #4b5563;
        }

        .ops-detail-page .content-header > div:last-child {
            margin-left: auto;
        }

        .ops-detail-page div[style*="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08)"],
        .ops-detail-page div[style*="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1)"],
        .ops-detail-page div[style*="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1)"] {
            border: 1px solid #dbe4f0 !important;
            border-radius: 14px !important;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08) !important;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%) !important;
        }

        .ops-detail-page div[style*="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden"] {
            border-color: #dbe4f0 !important;
            border-radius: 12px !important;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }

        .ops-detail-page div[style*="overflow-x: auto"] {
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            background: #ffffff;
        }

        .ops-detail-page table {
            border-collapse: separate !important;
            border-spacing: 0 !important;
            width: 100%;
        }

        .ops-detail-page table thead th {
            background: #edf3ff !important;
            color: #1f2a44 !important;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            font-size: 11px !important;
            border-bottom: 1px solid #d9e3f0 !important;
        }

        .ops-detail-page table tbody td {
            border-bottom: 1px solid #e8edf5 !important;
        }

        .ops-detail-page table tbody tr:nth-child(even) td {
            background-color: #fbfdff;
        }

        .ops-detail-page table tbody tr:hover td {
            background-color: #eef4ff !important;
        }

        .ops-detail-page .logs-table thead th,
        .ops-detail-page [id$="ActivityLogModal"] thead th {
            background: linear-gradient(135deg, #0f3b8a 0%, #1d4ed8 100%) !important;
            color: #ffffff !important;
        }

        .ops-detail-page .logs-table tbody td,
        .ops-detail-page [id$="ActivityLogModal"] tbody td {
            font-size: 12px;
        }

        .ops-detail-page input[type="file"],
        .ops-detail-page textarea,
        .ops-detail-page select {
            border: 1px solid #c7d5e8 !important;
            border-radius: 10px !important;
            background: #ffffff !important;
        }

        .ops-detail-page input[type="file"]:focus,
        .ops-detail-page textarea:focus,
        .ops-detail-page select:focus {
            outline: none;
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
        }

        .dashboard-file-input {
            width: 100%;
            padding: 6px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 12px;
            color: #111827;
            background: #f9fafb;
            cursor: pointer;
        }

        .dashboard-file-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.14);
        }

        .dashboard-file-input::file-selector-button,
        .dashboard-file-input::-webkit-file-upload-button {
            margin-right: 12px;
            padding: 9px 14px;
            border: 1px solid #1d4ed8;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
            background: linear-gradient(135deg, #0f3b8a 0%, #2563eb 100%);
            cursor: pointer;
            transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.18);
        }

        .dashboard-file-input::file-selector-button:hover,
        .dashboard-file-input::-webkit-file-upload-button:hover {
            background: linear-gradient(135deg, #0b2f6c 0%, #1d4ed8 100%);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.24);
        }

        .ops-detail-page button,
        .ops-detail-page a {
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }

        .ops-detail-page button:hover,
        .ops-detail-page a:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.14);
        }

        .ops-detail-page button[style*="background-color: #002C76"],
        .ops-detail-page a[style*="background-color: #002C76"] {
            background: linear-gradient(135deg, #0f3b8a 0%, #1d4ed8 100%) !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
        }

        .ops-detail-page .lpmc-accordion-toggle,
        .ops-detail-page .road-maintenance-accordion-toggle,
        .ops-detail-page .rbis-accordion-toggle {
            border-radius: 10px !important;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.16);
        }

        .ops-detail-page #activityLogsFab,
        .ops-detail-page #lpmcActivityLogFab,
        .ops-detail-page #roadMaintenanceActivityLogFab,
        .ops-detail-page #rbisActivityLogFab {
            right: 20px !important;
            bottom: 20px !important;
            box-shadow: 0 10px 24px rgba(0, 44, 118, 0.35) !important;
        }

        .ops-detail-page #logsModal,
        .ops-detail-page #lpmcActivityLogBackdrop,
        .ops-detail-page #roadMaintenanceActivityLogBackdrop,
        .ops-detail-page #rbisActivityLogBackdrop {
            background: rgba(15, 23, 42, 0.55) !important;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        .ops-detail-page #logsModal .modal-content,
        .ops-detail-page #lpmcActivityLogModal,
        .ops-detail-page #roadMaintenanceActivityLogModal,
        .ops-detail-page #rbisActivityLogModal {
            border: 1px solid #dbe4f0 !important;
            border-radius: 14px !important;
            background: #ffffff !important;
        }

        @media (max-width: 768px) {
            .ops-detail-page .content-header {
                padding: 14px 16px;
            }

            .ops-detail-page .content-header > div {
                width: 100%;
            }

            .ops-detail-page .content-header > div:last-child {
                margin-left: 0;
            }

            .ops-detail-page .content-header a,
            .ops-detail-page .content-header button {
                width: 100%;
                justify-content: center;
            }

            .ops-detail-page div[style*="padding: 30px"] {
                padding: 18px !important;
            }
        }

        @media (max-width: 640px) {
            .ops-detail-page #activityLogsFab span,
            .ops-detail-page #lpmcActivityLogFab span,
            .ops-detail-page #roadMaintenanceActivityLogFab span,
            .ops-detail-page #rbisActivityLogFab span {
                display: none;
            }

            .ops-detail-page #activityLogsFab,
            .ops-detail-page #lpmcActivityLogFab,
            .ops-detail-page #roadMaintenanceActivityLogFab,
            .ops-detail-page #rbisActivityLogFab {
                width: 52px;
                height: 52px;
                padding: 0 !important;
                border-radius: 50% !important;
                display: inline-flex;
                justify-content: center;
            }
        }

        .page-loader-overlay {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: transparent;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.18s ease, visibility 0.18s ease;
            z-index: 2200;
            will-change: opacity;
        }

        .page-loader-overlay.is-visible {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .page-loader-spinner {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: 4px solid rgba(29, 78, 216, 0.15);
            border-top-color: #1d4ed8;
            border-right-color: #60a5fa;
            animation: pageLoaderSpin 0.8s linear infinite;
            position: relative;
        }

        .page-loader-spinner::after {
            content: '';
            position: absolute;
            inset: 10px;
            border-radius: 50%;
            border: 3px solid rgba(14, 165, 233, 0.12);
            border-bottom-color: #0284c7;
            animation: pageLoaderSpin 1.1s linear infinite reverse;
        }

        body.page-loading {
            cursor: progress;
        }

        @keyframes pageLoaderSpin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .page-loader-overlay {
                transition: none;
            }

            .page-loader-spinner,
            .page-loader-spinner::after {
                animation: none;
            }
        }

        @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
            .page-loader-overlay {
                background: rgba(15, 23, 42, 0.08);
            }
        }

    </style>
    
    @yield('styles')
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="sidebar-brand-link" aria-label="Go to dashboard">
                <img src="{{ asset('PRISM2.png') }}" alt="PRISM" class="sidebar-logo sidebar-logo--expanded">
                <img src="{{ asset('DILG-Logo.png') }}" alt="DILG Logo" class="sidebar-logo sidebar-logo--collapsed">
            </a>
        </div>
        
        @php
            $authUserId = (int) Auth::id();
            $messageSchemaAvailable = \Illuminate\Support\Facades\Schema::hasTable('tbusers')
                && \Illuminate\Support\Facades\Schema::hasTable('user_messages')
                && \Illuminate\Support\Facades\Schema::hasTable('message_threads')
                && \Illuminate\Support\Facades\Schema::hasTable('message_thread_members')
                && \Illuminate\Support\Facades\Schema::hasColumn('user_messages', 'thread_id');
            $supportsManualMessageUnread = $messageSchemaAvailable
                && \Illuminate\Support\Facades\Schema::hasColumn('message_thread_members', 'manual_unread_at');
            $unreadMessageThreads = 0;
            $recentMessageThreads = collect();
            $messageUnreadBadgeText = '';

            if ($messageSchemaAvailable && $authUserId > 0) {
                $messagePreviewText = function ($message, $imagePath, int $limit = 72) {
                    $text = trim((string) $message);
                    if ($text !== '') {
                        return \Illuminate\Support\Str::limit($text, $limit);
                    }

                    return !empty($imagePath) ? 'Sent a photo' : 'No message preview available.';
                };

                $formatMessageTime = function ($timestamp) {
                    if (empty($timestamp)) {
                        return '';
                    }

                    try {
                        return \Illuminate\Support\Carbon::parse($timestamp)->format('M d, Y h:i A');
                    } catch (\Throwable $error) {
                        return (string) $timestamp;
                    }
                };

                $visibleThreadIds = \Illuminate\Support\Facades\DB::table('user_messages')
                    ->where('recipient_id', $authUserId)
                    ->whereNotNull('thread_id')
                    ->distinct()
                    ->pluck('thread_id')
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->values();

                if ($visibleThreadIds->isNotEmpty()) {
                    $actualUnreadThreadIds = \Illuminate\Support\Facades\DB::table('user_messages')
                        ->where('recipient_id', $authUserId)
                        ->where('sender_id', '!=', $authUserId)
                        ->whereNull('read_at')
                        ->whereIn('thread_id', $visibleThreadIds)
                        ->distinct()
                        ->pluck('thread_id')
                        ->map(fn ($id) => (int) $id)
                        ->values();

                    $manualUnreadThreadIds = collect();
                    if ($supportsManualMessageUnread) {
                        $manualUnreadThreadIds = \Illuminate\Support\Facades\DB::table('message_thread_members')
                            ->where('user_id', $authUserId)
                            ->whereNotNull('manual_unread_at')
                            ->whereIn('thread_id', $visibleThreadIds)
                            ->pluck('thread_id')
                            ->map(fn ($id) => (int) $id)
                            ->values();
                    }

                    $unreadMessageThreads = $actualUnreadThreadIds
                        ->merge($manualUnreadThreadIds)
                        ->unique()
                        ->count();

                    $latestByThread = \Illuminate\Support\Facades\DB::table('user_messages')
                        ->selectRaw('thread_id, MAX(created_at) as latest_created_at, MAX(id) as latest_message_id')
                        ->where('recipient_id', $authUserId)
                        ->whereIn('thread_id', $visibleThreadIds)
                        ->whereNotNull('thread_id')
                        ->groupBy('thread_id');

                    $threadRowsQuery = \Illuminate\Support\Facades\DB::table('message_thread_members as member')
                        ->join('message_threads as thread', 'thread.id', '=', 'member.thread_id')
                        ->joinSub($latestByThread, 'latest', function ($join) {
                            $join->on('latest.thread_id', '=', 'thread.id');
                        })
                        ->where('member.user_id', $authUserId)
                        ->select([
                            'thread.id as thread_id',
                            'thread.name as thread_name',
                            'thread.is_group',
                            'latest.latest_created_at',
                            'latest.latest_message_id',
                        ]);

                    if ($supportsManualMessageUnread) {
                        $threadRowsQuery->addSelect('member.manual_unread_at');
                    }

                    $threadRows = $threadRowsQuery
                        ->orderByDesc('latest.latest_created_at')
                        ->orderByDesc('latest.latest_message_id')
                        ->limit(8)
                        ->get();

                    $threadIds = $threadRows->pluck('thread_id')
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->values();
                    $latestIds = $threadRows->pluck('latest_message_id')
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->values();

                    if ($threadIds->isNotEmpty() && $latestIds->isNotEmpty()) {
                        $latestMessages = \Illuminate\Support\Facades\DB::table('user_messages')
                            ->whereIn('id', $latestIds)
                            ->get()
                            ->keyBy('id');

                        $threadMembersRaw = \Illuminate\Support\Facades\DB::table('message_thread_members as member')
                            ->join('tbusers as user', 'user.idno', '=', 'member.user_id')
                            ->whereIn('member.thread_id', $threadIds)
                            ->select([
                                'member.thread_id',
                                'user.idno',
                                'user.fname',
                                'user.lname',
                            ])
                            ->get();

                        $membersByThread = $threadMembersRaw->groupBy('thread_id');

                        $unreadByThread = \Illuminate\Support\Facades\DB::table('user_messages')
                            ->select('thread_id', \Illuminate\Support\Facades\DB::raw('COUNT(*) as unread_count'))
                            ->where('recipient_id', $authUserId)
                            ->where('sender_id', '!=', $authUserId)
                            ->whereNull('read_at')
                            ->whereIn('thread_id', $threadIds)
                            ->whereNotNull('thread_id')
                            ->groupBy('thread_id')
                            ->pluck('unread_count', 'thread_id');

                        $recentMessageThreads = $threadRows->map(function ($row) use ($authUserId, $latestMessages, $membersByThread, $unreadByThread, $supportsManualMessageUnread, $messagePreviewText, $formatMessageTime) {
                            $threadId = (int) ($row->thread_id ?? 0);
                            $latest = $latestMessages->get((int) ($row->latest_message_id ?? 0));
                            if ($threadId <= 0 || !$latest) {
                                return null;
                            }

                            $members = collect($membersByThread->get($threadId, collect()));
                            $counterparts = $members
                                ->filter(fn ($member) => (int) ($member->idno ?? 0) !== $authUserId)
                                ->values();
                            if ($counterparts->isEmpty()) {
                                return null;
                            }

                            $isGroup = (bool) ($row->is_group ?? false) || $counterparts->count() > 1;
                            $name = trim((string) ($row->thread_name ?? ''));

                            if ($isGroup) {
                                $names = $counterparts
                                    ->map(function ($member) {
                                        $memberName = trim((string) (($member->fname ?? '') . ' ' . ($member->lname ?? '')));
                                        return $memberName !== '' ? $memberName : 'Unknown User';
                                    })
                                    ->sortBy(fn ($memberName) => strtolower((string) $memberName))
                                    ->values();

                                if ($name === '') {
                                    $name = $names->take(2)->implode(', ');
                                    $remaining = max(0, $names->count() - 2);
                                    if ($remaining > 0) {
                                        $name .= ' +' . $remaining;
                                    }
                                }

                                if ($name === '') {
                                    $name = 'Group chat';
                                }
                            } else {
                                $counterpart = $counterparts->first();
                                $name = trim((string) (($counterpart->fname ?? '') . ' ' . ($counterpart->lname ?? '')));
                                $name = $name !== '' ? $name : 'Unknown User';
                            }

                            $actualUnreadCount = (int) ($unreadByThread[$threadId] ?? 0);
                            $manualUnread = $supportsManualMessageUnread && !empty($row->manual_unread_at);
                            $threadUnreadCount = $actualUnreadCount > 0 ? $actualUnreadCount : ($manualUnread ? 1 : 0);

                            return (object) [
                                'thread_id' => $threadId,
                                'name' => $name,
                                'preview' => $messagePreviewText($latest->message ?? '', $latest->image_path ?? null),
                                'time' => $formatMessageTime($latest->created_at ?? null),
                                'unread' => $threadUnreadCount,
                            ];
                        })->filter()->values();
                    }
                }
            }

            $messageUnreadBadgeText = $unreadMessageThreads > 99 ? '99+' : (string) $unreadMessageThreads;
        @endphp

        <ul class="sidebar-menu">
            <li>
                @php
                    $dashboardTabRouteActive = request()->routeIs('projects.rlip-lime.dashboard')
                        || request()->routeIs('projects.sglgif');
                    $dashboardMenuActive = Route::currentRouteName() == 'dashboard' || $dashboardTabRouteActive;
                @endphp
                <a href="{{ route('dashboard') }}" class="@if($dashboardMenuActive) active @endif">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            @php
                $canViewLocallyFundedProjects = Auth::user()->hasCrudPermission('locally_funded_projects', 'view');
                $canViewRssaProjects = $canViewLocallyFundedProjects;
                $canViewRlipLimeProjects = Auth::user()->hasCrudPermission('rlip_lime_projects', 'view');
                $canViewProjectAtRiskProjects = Auth::user()->hasCrudPermission('project_at_risk_projects', 'view');
                $canViewSglgifPortal = Auth::user()->hasCrudPermission('sglgif_portal', 'view');
                $canViewPreImplementationDocuments = Auth::user()->hasCrudPermission('pre_implementation_documents', 'view');
                $canViewRbisAnnualCertification = Auth::user()->hasCrudPermission('rbis_annual_certification', 'view');
                $canViewPdNoPbbmMonthlyReports = Auth::user()->hasCrudPermission('pd_no_pbbm_monthly_reports', 'view');
                $canViewSwaAnnexFMonthlyReports = Auth::user()->hasCrudPermission('swa_annex_f_monthly_reports', 'view');
                $canViewFundUtilizationReports = Auth::user()->hasCrudPermission('fund_utilization_reports', 'view');
                $canViewLpmcReports = Auth::user()->hasCrudPermission('local_project_monitoring_committee', 'view');
                $canViewRoadMaintenanceReports = Auth::user()->hasCrudPermission('road_maintenance_status_reports', 'view');
                $canViewTicketingSystem = Auth::user()->hasCrudPermission('ticketing_system', 'view');
                $canViewSubaybayanUploads = Auth::user()->hasCrudPermission('subaybayan_data_uploads', 'view');
                $canViewRssaLgsfUploads = $canViewSubaybayanUploads;
                $canViewRlipLimeUploads = Auth::user()->hasCrudPermission('rlip_lime_data_uploads', 'view');
                $canViewRssaUploads = $canViewRssaLgsfUploads || $canViewRlipLimeUploads;
                $canViewProjectAtRiskUploads = Auth::user()->hasCrudPermission('project_at_risk_data_uploads', 'view');
                $canViewSglgifUploads = Auth::user()->hasCrudPermission('sglgif_data_uploads', 'view');
                $canViewUtilitiesSystemSetup = Auth::user()->hasCrudPermission('utilities_system_setup', 'view');
                $canViewUtilitiesNotifications = Auth::user()->hasCrudPermission('utilities_bulk_notifications', 'view');
                $canViewUtilitiesDeadlines = Auth::user()->hasCrudPermission('utilities_deadlines_configuration', 'view');
                $canViewUtilitiesLocation = Auth::user()->hasCrudPermission('utilities_location_configuration', 'view');
                $canViewUtilitiesBackup = Auth::user()->hasCrudPermission('utilities_backup_restore', 'view');
                $hasAnyProjectMonitoringAccess = $canViewLocallyFundedProjects
                    || $canViewRlipLimeProjects
                    || $canViewProjectAtRiskProjects
                    || $canViewSglgifPortal;
                $hasAnyReportorialAccess = $canViewRbisAnnualCertification
                    || $canViewPdNoPbbmMonthlyReports
                    || $canViewSwaAnnexFMonthlyReports
                    || $canViewFundUtilizationReports
                    || $canViewLpmcReports
                    || $canViewRoadMaintenanceReports;
                $hasAnyUtilitiesAccess = $canViewUtilitiesSystemSetup
                    || $canViewUtilitiesNotifications
                    || $canViewUtilitiesDeadlines
                    || $canViewUtilitiesLocation
                    || $canViewUtilitiesBackup
                    || Auth::user()->isSuperAdmin();
            @endphp
            @if($hasAnyProjectMonitoringAccess)
                <li>
                    @php
                        $projectsMenuActive = (
                            request()->routeIs('projects.*')
                            && !request()->routeIs('projects.rssa')
                            && !$dashboardTabRouteActive
                        ) || request()->routeIs('projects.at-risk');
                    @endphp
                    <a href="#" class="@if($projectsMenuActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'projectsMenu')">
                        <i class="fas fa-project-diagram"></i>
                        <span>Project Monitoring</span>
                        <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 12px;"></i>
                    </a>
                    <ul id="projectsMenu" class="submenu" style="display: {{ $projectsMenuActive ? 'block' : 'none' }};">
                        @if($canViewLocallyFundedProjects)
                            <li>
                                <a href="{{ route('projects.locally-funded') }}" class="@if(Route::currentRouteName() == 'projects.locally-funded') active @endif">
                                    <i class="fas fa-hand-holding-usd"></i>
                                    <span>Locally Funded Projects</span>
                                </a>
                            </li>
                        @endif
                        @if($canViewRlipLimeProjects)
                            <li>
                                <a href="{{ route('projects.rlip-lime') }}" class="@if(request()->routeIs('projects.rlip-lime*') && !$dashboardTabRouteActive) active @endif">
                                    <i class="fas fa-leaf"></i>
                                    <span>RLIP/LIME-20% Development Fund</span>
                                </a>
                            </li>
                        @endif
                        @if($canViewProjectAtRiskProjects)
                            <li>
                                <a href="{{ url('/project-at-risk') }}" class="@if(Route::currentRouteName() == 'projects.at-risk') active @endif">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>Project At Risk</span>
                                </a>
                            </li>
                        @endif
                        @if($canViewSglgifPortal)
                            <li>
                                <a href="{{ route('projects.sglgif.table') }}" class="@if(request()->routeIs('projects.sglgif.table')) active @endif">
                                    <i class="fas fa-award"></i>
                                    <span>SGLGIF Portal</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if($canViewRssaProjects || $canViewRlipLimeProjects)
                <li>
                    @php
                        $rssaProjectsMenuActive = request()->routeIs('projects.rssa')
                            || request()->routeIs('projects.rlip-lime.dashboard');
                    @endphp
                    <a href="#" class="@if($rssaProjectsMenuActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'rssaProjectsMenu')">
                        <i class="fas fa-list-check"></i>
                        <span>Rapid Subproject Sustainability Assessment</span>
                        <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 11px;"></i>
                    </a>
                    <ul id="rssaProjectsMenu" class="submenu" style="display: {{ $rssaProjectsMenuActive ? 'block' : 'none' }};">
                        @if($canViewRssaProjects)
                            <li>
                                <a href="{{ route('projects.rssa') }}" class="@if(request()->routeIs('projects.rssa')) active @endif">
                                    <i class="fas fa-hand-holding-usd"></i>
                                    <span>Locally Funded Projects</span>
                                </a>
                            </li>
                        @endif
                        @if($canViewRlipLimeProjects)
                            <li>
                                <a href="{{ route('projects.rlip-lime.dashboard') }}" class="@if(request()->routeIs('projects.rlip-lime.dashboard')) active @endif">
                                    <i class="fas fa-leaf"></i>
                                    <span>LIME-20% Development Fund</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if($hasAnyReportorialAccess)
            <li>
                @php
                    $reportsAnnualActive = request()->routeIs('rbis-annual-certification.*');
                    $reportsAnnualRpmesActive = false;
                    $reportsQuarterlyRpmesActive = request()->routeIs('reports.quarterly.rpmes.form-2*');
                    $reportsQuarterlyActive = request()->routeIs('fund-utilization.*')
                        || request()->routeIs('local-project-monitoring-committee.*')
                        || request()->routeIs('road-maintenance-status.*')
                        || $reportsQuarterlyRpmesActive;
                    $reportsSwaAnnexFActive = request()->routeIs('reports.monthly.swa-annex-f*');
                    $reportsMonthlyReportActive = request()->routeIs('reports.monthly.pd-no-pbbm-2025-1572-1573*');
                    $reportsMonthlyRpmesActive = false;
                    $reportsMonthlyActive = $reportsMonthlyReportActive || $reportsSwaAnnexFActive;
                    $reportsMenuActive = Route::currentRouteName() == 'reports'
                        || $reportsAnnualActive
                        || $reportsQuarterlyActive
                        || $reportsMonthlyActive;
                @endphp
                <a href="#" class="@if($reportsMenuActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'reportsMenu')">
                    <i class="fas fa-file-alt"></i>
                    <span>LGU Reportorial Requirements</span>
                    <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 12px;"></i>
                </a>
                <ul id="reportsMenu" class="submenu" style="display: {{ $reportsMenuActive ? 'block' : 'none' }};">
                    @if($canViewRbisAnnualCertification)
                        <li>
                            <a href="#" class="@if($reportsAnnualActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'reportsAnnualMenu')">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Annual</span>
                                <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 11px;"></i>
                            </a>
                            <ul id="reportsAnnualMenu" class="submenu" style="display: {{ $reportsAnnualActive ? 'block' : 'none' }};">
                                <li>
                                    <a href="{{ route('rbis-annual-certification.index') }}" class="@if(request()->routeIs('rbis-annual-certification.*')) active @endif">
                                        <i class="fas fa-bridge"></i>
                                        <span>RBIS Annual Certification</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="@if($reportsAnnualRpmesActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'reportsAnnualRpmesMenu')">
                                        <i class="fas fa-file-alt"></i>
                                        <span>RPMES FORM</span>
                                        <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 11px;"></i>
                                    </a>
                                    <ul id="reportsAnnualRpmesMenu" class="submenu" style="display: {{ $reportsAnnualRpmesActive ? 'block' : 'none' }};">
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    @endif
                    @if($canViewFundUtilizationReports || $canViewLpmcReports || $canViewRoadMaintenanceReports)
                        <li>
                            <a href="#" class="@if($reportsQuarterlyActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'reportsQuarterlyMenu')">
                                <i class="fas fa-calendar-check"></i>
                                <span>Quarterly</span>
                                <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 11px;"></i>
                            </a>
                            <ul id="reportsQuarterlyMenu" class="submenu" style="display: {{ $reportsQuarterlyActive ? 'block' : 'none' }};">
                                @if($canViewFundUtilizationReports)
                                    <li>
                                        <a href="{{ route('fund-utilization.index') }}" class="@if(request()->routeIs('fund-utilization.*')) active @endif">
                                            <i class="fas fa-coins"></i>
                                            <span>Fund Utilization Report</span>
                                        </a>
                                    </li>
                                @endif
                                @if($canViewLpmcReports)
                                    <li>
                                        <a href="{{ route('local-project-monitoring-committee.index') }}" class="@if(request()->routeIs('local-project-monitoring-committee.*')) active @endif">
                                            <i class="fas fa-users-cog"></i>
                                            <span>Local Project Monitoring Committee</span>
                                        </a>
                                    </li>
                                @endif
                                @if($canViewRoadMaintenanceReports)
                                    <li>
                                        <a href="{{ route('road-maintenance-status.index') }}" class="@if(request()->routeIs('road-maintenance-status.*')) active @endif">
                                            <i class="fas fa-road"></i>
                                            <span>Road Maintenance Status Report</span>
                                        </a>
                                    </li>
                                @endif
                                <li>
                                    <a href="#" class="@if($reportsQuarterlyRpmesActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'reportsQuarterlyRpmesMenu')">
                                        <i class="fas fa-file-alt"></i>
                                        <span>RPMES FORM</span>
                                        <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 11px;"></i>
                                    </a>
                                    <ul id="reportsQuarterlyRpmesMenu" class="submenu" style="display: {{ $reportsQuarterlyRpmesActive ? 'block' : 'none' }};">
                                        <li>
                                            <a href="{{ route('reports.quarterly.rpmes.form-2') }}" class="@if(request()->routeIs('reports.quarterly.rpmes.form-2*')) active @endif">
                                                <i class="fas fa-file-signature"></i>
                                                <span>RPMES FORM 2</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    @endif
                    @if($canViewPdNoPbbmMonthlyReports || $canViewSwaAnnexFMonthlyReports)
                        <li>
                            <a href="#" class="@if($reportsMonthlyActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'reportsMonthlyMenu')">
                                <i class="fas fa-calendar-day"></i>
                                <span>Monthly</span>
                                <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 11px;"></i>
                            </a>
                            <ul id="reportsMonthlyMenu" class="submenu" style="display: {{ $reportsMonthlyActive ? 'block' : 'none' }};">
                                @if($canViewPdNoPbbmMonthlyReports)
                                    <li>
                                        <a href="{{ route('reports.monthly.pd-no-pbbm-2025-1572-1573') }}" class="@if($reportsMonthlyReportActive) active @endif">
                                            <i class="fas fa-file-alt"></i>
                                            <span>Report on PD No. PBBM-2025-1572-1573</span>
                                        </a>
                                    </li>
                                @endif
                                @if($canViewSwaAnnexFMonthlyReports)
                                    <li>
                                        <a href="#" class="@if($reportsSwaAnnexFActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'reportsSglgifMenu')">
                                            <i class="fas fa-award"></i>
                                            <span>SGLGIF</span>
                                            <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 11px;"></i>
                                        </a>
                                        <ul id="reportsSglgifMenu" class="submenu" style="display: {{ $reportsSwaAnnexFActive ? 'block' : 'none' }};">
                                            <li>
                                                <a href="{{ route('reports.monthly.swa-annex-f') }}" class="@if($reportsSwaAnnexFActive) active @endif">
                                                    <i class="fas fa-table"></i>
                                                    <span>SWA- Annex F</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif
                                <li>
                                    <a href="#" class="@if($reportsMonthlyRpmesActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'reportsMonthlyRpmesMenu')">
                                        <i class="fas fa-file-alt"></i>
                                        <span>RPMES FORM</span>
                                        <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 11px;"></i>
                                    </a>
                                    <ul id="reportsMonthlyRpmesMenu" class="submenu" style="display: {{ $reportsMonthlyRpmesActive ? 'block' : 'none' }};">
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    @endif
                </ul>
            </li>
            @endif
            @if($canViewPreImplementationDocuments)
                <li>
                    <a href="{{ route('pre-implementation-documents.index') }}" class="@if(request()->routeIs('pre-implementation-documents.*')) active @endif">
                        <i class="fas fa-folder-open"></i>
                        <span>Pre-Implementation Documents</span>
                    </a>
                </li>
            @endif
            @if($canViewTicketingSystem)
                <li>
                    @php
                        $ticketingMenuActive = request()->routeIs('ticketing.*');
                    @endphp
                    <a href="#" class="@if($ticketingMenuActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'ticketingMenu')">
                        <i class="fas fa-ticket"></i>
                        <span>Ticketing System</span>
                        <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 12px;"></i>
                    </a>
                    <ul id="ticketingMenu" class="submenu" style="display: {{ $ticketingMenuActive ? 'block' : 'none' }};">
                        <li>
                            <a href="{{ route('ticketing.dashboard') }}" class="@if(request()->routeIs('ticketing.dashboard')) active @endif">
                                <i class="fas fa-chart-pie"></i>
                                <span>Ticket Dashboard</span>
                            </a>
                        </li>
                        @if(Auth::user()->isLguUser())
                            <li>
                                <a href="{{ route('ticketing.create') }}" class="@if(request()->routeIs('ticketing.create')) active @endif">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Submit Ticket</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('ticketing.my-tickets') }}" class="@if(request()->routeIs('ticketing.my-tickets')) active @endif">
                                    <i class="fas fa-list"></i>
                                    <span>View My Tickets</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('ticketing.track') }}" class="@if(request()->routeIs('ticketing.track')) active @endif">
                                    <i class="fas fa-route"></i>
                                    <span>Track Ticket Status</span>
                                </a>
                            </li>
                        @endif
                        @if(Auth::user()->isProvincialUser())
                            <li>
                                <a href="{{ route('ticketing.province.index') }}" class="@if(request()->routeIs('ticketing.province.*')) active @endif">
                                    <i class="fas fa-building"></i>
                                    <span>Provincial Ticket List</span>
                                </a>
                            </li>
                        @endif
                        @if(Auth::user()->isRegionalUser())
                            <li>
                                <a href="{{ route('ticketing.region.index') }}" class="@if(request()->routeIs('ticketing.region.*')) active @endif">
                                    <i class="fas fa-building-circle-check"></i>
                                    <span>Regional Ticket List</span>
                                </a>
                            </li>
                        @endif
                        @if(Auth::user()->isSuperAdmin())
                            <li>
                                <a href="{{ route('ticketing.admin.index') }}" class="@if(request()->routeIs('ticketing.admin.*')) active @endif">
                                    <i class="fas fa-shield-halved"></i>
                                    <span>Admin Monitoring</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            <li>
                <a href="{{ route('messages.index') }}" class="sidebar-float-hover @if(request()->routeIs('messages.*')) active @endif">
                    <i class="fas fa-envelope-open-text"></i>
                    <span>Messages</span>
                    <span class="sidebar-menu-badge" data-message-unread-badge @if($unreadMessageThreads < 1) hidden @endif>{{ $messageUnreadBadgeText }}</span>
                </a>
            </li>
            @php
                $isRegionalDilg = strtoupper(trim((string) (Auth::user()->agency ?? ''))) === 'DILG'
                    && strtolower(trim((string) (Auth::user()->province ?? ''))) === 'regional office';
                $hasAnySystemManagementAccess = $isRegionalDilg && (
                    $canViewSubaybayanUploads
                    || $canViewRlipLimeUploads
                    || $canViewProjectAtRiskUploads
                    || $canViewSglgifUploads
                );
            @endphp
            @if($hasAnySystemManagementAccess)
                <li>
                    @php
                        $systemManagementActive = request()->routeIs('system-management.*');
                    @endphp
                    <a href="#" class="@if($systemManagementActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'systemManagementMenu')">
                        <i class="fas fa-cogs"></i>
                        <span>Data Management</span>
                        <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 12px;"></i>
                    </a>
                    <ul id="systemManagementMenu" class="submenu" style="display: {{ $systemManagementActive ? 'block' : 'none' }};">
                        @php
                            $rssaUploadsMenuActive = request()->routeIs('system-management.upload-rssa*')
                                || request()->routeIs('system-management.upload-rlip-lime*');
                        @endphp
                        @if($canViewSubaybayanUploads)
                            <li>
                                <a href="{{ route('system-management.upload-subaybayan') }}" class="@if(Route::currentRouteName() == 'system-management.upload-subaybayan') active @endif">
                                    <i class="fas fa-upload"></i>
                                    <span>Upload LFP Data</span>
                                </a>
                            </li>
                        @endif
                        @if($canViewRssaUploads)
                            <li>
                                <a href="#" class="@if($rssaUploadsMenuActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'rssaUploadsMenu')">
                                    <i class="fas fa-list-check"></i>
                                    <span>Upload RSSA Data</span>
                                    <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 11px;"></i>
                                </a>
                                <ul id="rssaUploadsMenu" class="submenu" style="display: {{ $rssaUploadsMenuActive ? 'block' : 'none' }};">
                                    @if($canViewRssaLgsfUploads)
                                        <li>
                                            <a href="{{ route('system-management.upload-rssa') }}" class="@if(request()->routeIs('system-management.upload-rssa*')) active @endif">
                                                <i class="fas fa-upload"></i>
                                                <span>LGSF Data</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if($canViewRlipLimeUploads)
                                        <li>
                                            <a href="{{ route('system-management.upload-rlip-lime') }}" class="@if(request()->routeIs('system-management.upload-rlip-lime*')) active @endif">
                                                <i class="fas fa-file-import"></i>
                                                <span>RLIP/LIME20 Data</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        @if($canViewProjectAtRiskUploads)
                            <li>
                                <a href="{{ route('system-management.upload-project-at-risk') }}" class="@if(request()->routeIs('system-management.upload-project-at-risk*')) active @endif">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>Upload Project-at-Risk</span>
                                </a>
                            </li>
                        @endif
                        @if($canViewSglgifUploads)
                            <li>
                                <a href="{{ route('system-management.upload-sglgif') }}" class="@if(Route::currentRouteName() == 'system-management.upload-sglgif') active @endif">
                                    <i class="fas fa-award"></i>
                                    <span>Upload SGLGIF Data</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if(Auth::user()->isSuperAdmin())
            <li>
                <a href="{{ route('users.index') }}" class="@if(Route::currentRouteName() == 'users.index') active @endif">
                    <i class="fas fa-user-shield"></i>
                    <span>User Management</span>
                </a>
            </li>
            @endif
            @if($canViewUtilitiesSystemSetup)
                <li>
                @php
                    $utilitiesMenuActive = request()->routeIs('utilities.*');
                @endphp
                <a href="#" class="@if($utilitiesMenuActive) active @endif submenu-toggle" onclick="toggleSubmenu(event, 'utilitiesMenu')">
                    <i class="fas fa-toolbox"></i>
                    <span>Utilities</span>
                    <i class="fas fa-chevron-down submenu-chevron" style="margin-left: auto; font-size: 12px;"></i>
                </a>
                <ul id="utilitiesMenu" class="submenu" style="display: {{ $utilitiesMenuActive ? 'block' : 'none' }};">
                    @if($canViewUtilitiesSystemSetup)
                        <li>
                            <a href="{{ route('utilities.system-setup.index') }}" class="@if(request()->routeIs('utilities.system-setup.*')) active @endif">
                                <i class="fas fa-sliders-h"></i>
                                <span>System Setup</span>
                            </a>
                        </li>
                    @endif
                </ul>
                </li>
            @endif
        </ul>
    </aside>
    
    <!-- Top Navigation Bar -->
        <div class="topbar" id="topbar">
            <div class="topbar-left">
                <button class="toggle-btn" id="toggleBtn" title="Toggle Sidebar">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        
        <div class="topbar-right">
            <div class="profile-dropdown">
                <div class="profile-icon" id="profileIcon" title="Profile">
                    <i class="fas fa-user"></i>
                </div>
                @php
                    $unreadNotificationQuery = \Illuminate\Support\Facades\DB::table('tbnotifications')
                        ->where('user_id', Auth::id())
                        ->whereNull('read_at');
                    $unreadNotifications = (clone $unreadNotificationQuery)->count();
                    $recentNotifications = \Illuminate\Support\Facades\DB::table('tbnotifications')
                        ->where('user_id', Auth::id())
                        ->orderByDesc('created_at')
                        ->limit(12)
                        ->get(['id', 'message', 'created_at', 'read_at']);
                @endphp
                <div class="notification-wrap">
                    <button
                        class="notification-bell"
                        id="notificationBell"
                        title="Notifications"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="notificationMenu"
                    >
                        <i class="fas fa-bell"></i>
                        @if($unreadNotifications > 0)
                            <span class="notification-badge">{{ $unreadNotifications }}</span>
                        @endif
                    </button>
                    <div class="notification-menu" id="notificationMenu">
                        <div class="notification-menu-header">
                            <span class="notification-menu-title">Notifications</span>
                        </div>
                        @if($recentNotifications->isEmpty())
                            <div class="notification-menu-empty">No notifications yet.</div>
                        @else
                            @foreach($recentNotifications as $notificationItem)
                                <a
                                    href="{{ route('notifications.read', ['id' => $notificationItem->id]) }}"
                                    class="notification-menu-item {{ is_null($notificationItem->read_at) ? 'unread' : '' }}"
                                >
                                    <div class="notification-menu-message-row">
                                        @if(is_null($notificationItem->read_at))
                                            <span class="notification-unread-dot" aria-label="Unread notification"></span>
                                        @endif
                                        <div class="notification-menu-message">{{ $notificationItem->message }}</div>
                                    </div>
                                    <div class="notification-menu-time">
                                        {{ \Illuminate\Support\Carbon::parse($notificationItem->created_at)->format('M d, Y h:i A') }}
                                    </div>
                                </a>
                            @endforeach
                        @endif
                        <div class="notification-menu-footer">
                            <div class="notification-menu-footer-actions">
                                @if($recentNotifications->isNotEmpty())
                                    <form method="POST" action="{{ route('notifications.clear') }}" class="notification-menu-action-form">
                                        @csrf
                                        <button type="submit" class="notification-menu-clear-action">
                                            <i class="fas fa-trash-alt"></i>
                                            <span>Clear Read</span>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('messages.index') }}" class="notification-menu-view-all">
                                    <i class="fas fa-envelope-open-text"></i>
                                    <span>Open Messages</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="notification-wrap">
                    <button
                        class="notification-bell"
                        id="messageBell"
                        title="Messages"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="messageMenu"
                    >
                        <i class="fas fa-comments"></i>
                        <span class="notification-badge" data-message-unread-badge @if($unreadMessageThreads < 1) hidden @endif>{{ $messageUnreadBadgeText }}</span>
                    </button>
                    <div class="notification-menu" id="messageMenu">
                        <div class="notification-menu-header">
                            <span class="notification-menu-title">Messages</span>
                        </div>
                        @if($recentMessageThreads->isEmpty())
                            <div class="notification-menu-empty">No messages yet.</div>
                        @else
                            @foreach($recentMessageThreads as $messageThread)
                                <a
                                    href="{{ route('messages.index', ['thread' => $messageThread->thread_id]) }}"
                                    class="notification-menu-item @if(($messageThread->unread ?? 0) > 0) unread @endif"
                                >
                                    <div class="notification-menu-message-row">
                                        @if(($messageThread->unread ?? 0) > 0)
                                            <span class="notification-unread-dot" aria-label="Unread message"></span>
                                        @endif
                                        <div style="min-width: 0;">
                                            <div class="notification-menu-message">{{ $messageThread->name }}</div>
                                            <div class="notification-menu-time">{{ $messageThread->preview }}</div>
                                        </div>
                                    </div>
                                    <div class="notification-menu-time">
                                        {{ $messageThread->time }}
                                    </div>
                                </a>
                            @endforeach
                        @endif
                        <div class="notification-menu-footer">
                            <a href="{{ route('messages.index') }}" class="notification-menu-view-all">
                                <i class="fas fa-envelope-open-text"></i>
                                <span>Open Messages</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="profile-menu" id="profileMenu">
                    <div class="profile-menu-header">
                        <div class="profile-menu-name">{{ Auth::user()->fname ?? 'User' }} {{ Auth::user()->lname ?? '' }}</div>
                        <div class="profile-menu-email">{{ Auth::user()->emailaddress ?? 'user@example.com' }}</div>
                        <div class="profile-menu-role">{{ Auth::user()->roleLabel() }}</div>
                    </div>
                    <a href="{{ route('profile.show') }}" class="profile-menu-item">
                        <i class="fas fa-user-circle"></i>
                        <span>My Profile</span>
                    </a>
                    <a href="{{ route('password.show') }}" class="profile-menu-item">
                        <i class="fas fa-lock"></i>
                        <span>Change Password</span>
                    </a>
                    <form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <button class="profile-menu-item logout" onclick="document.getElementById('logoutForm').submit();">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content Area -->
    <main class="main-content" id="mainContent">
        @yield('content')
    </main>

    <div id="globalPageLoader" class="page-loader-overlay" aria-hidden="true" role="status" aria-live="polite">
        <div class="page-loader-spinner" aria-hidden="true"></div>
    </div>

    @include('partials.confirmation-modal')

    <div id="globalErrorModal" class="system-dialog-modal" aria-hidden="true">
        <div class="system-dialog-backdrop" data-error-dismiss></div>
        <div class="system-dialog-card" role="dialog" aria-modal="true" aria-labelledby="globalErrorModalTitle">
            <div class="system-dialog-header">
                <h3 id="globalErrorModalTitle" class="system-dialog-title">System Error</h3>
            </div>
            <div class="system-dialog-body" id="globalErrorModalMessage">An unexpected error occurred.</div>
            <div class="system-dialog-actions">
                <button type="button" class="system-dialog-btn error-ok" id="globalErrorOkBtn">OK</button>
            </div>
        </div>
    </div>
    
    <script>
        // Sidebar Toggle
        const toggleBtn = document.getElementById('toggleBtn');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const topbar = document.getElementById('topbar');
        const body = document.body;
        
        // Check if sidebar should start collapsed (from localStorage)
        let sidebarExpanded = localStorage.getItem('sidebarExpanded') !== 'false';
        let mainContentShiftTimer = null;
        let mainContentShiftAnimation = null;
        let sidebarTransitionTimer = null;
        
        // Check if mobile
        function isMobile() {
            return window.innerWidth <= 768;
        }

        function animateMainContentShift(direction) {
            if (!mainContent || isMobile()) {
                return;
            }

            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            const animationClass = direction === 'expand'
                ? 'is-shifting-right'
                : 'is-shifting-left';

            mainContent.classList.remove('is-shifting-left', 'is-shifting-right');
            void mainContent.offsetWidth;
            mainContent.classList.add(animationClass);

            window.clearTimeout(mainContentShiftTimer);
            mainContentShiftTimer = window.setTimeout(() => {
                mainContent.classList.remove(animationClass);
            }, 360);

            if (typeof mainContent.animate === 'function') {
                if (mainContentShiftAnimation) {
                    mainContentShiftAnimation.cancel();
                }

                const fromX = direction === 'expand' ? -28 : 28;
                mainContentShiftAnimation = mainContent.animate(
                    [
                        { transform: `translateX(${fromX}px)`, opacity: 0.9 },
                        { transform: 'translateX(0)', opacity: 1 }
                    ],
                    {
                        duration: 360,
                        easing: 'cubic-bezier(0.2, 0.8, 0.2, 1)'
                    }
                );
            }
        }
        
        // Initialize sidebar state
        function updateSidebarState(options = {}) {
            const { animate = false } = options;
            const mobileView = isMobile();

            if (mainContent) {
                if (animate && !mobileView) {
                    mainContent.classList.add('sidebar-transition-enabled');
                    window.clearTimeout(sidebarTransitionTimer);
                    sidebarTransitionTimer = window.setTimeout(() => {
                        mainContent.classList.remove('sidebar-transition-enabled');
                    }, 320);
                } else {
                    mainContent.classList.remove('sidebar-transition-enabled');
                    window.clearTimeout(sidebarTransitionTimer);
                }
            }

            sidebar.classList.remove('collapsed', 'icon-collapsed');

            if (sidebarExpanded) {
                mainContent.classList.add('with-sidebar');
                topbar.classList.add('with-sidebar');
                
                if (mobileView) {
                    body.classList.add('sidebar-open');
                } else {
                    body.classList.remove('sidebar-open');
                }
            } else {
                mainContent.classList.remove('with-sidebar');
                topbar.classList.remove('with-sidebar');

                if (mobileView) {
                    sidebar.classList.add('collapsed');
                } else {
                    sidebar.classList.add('icon-collapsed');
                }

                body.classList.remove('sidebar-open');
            }

            if (animate && !mobileView) {
                animateMainContentShift(sidebarExpanded ? 'expand' : 'collapse');
            }

            localStorage.setItem('sidebarExpanded', sidebarExpanded);
        }
        
        // Initialize on page load
        updateSidebarState();
        
        // Toggle button click handler
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sidebarExpanded = !sidebarExpanded;
            updateSidebarState({ animate: true });
        });

        // Close sidebar when clicking on content area on mobile
        mainContent.addEventListener('click', function() {
            if (isMobile() && sidebarExpanded) {
                sidebarExpanded = false;
                updateSidebarState();
            }
        });

        // Recompute sidebar mode when viewport changes
        window.addEventListener('resize', function() {
            updateSidebarState();
        });
        
        // Profile Dropdown Toggle
        const profileIcon = document.getElementById('profileIcon');
        const profileMenu = document.getElementById('profileMenu');
        const notificationBell = document.getElementById('notificationBell');
        const notificationMenu = document.getElementById('notificationMenu');
        const messageBell = document.getElementById('messageBell');
        const messageMenu = document.getElementById('messageMenu');
        
        const SIDEBAR_SUBMENU_STORAGE_KEY = 'pdmuoms.sidebar.openSubmenus';
        const SIDEBAR_SUBMENU_COLLAPSE_ONCE_KEY = 'pdmuoms.sidebar.collapseSubmenusOnce';

        function findDirectSubmenu(listItem) {
            if (!listItem || !listItem.children) {
                return null;
            }

            return Array.from(listItem.children).find((child) => child.classList && child.classList.contains('submenu')) || null;
        }

        function getSubmenuToggle(submenuId) {
            if (!submenuId) {
                return null;
            }

            return document.querySelector(`.sidebar-menu a.submenu-toggle[data-submenu-id="${submenuId}"]`);
        }

        function isSubmenuOpen(submenu) {
            return !!submenu && window.getComputedStyle(submenu).display !== 'none';
        }

        function setSubmenuState(submenu, shouldOpen) {
            if (!submenu) {
                return;
            }

            submenu.style.display = shouldOpen ? 'block' : 'none';
            submenu.setAttribute('data-open', shouldOpen ? 'true' : 'false');

            const toggle = getSubmenuToggle(submenu.id);
            if (toggle) {
                toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            }
        }

        function closeSubmenuRecursively(submenu) {
            if (!submenu) {
                return;
            }

            const nestedSubmenus = submenu.querySelectorAll('.submenu');
            nestedSubmenus.forEach((nestedSubmenu) => {
                setSubmenuState(nestedSubmenu, false);
            });

            setSubmenuState(submenu, false);
        }

        function closeSiblingSubmenus(submenu) {
            if (!submenu) {
                return;
            }

            const submenuListItem = submenu.closest('li');
            if (!submenuListItem || !submenuListItem.parentElement) {
                return;
            }

            const siblingListItems = Array.from(submenuListItem.parentElement.children || []);
            siblingListItems.forEach((siblingListItem) => {
                if (siblingListItem === submenuListItem) {
                    return;
                }

                const siblingSubmenu = findDirectSubmenu(siblingListItem);
                if (siblingSubmenu) {
                    closeSubmenuRecursively(siblingSubmenu);
                }
            });
        }

        function openAncestorSubmenus(submenu) {
            if (!submenu) {
                return;
            }

            let currentSubmenu = submenu;
            while (currentSubmenu) {
                const parentListItem = currentSubmenu.parentElement ? currentSubmenu.parentElement.closest('li') : null;
                if (!parentListItem || !parentListItem.parentElement) {
                    break;
                }

                const parentSubmenu = parentListItem.parentElement.classList.contains('submenu')
                    ? parentListItem.parentElement
                    : null;

                if (!parentSubmenu) {
                    break;
                }

                setSubmenuState(parentSubmenu, true);
                currentSubmenu = parentSubmenu;
            }
        }

        function readStoredOpenSubmenus() {
            try {
                const raw = localStorage.getItem(SIDEBAR_SUBMENU_STORAGE_KEY);
                if (!raw) {
                    return new Set();
                }

                const ids = JSON.parse(raw);
                if (!Array.isArray(ids)) {
                    return new Set();
                }

                return new Set(ids.filter((id) => typeof id === 'string' && id !== ''));
            } catch (error) {
                return new Set();
            }
        }

        function saveOpenSubmenus() {
            try {
                const openSubmenuIds = Array.from(document.querySelectorAll('.sidebar-menu .submenu[id]'))
                    .filter((submenu) => isSubmenuOpen(submenu))
                    .map((submenu) => submenu.id);
                localStorage.setItem(SIDEBAR_SUBMENU_STORAGE_KEY, JSON.stringify(openSubmenuIds));
            } catch (error) {
                // Ignore storage errors.
            }
        }

        function consumeSidebarSubmenuCollapseOnce() {
            try {
                const shouldCollapse = localStorage.getItem(SIDEBAR_SUBMENU_COLLAPSE_ONCE_KEY) === 'true';

                if (shouldCollapse) {
                    localStorage.removeItem(SIDEBAR_SUBMENU_COLLAPSE_ONCE_KEY);
                }

                return shouldCollapse;
            } catch (error) {
                return false;
            }
        }

        function initializeSidebarSubmenus() {
            const submenuToggles = document.querySelectorAll('.sidebar-menu a.submenu-toggle[onclick*="toggleSubmenu"]');
            const storedOpenSubmenus = readStoredOpenSubmenus();
            const shouldForceCollapse = consumeSidebarSubmenuCollapseOnce();
            const hasActiveMenuSelection = !!document.querySelector('.sidebar-menu a.active');

            submenuToggles.forEach((submenuToggle) => {
                const onclickExpression = submenuToggle.getAttribute('onclick') || '';
                const match = onclickExpression.match(/toggleSubmenu\(event,\s*'([^']+)'\)/);
                if (!match || !match[1]) {
                    return;
                }

                const submenuId = match[1];
                submenuToggle.dataset.submenuId = submenuId;
                submenuToggle.setAttribute('aria-controls', submenuId);
                submenuToggle.setAttribute('aria-expanded', 'false');

                if (submenuToggle.dataset.keyToggleAttached !== '1') {
                    submenuToggle.dataset.keyToggleAttached = '1';
                    submenuToggle.addEventListener('keydown', function (keyboardEvent) {
                        if (keyboardEvent.key === 'Enter' || keyboardEvent.key === ' ') {
                            toggleSubmenu(keyboardEvent, submenuId);
                        }
                    });
                }
            });

            const allSubmenus = document.querySelectorAll('.sidebar-menu .submenu[id]');
            allSubmenus.forEach((submenu) => {
                if (shouldForceCollapse) {
                    setSubmenuState(submenu, false);
                    return;
                }

                const hasInlineOpenState = submenu.style.display === 'block';
                const hasActiveDescendant = !!submenu.querySelector('a.active');
                const hasStoredOpenState = storedOpenSubmenus.has(submenu.id);
                const shouldOpen = hasInlineOpenState
                    || hasActiveDescendant
                    || (!hasActiveMenuSelection && hasStoredOpenState);

                setSubmenuState(submenu, shouldOpen);
                if (shouldOpen) {
                    openAncestorSubmenus(submenu);
                }
            });

            // Keep only the active path expanded, including top-level menus.
            if (hasActiveMenuSelection && !shouldForceCollapse) {
                const activePathSubmenus = Array.from(allSubmenus).filter((submenu) => submenu.querySelector('a.active'));
                activePathSubmenus.forEach((submenu) => {
                    setSubmenuState(submenu, true);
                    openAncestorSubmenus(submenu);
                    closeSiblingSubmenus(submenu);
                });
            }

            saveOpenSubmenus();
        }

        // Toggle submenu function
        function toggleSubmenu(event, submenuId) {
            event.preventDefault();
            event.stopPropagation();

            if (!isMobile() && sidebar.classList.contains('icon-collapsed')) {
                sidebarExpanded = true;
                updateSidebarState();
            }

            const submenu = document.getElementById(submenuId);
            if (!submenu) {
                return;
            }

            const shouldOpen = !isSubmenuOpen(submenu);
            if (shouldOpen) {
                closeSiblingSubmenus(submenu);
                setSubmenuState(submenu, true);
                openAncestorSubmenus(submenu);
            } else {
                closeSubmenuRecursively(submenu);
            }

            saveOpenSubmenus();
        }

        initializeSidebarSubmenus();

        if (profileIcon && profileMenu) {
            profileIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                profileMenu.classList.toggle('show');
                if (notificationMenu) {
                    notificationMenu.classList.remove('show');
                }
                if (notificationBell) {
                    notificationBell.setAttribute('aria-expanded', 'false');
                }
                if (messageMenu) {
                    messageMenu.classList.remove('show');
                }
                if (messageBell) {
                    messageBell.setAttribute('aria-expanded', 'false');
                }
            });
        }

        if (notificationBell && notificationMenu) {
            notificationBell.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                notificationMenu.classList.toggle('show');
                this.setAttribute('aria-expanded', notificationMenu.classList.contains('show') ? 'true' : 'false');
                if (profileMenu) {
                    profileMenu.classList.remove('show');
                }
                if (messageMenu) {
                    messageMenu.classList.remove('show');
                }
                if (messageBell) {
                    messageBell.setAttribute('aria-expanded', 'false');
                }
            });
        }

        if (messageBell && messageMenu) {
            messageBell.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                messageMenu.classList.toggle('show');
                this.setAttribute('aria-expanded', messageMenu.classList.contains('show') ? 'true' : 'false');
                if (profileMenu) {
                    profileMenu.classList.remove('show');
                }
                if (notificationMenu) {
                    notificationMenu.classList.remove('show');
                }
                if (notificationBell) {
                    notificationBell.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // Close menus when clicking outside
        document.addEventListener('click', function(e) {
            if (profileMenu && profileIcon && !profileMenu.contains(e.target) && !profileIcon.contains(e.target)) {
                profileMenu.classList.remove('show');
            }
            if (notificationMenu && notificationBell && !notificationMenu.contains(e.target) && !notificationBell.contains(e.target)) {
                notificationMenu.classList.remove('show');
                notificationBell.setAttribute('aria-expanded', 'false');
            }
            if (messageMenu && messageBell && !messageMenu.contains(e.target) && !messageBell.contains(e.target)) {
                messageMenu.classList.remove('show');
                messageBell.setAttribute('aria-expanded', 'false');
            }
        });

        (function initializeGlobalMessageUnreadBadges() {
            const messagePollUrl = @json(route('messages.poll'));
            const messageUnreadBadges = Array.from(document.querySelectorAll('[data-message-unread-badge]'));

            if (!messagePollUrl || !messageUnreadBadges.length) {
                return;
            }

            let syncInProgress = false;
            const MESSAGE_POLL_MS = 15000;

            const renderUnreadCount = (value) => {
                const total = Math.max(0, Number(value || 0));
                const label = total > 99 ? '99+' : total.toLocaleString();

                messageUnreadBadges.forEach((badge) => {
                    badge.hidden = total <= 0;
                    badge.textContent = total > 0 ? label : '';
                });
            };

            const syncUnreadCount = async () => {
                if (syncInProgress || document.visibilityState === 'hidden') {
                    return;
                }

                syncInProgress = true;

                try {
                    const response = await fetch(messagePollUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    renderUnreadCount(payload.unread_count || 0);
                } catch (error) {
                    // Ignore transient polling failures.
                } finally {
                    syncInProgress = false;
                }
            };

            window.setInterval(syncUnreadCount, MESSAGE_POLL_MS);
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') {
                    syncUnreadCount();
                }
            });
        })();

        (function initializeSystemDialogs() {
            const confirmModal = document.getElementById('globalConfirmModal');
            const confirmMessage = document.getElementById('globalConfirmModalMessage');
            const confirmOkBtn = document.getElementById('globalConfirmOkBtn');
            const confirmCancelBtn = document.getElementById('globalConfirmCancelBtn');
            const confirmDismissTargets = document.querySelectorAll('[data-confirm-dismiss]');
            const errorModal = document.getElementById('globalErrorModal');
            const errorMessage = document.getElementById('globalErrorModalMessage');
            const errorOkBtn = document.getElementById('globalErrorOkBtn');
            const errorDismissTargets = document.querySelectorAll('[data-error-dismiss]');
            const nativeConfirm = window.confirm.bind(window);
            let nativeConfirmBypassCount = 0;
            let confirmCallback = null;
            let confirmCancelCallback = null;

            function openModal(modal) {
                if (!modal) return;
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('system-dialog-open');
            }

            function closeModal(modal) {
                if (!modal) return;
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                if (!document.querySelector('.system-dialog-modal.is-open')) {
                    document.body.classList.remove('system-dialog-open');
                }
            }

            function closeConfirmModal(runCancelCallback) {
                const shouldRunCancel = runCancelCallback === true;
                const pendingCancel = confirmCancelCallback;
                confirmCallback = null;
                confirmCancelCallback = null;
                closeModal(confirmModal);
                if (shouldRunCancel && pendingCancel) {
                    pendingCancel();
                }
            }

            window.openConfirmationModal = function(message, onConfirm, onCancel) {
                if (!confirmModal || !confirmMessage) return;
                if (confirmModal.classList.contains('is-open')) {
                    return;
                }
                confirmCallback = typeof onConfirm === 'function' ? onConfirm : null;
                confirmCancelCallback = typeof onCancel === 'function' ? onCancel : null;
                confirmMessage.textContent = message || 'Please confirm this action.';
                openModal(confirmModal);
                if (confirmOkBtn) {
                    confirmOkBtn.focus();
                }
            };

            window.showSystemErrorModal = function(message) {
                if (!errorModal || !errorMessage) return;
                errorMessage.textContent = message || 'An unexpected system error occurred. Please try again.';
                openModal(errorModal);
                if (errorOkBtn) {
                    errorOkBtn.focus();
                }
            };

            window.withNativeConfirmBypass = function(callback) {
                nativeConfirmBypassCount += 1;
                try {
                    return callback();
                } finally {
                    setTimeout(function() {
                        nativeConfirmBypassCount = Math.max(nativeConfirmBypassCount - 1, 0);
                    }, 0);
                }
            };

            window.confirm = function(message) {
                if (nativeConfirmBypassCount > 0) {
                    nativeConfirmBypassCount -= 1;
                    return true;
                }
                return nativeConfirm(message);
            };

            if (confirmOkBtn) {
                confirmOkBtn.addEventListener('click', function() {
                    const pending = confirmCallback;
                    closeConfirmModal(false);
                    if (pending) pending();
                });
            }

            if (confirmCancelBtn) {
                confirmCancelBtn.addEventListener('click', function() {
                    closeConfirmModal(true);
                });
            }

            confirmDismissTargets.forEach((el) => {
                el.addEventListener('click', function() {
                    closeConfirmModal(true);
                });
            });

            if (errorOkBtn) {
                errorOkBtn.addEventListener('click', function() {
                    closeModal(errorModal);
                });
            }

            errorDismissTargets.forEach((el) => {
                el.addEventListener('click', function() {
                    closeModal(errorModal);
                });
            });

            document.addEventListener('keydown', function(event) {
                if (event.key !== 'Escape') return;
                if (confirmModal && confirmModal.classList.contains('is-open')) {
                    closeConfirmModal(true);
                    return;
                }
                if (errorModal && errorModal.classList.contains('is-open')) {
                    closeModal(errorModal);
                }
            });

            const initialError = @json(session('error'));
            if (initialError) {
                window.showSystemErrorModal(initialError);
            }

            window.addEventListener('error', function(event) {
                const message = (event && event.message) ? event.message : '';
                if (!message || message === 'Script error.') return;
                const source = event && typeof event.filename === 'string' ? event.filename : '';
                const sameOriginSource = !source || source.startsWith(window.location.origin) || source.startsWith('/');
                if (!sameOriginSource) return;
                window.showSystemErrorModal(message);
            });

            window.addEventListener('unhandledrejection', function(event) {
                const reason = event ? event.reason : null;
                const message = typeof reason === 'string' ? reason : (reason && reason.message ? reason.message : '');
                window.showSystemErrorModal(message || 'A background process failed. Please try again.');
            });
        })();

        (function initializeGlobalPageLoader() {
            const loader = document.getElementById('globalPageLoader');
            const loaderRevealDelayMs = 90;
            let loaderVisible = false;
            let loaderPending = false;
            let loaderTimer = null;

            if (!loader) {
                return;
            }

            const commitShowLoader = function () {
                loaderPending = false;
                loaderTimer = null;
                loaderVisible = true;
                loader.classList.add('is-visible');
                loader.setAttribute('aria-hidden', 'false');
                document.body.classList.add('page-loading');
            };

            const showPageLoader = function (options) {
                const immediate = !!(options && options.immediate);

                if (loaderVisible) {
                    return;
                }

                if (immediate) {
                    if (loaderTimer) {
                        window.clearTimeout(loaderTimer);
                        loaderTimer = null;
                    }

                    commitShowLoader();
                    return;
                }

                if (loaderPending || loaderTimer) {
                    return;
                }

                loaderPending = true;
                loaderTimer = window.setTimeout(commitShowLoader, loaderRevealDelayMs);
            };

            const hidePageLoader = function () {
                if (loaderTimer) {
                    window.clearTimeout(loaderTimer);
                    loaderTimer = null;
                }

                loaderPending = false;
                loaderVisible = false;
                loader.classList.remove('is-visible');
                loader.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('page-loading');
            };

            const isSafeNavigableLink = function (link) {
                if (!link || link.hasAttribute('download')) {
                    return false;
                }

                const href = (link.getAttribute('href') || '').trim();
                if (href === '' || href === '#' || href.startsWith('javascript:')) {
                    return false;
                }

                const target = (link.getAttribute('target') || '').trim().toLowerCase();
                if (target !== '' && target !== '_self') {
                    return false;
                }

                try {
                    const url = new URL(link.href, window.location.href);
                    return url.origin === window.location.origin;
                } catch (error) {
                    return false;
                }
            };

            const showPageLoaderForLink = function (link) {
                if (!link || link.dataset.pageLoading === 'false' || !isSafeNavigableLink(link)) {
                    return false;
                }

                showPageLoader({ immediate: false });

                return true;
            };

            const showPageLoaderForForm = function (form, submitter) {
                if (!form || form.dataset.pageLoading === 'false') {
                    return false;
                }

                if (submitter && submitter.dataset && submitter.dataset.pageLoading === 'false') {
                    return false;
                }

                const targetAttr = (
                    (submitter && submitter.getAttribute && submitter.getAttribute('formtarget'))
                    || form.getAttribute('target')
                    || ''
                ).trim().toLowerCase();

                if (targetAttr !== '' && targetAttr !== '_self') {
                    return false;
                }

                const methodAttr = (
                    (submitter && submitter.getAttribute && submitter.getAttribute('formmethod'))
                    || form.getAttribute('method')
                    || 'get'
                ).trim().toLowerCase();

                if (methodAttr === 'dialog') {
                    return false;
                }

                const actionAttr = (
                    (submitter && submitter.getAttribute && submitter.getAttribute('formaction'))
                    || form.getAttribute('action')
                    || window.location.href
                ).trim();

                if (actionAttr.toLowerCase().startsWith('javascript:')) {
                    return false;
                }

                try {
                    const formActionUrl = new URL(actionAttr || window.location.href, window.location.href);
                    if (formActionUrl.origin !== window.location.origin) {
                        return false;
                    }
                } catch (error) {
                    return false;
                }

                showPageLoader({ immediate: false });

                return true;
            };

            window.AppUI = window.AppUI || {};
            window.AppUI.showPageLoader = showPageLoader;
            window.AppUI.hidePageLoader = hidePageLoader;
            window.AppUI.showPageLoaderForLink = showPageLoaderForLink;
            window.AppUI.showPageLoaderForForm = showPageLoaderForForm;
            window.showPageLoader = showPageLoader;
            window.hidePageLoader = hidePageLoader;

            document.addEventListener('click', function (event) {
                if (
                    event.defaultPrevented
                    || event.button !== 0
                    || event.metaKey
                    || event.ctrlKey
                    || event.shiftKey
                    || event.altKey
                ) {
                    return;
                }

                const link = event.target.closest('a[href]');
                if (!link) {
                    return;
                }

                showPageLoaderForLink(link);
            });

            document.addEventListener('submit', function (event) {
                if (event.defaultPrevented) {
                    return;
                }

                showPageLoaderForForm(event.target, event.submitter);
            });

            document.addEventListener('keydown', function (event) {
                if (event.defaultPrevented) {
                    return;
                }

                const key = (event.key || '').toLowerCase();
                const isF5 = event.key === 'F5';
                const isReloadShortcut = key === 'r' && (event.ctrlKey || event.metaKey);

                if (!isF5 && !isReloadShortcut) {
                    return;
                }

                showPageLoader({ immediate: true });
            });

            window.addEventListener('beforeunload', function () {
                showPageLoader({ immediate: true });
            });

            window.addEventListener('pageshow', hidePageLoader);
        })();

        // Confirmation for save/update/delete actions
        (function attachActionConfirms() {
            const defaultMessages = {
                save: 'Are you sure you want to save these changes?',
                delete: 'Are you sure you want to delete this item? This action cannot be undone.'
            };

            function getActionText(el) {
                const text = (el.textContent || el.value || '').trim().toLowerCase();
                return text;
            }

            function extractInlineConfirmMessage(code) {
                if (!code) return '';
                const match = code.match(/confirm\s*\(\s*(['"])(.*?)\1\s*\)/i);
                return match && match[2] ? match[2] : '';
            }

            function normalizeInlineConfirmHandlers() {
                document.querySelectorAll('form[onsubmit*="confirm("]').forEach((form) => {
                    const inlineCode = form.getAttribute('onsubmit') || '';
                    const message = extractInlineConfirmMessage(inlineCode);
                    if (message && !form.dataset.confirm) {
                        form.dataset.confirm = message;
                    }
                    form.removeAttribute('onsubmit');
                });
            }

            function needsAutoConfirm(el, form) {
                if (!el || el.disabled) return false;
                if (el.dataset && el.dataset.confirmSkip === 'true') return false;
                if (el.dataset && el.dataset.confirm) return true;
                if (form && form.dataset && form.dataset.confirm) return true;
                const text = getActionText(el);
                if (!text) return false;
                const isSave = text.includes('save');
                const isDelete = text.includes('delete');
                return isSave || isDelete;
            }

            function resolveMessage(el, form) {
                if (el.dataset && el.dataset.confirm) return el.dataset.confirm;
                if (form && form.dataset && form.dataset.confirm) return form.dataset.confirm;
                const text = getActionText(el);
                return text.includes('delete') ? defaultMessages.delete : defaultMessages.save;
            }

            normalizeInlineConfirmHandlers();

            document.addEventListener('click', function(e) {
                const target = e.target.closest('button, input[type="submit"], input[type="button"], a');
                if (!target) return;
                const form = target.closest('form');

                if (target.dataset && target.dataset.confirmed === 'true') {
                    delete target.dataset.confirmed;
                    return;
                }

                if (!needsAutoConfirm(target, form)) return;

                e.preventDefault();
                e.stopPropagation();
                const message = resolveMessage(target, form);
                window.openConfirmationModal(message, function() {
                    target.dataset.confirmed = 'true';
                    const targetTag = target.tagName ? target.tagName.toUpperCase() : '';
                    const explicitType = (target.getAttribute('type') || '').toLowerCase();
                    const resolvedType = (target.type || explicitType || '').toLowerCase();
                    const isSubmitButton = targetTag === 'BUTTON' && (resolvedType === '' || resolvedType === 'submit');
                    const isSubmitInput = targetTag === 'INPUT' && resolvedType === 'submit';

                    if (form && (isSubmitButton || isSubmitInput)) {
                        window.withNativeConfirmBypass(function() {
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit(target);
                            } else {
                                if (window.AppUI && typeof window.AppUI.showPageLoaderForForm === 'function') {
                                    window.AppUI.showPageLoaderForForm(form, target);
                                }
                                form.submit();
                            }
                        });
                        return;
                    }

                    if (targetTag === 'A' && window.AppUI && typeof window.AppUI.showPageLoaderForLink === 'function') {
                        window.AppUI.showPageLoaderForLink(target);
                    }

                    window.withNativeConfirmBypass(function() {
                        target.click();
                    });
                });
            }, true);

            document.addEventListener('submit', function(e) {
                const submitter = e.submitter;
                const form = e.target;

                if (form && form.dataset && form.dataset.confirmed === 'true') {
                    delete form.dataset.confirmed;
                    return;
                }

                if (!submitter) {
                    if (!form || !form.dataset || !form.dataset.confirm) return;
                    e.preventDefault();
                    e.stopPropagation();
                    window.openConfirmationModal(form.dataset.confirm, function() {
                        form.dataset.confirmed = 'true';
                        if (window.AppUI && typeof window.AppUI.showPageLoaderForForm === 'function') {
                            window.AppUI.showPageLoaderForForm(form, null);
                        }
                        window.withNativeConfirmBypass(function() {
                            form.submit();
                        });
                    });
                    return;
                }

                if (submitter.dataset && submitter.dataset.confirmed === 'true') {
                    delete submitter.dataset.confirmed;
                    return;
                }

                if (!needsAutoConfirm(submitter, form)) return;

                e.preventDefault();
                e.stopPropagation();
                const message = resolveMessage(submitter, form);
                window.openConfirmationModal(message, function() {
                    submitter.dataset.confirmed = 'true';
                    window.withNativeConfirmBypass(function() {
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit(submitter);
                        } else {
                            if (window.AppUI && typeof window.AppUI.showPageLoaderForForm === 'function') {
                                window.AppUI.showPageLoaderForForm(form, submitter);
                            }
                            form.submit();
                        }
                    });
                });
            }, true);
        })();

        (function initializeGlobalPagasaClock() {
            const endpoint = @json(route('pagasa-time.current'));
            let serverBaseMs = null;
            let syncedAtMs = null;
            const getMonotonicNow = () => {
                if (window.performance && typeof window.performance.now === 'function') {
                    return window.performance.now();
                }

                return Date.now();
            };

            function formatManila(date) {
                return date.toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true,
                    timeZone: 'Asia/Manila'
                });
            }

            function updateGlobalClock(text, color) {
                document.querySelectorAll('[data-pagasa-global-clock]').forEach((el) => {
                    el.textContent = text;
                    el.style.color = color;
                });
            }

            function updateTaggedTimeBlocks(isoTime) {
                document.querySelectorAll('[data-pagasa-time]').forEach((el) => {
                    // Keep PAGASA time running without rendering the "Current Time" text.
                    el.dataset.pagasaIso = isoTime;
                    el.style.display = 'none';
                    el.textContent = '';
                });
            }

            function tick() {
                if (serverBaseMs === null || syncedAtMs === null) {
                    return;
                }

                const elapsedMs = Math.max(0, getMonotonicNow() - syncedAtMs);
                const now = new Date(serverBaseMs + elapsedMs);
                const formatted = formatManila(now);

                updateGlobalClock(`PAGASA Time: ${formatted}`, '#002C76');
                updateTaggedTimeBlocks(now.toISOString());
            }

            async function syncServerTime() {
                try {
                    const response = await fetch(endpoint, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const data = await response.json();
                    const parsedMs = Date.parse(data?.ntp_time ?? '');

                    if (!data?.success || Number.isNaN(parsedMs)) {
                        throw new Error('Invalid time payload');
                    }

                    serverBaseMs = parsedMs;
                    syncedAtMs = getMonotonicNow();
                    tick();
                } catch (error) {
                    updateGlobalClock('PAGASA Time unavailable', '#dc2626');
                    updateTaggedTimeBlocks('');
                }
            }

            syncServerTime();
            setInterval(tick, 1000);
            setInterval(syncServerTime, 60000);

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    syncServerTime();
                }
            });
        })();
    </script>
    
    @yield('scripts')

    <script>
        (function initializePersistentTabState() {
            const storagePrefix = 'pdmuoms.tab-state';
            const tablistSelector = '[role="tablist"]';
            const tabSelector = '[role="tab"]';

            let storage = null;

            try {
                storage = window.sessionStorage;
            } catch (error) {
                storage = null;
            }

            if (!storage) {
                return;
            }

            function normalizeValue(value) {
                return String(value || '')
                    .trim()
                    .replace(/\s+/g, ' ');
            }

            function getPageKey() {
                return window.location.pathname + window.location.search;
            }

            function getTablists() {
                return Array.from(document.querySelectorAll(tablistSelector));
            }

            function getTabs(tablist) {
                return Array.from(tablist.querySelectorAll(tabSelector)).filter(function (tab) {
                    return tab.closest(tablistSelector) === tablist;
                });
            }

            function getTablistKey(tablist, index) {
                return normalizeValue(
                    tablist.id
                    || tablist.dataset.tablistKey
                    || tablist.getAttribute('aria-label')
                    || ('tablist-' + index)
                );
            }

            function getTabKey(tab, index) {
                return normalizeValue(
                    tab.id
                    || tab.getAttribute('aria-controls')
                    || tab.dataset.target
                    || tab.dataset.projectTabTarget
                    || tab.dataset.userTabTarget
                    || tab.dataset.utilityTabTarget
                    || tab.dataset.userTab
                    || tab.dataset.userAccessTab
                    || tab.dataset.roleConfigTab
                    || ('tab-' + index + ':' + normalizeValue(tab.textContent))
                );
            }

            function getStorageKey(tablist, tablistIndex) {
                return [
                    storagePrefix,
                    getPageKey(),
                    getTablistKey(tablist, tablistIndex),
                ].join(':');
            }

            function isTabActive(tab) {
                return tab.getAttribute('aria-selected') === 'true' || tab.classList.contains('is-active');
            }

            function persistActiveTabs() {
                getTablists().forEach(function (tablist, tablistIndex) {
                    const tabs = getTabs(tablist);
                    const activeTab = tabs.find(isTabActive);

                    if (!activeTab) {
                        return;
                    }

                    const activeTabIndex = tabs.indexOf(activeTab);
                    storage.setItem(getStorageKey(tablist, tablistIndex), getTabKey(activeTab, activeTabIndex));
                });
            }

            function restoreActiveTabs() {
                getTablists().forEach(function (tablist, tablistIndex) {
                    const storedTabKey = storage.getItem(getStorageKey(tablist, tablistIndex));

                    if (!storedTabKey) {
                        return;
                    }

                    const tabs = getTabs(tablist);
                    const targetTab = tabs.find(function (tab, tabIndex) {
                        return getTabKey(tab, tabIndex) === storedTabKey;
                    });

                    if (!targetTab || isTabActive(targetTab)) {
                        return;
                    }

                    targetTab.dispatchEvent(new MouseEvent('click', {
                        bubbles: true,
                        cancelable: true,
                    }));
                });
            }

            document.addEventListener('click', function (event) {
                const tab = event.target.closest(tabSelector);

                if (!tab) {
                    return;
                }

                const tablist = tab.closest(tablistSelector);

                if (!tablist) {
                    return;
                }

                const tablists = getTablists();
                const tablistIndex = tablists.indexOf(tablist);
                const tabs = getTabs(tablist);
                const tabIndex = tabs.indexOf(tab);

                if (tablistIndex === -1 || tabIndex === -1) {
                    return;
                }

                storage.setItem(getStorageKey(tablist, tablistIndex), getTabKey(tab, tabIndex));
            }, true);

            window.addEventListener('beforeunload', persistActiveTabs);
            window.addEventListener('pagehide', persistActiveTabs);

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    window.requestAnimationFrame(function () {
                        window.requestAnimationFrame(restoreActiveTabs);
                    });
                }, { once: true });
                return;
            }

            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(restoreActiveTabs);
            });
        })();
    </script>
</body>
</html>
