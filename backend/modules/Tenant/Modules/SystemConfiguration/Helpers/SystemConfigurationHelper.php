<?php

namespace Modules\Tenant\Modules\SystemConfiguration\Helpers;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Enum\DaysOfWeek;
use Modules\Tenant\Modules\SystemConfiguration\Models\SystemConfiguration;
use Illuminate\Support\Facades\Validator;
use Modules\Shared\Enum\ParameterTypes;
use Illuminate\Support\Facades\Storage;

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
                $parameter->value = (string) $parameter->value;
                break;
        }
    }

    public static function getInstitutionName()
    {
        $key = 'application_name';
        $value = self::getValueByKey($key);

        return $value;
    }

    public static function getInstitutionLogo()
    {
        $key = 'logo';

        $value = self::getValueByKey($key);

        $path = !empty($value) ? 'public/' . $value : 'public/default/logo.png';

        if (!Storage::exists($path)) {
            // throw new Exception('Logo no encontrado');
          return null;

        }

        return $path;
    }

    public static function getCertificateBackground()
    {
        $key = 'certificate_background';

        $value = self::getValueByKey($key);

        $path = !empty($value) ? 'public/' . $value :  'public/default/certificate_background.png';

        if (!Storage::exists($path)) {
            throw new Exception('Certificado no encontrado');
        }

        return $path;
    }

    public static function getStudyDays()
    {
        $key = 'study_days';
        $value_days = self::getValueByKey($key);

        $keys = array_flip($value_days);
        $daysOfWeek = array_intersect_key(DaysOfWeek::DAYS, $keys);

        return $daysOfWeek;
    }

    public static function getStudyHours()
    {
        $key_shs = 'study_hour_start';
        $key_she = 'study_hour_end';

        $value_start = self::getValueByKey($key_shs);
        $value_end = self::getValueByKey($key_she);

        $hours = (object) [
            "start" => $value_start,
            "end" => $value_end,
        ];

        return $hours;
    }

    public static function validateUpdateRequest(string $key, Request $request)
    {
        if (in_array($key, ['logo', 'favicon', 'banner'])) {
            if ($request->hasFile('value')) {
                $rules["value"] = "file|mimes:jpeg,jpg,png";
            } else {
                $rules["value"] = "nullable";
            }
        } else {
            $rules["value"] = "required|string";
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function getLandingPage()
    {
        $key = 'landing_page';
        $value = self::getValueByKey($key);

        return $value;
    }

    public static function validateUploadImageRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|image',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function getAlertsImports()
    {
        $key = 'alerts_imports';
        $value = self::getValueByKey($key);

        return $value;
    }

    public static function getIGV()
    {
        $key = 'igv_amount';
        $value = self::getValueByKey($key);

        return $value;
    }
}
