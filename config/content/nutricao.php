<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'Clínica de Nutrição | Consulta nutricional com plano alimentar personalizado',
        'description' => 'Nutrição clínica com avaliação individual, plano alimentar personalizado e acompanhamento contínuo para saúde, rotina e desempenho.',
        'site_name' => 'Clínica de Nutrição',
        'image' => [
            'src' => 'assets/img/social/nutricao-og.jpg',
            'width' => 1200,
            'height' => 630,
            'alt' => 'Nutricionista orientando paciente em consulta nutricional',
        ],
        'schema' => [
            'type' => 'MedicalBusiness',
            'logo' => 'assets/img/nutricao-mark.svg',
            'area_served' => 'Natal e região',
            'include_services' => true,
            'include_faq' => true,
        ],
    ],
    'nav' => [
        'badge' => 'Nutrição',
        'cta' => 'Agendar',
    ],
    'typography' => [
        'profile' => 'family',
    ],
    'hero' => [
        'badge_icon' => 'bandaid',
        'badge' => 'Nutrição com orientação prática para o dia a dia',
        'title_parts' => [
            'Nutrição clínica',
            'com plano possível',
            'para melhorar saúde, energia e rotina alimentar.',
        ],
        'lead' => 'Atendimento nutricional com avaliação individual, estratégia alimentar personalizada e acompanhamento contínuo para alcançar metas reais sem dietas extremas.',
        'secondary_cta' => [
            'label' => 'Ver atendimentos',
            'href' => '#features',
            'icon' => 'clipboard2-pulse',
        ],
        'trust_items' => [
            ['icon' => 'shield-check', 'label' => 'Plano individual'],
            ['icon' => 'calendar2-check', 'label' => 'Retornos programados'],
            ['icon' => 'person-heart', 'label' => 'Acompanhamento próximo'],
        ],
        'proof' => [
            'title' => 'Acompanhamento com estratégia e constância',
            'lines' => [
                'As metas são ajustadas com base na rotina, exames e evolução para reduzir recaídas.',
                'Canal direto para agendamento e confirmação.',
            ],
        ],
        'image' => [
            'src' => 'assets/img/hero/nutricao-640.webp',
            'sources' => [
                ['path' => 'assets/img/hero/nutricao-640.webp', 'width' => 640],
                ['path' => 'assets/img/hero/nutricao-960.webp', 'width' => 960],
                ['path' => 'assets/img/hero/nutricao-1896.webp', 'width' => 1896],
            ],
            'mobile' => [
                'src' => 'assets/img/hero/nutricao-mobile-640.webp',
                'sources' => [
                    ['path' => 'assets/img/hero/nutricao-mobile-640.webp', 'width' => 640],
                ],
                'sizes' => '92vw',
                'media' => '(max-width: 576px)',
                'width' => 640,
                'height' => 800,
            ],
            'alt' => 'Nutricionista conversando com paciente e orientando plano alimentar',
            'width' => 640,
            'height' => 360,
        ],
        'metrics' => [
            ['kpi' => 'Plano', 'label' => 'Personalizado'],
            ['kpi' => 'Rotina', 'label' => 'Sustentável'],
            ['kpi' => 'Retorno', 'label' => 'Contínuo'],
        ],
    ],
    'moments' => [
        'title' => 'Nutrição para objetivos diferentes da sua rotina',
        'text' => 'Atendimento para saúde metabólica, reeducação alimentar, composição corporal e melhoria de performance com plano adaptado ao seu contexto.',
        'pills' => [
            ['icon' => 'heart-pulse', 'label' => 'Saúde metabólica'],
            ['icon' => 'clipboard2-check', 'label' => 'Reeducação alimentar'],
            ['icon' => 'activity', 'label' => 'Composição corporal'],
            ['icon' => 'egg-fried', 'label' => 'Planejamento de refeições'],
            ['icon' => 'file-medical', 'label' => 'Leitura de exames'],
            ['icon' => 'calendar2-check', 'label' => 'Acompanhamento'],
        ],
    ],
    'services' => [
        'title' => 'Serviços em nutrição',
        'text' => 'Cuidado nutricional com foco em consistência, resultados mensuráveis e orientação clara para o cotidiano.',
        'items' => [
            ['icon' => 'heart-pulse', 'title' => 'Consulta nutricional', 'text' => 'Avaliação de rotina, hábitos, sinais clínicos, histórico e definição do plano inicial.'],
            ['icon' => 'clipboard2-check', 'title' => 'Plano alimentar personalizado', 'text' => 'Estratégia ajustada aos seus horários, preferências, orçamento e objetivo de saúde.'],
            ['icon' => 'activity', 'title' => 'Acompanhamento de composição corporal', 'text' => 'Monitoramento de evolução com ajustes graduais para ganho de massa ou redução de gordura.'],
            ['icon' => 'capsule', 'title' => 'Nutrição para condições clínicas', 'text' => 'Orientação alimentar para diabetes, colesterol alto, hipertensão, esteatose e outras condições.'],
            ['icon' => 'file-medical', 'title' => 'Interpretação de exames', 'text' => 'Leitura nutricional de exames laboratoriais para direcionar condutas e metas.'],
            ['icon' => 'person-heart', 'title' => 'Educação alimentar', 'text' => 'Suporte para construir autonomia nas escolhas sem dependência de dietas rígidas.'],
        ],
    ],
    'how' => [
        'title' => 'Como funciona o atendimento nutricional',
        'text' => 'Um processo simples para entender seu contexto, definir metas realistas e acompanhar evolução com ajustes práticos.',
        'steps' => [
            'Você solicita o agendamento pelo formulário ou WhatsApp',
            'A equipe confirma horário e objetivo principal do acompanhamento',
            'A consulta avalia rotina, histórico, exames e comportamento alimentar',
            'O plano alimentar é montado com orientações objetivas para a semana',
            'Os retornos revisam evolução, adesão e próximos ajustes',
        ],
        'details_title' => 'Diferenciais do acompanhamento',
        'details_badge' => 'Nutrição',
        'details' => [
            'Plano adaptado à rotina real',
            'Ajustes progressivos sem radicalismo',
            'Metas claras e mensuráveis',
            'Acompanhamento com continuidade',
            'Orientação baseada em evidências',
        ],
    ],
    'structure' => [
        'title' => 'Estrutura de atendimento organizada',
        'text' => 'A experiência combina acolhimento, clareza de plano e acompanhamento frequente para manter consistência.',
        'cards' => [
            ['icon' => 'door-open', 'title' => 'Recepção orientada', 'text' => 'Chegada com confirmação de dados, objetivo de consulta e suporte para dúvidas iniciais.'],
            ['icon' => 'shield-lock', 'title' => 'Privacidade', 'text' => 'Dados de contato, hábitos e informações clínicas tratados com cuidado e acesso restrito.'],
            ['icon' => 'clipboard2-check', 'title' => 'Plano de evolução', 'text' => 'Registro das metas, orientações práticas e próximos passos para acompanhamento.'],
        ],
    ],
    'cta' => [
        'title' => 'Quer começar seu acompanhamento nutricional?',
        'text' => 'Envie seus dados e objetivo principal. A equipe retorna para confirmar o melhor horário e as orientações para a primeira consulta.',
        'note' => 'Atendimento eletivo. Em caso de urgência clínica, procure avaliação médica presencial imediata.',
    ],
    'form' => [
        'title' => 'Solicite seu agendamento em nutrição',
        'text' => 'Informe seus contatos e o objetivo principal do acompanhamento para receber retorno da equipe.',
        'fields' => [
            'message_label' => 'Objetivo do acompanhamento',
            'message_placeholder' => 'Ex.: Gostaria de plano alimentar para perda de peso e controle de glicemia.',
            'optional_summary' => 'Adicionar disponibilidade de horário ou observações práticas (opcional)',
            'optional_label' => 'Horário / observações práticas',
        ],
        'errors' => [
            'message' => 'Descreva brevemente seu objetivo nutricional.',
        ],
        'privacy_note' => 'Ao enviar, você autoriza o uso dos dados informados para retorno sobre o agendamento nutricional. Informe apenas o essencial no primeiro contato.',
    ],
    'faq' => [
        'title' => 'Dúvidas frequentes sobre nutrição',
        'text' => 'Informações essenciais antes de solicitar seu agendamento.',
        'items' => [
            [
                'question' => 'A consulta é só para emagrecimento?',
                'answer' => 'Não. O atendimento também contempla ganho de massa, saúde metabólica, reeducação alimentar, performance e suporte nutricional para condições clínicas.',
            ],
            [
                'question' => 'Como é definido o plano alimentar?',
                'answer' => 'O plano considera objetivo, rotina, preferências, histórico, exames e viabilidade prática para aumentar adesão e consistência.',
            ],
            [
                'question' => 'Preciso levar exames?',
                'answer' => 'Quando possível, sim. Exames recentes, receitas em uso e histórico clínico ajudam a personalizar melhor o acompanhamento nutricional.',
            ],
            [
                'question' => 'Com que frequência acontecem os retornos?',
                'answer' => 'A frequência é definida conforme objetivo e necessidade. Em geral, os retornos acontecem a cada 15 a 45 dias para ajustes do plano.',
            ],
            [
                'question' => 'Este site substitui consulta médica?',
                'answer' => 'Não. O site facilita contato e agendamento. Diagnóstico e condutas médicas dependem de avaliação médica apropriada.',
            ],
        ],
    ],
    'footer' => [
        'label' => 'Nutrição',
        'address' => 'Atendimento nutricional com horário agendado',
        'meta' => 'Plano alimentar personalizado e acompanhamento contínuo',
        'emergency_note' => 'Atendimento eletivo. Em caso de urgência ou emergência clínica, procure pronto atendimento médico.',
    ],
    'floating_whatsapp' => [
        'aria_label' => 'Falar no WhatsApp sobre consulta nutricional',
    ],
];
