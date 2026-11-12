<?php

namespace Modules\Admin\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Admin\Models\Domain;
use Modules\Admin\Models\SystemConfiguration;
use Modules\Shared\Enum\ParameterTypes;

class SystemConfigurationHelper
{
    public static function getValueByKey($key)
    {
        $parameter = SystemConfiguration::byKey('key', $key);

        self::setTypeValue($parameter);

        return $parameter->value;
    }

    public static function setTypeValue(SystemConfiguration $parameter)
    {
        switch ($parameter->type) {
            case ParameterTypes::NUMBER:
                $parameter->value = (float) $parameter->value;
                break;
            case ParameterTypes::BOOLEAN:
                $parameter->value = (bool) $parameter->value;
                break;
            case ParameterTypes::ARRAY:
                $parameter->value = (array) json_decode($parameter->value);
                break;
            case ParameterTypes::JSON:
                $parameter->value = (object) json_decode($parameter->value);
                break;
            default:
                $parameter->value = $parameter->value;
                break;
        }
    }

    public static function getName()
    {
        $key = 'application_name';
        $value = self::getValueByKey($key);

        return $value;
    }

    public static function getDomain()
    {
        $key = 'domain';
        $value = self::getValueByKey($key);

        return $value;
    }

    public static function getDefaultUser()
    {
        $key = 'user_default_auth_institution';
        $value = self::getValueByKey($key);

        return $value;
    }

    public static function getDefaultLimitSpace()
    {
        $key = 'default_limit_space_institution_mb';
        $value = self::getValueByKey($key);

        return $value;
    }

    public static function getSubDomain()
    {
        $tenantId = tenant('id');
        $find = Domain::byKey('tenant_id', $tenantId);
        $value = $find->domain;

        return $value;
    }

    public static function validateUpdateRequest(string $key, Request $request)
    {
        if (in_array($key, ['logo', 'favicon', 'banner'])) {
            $rules["value"] = "nullable|file|mimes:jpeg,jpg,png";
        } else {
            $rules["value"] = "required|string";
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
