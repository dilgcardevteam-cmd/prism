<?php

use App\Models\User;

return [
    'uploader_chains' => [
        'lgu' => [
            [
                'level' => 1,
                'name' => 'Provincial Officer',
                'role' => User::ROLE_PROVINCIAL,
                'scope' => 'province',
                'pending_status' => 'Pending Level 1 Approval',
                'returned_status' => 'Returned by Provincial Officer',
                'document_type' => 'fund-utilization',
            ],
            [
                'level' => 2,
                'name' => 'Regional Officer',
                'role' => User::ROLE_REGIONAL,
                'scope' => 'region',
                'pending_status' => 'Pending Level 2 Approval',
                'returned_status' => 'Returned by Regional Officer',
                'document_type' => 'fund-utilization',
            ],
        ],
        'provincial' => [
            [
                'level' => 2,
                'name' => 'Regional Officer',
                'role' => User::ROLE_REGIONAL,
                'scope' => 'region',
                'pending_status' => 'Pending Level 2 Approval',
                'returned_status' => 'Returned by Regional Officer',
                'document_type' => 'fund-utilization',
            ],
        ],
    ],
];
