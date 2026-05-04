<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'Clínica Veterinária | Consulta veterinária com hora marcada',
        'description' => 'Clínica veterinária com consultas, vacinação, check-ups e acompanhamento com orientação clara para tutores.',
        'site_name' => 'Clínica Veterinária',
        'image' => [
            'src' => 'assets/img/social/veterinaria-og.jpg',
            'width' => 1200,
            'height' => 630,
            'alt' => 'Atendimento veterinário acolhedor com tutor',
        ],
        'schema' => [
            'type' => 'VeterinaryCare',
            'area_served' => 'Natal e região',
            'include_services' => true,
            'include_faq' => true,
        ],
    ],
    'nav' => [
        'badge' => 'Veterinária',
        'cta' => 'Agendar',
    ],
    'typography' => [
        'profile' => 'warm',
    ],
    'hero' => [
        'badge_icon' => 'suit-heart',
        'badge' => 'Cuidado veterinário com acolhimento para tutores',
        'title_parts' => [
            'Atendimento veterinário',
            'próximo e cuidadoso',
            'para acompanhar saúde, prevenção e rotina.',
        ],
        'lead' => 'Consultas veterinárias, vacinação, check-ups e acompanhamento com escuta atenta, agenda organizada e orientação clara para tutores.',
        'primary_cta' => [
            'label' => 'Agendar consulta',
            'href' => '#form-orcamento',
            'icon' => 'arrow-right-short',
        ],
        'secondary_cta' => [
            'label' => 'Ver cuidados',
            'href' => '#features',
            'icon' => 'clipboard2-heart',
        ],
        'trust_items' => [
            ['icon' => 'shield-check', 'label' => 'Ambiente seguro'],
            ['icon' => 'calendar-heart', 'label' => 'Consulta agendada'],
            ['icon' => 'people', 'label' => 'Tutor orientado'],
        ],
        'proof' => [
            'title' => 'Acompanhamento com vínculo',
            'lines' => [
                'Da primeira consulta ao retorno, a equipe orienta vacinas, exames, rotina e sinais de alerta.',
                'Canal direto para agendamento, confirmação e orientação inicial.',
            ],
        ],
        'image' => [
            'src' => 'assets/img/hero/veterinaria-640.webp',
            'sources' => [
                ['path' => 'assets/img/hero/veterinaria-640.webp', 'width' => 640],
                ['path' => 'assets/img/hero/veterinaria-960.webp', 'width' => 960],
                ['path' => 'assets/img/hero/veterinaria-1896.webp', 'width' => 1896],
            ],
            'mobile' => [
                'src' => 'assets/img/hero/veterinaria-mobile-640.webp',
                'sources' => [
                    ['path' => 'assets/img/hero/veterinaria-mobile-640.webp', 'width' => 640],
                ],
                'sizes' => '92vw',
                'media' => '(max-width: 576px)',
                'width' => 640,
                'height' => 800,
            ],
            'alt' => 'Veterinária conversando com tutor durante atendimento',
            'width' => 640,
            'height' => 360,
        ],
        'metrics' => [
            ['kpi' => 'Vacinas', 'label' => 'Prevenção'],
            ['kpi' => 'Check-up', 'label' => 'Rotina'],
            ['kpi' => 'Tutor', 'label' => 'Orientado'],
        ],
    ],
    'moments' => [
        'title' => 'Cuidado veterinário para prevenção e acompanhamento',
        'text' => 'Atendimento organizado para rotina, vacinação, sintomas, exames e orientação aos tutores.',
        'pills' => [
            ['icon' => 'heart-pulse', 'label' => 'Consulta clínica'],
            ['icon' => 'shield-check', 'label' => 'Vacinação'],
            ['icon' => 'activity', 'label' => 'Check-up'],
            ['icon' => 'clipboard2-heart', 'label' => 'Acompanhamento'],
            ['icon' => 'file-medical', 'label' => 'Exames'],
            ['icon' => 'calendar-heart', 'label' => 'Consulta agendada'],
        ],
    ],
    'services' => [
        'title' => 'Serviços veterinários',
        'text' => 'Rotina de cuidado veterinário com prevenção, avaliação clínica e orientação clara para tutores.',
        'items' => [
            ['icon' => 'heart-pulse', 'title' => 'Consulta veterinária', 'text' => 'Avaliação clínica, histórico, exame físico e orientação dos próximos cuidados.'],
            ['icon' => 'shield-check', 'title' => 'Vacinação e prevenção', 'text' => 'Orientação sobre calendário vacinal, vermifugação e medidas preventivas conforme avaliação.'],
            ['icon' => 'activity', 'title' => 'Check-up de rotina', 'text' => 'Acompanhamento preventivo com avaliação de saúde, exames e sinais que merecem atenção.'],
            ['icon' => 'capsule', 'title' => 'Orientação terapêutica', 'text' => 'Revisão de prescrições, cuidados em casa, retornos e sinais de alerta.'],
            ['icon' => 'file-medical', 'title' => 'Exames e encaminhamentos', 'text' => 'Solicitação, leitura de resultados e encaminhamento responsável quando necessário.'],
            ['icon' => 'people', 'title' => 'Apoio ao tutor', 'text' => 'Comunicação clara para dúvidas frequentes, rotina, alimentação e próximos passos.'],
        ],
    ],
    'how' => [
        'title' => 'Como funciona a consulta veterinária',
        'text' => 'Um fluxo simples para entender a necessidade, reduzir espera e orientar o tutor com clareza.',
        'steps' => [
            'O tutor solicita o agendamento pelo formulário ou WhatsApp',
            'A equipe confirma horário, dados básicos e motivo da consulta',
            'A consulta avalia histórico, sinais relatados, rotina e exame físico',
            'Vacinas, exames, medicamentos e cuidados em casa são orientados quando necessários',
            'O retorno acompanha evolução, resultados e próximos cuidados',
        ],
        'details_title' => 'Diferenciais no atendimento veterinário',
        'details_badge' => 'Veterinária',
        'details' => [
            'Agenda com confirmação prévia',
            'Orientação clara para tutores',
            'Ambiente acolhedor e organizado',
            'Histórico de cuidado registrado com segurança',
            'Encaminhamento responsável quando necessário',
        ],
    ],
    'structure' => [
        'title' => 'Estrutura acolhedora para atendimento veterinário',
        'text' => 'A experiência combina organização, privacidade e comunicação simples para tutores.',
        'cards' => [
            ['icon' => 'door-open', 'title' => 'Recepção orientada', 'text' => 'Chegada com confirmação de dados, motivo da consulta e suporte para dúvidas iniciais.'],
            ['icon' => 'shield-lock', 'title' => 'Privacidade e segurança', 'text' => 'Dados de contato e informações clínicas tratados com cuidado e acesso restrito.'],
            ['icon' => 'clipboard2-check', 'title' => 'Plano de cuidado', 'text' => 'Registro das orientações, pedidos de exame, vacinas e próximos passos de acompanhamento.'],
        ],
    ],
    'cta' => [
        'title' => 'Precisa marcar uma consulta veterinária?',
        'text' => 'Preencha com seus dados, o nome do pet e a principal necessidade. A equipe retorna para alinhar disponibilidade e orientação inicial.',
        'primary_label' => 'Solicitar agendamento',
        'secondary_label' => 'Falar no WhatsApp',
        'helper_points' => [
            ['icon' => 'clock-history', 'label' => 'Retorno para alinhar horário e espécie'],
            ['icon' => 'shield-lock', 'label' => 'Contato simples para tutor e pet'],
            ['icon' => 'clipboard2-check', 'label' => 'Orientação inicial antes da consulta'],
        ],
        'note' => 'Em caso de urgência veterinária, procure atendimento emergencial.',
    ],
    'form' => [
        'title' => 'Solicite o agendamento veterinário',
        'text' => 'Você pode informar só o essencial agora: dados do tutor, contato e motivo principal da consulta do pet.',
        'helper_points' => [
            [
                'icon' => 'chat-square-text',
                'title' => 'Primeiro contato direto',
                'text' => 'Basta indicar tutor, contato e necessidade principal para a equipe organizar o retorno.',
            ],
            [
                'icon' => 'calendar2-check',
                'title' => 'Agendamento com contexto',
                'text' => 'A equipe confirma espécie, faixa etária e o melhor horário antes da consulta.',
            ],
            [
                'icon' => 'shield-lock',
                'title' => 'Menos atrito no envio',
                'text' => 'Evite detalhar o caso inteiro no formulário. O básico já ajuda a direcionar o atendimento.',
            ],
        ],
        'fields' => [
            'name_label' => 'Nome do tutor',
            'phone_label' => 'Telefone / WhatsApp',
            'email_label' => 'Email',
            'message_label' => 'Motivo da consulta',
            'message_placeholder' => 'Ex.: Gostaria de agendar consulta para vacinação e check-up.',
            'optional_summary' => 'Adicionar nome do pet, idade, espécie ou observações práticas (opcional)',
            'optional_label' => 'Pet / idade / espécie / observações práticas',
        ],
        'errors' => [
            'name' => 'Informe o nome do tutor.',
            'phone' => 'Informe um telefone válido para retorno.',
            'email' => 'Informe um email válido.',
            'message' => 'Descreva brevemente a necessidade do atendimento.',
        ],
        'privacy_note' => 'Ao enviar, você autoriza o uso dos dados informados para retorno sobre o agendamento veterinário. Informe apenas o necessário no primeiro contato e detalhe o caso completo no atendimento.',
    ],
    'faq' => [
        'title' => 'Dúvidas frequentes sobre atendimento veterinário',
        'text' => 'Informações essenciais antes de solicitar o agendamento.',
        'items' => [
            [
                'question' => 'Quais atendimentos veterinários são realizados?',
                'answer' => 'A clínica realiza consultas veterinárias, vacinação, check-ups, orientação preventiva, revisão de exames e encaminhamentos quando necessário.',
            ],
            [
                'question' => 'Como confirmo o horário da consulta?',
                'answer' => 'Após o envio do formulário, a equipe entra em contato por WhatsApp ou email para confirmar disponibilidade, horário e orientações de chegada.',
            ],
            [
                'question' => 'Devo levar carteira de vacinação e exames?',
                'answer' => 'Sim, quando possível. Carteira de vacinação, exames anteriores, receitas em uso e histórico ajudam a equipe a entender melhor o caso.',
            ],
            [
                'question' => 'Atende urgência veterinária?',
                'answer' => 'O site organiza solicitações de contato e agendamento. Em caso de urgência, falta de ar, sangramento, convulsão, trauma ou piora rápida, procure atendimento emergencial.',
            ],
            [
                'question' => 'Este site substitui consulta veterinária?',
                'answer' => 'Não. O site facilita contato e agendamento. Diagnóstico, tratamento e orientação clínica dependem de avaliação veterinária adequada.',
            ],
        ],
    ],
    'footer' => [
        'label' => 'Veterinária',
        'address' => 'Atendimento veterinário com horário agendado',
        'meta' => 'Consultas, vacinação, check-ups e orientação para tutores',
        'emergency_note' => 'Atendimento eletivo. Em caso de falta de ar, sangramento, convulsão, trauma, ingestão de tóxicos ou sinais de gravidade, procure atendimento veterinário emergencial.',
    ],
    'floating_whatsapp' => [
        'label' => 'WhatsApp',
        'aria_label' => 'Falar no WhatsApp sobre consulta veterinária',
    ],
];
