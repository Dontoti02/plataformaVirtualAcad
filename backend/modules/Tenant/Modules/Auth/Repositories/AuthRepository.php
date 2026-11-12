<?php

namespace Modules\Tenant\Modules\Auth\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Shared\Enum\EmailBodyTemplate;
use Modules\Shared\Services\JWTService;
use Modules\Shared\Services\MailerService;
use Modules\Tenant\Modules\Auth\Helpers\AuthHelper;
use Modules\Tenant\Modules\Management\Helpers\PeriodHelper;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Modules\User\Helpers\MenuHelper;
use Modules\Tenant\Modules\User\Models\User;

class AuthRepository
{
    public static function login(Request $request)
    {
        AuthHelper::validateLoginRequest($request);

        $now = Carbon::now();

        $user = User::select()
            ->where('email', $request->email)
            ->first();

        if (!$user) {
            throw new Exception('Credenciales incorrectas');
        }

        $attempts = $user->attempts;
        $last_attempt = $user->last_attempt;

        if ($last_attempt) {

            $last_attempt = Carbon::parse($last_attempt);

            $minutes = $last_attempt->diffInMinutes($now);
            $restMinutes = round(5 - $minutes);

            if ($attempts == 5 && $restMinutes > 0) {
                throw new Exception("Has alcanzado el número máximo de intentos, $restMinutes min de espera");
            }
        }

        if (!Hash::check($request->password, $user->password, ['rounds' => 16])) {

            $user->update([
                'attempts' => $attempts + 1,
                'last_attempt' => $now,
            ]);

            throw new Exception('Contraseña incorrecta');
        }

        if ($user->attempts != 0 || $user->last_attempt != null) {
            $user->update([
                'attempts' => 0,
                'last_attempt' => null,
            ]);
        }

        if (!$user->is_active) {
            throw new Exception('Tu usuario se encuentra inactivo');
        }

        $payload = [
            'id'        => $user->id,
            'email'     => $user->email,
            'names'     => $user->person->names,
            'central'   => false
        ];

        $token = JWTService::sign($payload, $request->remember);

        $rol = $user->roles()->orderBy('id', 'asc')->first();

        $user->update([
            'rol_id'     => $rol->id,
            'last_login' => Carbon::now(),
        ]);

        // Menu
        $period = PeriodHelper::current();
        $menu = MenuHelper::listByUser($user, $period);

        // Profile
        $roles = $user->roles()
            ->select('rol.id', 'rol.name', 'rol.level')
            ->get();

        $size_mb = SystemConfigurationHelper::getValueByKey('maximum_file_size_to_upload');

        $mimes = SystemConfigurationHelper::getValueByKey('extensions_allowed_to_upload');
        $mimes = array_filter($mimes, function ($item) {
            return $item->permitted == true;
        });
        $mimes = array_values($mimes);
        $mimes = array_map(function ($item) {
            return $item->extension;
        }, $mimes);

        $result = [
            'token' => $token,
            'menu' => $menu,
            'user'  => [
                'document_type' => $user->person->document_type,
                'document_number' => $user->person->document_number,
                'names' => $user->person->names,
                'phone' => $user->person->phone,
                'email' => $user->email,
                'photo' => $user->avatar,
            ],
            'current_role' => $user->rol_id,
            'roles' => $roles,
            'period' => $period,
            'maximum_file_size_to_upload' => $size_mb,
            'extensions_allowed_to_upload' => $mimes,
        ];

        return $result;
    }

    public static function resetPassword(Request $request)
    {
        AuthHelper::validateResetPasswordRequest($request);

        $user = User::byKey('email', $request->email);

        $payload = [
            'id'        => $user->id,
            'email'     => $user->email,
            'names'     => $user->person->names,
            'central'   => false
        ];

        $reset_password_token = JWTService::sign($payload);

        $user->update([
            'reset_password_token' => $reset_password_token,
        ]);

        $subject = 'Restablecer contraseña';
        $body = EmailBodyTemplate::resetPassword;
        $host = $request->header('x-subdomain');
        self::sendMail([
            'subject' => $subject,
            'body' => $body,
            'name' => $user->person->names,
            'email' => $user->email,
            'url' => 'https://' . $host . '/reset-password?token=' . $reset_password_token,
        ]);

        return 'Solicitud recibida correctamente';
    }

    public static function checkResetPassword(Request $request)
    {
        try {
            JWTService::valid($request->token);
        } catch (Exception $e) {
            throw new Exception('El token para cambiar la contraseña ha expirado, por favor genera otro');
        }
    }

    public static function changePassword(Request $request)
    {
        AuthHelper::validateChangePasswordRequest($request);

        $user = User::byKey('email', $request->email);

        self::checkResetPassword($request);

        if (!$user->reset_password_token) {
            throw new Exception('El token para cambiar la contraseña ya ha sido utilizado, por favor genera otro');
        }

        if ($user->reset_password_token != $request->token) {
            throw new Exception('El token para cambiar la contraseña no esta asociado a este correo, por favor verifica tu correo o genera otro');
        }

        $user->update([
            'password' => Hash::make($request->password),
            'reset_password_token' => null,
            'attempts' => 0,
            'last_attempt' => null,
        ]);

        return 'Contraseña actualizada correctamente';
    }

    private static function sendMail(array $info)
    {
        $info = (object) $info;

        $subject = $info->subject;
        $body = $info->body;

        $name = $info->name;
        $email = $info->email;
        $url = $info->url;

        $body = str_replace('{{name}}', $name, $body);
        $body = str_replace('{{email}}', $email, $body);
        $body = str_replace('{{url}}', $url, $body);

        $data = (object) [
            'subject' => $subject,
            'body' => $body,
            'to' => $email,
        ];

        MailerService::send($data);
    }
}
