<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'Clínica Odontológica | Atendimento odontológico com hora marcada',
        'description' => 'Odontologia com avaliação, prevenção, estética dental e acompanhamento com comunicação clara e agenda organizada.',
        'site_name' => 'Clínica Odontológica',
        'image' => [
            'src' => 'assets/img/social/odontologia-og.jpg',
            'width' => 1200,
            'height' => 630,
            'alt' => 'Atendimento odontológico em consultório moderno',
        ],
        'schema' => [
            'type' => 'Dentist',
            'logo' => 'assets/img/odontologia-mark.svg',
            'area_served' => 'Natal e região',
            'include_services' => true,
            'include_faq' => true,
        ],
    ],
    'nav' => [
        'badge' => 'Odontologia',
        'cta' => 'Agendar',
    ],
    'typography' => [
        'profile' => 'premium',
    ],
    'hero' => [
        'badge_icon' => 'stars',
        'badge' => 'Odontologia moderna com atendimento preciso',
        'title_parts' => [
            'Cuidado odontológico',
            'limpo e planejado',
            'para preservar saúde, função e estética do sorriso.',
        ],
        'lead' => 'Avaliação odontológica, prevenção, estética dental e acompanhamento com orientação clara, agenda organizada e condutas indicadas de forma responsável.',
        'primary_cta' => [
            'label' => 'Agendar avaliação',
            'href' => '#cta',
            'icon' => 'arrow-right-short',
        ],
        'secondary_cta' => [
            'label' => 'Ver tratamentos',
            'href' => '#features',
            'icon' => 'clipboard2-pulse',
        ],
        'trust_items' => [
            ['icon' => 'shield-check', 'label' => 'Biossegurança'],
            ['icon' => 'calendar2-check', 'label' => 'Horário marcado'],
            ['icon' => 'stars', 'label' => 'Planejamento estético'],
        ],
        'proof' => [
            'title' => 'Plano odontológico com clareza',
            'lines' => [
                'Da avaliação ao retorno, a equipe organiza diagnóstico, etapas do tratamento e orientações de manutenção.',
                'Canal direto para agendamento, confirmação e acompanhamento.',
            ],
        ],
        'image' => [
            'src' => 'assets/img/hero/odontologia-640.webp',
            'sources' => [
                ['path' => 'assets/img/hero/odontologia-640.webp', 'width' => 640],
                ['path' => 'assets/img/hero/odontologia-960.webp', 'width' => 960],
                ['path' => 'assets/img/hero/odontologia-1896.webp', 'width' => 1896],
            ],
            'mobile' => [
                'src' => 'assets/img/hero/odontologia-mobile-640.webp',
                'sources' => [
                    ['path' => 'assets/img/hero/odontologia-mobile-640.webp', 'width' => 640],
                ],
                'sizes' => '92vw',
                'media' => '(max-width: 576px)',
                'width' => 640,
                'height' => 800,
            ],
            'alt' => 'Dentista conversando com paciente em consultório odontológico',
            'width' => 640,
            'height' => 360,
        ],
        'metrics' => [
            ['kpi' => 'Plano', 'label' => 'Tratamento'],
            ['kpi' => 'Prevenção', 'label' => 'Manutenção'],
            ['kpi' => 'Sorriso', 'label' => 'Estética'],
        ],
    ],
    'moments' => [
        'title' => 'Atendimento para saúde e estética do sorriso',
        'text' => 'Uma rotina organizada para avaliação, prevenção, tratamento e manutenção odontológica.',
        'pills' => [
            ['icon' => 'search-heart', 'label' => 'Avaliação odontológica'],
            ['icon' => 'shield-check', 'label' => 'Prevenção'],
            ['icon' => 'stars', 'label' => 'Estética dental'],
            ['icon' => 'patch-check', 'label' => 'Restaurações'],
            ['icon' => 'clipboard2-check', 'label' => 'Plano de tratamento'],
            ['icon' => 'calendar2-check', 'label' => 'Consulta agendada'],
        ],
    ],
    'services' => [
        'title' => 'Serviços odontológicos',
        'text' => 'Atendimento para prevenção, diagnóstico inicial, estética dental e acompanhamento de tratamentos.',
        'items' => [
            ['icon' => 'search-heart', 'title' => 'Avaliação odontológica', 'text' => 'Análise clínica, histórico, queixas principais e orientação sobre próximos passos do cuidado.'],
            ['icon' => 'shield-check', 'title' => 'Prevenção e profilaxia', 'text' => 'Rotina de limpeza, orientação de higiene oral e acompanhamento preventivo.'],
            ['icon' => 'stars', 'title' => 'Estética dental', 'text' => 'Avaliação de sorriso, cor, forma e possibilidades estéticas conforme indicação profissional.'],
            ['icon' => 'patch-check', 'title' => 'Restaurações', 'text' => 'Condutas restauradoras para recuperar estrutura dental, conforto e função.'],
            ['icon' => 'clipboard2-check', 'title' => 'Plano de tratamento', 'text' => 'Organização das etapas, prioridades, retornos e encaminhamentos quando necessários.'],
            ['icon' => 'bandaid', 'title' => 'Dor e urgências eletivas', 'text' => 'Triagem de dor, sensibilidade, fraturas e situações que exigem avaliação odontológica breve.'],
        ],
    ],
    'how' => [
        'title' => 'Como funciona o atendimento odontológico',
        'text' => 'Um fluxo objetivo para avaliar, planejar e acompanhar o tratamento com previsibilidade.',
        'steps' => [
            'Você solicita o agendamento pelo formulário ou WhatsApp',
            'A equipe confirma horário, dados básicos e motivo da consulta',
            'A avaliação identifica queixas, histórico, prioridades e necessidades clínicas',
            'O plano de tratamento organiza etapas, retornos e orientações de manutenção',
            'O acompanhamento revisa evolução, conforto e resultados alcançados',
        ],
        'details_title' => 'Diferenciais no cuidado odontológico',
        'details_badge' => 'Odontologia',
        'details' => [
            'Agenda com confirmação prévia',
            'Ambiente limpo e biosseguro',
            'Plano de tratamento explicado com clareza',
            'Orientação preventiva para manutenção',
            'Encaminhamento responsável quando necessário',
        ],
    ],
    'structure' => [
        'title' => 'Estrutura pensada para atendimento odontológico',
        'text' => 'A experiência combina organização, privacidade e cuidado técnico em cada etapa da consulta.',
        'cards' => [
            ['icon' => 'door-open', 'title' => 'Recepção organizada', 'text' => 'Chegada orientada, confirmação de dados e suporte para dúvidas antes da consulta.'],
            ['icon' => 'shield-lock', 'title' => 'Biossegurança e privacidade', 'text' => 'Informações e ambiente de atendimento tratados com critério e acesso restrito.'],
            ['icon' => 'clipboard2-check', 'title' => 'Plano documentado', 'text' => 'Registro das orientações, etapas sugeridas e próximos retornos de acompanhamento.'],
        ],
    ],
    'cta' => [
        'title' => 'Quer agendar uma avaliação odontológica?',
        'text' => 'Preencha com seus contatos e a principal necessidade odontológica. A equipe retorna para alinhar disponibilidade, horário e orientação inicial.',
        'primary_label' => 'Solicitar agendamento',
        'secondary_label' => 'Falar no WhatsApp',
        'helper_points' => [
            ['icon' => 'clock-history', 'label' => 'Retorno para alinhar horário e prioridade'],
            ['icon' => 'shield-lock', 'label' => 'Contato simples e sem exposição desnecessária'],
            ['icon' => 'clipboard2-check', 'label' => 'Avaliação antes do plano definitivo'],
        ],
        'note' => 'Em caso de urgência intensa, trauma ou sangramento importante, procure atendimento odontológico de urgência.',
    ],
    'form' => [
        'title' => 'Solicite seu agendamento odontológico',
        'text' => 'Você não precisa explicar tudo agora. Informe seus contatos e o motivo principal da consulta para que a equipe organize o primeiro retorno.',
        'helper_points' => [
            [
                'icon' => 'chat-square-text',
                'title' => 'Primeiro contato objetivo',
                'text' => 'Basta indicar sua principal necessidade para a equipe iniciar o agendamento com clareza.',
            ],
            [
                'icon' => 'calendar2-check',
                'title' => 'Triagem antes da consulta',
                'text' => 'O retorno serve para alinhar horário, prioridade e orientar o que levar no dia.',
            ],
            [
                'icon' => 'shield-lock',
                'title' => 'Menos atrito no envio',
                'text' => 'Evite detalhes clínicos extensos agora. O essencial já ajuda a organizar o atendimento.',
            ],
        ],
        'fields' => [
            'name_label' => 'Nome completo',
            'phone_label' => 'Telefone / WhatsApp',
            'email_label' => 'Email',
            'message_label' => 'Motivo da consulta',
            'message_placeholder' => 'Ex.: Gostaria de agendar avaliação para limpeza e estética dental.',
            'optional_summary' => 'Adicionar convênio, preferência de horário ou observações práticas (opcional)',
            'optional_label' => 'Convênio / horário / observações práticas',
        ],
        'errors' => [
            'name' => 'Informe seu nome.',
            'phone' => 'Informe um telefone válido para contato.',
            'email' => 'Informe um email válido.',
            'message' => 'Descreva brevemente sua necessidade odontológica.',
        ],
        'privacy_note' => 'Ao enviar, você autoriza o uso dos dados informados para retorno sobre o agendamento odontológico. Use este espaço apenas para o necessário no primeiro contato e deixe detalhes clínicos para a avaliação.',
    ],
    'faq' => [
        'title' => 'Dúvidas frequentes sobre odontologia',
        'text' => 'Informações essenciais antes de solicitar seu agendamento.',
        'items' => [
            [
                'question' => 'Quais atendimentos odontológicos são realizados?',
                'answer' => 'A clínica realiza avaliação odontológica, prevenção, profilaxia, restaurações, orientação estética, revisão de exames e encaminhamentos quando necessário.',
            ],
            [
                'question' => 'Como é definido o plano de tratamento?',
                'answer' => 'O plano depende da avaliação clínica. A equipe organiza prioridades, etapas, retornos e orientações de manutenção após a consulta.',
            ],
            [
                'question' => 'Devo levar exames ou radiografias anteriores?',
                'answer' => 'Sim, quando possível. Exames, radiografias e histórico de tratamentos ajudam o dentista a avaliar o caso com mais precisão.',
            ],
            [
                'question' => 'Atende convênio?',
                'answer' => 'Informe seu convênio no campo de observações. A equipe confirma cobertura, disponibilidade e condições de atendimento no retorno.',
            ],
            [
                'question' => 'Este site substitui uma consulta odontológica?',
                'answer' => 'Não. O site facilita contato e agendamento. Diagnóstico e tratamento dependem de avaliação odontológica adequada.',
            ],
        ],
    ],
    'footer' => [
        'label' => 'Odontologia',
        'address' => 'Atendimento odontológico com horário agendado',
        'meta' => 'Avaliação, prevenção, estética dental e acompanhamento odontológico',
        'emergency_note' => 'Atendimento eletivo. Em caso de dor intensa, trauma, sangramento importante ou sinais de gravidade, procure atendimento odontológico de urgência.',
    ],
    'floating_whatsapp' => [
        'label' => 'WhatsApp',
        'aria_label' => 'Falar no WhatsApp sobre atendimento odontológico',
    ],
];
