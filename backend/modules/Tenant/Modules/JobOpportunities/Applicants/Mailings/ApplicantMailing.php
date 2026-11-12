<?php

namespace Modules\Tenant\Modules\JobOpportunities\Applicants\Mailings;

use Modules\Shared\Enum\EmailBodyTemplate;
use Modules\Shared\Services\MailerService;

class ApplicantMailing
{
  public static function applicantToCompany(array $info)
  {
    $info = (object) $info;
    $subject = $info->subject;
    $body = EmailBodyTemplate::applicantToCompany;
    $body = strtr($body, [
      '{{offer_title}}' => $info->offerTitle,
      '{{applicant_name}}' => $info->applicantName,
      '{{applicant_email}}' => $info->applicantEmail,
      '{{applicant_phone}}' => $info->applicantPhone,
      '{{cv_url}}' => $info->cvUrl,
      '{{offer_url}}' => $info->offerUrl,
    ]);
    $data = (object) [
      'subject' => $subject,
      'body' => $body,
      'to' => $info->companyMailBox,
      'cc' => [],
    ];
    MailerService::send($data);
  }


  public static function applicantConfirmation(array $info)
  {
    $info = (object) $info;
    $subject = $info->subject;
    $body = EmailBodyTemplate::applicantConfirmation;
    $body = str_replace('{{applicant_name}}', $info->applicantName, $body);
    $body = str_replace('{{offer_title}}', $info->offerTitle, $body);
    $body = str_replace('{{company_name}}', $info->companyName, $body);
    $body = str_replace('{{cv_url}}', $info->cvUrl, $body);
    $data = (object) [
      'subject' => $subject,
      'body' => $body,
      'to' => $info->applicantEmail,
      'cc' => [],
    ];
    MailerService::send($data);
  }


  public static function applicantDecision(array $info) {
    $info = (object) $info;
    $subject = $info->subject;
    $body = EmailBodyTemplate::applicantDecision;
    $body = str_replace('{{status_icon}}', $info->statusIcon, $body);
    $body = str_replace('{{applicant_name}}', $info->applicantName, $body);
    $body = str_replace('{{offer_title}}', $info->offerTitle, $body);
    $body = str_replace('{{company_name}}', $info->companyName, $body);
    $body = str_replace('{{message}}', $info->message, $body);
    $body = str_replace('{{feedback}}', $info->feedback, $body);
    $data = (object) [
      'subject' => $subject,
      'body' => $body,
      'to' => $info->applicantEmail,
      'cc' => [],
    ];
    MailerService::send($data);
  }
}
