<?php

namespace App\Repositories\Client;

use App\Models\Client;

interface ClientRepositoryInterface
{
    public function register(array $data);
    public function login(array $credentials);
    public function logout();
    public function forgotPassword($phone);
    public function getAllClients();

    /**
     * Return the currently authenticated client or null.
     *
     * Implementations should try the auth guard first then fall back to parsing
     * the JWT token (or whatever auth mechanism your app uses).
     *
     * @return \App\Models\Client|null
     */
    public function getAuthenticatedClient(): ?Client;
}