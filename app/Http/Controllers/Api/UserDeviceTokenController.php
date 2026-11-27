<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserDeviceToken\StoreUserDeviceTokenRequest;
use App\Http\Requests\UserDeviceToken\UpdateUserDeviceTokenRequest;
use App\Models\UserDeviceToken;

class UserDeviceTokenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserDeviceTokenRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(UserDeviceToken $userDeviceToken)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserDeviceTokenRequest $request, UserDeviceToken $userDeviceToken)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserDeviceToken $userDeviceToken)
    {
        //
    }
}
