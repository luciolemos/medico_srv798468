<?php

declare(strict_types=1);

return [
    'medico' => [
        'name' => 'Clínica Médica',
        'mark' => 'M',
        'palette' => 'blue',
        'typography' => 'clinical',
        'schema_type' => 'MedicalClinic',
        'request_prefix' => 'MED',
        'tone' => 'Clínico, seguro, objetivo e humanizado.',
    ],
    'pediatria' => [
        'name' => 'Clínica Pediátrica',
        'mark' => 'P',
        'palette' => 'emerald',
        'typography' => 'family',
        'schema_type' => 'MedicalClinic',
        'request_prefix' => 'PED',
        'tone' => 'Acolhedor, leve, familiar e orientado a responsáveis.',
    ],
    'odontologia' => [
        'name' => 'Clínica Odontológica',
        'mark' => 'O',
        'palette' => 'blue',
        'typography' => 'premium',
        'schema_type' => 'Dentist',
        'request_prefix' => 'ODO',
        'tone' => 'Limpo, preciso, moderno e premium.',
    ],
    'veterinaria' => [
        'name' => 'Clínica Veterinária',
        'mark' => 'V',
        'palette' => 'emerald',
        'typography' => 'warm',
        'schema_type' => 'VeterinaryCare',
        'request_prefix' => 'VET',
        'tone' => 'Próximo, cuidadoso, acolhedor e humano.',
    ],
    'estetica' => [
        'name' => 'Clínica de Estética',
        'mark' => 'E',
        'palette' => 'violet',
        'typography' => 'premium',
        'schema_type' => 'HealthAndBeautyBusiness',
        'request_prefix' => 'EST',
        'tone' => 'Refinado, sensorial, elegante e consultivo.',
    ],
    'psicologia' => [
        'name' => 'Clínica de Psicologia',
        'mark' => 'P',
        'palette' => 'amber',
        'typography' => 'warm',
        'schema_type' => 'MedicalBusiness',
        'request_prefix' => 'PSI',
        'tone' => 'Calmo, reservado, empático e claro.',
    ],
    'nutricao' => [
        'name' => 'Clínica de Nutrição',
        'mark' => 'N',
        'palette' => 'emerald',
        'typography' => 'family',
        'schema_type' => 'MedicalBusiness',
        'request_prefix' => 'NUT',
        'tone' => 'Acolhedor, educativo, objetivo e orientado a rotina.',
    ],
];
