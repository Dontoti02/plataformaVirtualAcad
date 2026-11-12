<?php

namespace Modules\Tenant\Modules\JobOpportunities\JobOffer\Application\Enums;

class FilterOfferEnum
{
  const DATE_FILTERS = [
    [
      'name' => 'Urgente',
      'id' => 'urgent',
      'days' => 0
    ],
    [
      'name' => 'Ayer',
      'id' => 'yesterday',
      'days' => 1
    ],
    [
      'name' => 'Esta semana',
      'id' => 'week',
      'days' => 7
    ],
    [
      'name' => 'Este mes',
      'id' => 'month',
      'days' => 30
    ]
  ];

  const ORDER_BY = [
    [
      'name' => 'Fecha',
      'id' => 'publication_date'
    ],
    [
      'name' => 'Salario',
      'id' => 'salary'
    ]
  ];

  const SALARY_RANGES = [
    [
      'name' => 'Menos de S/. 1000',
      'id' => '-1000'
    ],
    [
      'name' => 'Más de S/ 1500',
      'id' => '+1500'
    ],
    [
      'name' => 'Más de S/. 2000',
      'id' => '+2000'
    ],
    [
      'name' => 'Más de S/. 3000',
      'id' => '+000'
    ],
    [
      'name' => 'Más de S/. 4000',
      'id' => '+4000'
    ]
  ];
}
