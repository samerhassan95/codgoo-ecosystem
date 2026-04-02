<?php

$privateKey = openssl_pkey_new([
    "private_key_bits" => 4096,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
]);

openssl_pkey_export($privateKey, $privateKeyPEM);

$publicKeyDetails = openssl_pkey_get_details($privateKey);
$publicKeyPEM = $publicKeyDetails["key"];

if (!is_dir('storage/keys')) {
    mkdir('storage/keys', 0777, true);
}

file_put_contents('storage/keys/jwt_private.pem', $privateKeyPEM);
file_put_contents('storage/keys/jwt_public.pem', $publicKeyPEM);

echo "Keys generated successfully!\n";
