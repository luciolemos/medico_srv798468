<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'Clínica Pediátrica | Consulta infantil com hora marcada',
        'description' => 'Pediatria com consultas infantis, puericultura, acompanhamento do desenvolvimento e orientação clara para responsáveis.',
        'site_name' => 'Clínica Pediátrica',
        'image' => [
            'src' => 'assets/img/social/pediatria-og.jpg',
            'width' => 1200,
            'height' => 630,
            'alt' => 'Pediatra em atendimento acolhedor com criança e responsável',
        ],
        'schema' => [
            'type' => 'MedicalClinic',
            'logo' => 'assets/img/pediatria-mark.svg',
            'area_served' => 'Natal e região',
            'include_services' => true,
            'include_faq' => true,
        ],
    ],
    'nav' => [
        'badge' => 'Pediatria',
        'cta' => 'Agendar',
    ],
    'typography' => [
        'profile' => 'family',
    ],
    'hero' => [
        'badge_icon' => 'emoji-smile',
        'badge' => 'Pediatria com escuta para a família',
        'title_parts' => [
            'Cuidado pediátrico',
            'leve e seguro',
            'para acompanhar cada fase da infância.',
        ],
        'lead' => 'Consultas pediátricas, puericultura e acompanhamento do desenvolvimento com orientação clara para responsáveis, rotina organizada e acolhimento desde o primeiro contato.',
        'primary_cta' => [
            'label' => 'Agendar consulta',
            'href' => '#cta',
            'icon' => 'arrow-right-short',
        ],
        'secondary_cta' => [
            'label' => 'Ver cuidados',
            'href' => '#features',
            'icon' => 'clipboard2-heart',
        ],
        'trust_items' => [
            ['icon' => 'shield-check', 'label' => 'Ambiente seguro'],
            ['icon' => 'calendar2-check', 'label' => 'Consulta agendada'],
            ['icon' => 'people', 'label' => 'Família orientada'],
        ],
        'proof' => [
            'title' => 'Acompanhamento com vínculo',
            'lines' => [
                'Da primeira consulta aos retornos, a equipe orienta vacinas, exames, rotina e sinais de alerta.',
                'Canal direto para agendamento e confirmação com responsáveis.',
            ],
        ],
        'image' => [
            'src' => 'assets/img/hero/pediatria-640.webp',
            'sources' => [
                ['path' => 'assets/img/hero/pediatria-640.webp', 'width' => 640],
                ['path' => 'assets/img/hero/pediatria-960.webp', 'width' => 960],
                ['path' => 'assets/img/hero/pediatria-1896.webp', 'width' => 1896],
            ],
            'mobile' => [
                'src' => 'assets/img/hero/pediatria-mobile-640.webp',
                'sources' => [
                    ['path' => 'assets/img/hero/pediatria-mobile-640.webp', 'width' => 640],
                ],
                'sizes' => '92vw',
                'media' => '(max-width: 576px)',
                'width' => 640,
                'height' => 800,
            ],
            'alt' => 'Pediatra conversando com criança e responsável no consultório',
            'width' => 640,
            'height' => 360,
        ],
        'metrics' => [
            ['kpi' => '0-12', 'label' => 'Infância acompanhada'],
            ['kpi' => 'Rotina', 'label' => 'Puericultura'],
            ['kpi' => 'Família', 'label' => 'Orientada'],
        ],
    ],
    'moments' => [
        'title' => 'Cuidado para cada fase da infância',
        'text' => 'Atendimento organizado para prevenção, desenvolvimento, queixas do dia a dia e orientação aos responsáveis.',
        'pills' => [
            ['icon' => 'emoji-smile', 'label' => 'Primeira infância'],
            ['icon' => 'clipboard2-check', 'label' => 'Puericultura'],
            ['icon' => 'activity', 'label' => 'Desenvolvimento'],
            ['icon' => 'capsule', 'label' => 'Orientação terapêutica'],
            ['icon' => 'file-medical', 'label' => 'Vacinas e exames'],
            ['icon' => 'calendar2-check', 'label' => 'Consulta agendada'],
        ],
    ],
    'services' => [
        'title' => 'Serviços pediátricos',
        'text' => 'Rotina de cuidado infantil com prevenção, acompanhamento do crescimento e orientação para responsáveis.',
        'items' => [
            ['icon' => 'heart-pulse', 'title' => 'Consulta pediátrica', 'text' => 'Avaliação de sintomas, histórico, exame físico e orientação do cuidado infantil.'],
            ['icon' => 'clipboard2-check', 'title' => 'Puericultura', 'text' => 'Acompanhamento de crescimento, desenvolvimento, alimentação, sono e rotina.'],
            ['icon' => 'activity', 'title' => 'Acompanhamento do desenvolvimento', 'text' => 'Observação de marcos motores, linguagem, comportamento e necessidades de encaminhamento.'],
            ['icon' => 'capsule', 'title' => 'Orientação terapêutica', 'text' => 'Revisão de prescrições, cuidados em casa e sinais de alerta conforme avaliação médica.'],
            ['icon' => 'file-medical', 'title' => 'Vacinas e exames', 'text' => 'Orientação sobre calendário vacinal, exames solicitados e retorno com resultados.'],
            ['icon' => 'people', 'title' => 'Apoio aos responsáveis', 'text' => 'Comunicação clara para dúvidas frequentes e organização dos próximos passos.'],
        ],
    ],
    'how' => [
        'title' => 'Como funciona a consulta pediátrica',
        'text' => 'Um fluxo simples para receber informações importantes, reduzir espera e orientar a família com clareza.',
        'steps' => [
            'O responsável solicita o agendamento pelo formulário ou WhatsApp',
            'A equipe confirma dados da criança, idade e motivo da consulta',
            'A consulta avalia sintomas, rotina, desenvolvimento e histórico',
            'Receitas, exames, vacinas e cuidados em casa são orientados quando necessários',
            'O retorno acompanha evolução, resultados e próximos cuidados',
        ],
        'details_title' => 'Diferenciais para famílias',
        'details_badge' => 'Pediatria',
        'details' => [
            'Agenda com confirmação prévia',
            'Orientação clara para responsáveis',
            'Ambiente acolhedor para crianças',
            'Histórico infantil organizado com segurança',
            'Encaminhamento responsável quando necessário',
        ],
    ],
    'structure' => [
        'title' => 'Estrutura acolhedora para crianças e responsáveis',
        'text' => 'A experiência de atendimento combina organização, privacidade e comunicação simples para a família.',
        'cards' => [
            ['icon' => 'door-open', 'title' => 'Recepção orientada', 'text' => 'Chegada com confirmação de dados, idade da criança e suporte para dúvidas iniciais.'],
            ['icon' => 'shield-lock', 'title' => 'Privacidade familiar', 'text' => 'Dados de contato e informações clínicas tratados com cuidado e acesso restrito.'],
            ['icon' => 'clipboard2-check', 'title' => 'Plano de cuidado infantil', 'text' => 'Registro das orientações, pedidos de exame, vacinas e próximos passos de acompanhamento.'],
        ],
    ],
    'cta' => [
        'title' => 'Precisa marcar consulta para uma criança?',
        'text' => 'Preencha com os dados do responsável e a principal necessidade da criança. A equipe retorna para alinhar horário, idade e orientações iniciais.',
        'primary_label' => 'Solicitar agendamento',
        'secondary_label' => 'Falar no WhatsApp',
        'helper_points' => [
            ['icon' => 'clock-history', 'label' => 'Retorno para alinhar idade e horário'],
            ['icon' => 'shield-lock', 'label' => 'Contato simples para a família'],
            ['icon' => 'clipboard2-check', 'label' => 'Orientação inicial antes da consulta'],
        ],
        'note' => 'Em caso de urgência pediátrica, procure o pronto atendimento mais próximo.',
    ],
    'form' => [
        'title' => 'Solicite o agendamento pediátrico',
        'text' => 'Você pode informar só o essencial agora: dados do responsável, contato e o motivo principal da consulta infantil.',
        'helper_points' => [
            [
                'icon' => 'chat-square-text',
                'title' => 'Primeiro contato descomplicado',
                'text' => 'Basta enviar os dados do responsável e a necessidade principal da criança.',
            ],
            [
                'icon' => 'calendar2-check',
                'title' => 'Agendamento mais orientado',
                'text' => 'A equipe confirma idade, motivo da consulta e o melhor encaixe de horário.',
            ],
            [
                'icon' => 'shield-lock',
                'title' => 'Menos exposição',
                'text' => 'Evite relatar detalhes clínicos extensos no formulário. O básico já é suficiente para começar.',
            ],
        ],
        'fields' => [
            'name_label' => 'Nome do responsável',
            'phone_label' => 'Telefone / WhatsApp',
            'email_label' => 'Email',
            'message_label' => 'Motivo da consulta',
            'message_placeholder' => 'Ex.: Gostaria de agendar consulta para meu filho de 4 anos.',
            'optional_summary' => 'Adicionar idade da criança, convênio ou observações práticas (opcional)',
            'optional_label' => 'Idade / convênio / observações práticas',
        ],
        'errors' => [
            'name' => 'Informe o nome do responsável.',
            'phone' => 'Informe um telefone válido para retorno.',
            'email' => 'Informe um email válido.',
            'message' => 'Descreva brevemente a necessidade da criança.',
        ],
        'privacy_note' => 'Ao enviar, você autoriza o uso dos dados informados para retorno sobre o agendamento pediátrico. Informe apenas o necessário no primeiro contato e deixe detalhes clínicos para a consulta.',
    ],
    'faq' => [
        'title' => 'Dúvidas frequentes sobre pediatria',
        'text' => 'Informações essenciais antes de solicitar o agendamento infantil.',
        'items' => [
            [
                'question' => 'Quais atendimentos pediátricos são realizados?',
                'answer' => 'A clínica realiza consultas pediátricas, puericultura, orientação sobre desenvolvimento, revisão de exames, vacinas e encaminhamentos quando necessário.',
            ],
            [
                'question' => 'Como confirmo o horário da criança?',
                'answer' => 'Após o envio do formulário, a equipe entra em contato com o responsável por WhatsApp ou email para confirmar disponibilidade, horário e orientações de chegada.',
            ],
            [
                'question' => 'Devo levar caderneta de vacinação e exames?',
                'answer' => 'Sim, quando possível. Caderneta, exames anteriores, receitas em uso e relatórios ajudam o pediatra a entender melhor o histórico da criança.',
            ],
            [
                'question' => 'Atende convênio?',
                'answer' => 'Informe o convênio no campo de observações. A equipe confirma cobertura, disponibilidade e condições de atendimento no retorno.',
            ],
            [
                'question' => 'Este site substitui atendimento médico?',
                'answer' => 'Não. O site facilita contato e agendamento. Diagnóstico, tratamento e orientação clínica dependem de avaliação pediátrica adequada.',
            ],
        ],
    ],
    'footer' => [
        'label' => 'Pediatria',
        'address' => 'Atendimento pediátrico com horário agendado',
        'meta' => 'Consultas infantis, puericultura e orientação para responsáveis',
        'emergency_note' => 'Atendimento eletivo. Em caso de urgência, febre persistente, falta de ar, sonolência intensa ou sinais de gravidade, procure pronto atendimento pediátrico.',
    ],
    'floating_whatsapp' => [
        'label' => 'WhatsApp',
        'aria_label' => 'Falar no WhatsApp sobre consulta pediátrica',
    ],
];
