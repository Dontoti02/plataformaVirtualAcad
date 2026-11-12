<?php

namespace Modules\Admin\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\Admin\Models\Tenant;
use Modules\Tenant\Modules\User\Models\User;
class InstitutionHelper
{
    public static function validateRequest(Request $request, $exceptId = 0)
    {
        $validator = Validator::make($request->all(), [
            "modular_code"      => "required|string|size:7|unique:institution,modular_code,$exceptId",
            "ruc"               => "required|string|size:11|unique:institution,ruc,$exceptId",
            "name"              => "required|string|max:255|unique:institution,name,$exceptId",
            "type_management"   => "required|string|in:Pública,Privada",
            "department"        => "required|string|exists:ubigeo,department",
            "province"          => "required|string|exists:ubigeo,province",
            "district"          => "required|string|exists:ubigeo,district",
            "address"           => "required|string|max:255",
            "subdomain"         => "required|string|max:255|unique:domain,domain,$exceptId",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateSubscriptionRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "start_date"    => "required|date",
            "end_date"      => "nullable|date|after_or_equal:start_date",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function existsDatabase(string $name)
    {
        $connection = 'central';

        $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$name'";

        $result = DB::connection($connection)->select($query);

        if (!empty($result)) {
            $queryDrop = "DROP DATABASE $name";
            DB::connection($connection)->statement($queryDrop);
        }
    }

    public static function updateDefaultAdminUser (string $email, string $password,string $names) {
 
        $tenants = Tenant::all();

        tenancy()->runForMultiple($tenants,function() use ($email,$password,$names) {

            $newPassword = Hash::make($password);

            $findEmail = User::where('email',$email)->where('default_user',false)->first();

            if ($findEmail) return;

            $user = User::where('default_user', true)->with('person')->first();

            if (!$user) return;

            $user->update([
                'email' => $email, 
                'password' => $newPassword
            ]);

            $user->person->update([
                'names' => $names,
                'email' => $email
            ]);
        });

        tenancy()->end();
    }
}
