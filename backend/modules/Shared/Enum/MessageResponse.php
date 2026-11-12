<?php

namespace Modules\Shared\Enum;

enum MessageResponse: string
{
    case notFound  = "No encontrado, {var}";
    case spaceLimitExceeded = "Excediste el limite de espacio disponible configurado para la institución, consulta con el administrador";
}
