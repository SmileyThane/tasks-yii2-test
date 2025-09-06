<?php

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'jwt' => [
        'key'    => getenv('JWT_KEY') ?: 'change-me-dev-secret',
        'issuer' => 'your-api',
        'aud'    => 'your-client',
        'ttl'    => 3600 * 24,
    ],
];
