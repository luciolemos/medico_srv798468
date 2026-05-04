<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'Clínica de Estética | Avaliação estética com atendimento consultivo',
        'description' => 'Clínica de estética com avaliação facial e corporal, planejamento individual e acompanhamento com orientação clara e responsável.',
        'site_name' => 'Clínica de Estética',
        'image' => [
            'src' => 'assets/img/social/estetica-og.jpg',
            'width' => 1200,
            'height' => 630,
            'alt' => 'Atendimento estético em ambiente elegante e acolhedor',
        ],
        'schema' => [
            'type' => 'HealthAndBeautyBusiness',
            'area_served' => 'Natal e região',
            'include_services' => true,
            'include_faq' => true,
        ],
    ],
    'nav' => [
        'badge' => 'Estética',
        'cta' => 'Agendar',
    ],
    'typography' => [
        'profile' => 'premium',
    ],
    'hero' => [
        'badge_icon' => 'gem',
        'badge' => 'Estética autoral com avaliação sutil e personalizada',
        'title_parts' => [
            'Cuidado estético',
            'sofisticado e sutil',
            'para valorizar sua imagem com leveza e naturalidade.',
        ],
        'lead' => 'Avaliação estética facial e corporal com direção cuidadosa, planejamento individual e acompanhamento consultivo para construir resultados coerentes com seu estilo, ritmo e objetivo.',
        'primary_cta' => [
            'label' => 'Agendar avaliação',
            'href' => '#form-orcamento',
            'icon' => 'arrow-right-short',
        ],
        'secondary_cta' => [
            'label' => 'Explorar cuidados',
            'href' => '#features',
            'icon' => 'sparkles',
        ],
        'trust_items' => [
            ['icon' => 'shield-check', 'label' => 'Indicação responsável'],
            ['icon' => 'calendar2-check', 'label' => 'Agenda reservada'],
            ['icon' => 'gem', 'label' => 'Plano autoral'],
        ],
        'proof' => [
            'title' => 'Jornada estética sem excessos',
            'lines' => [
                'Cada etapa é apresentada com clareza, intenção e expectativa realista desde a primeira conversa.',
                'Agendamento, confirmação e manutenção seguem um plano consistente e discreto.',
            ],
        ],
        'image' => [
            'src' => 'assets/img/hero/estetica-640.webp',
            'sources' => [
                ['path' => 'assets/img/hero/estetica-640.webp', 'width' => 640],
                ['path' => 'assets/img/hero/estetica-960.webp', 'width' => 960],
                ['path' => 'assets/img/hero/estetica-1896.webp', 'width' => 1896],
            ],
            'mobile' => [
                'src' => 'assets/img/hero/estetica-mobile-640.webp',
                'sources' => [
                    ['path' => 'assets/img/hero/estetica-mobile-640.webp', 'width' => 640],
                ],
                'sizes' => '92vw',
                'media' => '(max-width: 576px)',
                'width' => 640,
                'height' => 800,
            ],
            'alt' => 'Profissional de estética conversando com paciente em ambiente sofisticado',
            'width' => 640,
            'height' => 360,
        ],
        'metrics' => [
            ['kpi' => 'Facial', 'label' => 'Estratégico'],
            ['kpi' => 'Corporal', 'label' => 'Personalizado'],
            ['kpi' => 'Resultado', 'label' => 'Natural'],
        ],
    ],
    'moments' => [
        'title' => 'Estética pensada para presença, proporção e continuidade',
        'text' => 'Um atendimento que combina direção visual, critério técnico e manutenção organizada para objetivos faciais e corporais.',
        'pills' => [
            ['icon' => 'gem', 'label' => 'Avaliação autoral'],
            ['icon' => 'emoji-smile', 'label' => 'Beleza sutil'],
            ['icon' => 'person-standing', 'label' => 'Leitura corporal'],
            ['icon' => 'clipboard2-check', 'label' => 'Plano sob medida'],
            ['icon' => 'droplet-half', 'label' => 'Manutenção elegante'],
            ['icon' => 'calendar2-heart', 'label' => 'Agenda reservada'],
        ],
    ],
    'services' => [
        'title' => 'Serviços de estética',
        'text' => 'Atendimento consultivo para avaliação facial e corporal, manutenção e organização do plano estético.',
        'items' => [
            ['icon' => 'stars', 'title' => 'Avaliação estética', 'text' => 'Análise de objetivos, histórico, rotina e possibilidades de cuidado com orientação responsável.'],
            ['icon' => 'emoji-smile', 'title' => 'Planejamento facial', 'text' => 'Definição de etapas para cuidado de textura, viço, contorno e harmonia conforme avaliação profissional.'],
            ['icon' => 'person-standing', 'title' => 'Planejamento corporal', 'text' => 'Organização de estratégias para queixas corporais, rotina de sessões e acompanhamento da evolução.'],
            ['icon' => 'droplet-half', 'title' => 'Cuidados de manutenção', 'text' => 'Orientação sobre intervalos, rotina domiciliar e cuidados para preservar resultados com segurança.'],
            ['icon' => 'clipboard2-check', 'title' => 'Protocolo individual', 'text' => 'Plano documentado com prioridades, sequência de atendimento e retornos sugeridos.'],
            ['icon' => 'chat-heart', 'title' => 'Acompanhamento consultivo', 'text' => 'Espaço para esclarecer dúvidas, revisar evolução e ajustar próximos passos quando necessário.'],
        ],
    ],
    'how' => [
        'title' => 'Como funciona o atendimento estético',
        'text' => 'Um fluxo objetivo para alinhar expectativas, avaliar indicações e organizar o cuidado com previsibilidade.',
        'steps' => [
            'Você solicita o agendamento pelo formulário ou WhatsApp',
            'A equipe confirma horário, objetivo principal e dados básicos de contato',
            'A avaliação identifica rotina, histórico, prioridades e possibilidades de cuidado',
            'O plano organiza etapas, frequência, orientações de manutenção e retornos',
            'O acompanhamento revisa evolução, conforto e próximos ajustes quando necessário',
        ],
        'details_title' => 'Diferenciais no cuidado estético',
        'details_badge' => 'Estética',
        'details' => [
            'Agenda com confirmação prévia',
            'Avaliação consultiva e individual',
            'Plano com etapas explicadas com clareza',
            'Orientação de manutenção entre sessões',
            'Encaminhamento responsável quando necessário',
        ],
    ],
    'structure' => [
        'title' => 'Estrutura pensada para atendimento estético',
        'text' => 'A experiência combina acolhimento, privacidade e uma atmosfera organizada para conversas mais reservadas.',
        'cards' => [
            ['icon' => 'door-open', 'title' => 'Recepção discreta', 'text' => 'Chegada orientada, confirmação de dados e suporte para dúvidas antes do atendimento.'],
            ['icon' => 'shield-lock', 'title' => 'Privacidade e cuidado', 'text' => 'Informações pessoais e registros tratados com critério e acesso restrito.'],
            ['icon' => 'clipboard2-check', 'title' => 'Plano registrado', 'text' => 'Orientações, sequência sugerida e retornos organizados para acompanhar a evolução.'],
        ],
    ],
    'cta' => [
        'title' => 'Quer agendar uma avaliação estética?',
        'text' => 'Preencha o formulário com seu objetivo principal e a equipe retorna para alinhar disponibilidade, proposta inicial e o melhor horário.',
        'primary_label' => 'Solicitar avaliação',
        'secondary_label' => 'Falar no WhatsApp',
        'helper_points' => [
            ['icon' => 'clock-history', 'label' => 'Retorno para alinhar horário e objetivo'],
            ['icon' => 'shield-lock', 'label' => 'Contato discreto e sem exposição desnecessária'],
            ['icon' => 'clipboard2-check', 'label' => 'Avaliação antes de qualquer proposta'],
        ],
        'note' => 'Atendimento consultivo e eletivo, com indicação responsável, expectativa realista e planejamento individual.',
    ],
    'form' => [
        'title' => 'Solicite seu agendamento estético',
        'text' => 'Você não precisa detalhar tudo agora. Envie seus contatos e o objetivo principal para que a equipe organize o primeiro retorno.',
        'helper_points' => [
            [
                'icon' => 'chat-square-text',
                'title' => 'Primeiro contato simples',
                'text' => 'Basta informar objetivo, telefone e email para a equipe iniciar a conversa.',
            ],
            [
                'icon' => 'person-check',
                'title' => 'Avaliação antes de decidir',
                'text' => 'A indicação do cuidado depende da avaliação, não de promessa automática no formulário.',
            ],
            [
                'icon' => 'shield-lock',
                'title' => 'Mais discrição',
                'text' => 'Evite dados sensíveis agora. O essencial é suficiente para organizar seu retorno.',
            ],
        ],
        'fields' => [
            'name_label' => 'Nome completo',
            'phone_label' => 'Telefone / WhatsApp',
            'email_label' => 'Email',
            'message_label' => 'Objetivo da avaliação',
            'message_placeholder' => 'Ex.: Gostaria de agendar uma avaliação para cuidado facial e rotina de manutenção.',
            'optional_summary' => 'Adicionar preferência de horário, rotina ou observações (opcional)',
            'optional_label' => 'Horário / rotina / observações',
        ],
        'errors' => [
            'name' => 'Informe seu nome.',
            'phone' => 'Informe um telefone válido para contato.',
            'email' => 'Informe um email válido.',
            'message' => 'Descreva brevemente seu objetivo com a avaliação.',
        ],
        'privacy_note' => 'Ao enviar, você autoriza o uso dos dados informados para retorno sobre o agendamento estético. Use este espaço apenas para o necessário no primeiro contato e deixe detalhes sensíveis para a avaliação.',
    ],
    'faq' => [
        'title' => 'Dúvidas frequentes sobre estética',
        'text' => 'Informações essenciais antes de solicitar sua avaliação.',
        'items' => [
            [
                'question' => 'Quais atendimentos estéticos são realizados?',
                'answer' => 'A clínica realiza avaliação estética, planejamento facial e corporal, acompanhamento de protocolos e orientação de manutenção conforme indicação profissional.',
            ],
            [
                'question' => 'O plano é igual para todos os pacientes?',
                'answer' => 'Não. O plano depende da avaliação, do objetivo, da rotina e da indicação responsável para cada caso. A equipe organiza etapas e expectativas com clareza.',
            ],
            [
                'question' => 'Como confirmo meu horário?',
                'answer' => 'Após o envio do formulário, a equipe entra em contato por WhatsApp ou email para confirmar disponibilidade, horário e orientações iniciais.',
            ],
            [
                'question' => 'Posso informar preferências no primeiro contato?',
                'answer' => 'Sim. Use o campo de observações para indicar objetivo principal, preferência de horário ou dúvidas iniciais. Evite compartilhar informações sensíveis desnecessárias.',
            ],
            [
                'question' => 'Este site substitui uma avaliação profissional?',
                'answer' => 'Não. O site facilita contato e agendamento. A indicação de cuidados ou procedimentos depende de avaliação adequada e responsável.',
            ],
        ],
    ],
    'footer' => [
        'label' => 'Estética',
        'address' => 'Atendimento estético com horário agendado',
        'meta' => 'Avaliação estética, planejamento individual e acompanhamento consultivo',
        'emergency_note' => 'Atendimento eletivo. Em caso de intercorrência importante, dor intensa ou sinais de gravidade, procure avaliação presencial imediata.',
    ],
    'floating_whatsapp' => [
        'label' => 'WhatsApp',
        'aria_label' => 'Falar no WhatsApp sobre avaliação estética',
    ],
];
