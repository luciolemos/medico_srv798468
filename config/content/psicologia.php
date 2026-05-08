<?php

declare(strict_types=1);

return [
    'seo' => [
        'title' => 'Clínica de Psicologia | Atendimento psicológico com escuta qualificada',
        'description' => 'Psicologia com escuta acolhedora, atendimento individual e acompanhamento com privacidade, clareza e organização.',
        'site_name' => 'Clínica de Psicologia',
        'image' => [
            'src' => 'assets/img/social/psicologia-og.jpg',
            'width' => 1200,
            'height' => 630,
            'alt' => 'Atendimento psicológico em ambiente reservado e acolhedor',
        ],
        'schema' => [
            'type' => 'MedicalBusiness',
            'logo' => 'assets/img/psicologia-mark.svg',
            'area_served' => 'Natal e região',
            'include_services' => true,
            'include_faq' => true,
        ],
    ],
    'nav' => [
        'badge' => 'Psicologia',
        'cta' => 'Agendar',
    ],
    'typography' => [
        'profile' => 'warm',
    ],
    'hero' => [
        'badge_icon' => 'journal-heart',
        'badge' => 'Psicologia com escuta atenta, ritmo seguro e ambiente reservado',
        'title_parts' => [
            'Atendimento psicológico',
            'calmo e presente',
            'para cuidar da sua saúde emocional com mais clareza e fôlego.',
        ],
        'lead' => 'Atendimento psicológico individual com escuta qualificada, organização do cuidado e uma presença clínica acolhedora para atravessar ansiedade, sobrecarga, conflitos e processos de mudança com mais estabilidade.',
        'primary_cta' => [
            'label' => 'Marcar primeiro atendimento',
            'href' => '#form-orcamento',
            'icon' => 'arrow-right-short',
        ],
        'secondary_cta' => [
            'label' => 'Ver como funciona',
            'href' => '#how',
            'icon' => 'journal-text',
        ],
        'trust_items' => [
            ['icon' => 'shield-check', 'label' => 'Privacidade emocional'],
            ['icon' => 'calendar2-check', 'label' => 'Horário protegido'],
            ['icon' => 'journal-heart', 'label' => 'Escuta clínica'],
        ],
        'proof' => [
            'title' => 'Um espaço de fala com continuidade',
            'lines' => [
                'O atendimento respeita o seu tempo, organiza o processo com clareza e evita uma experiência apressada.',
                'Agendamento, confirmação e orientações iniciais acontecem de forma simples e discreta.',
            ],
        ],
        'image' => [
            'src' => 'assets/img/hero/psicologia-640.webp',
            'sources' => [
                ['path' => 'assets/img/hero/psicologia-640.webp', 'width' => 640],
                ['path' => 'assets/img/hero/psicologia-960.webp', 'width' => 960],
                ['path' => 'assets/img/hero/psicologia-1896.webp', 'width' => 1896],
            ],
            'mobile' => [
                'src' => 'assets/img/hero/psicologia-mobile-640.webp',
                'sources' => [
                    ['path' => 'assets/img/hero/psicologia-mobile-640.webp', 'width' => 640],
                ],
                'sizes' => '92vw',
                'media' => '(max-width: 576px)',
                'width' => 640,
                'height' => 800,
            ],
            'alt' => 'Profissional de psicologia em conversa acolhedora com paciente',
            'width' => 640,
            'height' => 360,
        ],
        'metrics' => [
            ['kpi' => 'Escuta', 'label' => 'Profunda'],
            ['kpi' => 'Ritmo', 'label' => 'Seguro'],
            ['kpi' => 'Processo', 'label' => 'Contínuo'],
        ],
    ],
    'moments' => [
        'title' => 'Psicologia para atravessar fases, vínculos e sobrecargas',
        'text' => 'Um acompanhamento que acolhe sofrimento emocional, organiza o processo terapêutico e sustenta conversas importantes com presença e respeito.',
        'pills' => [
            ['icon' => 'journal-heart', 'label' => 'Escuta clínica'],
            ['icon' => 'emoji-neutral', 'label' => 'Ansiedade e sobrecarga'],
            ['icon' => 'people', 'label' => 'Relações e limites'],
            ['icon' => 'clipboard2-check', 'label' => 'Processo terapêutico'],
            ['icon' => 'journal-text', 'label' => 'Acompanhamento contínuo'],
            ['icon' => 'calendar2-heart', 'label' => 'Horário protegido'],
        ],
    ],
    'services' => [
        'title' => 'Serviços de psicologia',
        'text' => 'Atendimento individual com escuta qualificada, organização do processo terapêutico e comunicação clara sobre o acompanhamento.',
        'items' => [
            ['icon' => 'chat-heart', 'title' => 'Atendimento psicológico individual', 'text' => 'Espaço de escuta para compreender sofrimento emocional, demandas atuais e contexto de vida.'],
            ['icon' => 'emoji-neutral', 'title' => 'Ansiedade e estresse', 'text' => 'Acompanhamento para lidar com sobrecarga, sintomas ansiosos, pressão cotidiana e regulação emocional.'],
            ['icon' => 'people', 'title' => 'Relações e vínculos', 'text' => 'Apoio para conflitos relacionais, comunicação, limites e dinâmicas familiares ou afetivas.'],
            ['icon' => 'journal-text', 'title' => 'Acompanhamento contínuo', 'text' => 'Organização de frequência, objetivos terapêuticos e revisão da evolução ao longo do processo.'],
            ['icon' => 'clipboard2-check', 'title' => 'Plano de acompanhamento', 'text' => 'Definição conjunta de foco inicial, ritmo de sessões e combinados importantes do cuidado.'],
            ['icon' => 'person-lines-fill', 'title' => 'Orientação inicial', 'text' => 'Primeiro contato para entender a demanda e indicar o melhor fluxo de atendimento disponível.'],
        ],
    ],
    'how' => [
        'title' => 'Como funciona o atendimento psicológico',
        'text' => 'Um fluxo simples para acolher sua demanda, organizar o primeiro contato e explicar como o acompanhamento acontece.',
        'steps' => [
            'Você solicita o agendamento pelo formulário ou WhatsApp',
            'A equipe confirma horário, dados básicos e necessidade inicial de atendimento',
            'A primeira sessão acolhe a demanda, contexto e expectativas do processo',
            'O acompanhamento organiza frequência, objetivos e combinados terapêuticos',
            'As sessões seguintes acompanham evolução, dificuldades e próximos passos do cuidado',
        ],
        'details_title' => 'Diferenciais no atendimento psicológico',
        'details_badge' => 'Psicologia',
        'details' => [
            'Agenda com confirmação prévia',
            'Escuta qualificada e ambiente reservado',
            'Fluxo de atendimento explicado com clareza',
            'Acompanhamento com continuidade',
            'Encaminhamento responsável quando necessário',
        ],
    ],
    'structure' => [
        'title' => 'Estrutura acolhedora e reservada',
        'text' => 'A experiência prioriza privacidade, previsibilidade e um ambiente confortável para conversas sensíveis.',
        'cards' => [
            ['icon' => 'door-open', 'title' => 'Recepção tranquila', 'text' => 'Chegada orientada, confirmação discreta de dados e suporte para dúvidas iniciais.'],
            ['icon' => 'shield-lock', 'title' => 'Privacidade emocional', 'text' => 'Dados de contato e informações compartilhadas tratados com cuidado e acesso restrito.'],
            ['icon' => 'clipboard2-check', 'title' => 'Processo organizado', 'text' => 'Frequência, orientações iniciais e próximos passos alinhados com clareza e respeito.'],
        ],
    ],
    'cta' => [
        'title' => 'Quer agendar seu primeiro atendimento psicológico?',
        'text' => 'Preencha com seus dados e a necessidade inicial. A equipe retorna para combinar horário, formato do primeiro atendimento e orientações básicas.',
        'primary_label' => 'Solicitar agendamento',
        'secondary_label' => 'Falar no WhatsApp',
        'helper_points' => [
            ['icon' => 'clock-history', 'label' => 'Retorno para combinar horário e formato inicial'],
            ['icon' => 'shield-lock', 'label' => 'Contato com discrição e privacidade'],
            ['icon' => 'journal-check', 'label' => 'Você pode explicar o restante com calma na sessão'],
        ],
        'note' => 'Em situação de crise aguda ou risco imediato, procure apoio emergencial presencial na sua região.',
    ],
    'form' => [
        'title' => 'Solicite seu agendamento em psicologia',
        'text' => 'Você não precisa contar toda a sua história aqui. Informe seus contatos e a necessidade inicial para que a equipe organize o primeiro atendimento.',
        'helper_points' => [
            [
                'icon' => 'chat-square-heart',
                'title' => 'Sem excesso no primeiro envio',
                'text' => 'Descreva só o suficiente para o contato inicial. O aprofundamento acontece no espaço terapêutico.',
            ],
            [
                'icon' => 'calendar2-check',
                'title' => 'Primeira sessão organizada',
                'text' => 'O retorno serve para alinhar disponibilidade, horário e orientações básicas do início do processo.',
            ],
            [
                'icon' => 'shield-lock',
                'title' => 'Privacidade emocional',
                'text' => 'Evite detalhes sensíveis no formulário. O essencial já basta para dar o primeiro passo.',
            ],
        ],
        'fields' => [
            'name_label' => 'Nome completo',
            'phone_label' => 'Telefone / WhatsApp',
            'email_label' => 'Email',
            'message_label' => 'Motivo do contato',
            'message_placeholder' => 'Ex.: Gostaria de iniciar acompanhamento psicológico para lidar com ansiedade e sobrecarga.',
            'optional_summary' => 'Adicionar preferência de horário ou observações práticas (opcional)',
            'optional_label' => 'Horário / observações práticas',
        ],
        'errors' => [
            'name' => 'Informe seu nome.',
            'phone' => 'Informe um telefone válido para contato.',
            'email' => 'Informe um email válido.',
            'message' => 'Descreva brevemente sua necessidade inicial.',
        ],
        'privacy_note' => 'Ao enviar, você autoriza o uso dos dados informados para retorno sobre o agendamento psicológico. Evite relatar detalhes íntimos ou sensíveis além do necessário para o primeiro contato.',
    ],
    'faq' => [
        'title' => 'Dúvidas frequentes sobre psicologia',
        'text' => 'Informações importantes antes de solicitar seu primeiro atendimento.',
        'items' => [
            [
                'question' => 'Quais demandas podem ser acolhidas no atendimento psicológico?',
                'answer' => 'O atendimento pode acolher ansiedade, estresse, sofrimento emocional, dificuldades de rotina, relações, luto, autoestima e outras demandas que precisem de escuta qualificada.',
            ],
            [
                'question' => 'Como acontece o primeiro atendimento?',
                'answer' => 'A primeira sessão busca compreender sua demanda, contexto e expectativa. A partir daí, a profissional orienta como o acompanhamento pode ser organizado.',
            ],
            [
                'question' => 'Preciso detalhar tudo no formulário?',
                'answer' => 'Não. Informe apenas o suficiente para o primeiro contato e agendamento. Questões mais sensíveis podem ser abordadas diretamente no atendimento.',
            ],
            [
                'question' => 'Como confirmo meu horário?',
                'answer' => 'Após o envio do formulário, a equipe entra em contato por WhatsApp ou email para confirmar disponibilidade, horário e orientações iniciais.',
            ],
            [
                'question' => 'Este site substitui atendimento em crise?',
                'answer' => 'Não. O site facilita contato e agendamento. Em caso de risco imediato, crise aguda ou urgência em saúde mental, procure atendimento emergencial presencial.',
            ],
        ],
    ],
    'footer' => [
        'label' => 'Psicologia',
        'address' => 'Atendimento psicológico com horário agendado',
        'meta' => 'Escuta qualificada, acompanhamento individual e cuidado emocional',
        'emergency_note' => 'Atendimento eletivo. Em caso de crise aguda, risco imediato ou urgência em saúde mental, procure atendimento emergencial presencial.',
    ],
    'floating_whatsapp' => [
        'label' => 'WhatsApp',
        'aria_label' => 'Falar no WhatsApp sobre atendimento psicológico',
    ],
];
