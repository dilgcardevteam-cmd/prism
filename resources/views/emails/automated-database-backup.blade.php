<x-mail::message>
# Automated Database Backup

A scheduled backup for database `{{ $databaseName }}` was generated on {{ now()->format('F j, Y g:i A') }}.

Attachment: `{{ $fileName }}`

@if ($wasEncrypted)
This backup attachment is encrypted using the password configured in Backup and Restore.
@endif

@if ($retentionDays)
Retention cleanup is enabled for backups older than {{ $retentionDays }} day(s).
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
