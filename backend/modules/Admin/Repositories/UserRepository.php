<?php

namespace Modules\Admin\Repositories;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Helpers\UserHelper;
use Modules\Admin\Models\Rol;
use Modules\Admin\Models\RolUser;
use Modules\Admin\Models\User;

class UserRepository
{
    public static function params()
    {
        $roles = Rol::select('id', 'name')
            ->get();

        $result = [
            'roles' => $roles,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        UserHelper::validateListRequest($request);

        $users = User::select([
            'user.id',
            'rol.id as rol_id',
            'user.avatar as photo',
            'user.names',
            'user.email',
            'user.is_active',
            'user.last_login',
        ])
            ->join('rol_user', 'user.id', 'rol_user.user_id')
            ->join('rol', function ($join) use ($request) {
                $join->on('rol_user.rol_id', 'rol.id')
                    ->whereIn('rol.id', $request->rol_ids);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('user.names', 'like', "%$request->search%")
                        ->orWhere('user.email', 'like', "%$request->search%");
                });
            })
            ->orderBy('user.names')
            ->distinct()
            ->paginate($request->size, ['*'], 'page', $request->page);

        foreach ($users as $user) {
            $user->rol_ids = RolUser::select('rol_id')
                ->where('user_id', $user->id)
                ->pluck('rol_id')
                ->toArray();
        }

        $result = [
            'page' => $request->page,
            'size' => $request->size,
            'total' => $users->total(),
            'users' => $users->items(),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        UserHelper::validateCreateRequest($request);

        $user = User::create([
            'names' => $request->names,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->roles()->attach($request->rol_ids);

        return 'Usuario creado correctamente';
    }

    public static function update(int $id, Request $request)
    {
        $user = User::byKey('id', $id);

        UserHelper::validateUpdateRequest($request, $id);

        $user->update([
            'names' => $request->names,
            'email' => $request->email,
        ]);

        $user->roles()->sync($request->rol_ids);

        return 'Usuario actualizado correctamente';
    }

    public static function changePassword(int $id, Request $request)
    {
        $user = UserHelper::validateAccess($id);

        UserHelper::validateChangePasswordRequest($request);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return 'Contraseña actualizada correctamente';
    }

    public static function changePhoto(int $id, Request $request)
    {
        $user = UserHelper::validateAccess($id);

        UserHelper::validateChangePhotoRequest($request);

        if ($user->avatar) {
            $user->avatar = 'public/' . $user->avatar;

            if (Storage::exists($user->avatar)) {
                Storage::delete($user->avatar);
            }
        }

        $file = $request->file('file');
        $path = $file->store('public/user');
        $path = str_replace('public/', '', $path);

        $user->update([
            'avatar' => $path,
        ]);

        return $path;
    }

    public static function deletePhoto(int $id)
    {
        $user = UserHelper::validateAccess($id);

        if ($user->avatar) {
            $user->avatar = 'public/' . $user->avatar;

            if (Storage::exists($user->avatar)) {
                Storage::delete($user->avatar);
            }
        }

        $user->update([
            'avatar' => null,
        ]);

        return "Foto eliminada correctamente";
    }

    public static function disable(int $id)
    {
        $user = UserHelper::validateDisableOrDeleteAccess($id);

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return "Usuario " . ($user->is_active ? "habilitado" : "deshabilitado") . " correctamente";
    }

    public static function delete(int $id)
    {
        $user = UserHelper::validateDisableOrDeleteAccess($id);

        $user->roles()->detach();
        $user->delete();

        return "Usuario eliminado correctamente";
    }
}
