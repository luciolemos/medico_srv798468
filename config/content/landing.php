<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'Clínica Médica | Atendimento médico com hora marcada',
        'description' => 'Clínica médica com consultas, check-ups preventivos e acompanhamento de saúde com atendimento humanizado.',
        'site_name' => 'Clínica Médica',
        'locale' => 'pt_BR',
        'type' => 'website',
        'twitter_card' => 'summary_large_image',
        'image' => [
            'src' => 'assets/img/social/medico-og.webp',
            'width' => 1896,
            'height' => 1067,
            'alt' => 'Médica em consulta com paciente no consultório',
        ],
        'schema' => [
            'type' => 'MedicalClinic',
            'logo' => 'assets/img/clinic-mark.svg',
            'price_range' => '$$',
            'area_served' => 'Natal e região',
            'include_services' => true,
            'include_faq' => true,
        ],
    ],
    'nav' => [
        'badge' => 'Clínica médica',
        'cta' => 'Agendar',
    ],
    'hero' => [
        'badge_icon' => 'heart-pulse',
        'badge' => 'Clínica médica com atendimento humanizado',
        'title_parts' => [
            'Cuidado médico',
            'próximo e seguro',
            'para acompanhar sua saúde em cada etapa.',
        ],
        'lead' => 'Consultas clínicas, check-ups preventivos e acompanhamento contínuo com escuta atenta, agenda organizada e orientação clara para o próximo passo do cuidado.',
        'primary_cta' => [
            'label' => 'Agendar consulta',
            'href' => '#form-orcamento',
            'icon' => 'arrow-right-short',
        ],
        'secondary_cta' => [
            'label' => 'Ver atendimentos',
            'href' => '#features',
            'icon' => 'clipboard2-pulse',
        ],
        'trust_items' => [
            ['icon' => 'shield-check', 'label' => 'Prontuário seguro'],
            ['icon' => 'calendar2-check', 'label' => 'Horário agendado'],
            ['icon' => 'person-heart', 'label' => 'Escuta acolhedora'],
        ],
        'proof' => [
            'avatar' => [
                'src' => 'assets/img/avatars/face2_96.webp',
                'sources' => [
                    ['path' => 'assets/img/avatars/face2_96.webp', 'width' => 96],
                    ['path' => 'assets/img/avatars/face2_160.webp', 'width' => 160],
                ],
                'sizes' => '56px',
                'width' => 56,
                'height' => 56,
            ],
            'title' => 'Atendimento com continuidade',
            'lines' => [
                'Da primeira consulta ao retorno, a equipe orienta exames, encaminhamentos e acompanhamento.',
                'Canal direto para agendamento e confirmação.',
            ],
        ],
        'image' => [
            'src' => 'assets/img/hero/medico-640.webp',
            'sources' => [
                ['path' => 'assets/img/hero/medico-640.webp', 'width' => 640],
                ['path' => 'assets/img/hero/medico-960.webp', 'width' => 960],
                ['path' => 'assets/img/hero/medico-1896.webp', 'width' => 1896],
            ],
            'sizes' => '(max-width: 768px) 92vw, (max-width: 1200px) 44vw, 840px',
            'desktop_media' => '(min-width: 577px)',
            'mobile' => [
                'src' => 'assets/img/hero/medico-mobile-640.webp',
                'sources' => [
                    ['path' => 'assets/img/hero/medico-mobile-640.webp', 'width' => 640],
                ],
                'sizes' => '92vw',
                'media' => '(max-width: 576px)',
                'width' => 640,
                'height' => 360,
            ],
            'alt' => 'Médica em consulta com paciente no consultório',
            'width' => 640,
            'height' => 360,
        ],
        'metrics' => [
            ['kpi' => 'Seg-Sex', 'label' => 'Agenda clínica'],
            ['kpi' => '24h', 'label' => 'Confirmação'],
            ['kpi' => 'Retorno', 'label' => 'Acompanhado'],
        ],
    ],
    'moments' => [
        'title' => 'Atendimento para diferentes momentos da sua saúde',
        'text' => 'Uma clínica organizada para prevenção, diagnóstico inicial, acompanhamento e orientação médica responsável.',
        'pills' => [
            ['icon' => 'heart-pulse', 'label' => 'Clínica geral'],
            ['icon' => 'clipboard2-check', 'label' => 'Check-up preventivo'],
            ['icon' => 'activity', 'label' => 'Acompanhamento contínuo'],
            ['icon' => 'capsule', 'label' => 'Orientação terapêutica'],
            ['icon' => 'file-medical', 'label' => 'Exames e encaminhamentos'],
            ['icon' => 'calendar2-check', 'label' => 'Consulta com hora marcada'],
        ],
    ],
    'services' => [
        'title' => 'Serviços da clínica',
        'text' => 'Atendimento médico para queixas do dia a dia, prevenção e seguimento de condições que precisam de cuidado regular.',
        'items' => [
            ['icon' => 'heart-pulse', 'title' => 'Consulta clínica', 'text' => 'Avaliação de sintomas, histórico de saúde, exame físico e orientação do plano de cuidado.'],
            ['icon' => 'clipboard2-pulse', 'title' => 'Check-up preventivo', 'text' => 'Revisão de saúde, fatores de risco, solicitação de exames e leitura dos resultados em consulta.'],
            ['icon' => 'activity', 'title' => 'Acompanhamento contínuo', 'text' => 'Seguimento de pressão alta, diabetes, alterações metabólicas e outras condições crônicas.'],
            ['icon' => 'person-heart', 'title' => 'Saúde da família', 'text' => 'Cuidado para adultos e idosos com comunicação clara para pacientes e familiares.'],
            ['icon' => 'capsule', 'title' => 'Revisão de medicações', 'text' => 'Conferência de uso, horários, interações relatadas e adesão ao tratamento prescrito.'],
            ['icon' => 'hospital', 'title' => 'Encaminhamentos', 'text' => 'Orientação para especialistas, exames complementares e retorno com os resultados.'],
        ],
    ],
    'how' => [
        'title' => 'Como funciona o atendimento',
        'text' => 'Um fluxo simples para reduzir espera, organizar informações e deixar claro o próximo passo.',
        'steps' => [
            'Você solicita o agendamento pelo formulário ou WhatsApp',
            'A equipe confirma horário, dados básicos e motivo da consulta',
            'A consulta é realizada com escuta, exame e orientação médica',
            'Exames, receitas e encaminhamentos são organizados quando necessários',
            'O retorno acompanha resultados e evolução do cuidado',
        ],
        'details_title' => 'Diferenciais no atendimento',
        'details_badge' => 'Clínica',
        'details' => [
            'Agenda com confirmação prévia',
            'Orientação clara antes e depois da consulta',
            'Ambiente reservado e acolhedor',
            'Histórico clínico organizado com segurança',
            'Encaminhamento responsável quando necessário',
        ],
    ],
    'structure' => [
        'title' => 'Estrutura pensada para o paciente',
        'text' => 'A experiência de atendimento combina acolhimento, organização e segurança das informações.',
        'cards' => [
            ['icon' => 'door-open', 'title' => 'Recepção organizada', 'text' => 'Chegada orientada, confirmação de dados e suporte para dúvidas antes da consulta.'],
            ['icon' => 'shield-lock', 'title' => 'Privacidade', 'text' => 'Dados de contato e informações clínicas tratados com cuidado e acesso restrito.'],
            ['icon' => 'clipboard2-check', 'title' => 'Plano de cuidado', 'text' => 'Registro das orientações, pedidos de exame e próximos passos para acompanhamento.'],
        ],
    ],
    'cta' => [
        'title' => 'Precisa marcar uma consulta?',
        'text' => 'Envie seus dados e a equipe retorna para confirmar o melhor horário disponível.',
        'primary_label' => 'Solicitar agendamento',
        'secondary_label' => 'Falar no WhatsApp',
        'note' => 'Em caso de urgência ou emergência, procure o pronto atendimento mais próximo.',
    ],
    'form' => [
        'title' => 'Solicite seu agendamento',
        'text' => 'Informe seus contatos e o motivo da consulta para receber retorno da equipe.',
        'steps' => ['1. Dados', '2. Consulta'],
        'fields' => [
            'name_label' => 'Nome completo',
            'phone_label' => 'Telefone / WhatsApp',
            'phone_placeholder' => '(84) 99999-9999',
            'email_label' => 'Email',
            'message_label' => 'Motivo da consulta',
            'message_placeholder' => 'Ex.: Gostaria de agendar uma consulta clínica na próxima semana.',
            'optional_summary' => 'Adicionar convênio ou observações (opcional)',
            'optional_label' => 'Convênio / observações',
        ],
        'errors' => [
            'name' => 'Informe seu nome.',
            'phone' => 'Informe um telefone válido para contato.',
            'email' => 'Informe um email válido para retorno.',
            'message' => 'Descreva brevemente sua necessidade.',
        ],
        'buttons' => [
            'previous' => 'Voltar',
            'next' => 'Próximo',
            'submit' => 'Enviar solicitação',
        ],
    ],
    'faq' => [
        'title' => 'Dúvidas frequentes',
        'text' => 'Informações essenciais antes de solicitar seu agendamento.',
        'items' => [
            [
                'question' => 'Quais atendimentos a clínica realiza?',
                'answer' => 'A clínica realiza consultas médicas, check-ups preventivos, revisão de exames, acompanhamento de condições crônicas e encaminhamentos quando necessário.',
            ],
            [
                'question' => 'Como confirmo meu horário?',
                'answer' => 'Após o envio do formulário, a equipe entra em contato por WhatsApp ou email para confirmar disponibilidade, horário e orientações de chegada.',
            ],
            [
                'question' => 'Devo levar exames anteriores?',
                'answer' => 'Sim, quando possível. Exames, receitas em uso e relatórios anteriores ajudam o médico a entender seu histórico com mais precisão.',
            ],
            [
                'question' => 'Atende convênio?',
                'answer' => 'Informe seu convênio no campo de observações. A equipe confirma cobertura, disponibilidade e condições de atendimento no retorno.',
            ],
            [
                'question' => 'Este site substitui uma consulta médica?',
                'answer' => 'Não. O site facilita contato e agendamento. Orientações, diagnóstico e tratamento dependem de avaliação médica adequada.',
            ],
        ],
    ],
    'footer' => [
        'label' => 'Clínica médica',
        'address' => 'Atendimento presencial com horário agendado',
        'meta' => 'Consultas, check-ups e acompanhamento clínico',
        'emergency_note' => 'Atendimento eletivo. Em caso de urgência ou emergência, procure o pronto atendimento mais próximo.',
    ],
    'floating_whatsapp' => [
        'label' => 'WhatsApp',
        'aria_label' => 'Falar no WhatsApp',
    ],
];
