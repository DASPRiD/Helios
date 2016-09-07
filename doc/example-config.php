<?php
return [
    'helios' => [
        'cookie' => [
            // Name of the cookie to store the identity in
            'name' => 'helios',

            // Whether the cookie is limited to HTTPS
            'secure' => true,

            // Lifetime of the cookie, here 30 days
            'lifetime' => 2592000,
        ],

        'token' => [
            // Signer used for signing and verification
            'signer_class' => Lcobucci\JWT\Signer\Rsa\Sha256::class,

            // Signature and verification keys. See: https://github.com/lcobucci/jwt#token-signature
            'signature_key' => '',
            'verification_key' => '',
        ],

        'middleware' => [
            // The ID in the container pointing to your identity lookup. Must implement
            // DASPRiD\Helios\IdentityLookupInterface.
            'identity_lookup_id' => '',

            // Time after which a the cookie will automatically be renewed.
            'refresh_time' => 60,
        ],
    ],
];
