<?php
namespace Modules\Tenant\Modules\JobOpportunities\Company\Application\DTOs;

class RegisterCompanyDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $ruc,
        public readonly string $phone,
        public readonly string $mailbox,
        public readonly ?string $description,
        public readonly ?string $website,
        public readonly ?string $address
    ) {}
}
