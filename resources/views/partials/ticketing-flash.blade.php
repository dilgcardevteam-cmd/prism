@if (session('success'))
    <div class="ticketing-card" style="border-color: #bbf7d0; background: #f0fdf4;">
        <div style="color: #166534; font-weight: 700; font-size: 14px;">
            <i class="fas fa-circle-check" style="margin-right: 8px;"></i>
            {{ session('success') }}
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="ticketing-card" style="border-color: #fecaca; background: #fef2f2;">
        <div style="color: #991b1b; font-weight: 700; font-size: 14px; margin-bottom: 8px;">
            <i class="fas fa-triangle-exclamation" style="margin-right: 8px;"></i>
            Please review the following:
        </div>
        <ul style="margin: 0; padding-left: 18px; color: #7f1d1d; font-size: 13px; line-height: 1.6;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
