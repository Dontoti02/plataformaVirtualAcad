<?php
namespace Modules\Tenant\Modules\JobOpportunities\Shared\Domain\Utils;
use Illuminate\Support\Str;
class CreateSlugJobOffer
{
    public static function create(string $title): string
    {
        $ramdonuniqueId = uniqid();
        $slug = Str::of($title)->slug("-")->limit(255 - mb_strlen($ramdonuniqueId) - 1, "")->trim("-")->append("-", $ramdonuniqueId);
        return $slug;
    }
}
