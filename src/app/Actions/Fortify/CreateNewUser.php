<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    const DEFAULT_PAYMENT_METHOD_ID = 1;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // リクエストクラスをインスタンス化してバリデーションを実行
        $request = app(RegisterRequest::class);
        $request->merge($input);
        $request->validateResolved();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'payment_method_id' => self::DEFAULT_PAYMENT_METHOD_ID,
        ]);
    }
}
