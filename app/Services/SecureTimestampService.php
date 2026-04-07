<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SecureTimestampService
{
    /**
     * Get a secure, tamper-proof upload timestamp
     * This always uses server-side PAGASA time, never client-side time
     * Users cannot manipulate this by changing their computer clock
     *
     * @return \Carbon\Carbon
     */
    public static function getUploadTimestamp(): Carbon
    {
        // Always fetch from PAGASA server - user cannot change this
        $timestamp = PagasaTimeService::getCurrentTime();
        
        Log::info('Secure upload timestamp created', [
            'timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'timezone' => $timestamp->timezone->getName(),
            'source' => 'PAGASA_SERVER'
        ]);
        
        return $timestamp;
    }

    /**
     * Validate that an uploaded timestamp is reasonable
     * Prevents attempts to inject past/future timestamps
     *
     * @param \Carbon\Carbon $timestamp
     * @param int $toleranceSeconds How many seconds difference is acceptable (default 60 seconds)
     * @return bool
     */
    public static function isValidUploadTimestamp(Carbon $timestamp, int $toleranceSeconds = 60): bool
    {
        $now = PagasaTimeService::getCurrentTime();
        $difference = abs($now->diffInSeconds($timestamp));
        
        // Check if timestamp is within acceptable tolerance
        // This allows for slight network delays but prevents massive manipulation
        if ($difference > $toleranceSeconds) {
            Log::warning('Invalid upload timestamp detected', [
                'provided_timestamp' => $timestamp->format('Y-m-d H:i:s'),
                'current_time' => $now->format('Y-m-d H:i:s'),
                'difference_seconds' => $difference,
                'tolerance_seconds' => $toleranceSeconds
            ]);
            
            return false;
        }
        
        return true;
    }

    /**
     * Get the current time with anti-tampering info
     * Returns timestamp + metadata about how it was obtained
     *
     * @return array
     */
    public static function getSecureTimestampWithMetadata(): array
    {
        $timestamp = self::getUploadTimestamp();
        
        return [
            'timestamp' => $timestamp,
            'formatted' => $timestamp->format('Y-m-d H:i:s'),
            'timezone' => $timestamp->timezone->getName(),
            'source' => 'PAGASA_SERVER', // Not user's computer
            'tamper_proof' => true,
            'can_be_modified_by_user' => false,
            'notes' => 'Timestamp obtained from official Philippine time server (PAGASA)'
        ];
    }

    /**
     * Log timestamp for audit trail
     * Creates permanent record of when upload occurred
     *
     * @param string $documentType (e.g., 'mov', 'written-notice-dbm', 'fdp')
     * @param string $projectCode
     * @param string $quarter
     * @param \Carbon\Carbon $timestamp
     * @return void
     */
    public static function logUploadTimestamp(
        string $documentType,
        string $projectCode,
        string $quarter,
        Carbon $timestamp
    ): void {
        Log::channel('upload_timestamps')->info('Document uploaded', [
            'document_type' => $documentType,
            'project_code' => $projectCode,
            'quarter' => $quarter,
            'upload_timestamp' => $timestamp->format('Y-m-d H:i:s'),
            'timezone' => $timestamp->timezone->getName(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id() ?? 'anonymous',
            'user_local_timezone' => config('app.timezone')
        ]);
    }

    /**
     * Verify that uploaded timestamp hasn't been tampered with
     * Can be called during approval or audit processes
     *
     * @param \Carbon\Carbon $uploadedTimestamp
     * @param \Carbon\Carbon|null $approvalTimestamp
     * @return array
     */
    public static function verifyTimestampIntegrity(
        Carbon $uploadedTimestamp,
        ?Carbon $approvalTimestamp = null
    ): array {
        $now = PagasaTimeService::getCurrentTime();
        
        $verification = [
            'upload_timestamp_valid' => true,
            'approval_sequence_valid' => true,
            'integrity_checks' => []
        ];

        // Check 1: Upload timestamp is in the past (not future)
        if ($uploadedTimestamp->isFuture()) {
            $verification['upload_timestamp_valid'] = false;
            $verification['integrity_checks'][] = 'Upload timestamp is in the future (suspicious)';
        }

        // Check 2: Upload timestamp is not too old (e.g., more than 30 days)
        if ($uploadedTimestamp->diffInDays($now) > 30) {
            $verification['integrity_checks'][] = 'Upload timestamp is older than 30 days (informational)';
        }

        // Check 3: If approval exists, it should be after upload
        if ($approvalTimestamp) {
            if ($approvalTimestamp->isBefore($uploadedTimestamp)) {
                $verification['approval_sequence_valid'] = false;
                $verification['integrity_checks'][] = 'Approval timestamp is before upload timestamp (impossible)';
            }
        }

        return $verification;
    }
}
