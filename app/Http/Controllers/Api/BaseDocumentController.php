<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use PhpOffice\PhpWord\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BaseDocumentController extends Controller
{
    protected $templatesPath;
    protected $cacheTime = 3600; // 1 hora
    
    public function __construct()
    {
        $this->templatesPath = public_path('templates');
        Settings::setOutputEscapingEnabled(true);
        
        // Aumenta o timeout do PHP para 300 segundos
        set_time_limit(300);
    }

    public function generateAiData(string $type, Request $request): array
    {
        // Gera uma chave única para o cache baseada no tipo e nos dados da requisição
        $cacheKey = $this->generateCacheKey($type, $request);
        
        // Tenta recuperar do cache primeiro
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $apiKey = config('services.openai.key');
        $prompt = $this->buildPrompt($type, $request);

        try {
            \Log::info('Enviando prompt para IA:', ['type' => $type, 'prompt' => $prompt]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])
            ->timeout(120)
            ->retry(3, 2000, function ($exception, $request) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException ||
                       $exception instanceof \Illuminate\Http\Client\TimeoutException;
            })
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => env('OPENAI_MODEL', 'gpt-4-turbo'),
                'messages' => [
                    ['role' => 'system', 'content' => 'Você é um assistente de geração de documentos públicos brasileiros. Retorne APENAS JSON válido, sem texto adicional. O JSON deve ser um objeto com chaves e valores, onde todos os valores são strings.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 2000,
            ]);

            if (!$response->successful()) {
                \Log::error('OpenAI API Error: ' . $response->body());
                throw new \Exception('Erro ao comunicar com a OpenAI: ' . $response->body());
            }

            $content = trim($response->json('choices.0.message.content'));
            \Log::info('Resposta bruta da IA:', ['content' => $content]);
            
            // Remove possíveis caracteres não-JSON do início e fim
            $content = preg_replace('/^[^{]*/', '', $content);
            $content = preg_replace('/[^}]*$/', '', $content);
            
            // Tenta fazer o parse do JSON
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                \Log::error('JSON Parse Error: ' . json_last_error_msg());
                \Log::error('Conteúdo recebido: ' . $content);
                throw new \Exception('Erro ao interpretar o JSON: ' . json_last_error_msg());
            }

            // Valida se o JSON tem a estrutura esperada
            if (!is_array($data)) {
                throw new \Exception('O JSON retornado não é um array válido');
            }

            // Para matriz de risco, garante a estrutura correta
            if ($type === 'risco') {
                if (!isset($data['data'])) {
                    $data = ['data' => $data];
                }
                if (!isset($data['data']['riscos'])) {
                    throw new \Exception('Estrutura de riscos não encontrada na resposta da IA');
                }
                \Log::info('Dados de risco processados:', ['data' => $data]);
            }

            // Salva no cache
            Cache::put($cacheKey, $data, $this->cacheTime);

            return $data;
        } catch (\Exception $e) {
            \Log::error('Error in generateAiData: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function generateCacheKey(string $type, Request $request): string
    {
        // Cria uma chave única baseada no tipo e nos dados relevantes da requisição
        $relevantData = [
            'type' => $type,
            'municipality' => $request->input('municipality'),
            'institution' => $request->input('institution'),
            'objectDescription' => $request->input('objectDescription'),
            'valor' => $request->input('valor'),
        ];
        
        return 'ai_data_' . md5(json_encode($relevantData));
    }

    protected function buildPrompt(string $type, Request $request): string
    {
        $municipality = $request->input('municipality');
        $institution = $request->input('institution');
        $address = $request->input('address');
        $objectDescription = $request->input('objectDescription');
        $date = now()->format('d \d\e F \d\e Y');

        // Adicionar logs
        \Log::info('Dados recebidos no BaseDocumentController:', [
            'municipality' => $municipality,
            'institution' => $institution,
            'address' => $address,
            'objectDescription' => $objectDescription,
            'date' => $date
        ]);

        switch ($type) {
            case 'institutional':
                return $this->buildInstitutionalPrompt($municipality, $institution, $address, $objectDescription, $date);
            
            case 'etp':
                return $this->buildETPPrompt($objectDescription, $request->input('valor', '00'));
            
            case 'tr':
                return $this->buildTRPrompt($objectDescription, $request->input('valor', '00'));
            
            case 'demanda':
                return $this->buildDemandaPrompt($objectDescription, $request->input('valor', '00'));
            
            case 'risco':
                return $this->buildRiscoPrompt($objectDescription);
            
            default:
                throw new \InvalidArgumentException("Tipo de documento inválido: {$type}");
        }
    }

    protected function buildInstitutionalPrompt($municipality, $institution, $address, $objectDescription, $date): string
    {
        return <<<PROMPT
            Gere os dados institucionais para preencher documentos oficiais de um município brasileiro, considerando as seguintes informações fornecidas:

            - Município: {$municipality}
            - Instituição: {$institution}
            - Endereço: {$address}
            - Descrição do objeto da contratação: {$objectDescription}
            - Data atual: {$date}

            Retorne os dados exclusivamente no formato JSON, obedecendo exatamente esta estrutura:

            {
            "cidade": "<nome do município>",
            "cidade_maiusculo": "<nome do município em letras maiúsculas>",
            "endereco": "<endereço sem a cidade>",
            "cep": "<CEP do município>",
            "nome_autoridade": "<nome do principal representante legal da instituição>",
            "cargo_autoridade": "<cargo do representante>",
            "data_extenso": "<data por extenso, ex: '26 de abril de 2025'>"
            "data_aprovacao": "<data por extenso, ex: '26 de abril de 2025'>"
            }

            Instruções importantes:
            - O endereço deve ser o informado, complementado se necessário para realismo
            - O nome da autoridade pode ser fictício, mas típico (ex: Maria Souza, João Silva)
            - O cargo deve ser condizente com a instituição (ex: Prefeito Municipal, Secretário de Administração)
            - Não adicione textos explicativos
            - Não adicione comentários
            - Apenas o JSON puro como resposta
            PROMPT;
    }

    protected function buildDemandaPrompt($descricao, $valor): string
    {
        return <<<PROMPT
            Você é um assistente especializado em licitações públicas e contratos administrativos, com profundo conhecimento da Lei nº 14.133/2021.

            Sua tarefa é gerar um JSON estruturado e técnico que represente um Documento de Formalização de Demanda (DFD) com todos os dados necessários para instruir uma contratação pública.

            ---

            Importante:

            - *Não utilize valores simulados ou fictícios.*
            - Se alguma informação não estiver disponível, insira o caractere "", "-" ou "" (campo vazio), para indicar que deverá ser preenchido manualmente.
            - *Não invente dados para "completar" o documento.*
            - Todos os campos devem estar presentes no JSON, mesmo que sem valor.

            ---

            Requisitos:

            - Utilize linguagem formal, precisa e técnica.
            - Utilize termos da administração pública e retorne os dados no formato JSON.
            - As informações devem estar completas, compatíveis com práticas de órgãos públicos e com foco em justificar tecnicamente a demanda.

            ---

            Gere um JSON para Documento de Formalização de Demanda:

            - Objeto: {$descricao}
            - Valor: R\$ {$valor}

            Retorne os dados no formato JSON com os seguintes campos:
            {
                "setor": "<setor solicitante>",
                "departamento": "<departamento responsável>",
                "responsavel": "<nome do responsável pela demanda>",
                "descricaoObjeto": "<descrição técnica e funcional do objeto>",
                "valor": "<valor estimado da contratação ou "–">",
                "origem_fonte": "<origem orçamentária (ex: PPA, LOA) ou "–">",
                "unidade_nome": "<nome da unidade solicitante>",
                "justificativa": "<justificativa formal e fundamentada>",
                "impacto_meta": "<impacto na meta institucional, PME ou ODS>",
                "criterio": "<critério de julgamento conforme art. 33 da Lei nº 14.133/2021>",
                "priorizacao_justificativa": "<por que a demanda é prioritária>",
                "escopo": "<detalhamento do escopo funcional e técnico>",
                "requisitos_tecnicos": "<requisitos funcionais, de segurança e usabilidade>",
                "riscos_ocupacionais": "<riscos à saúde, segurança e ambiente de trabalho ou "–">",
                "riscos_normas": "<normas aplicáveis e riscos envolvidos>",
                "riscos_justificativa": "<justificativa técnica sobre os riscos>",
                "alternativa_a": "<alternativa analisada>",
                "alternativa_b": "<segunda alternativa analisada>",
                "alternativa_conclusao": "<análise comparativa das alternativas>",
                "inerciarisco": "<risco de inércia administrativa ou omissão>",
                "inerciaplano": "<plano de resposta em caso de inércia>",
                "prazo_execucao": "<prazo total previsto de execução ou "–">",
                "forma_pagamento": "<forma de pagamento prevista ou "–">",
                "prazo_vigencia": "<vigência contratual prevista ou "–">",
                "condicoes_pagamento": "<condições específicas de pagamento ou "–">",
                "ods_vinculados": "<vínculo com Objetivos do Desenvolvimento Sustentável>",
                "acao_sustentavel": "<ação ambiental ou socioeconômica envolvida>",
                "ia_duplicidade": "<verificação de duplicidade por IA ou "–">",
                "ia_validacao": "<validação por IA do objeto ou planejamento ou "–">",
                "transparencia_resumo": "<resumo claro para publicação pública>",
                "transparencia_faq": "<FAQ institucional sobre o objeto ou contratação>",
                "transparencia_prazo": "<prazo de publicação em portal ou "–">",
                "assinatura_formato": "<formato exigido: assinatura digital ICP-Brasil, etc.>"
            }

            Instruções:
            - Seja específico e técnico
            - Use linguagem formal
            - Apenas retorne o JSON
            PROMPT;
    }

    protected function buildETPPrompt($descricao, $valor): string
    {
        return <<<PROMPT
            Você é um assistente especializado em contratações públicas com base na Lei nº 14.133/2021, IN SEGES nº 05/2017 e nº 65/2021.

            Sua tarefa é gerar um JSON **estruturado e válido** com todos os campos necessários para compor um Estudo Técnico Preliminar (ETP), compatível com o modelo institucional adotado pela Administração Pública.

            ---

            Objeto da contratação: {$descricao}  
            Valor estimado: R\$ {$valor}

            ---

            Diretrizes:
            - Use linguagem formal, precisa e técnica.
            - Fundamente as justificativas conforme os princípios da legalidade, eficiência, economicidade, interesse público e inovação.
            - Todos os campos devem estar presentes, mesmo que estejam vazios. Use `""` ou `"–"` para indicar ausência de valor.
            - Retorne apenas o JSON puro (sem crases, markdown ou explicações adicionais).
            - O JSON deve estar **100% válido** e pronto para ser interpretado por máquina.

            ---

            Retorne os dados no formato JSON com os seguintes campos:
            {
                "etp_objeto": "<descrição detalhada do objeto>",
                "etp_justificativa": "<justificativa técnica e legal>",
                "etp_plano_contratacao": "<previsão no Plano de Contratação Anual>",
                "etp_requisitos_linguagens": "<linguagens de programação e frameworks necessários>",
                "etp_requisitos_banco": "<requisitos de banco de dados e armazenamento>",
                "etp_requisitos_api": "<requisitos para integração via APIs>",
                "etp_experiencia_publica": "<exigência de experiência anterior com projetos públicos>",
                "etp_prazo_execucao": "<prazo estimado de execução em meses>",
                "etp_forma_pagamento": "<forma de pagamento (ex: por etapa, por entrega)>",
                "etp_criterios_selecao": "<critérios técnicos e legais de julgamento da proposta>",
                "etp_estimativa_quantidades": "<quantidades estimadas para contratação>",
                "etp_alternativa_a": "<alternativa 1 analisada>",
                "etp_alternativa_b": "<alternativa 2 analisada>",
                "etp_alternativa_c": "<alternativa 3 analisada>",
                "etp_analise_comparativa": "<análise técnica e comparativa das alternativas>",
                "etp_estimativa_precos": "<fundamentação do valor estimado da contratação>",
                "etp_solucao_total": "<descrição detalhada da solução escolhida>",
                "etp_parcelamento": "<avaliação e justificativa sobre possibilidade de parcelamento>",
                "etp_resultados_esperados": "<descrição dos resultados qualitativos e quantitativos esperados>",
                "etp_providencias_previas": "<ações anteriores à contratação (como reuniões, consultas técnicas, pareceres)>",
                "etp_contratacoes_correlatas": "<existência de contratos similares no órgão>",
                "etp_impactos_ambientais": "<impactos ambientais diretos ou indiretos>",
                "etp_viabilidade_contratacao": "<conclusão técnica sobre a viabilidade da contratação>",
                "etp_previsao_dotacao": "<previsão de dotação orçamentária e programa orçamentário vinculado>",
                "etp_plano_implantacao": "<fases e cronograma de implantação da solução>",
                "etp_conformidade_lgpd": "<medidas de conformidade com a LGPD>",
                "etp_riscos_tecnicos": "<riscos técnicos envolvidos na contratação>",
                "etp_riscos_mitigacao": "<estratégias de mitigação dos riscos identificados>",
                "etp_beneficios_qualitativos": "<benefícios não mensuráveis diretamente em reais, como melhoria na transparência, atendimento ao cidadão, automação>"
            }

            PROMPT;
    }

    protected function buildTRPrompt($descricao, $valor): string
    {
        return <<<PROMPT
            Você é um assistente especializado em contratações públicas conforme a Lei nº 14.133/2021, e sua tarefa é gerar um JSON técnico e completo que represente um Termo de Referência (TR) com base no template institucional da Administração Pública.

            ---

            **Objeto**: {$descricao}

            **Valor estimado**: R\$ {$valor}

            ---

            Retorne os dados no formato JSON com os seguintes campos:
            {
                "descricao_tecnica": "<descrição técnica detalhada do objeto, com base em especificações funcionais e normativas>",
                "justificativa_demanda": "<justificativa da necessidade pública, com base em demanda funcional, eficiência ou conformidade legal>",
                "base_legal": "<base jurídica da contratação, incluindo artigos da Lei nº 14.133/2021>",
                "normas_aplicaveis": "<normas técnicas da ABNT, NRs, IN SEGES, ou outras específicas ao objeto>",

                "execucao_etapas": "<etapas operacionais de execução: planejamento, implantação, testes, entrega, etc.>",
                "tolerancia_tecnica": "<limites técnicos permitidos ou variações aceitáveis>",
                "materiais_sustentaveis": "<uso de materiais recicláveis, reutilizáveis ou certificados ambientalmente>",

                "cronograma_execucao": "<prazo total e cronograma físico de execução, com fases e entregas>",

                "execucao_similar": "<exigência de comprovação de execução anterior similar>",
                "certificacoes": "<certificações obrigatórias, como CREA, ISO, NR10, NR35 etc.>",
                "pgr_pcmso": "<obrigações relativas à segurança do trabalho e saúde ocupacional>",

                "criterio_julgamento": "<critério previsto no art. 33 da Lei nº 14.133/2021 (ex: menor preço, técnica e preço, etc.)>",
                "garantia_qualidade": "<como será assegurada a qualidade do serviço (ex: testes, indicadores, validações)>",

                "painel_fiscalizacao": "<como será feita a fiscalização (checklists, relatórios, registros fotográficos)>",
                "kpis_operacionais": "<indicadores-chave de desempenho aplicáveis à execução contratual>",
                "validacao_kpis": "<forma de medição e validação dos KPIs: relatórios, sistema, indicadores auditáveis>",
                "designacao_formal_fiscal": "<nome ou cargo do fiscal e menção à portaria de designação>",

                "penalidades": "<sanções previstas conforme o art. 156 da Lei nº 14.133/2021>",
                "alertas_ia": "<eventuais alertas automáticos sobre duplicidade de escopo, conflito com planejamento ou risco regulatório>",

                "anexos_obrigatorios": "<lista de documentos obrigatórios como planilha de custos, cronograma físico-financeiro, projeto executivo, memorial descritivo, matriz de risco>",
                
                "transparencia_resumo": "<resumo claro e acessível do objeto para publicação no Portal da Transparência>",
                "faq_juridico": "<respostas a perguntas frequentes relacionadas à legalidade da contratação>",
                "prazo_publicacao": "<número de dias úteis para publicação do contrato no Portal da Transparência>"
                "transparencia_contato": "<canal de atendimento ao cidadão: e-mail, telefone ou formulário eletrônico>",
                
                "assinatura_formato": "<formato exigido para assinatura (ex: assinatura digital com ICP-Brasil, carimbo do tempo)>",
                "nome_elaborador": "<nome do responsável técnico pela elaboração>",
                "cargo_elaborador": "<cargo do responsável técnico>",
                "nome_autoridade_aprovacao": "<nome da autoridade competente que aprova o TR>",
                "cargo_autoridade_aprovacao": "<cargo da autoridade competente>"
            }

            Instruções obrigatórias:
            Preencha todos os campos, mesmo que com "" ou "–" quando não houver informação.

            Use linguagem técnica e formal, como se fosse um parecer emitido por equipe de planejamento e engenharia.

            Fundamente tudo com base na Lei nº 14.133/2021, IN SEGES nº 5/2017 e boas práticas administrativas.

            Evite jargões vagos como "melhorar o serviço" sem descrição técnica clara.

            Inclua os anexos necessários para assegurar a completude do documento.
                            
            Preencha todos os campos. Onde não houver valor real, insira "–", "", ou "A preencher". Nunca deixe campos faltando ou remova campos do JSON.

            Instruções:
            - Seja específico e técnico
            - Use linguagem formal
            - Apenas retorne o JSON
            PROMPT;
    }   

    protected function buildRiscoPrompt($descricao): string
    {
        return <<<PROMPT
            Você é um especialista em contratações públicas e gestão de riscos, com base na Lei nº 14.133/2021.

            Sua tarefa é gerar uma matriz de risco no formato JSON, com base no objeto da contratação informado, contendo:

            - Dados gerais do processo
            - Lista de ao menos 5 riscos relevantes
            - Classificação formal e específica de impacto e probabilidade
            - Ações preventivas e contingenciais bem detalhadas
            - Indicação de responsáveis

            ---

            Objeto da contratação: {$descricao}

            Retorne os dados no formato JSON conforme estrutura abaixo:

            {
            "processo_administrativo": "<número do processo>",
            "objeto_matriz": "<descrição completa do objeto>",
            "data_inicio_contratacao": "<DD/MM/AAAA>",
            "unidade_responsavel": "<nome da unidade ou secretaria>",
            "fase_analise": "<fase da contratação (ex: planejamento, instrução, execução)>",
            "riscos": [
                {
                    "seq": "1",
                    "evento": "<evento de risco específico e descritivo>",
                    "dano": "<consequência direta ou prejuízo administrativo>",
                    "impacto": "<baixo | médio | alto>",
                    "probabilidade": "<baixa | média | alta>",
                    "acao_preventiva": "<ação clara para evitar a ocorrência do risco>",
                    "responsavel_preventiva": "<responsável técnico ou unidade gestora>",
                    "acao_contingencia": "<ação a ser executada se o risco ocorrer>",
                    "responsavel_contingencia": "<responsável pela mitigação pós-evento>"
                }
            ]
            }

            ---

            ⚠️ Instruções adicionais:

            - Liste pelo menos **5 riscos reais e prováveis** ao tipo de contratação.
            - Considere **pelo menos um risco de descumprimento contratual** e **um relacionado à LGPD**, se o objeto envolver dados pessoais.
            - Use **linguagem formal, técnica e precisa**.
            - As ações devem ser detalhadas (o quê, como, por quem, quando).
            - Classifique impacto e probabilidade usando somente os valores padronizados: **baixo | médio | alto**.
            - IMPORTANTE: Mantenha a estrutura exata do JSON, incluindo todos os campos para cada risco.
            - NÃO modifique a estrutura do JSON ou adicione campos extras.

            ---

            O conteúdo deve estar pronto para ser inserido automaticamente em uma tabela institucional. Seja claro e específico. Não utilize termos vagos ou genéricos.

            PROMPT;
    }

    public function setInstitutionalData($templateProcessor, Request $request)
    {
        try {
            $data = $this->generateAiData('institutional', $request);
            
            // Preenche os dados básicos
            $templateProcessor->setValue('cidade', $data['cidade']);
            $templateProcessor->setValue('cidade_maiusculo', strtoupper($data['cidade_maiusculo']));
            $templateProcessor->setValue('endereco', $data['endereco']);
            $templateProcessor->setValue('cep', $data['cep']);
            $templateProcessor->setValue('nome_autoridade', $data['nome_autoridade']);
            $templateProcessor->setValue('cargo_autoridade', $data['cargo_autoridade']);
            $templateProcessor->setValue('data_extenso', $data['data_extenso']);
            $templateProcessor->setValue('data_aprovacao', $data['data_aprovacao']);

            // Processa o brasão de forma otimizada
            $this->processBrasao($templateProcessor, $data['cidade']);
        } catch (\Exception $e) {
            Log::error('Error setting institutional data: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processBrasao($templateProcessor, $municipality)
    {
        try {
            // Normaliza o nome do município
            $filename = $this->normalizeMunicipalityName($municipality) . '.png';
            $brasaoPath = public_path('brasoes/' . $filename);

            // Verifica se o brasão específico existe
            if (!file_exists($brasaoPath)) {
                Log::info("Brasão específico não encontrado para {$municipality}, usando padrão");
                $brasaoPath = public_path('brasoes/default.png');
            }

            // Verifica se o arquivo existe antes de tentar processá-lo
            if (file_exists($brasaoPath)) {
                Log::info("Processando brasão: {$brasaoPath}");
                $templateProcessor->setImageValue('brasao', [
                    'path' => $brasaoPath,
                    'width' => 80,
                    'ratio' => true
                ]);
            } else {
                Log::warning("Nenhum brasão encontrado para {$municipality}");
            }
        } catch (\Exception $e) {
            Log::error("Erro ao processar brasão: " . $e->getMessage());
            throw $e;
        }
    }

    protected function normalizeMunicipalityName($municipality)
    {
        // Remove acentos
        $municipality = iconv('UTF-8', 'ASCII//TRANSLIT', $municipality);
        
        // Remove caracteres especiais e espaços
        $municipality = preg_replace('/[^a-zA-Z0-9]/', '', $municipality);
        
        // Converte para minúsculo
        return strtolower($municipality);
    }
} 
