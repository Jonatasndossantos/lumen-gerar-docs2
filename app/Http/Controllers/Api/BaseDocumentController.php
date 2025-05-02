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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])
            ->timeout(60) // Aumenta o timeout para 60 segundos
            ->retry(3, 1000) // Tenta 3 vezes com delay de 1 segundo
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => env('OPENAI_MODEL', 'gpt-3.5-turbo'), // Usa GPT-3.5 por padrão
                'messages' => [
                    ['role' => 'system', 'content' => 'Você é um assistente de geração de documentos públicos brasileiros.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.4,
                'max_tokens' => 2000,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI API Error: ' . $response->body());
                throw new \Exception('Erro ao comunicar com a OpenAI: ' . $response->body());
            }

            $content = trim($response->json('choices.0.message.content'));
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON Parse Error: ' . json_last_error_msg());
                throw new \Exception('Erro ao interpretar o JSON: ' . json_last_error_msg());
            }

            // Salva no cache
            Cache::put($cacheKey, $data, $this->cacheTime);

            return $data;
        } catch (\Exception $e) {
            Log::error('Error in generateAiData: ' . $e->getMessage());
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

        switch ($type) {
            case 'institutional':
                return $this->buildInstitutionalPrompt($municipality, $institution, $address, $objectDescription, $date);
            
            case 'etp':
                return $this->buildETPPrompt($objectDescription, $request->input('valor', '350.000,00'));
            
            case 'tr':
                return $this->buildTRPrompt($objectDescription, $request->input('valor', '350.000,00'));
            
            case 'demanda':
                return $this->buildDemandaPrompt($objectDescription, $request->input('valor', '350.000,00'));
            
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

    protected function buildETPPrompt($descricao, $valor): string
    {
        return <<<PROMPT
            Gere um JSON para Estudo Técnico Preliminar com:

            - Objeto: {$descricao}
            - Valor: R\$ {$valor}

            Retorne os dados no formato JSON com os seguintes campos:
            {
                "etp_objeto": "<descrição detalhada do objeto>",
                "etp_justificativa": "<justificativa técnica e legal>",
                "etp_plano_contratacao": "<plano de contratação>",
                "etp_requisitos_linguagens": "<linguagens de programação necessárias>",
                "etp_requisitos_banco": "<requisitos de banco de dados>",
                "etp_requisitos_api": "<requisitos de API>",
                "etp_experiencia_publica": "<experiência com setor público>",
                "etp_prazo_execucao": "<prazo estimado em meses>",
                "etp_forma_pagamento": "<forma de pagamento>",
                "etp_criterios_selecao": "<critérios de seleção>",
                "etp_estimativa_quantidades": "<quantidades estimadas>",
                "etp_alternativa_a": "<primeira alternativa>",
                "etp_alternativa_b": "<segunda alternativa>",
                "etp_alternativa_c": "<terceira alternativa>",
                "etp_analise_comparativa": "<análise comparativa das alternativas>",
                "etp_estimativa_precos": "<estimativa de preços>",
                "etp_solucao_total": "<solução total proposta>",
                "etp_parcelamento": "<possibilidade de parcelamento>",
                "etp_resultados_esperados": "<resultados esperados>",
                "etp_providencias_previas": "<providências prévias>",
                "etp_contratacoes_correlatas": "<contratações correlatas>",
                "etp_impactos_ambientais": "<impactos ambientais>",
                "etp_viabilidade_contratacao": "<viabilidade da contratação>",
                "cidade_maiusculo": "<nome da cidade em maiúsculas>",
                "data_extenso": "<data por extenso>",
                "nome_autoridade": "<nome da autoridade>",
                "cargo_autoridade": "<cargo da autoridade>",
                "endereco": "<endereço>",
                "cidade": "<nome da cidade>",
                "cep": "<CEP>"
            }

            Instruções:
            - Seja específico e técnico
            - Use linguagem formal
            - Apenas retorne o JSON
            PROMPT;
    }

    protected function buildTRPrompt($descricao, $valor): string
    {
        return <<<PROMPT
            Gere um JSON para Termo de Referência com:

            - Objeto: {$descricao}
            - Valor: R\$ {$valor}

            Retorne os dados no formato JSON com os seguintes campos:
            {
                "descricao_tecnica": "<descrição técnica detalhada>",
                "justificativa_demanda": "<justificativa da necessidade>",
                "base_legal": "<base legal da contratação>",
                "normas_aplicaveis": "<normas aplicáveis>",
                "execucao_etapas": "<etapas de execução>",
                "tolerancia_tecnica": "<tolerância técnica>",
                "materiais_sustentaveis": "<materiais sustentáveis>",
                "execucao_similar": "<execução similar>",
                "certificacoes": "<certificações necessárias>",
                "pgr_pcmso": "<PGR e PCMSO>",
                "criterio_julgamento": "<critério de julgamento>",
                "garantia_qualidade": "<garantia de qualidade>",
                "painel_fiscalizacao": "<painel de fiscalização>",
                "kpis_operacionais": "<KPIs operacionais>",
                "designacao_formal_fiscal": "<designação formal do fiscal>",
                "penalidades": "<penalidades>",
                "alertas_ia": "<alertas de IA>",
                "anexos_obrigatorios": "<anexos obrigatórios>",
                "transparencia_resumo": "<resumo para transparência>",
                "faq_juridico": "<FAQ jurídico>",
                "assinatura_formato": "<formato de assinatura>",
                "prazo_publicacao": "<prazo de publicação>",
                "endereco": "<endereço>",
                "cidade": "<nome da cidade>",
                "cep": "<CEP>"
            }

            Instruções:
            - Seja específico e técnico
            - Use linguagem formal
            - Apenas retorne o JSON
            PROMPT;
    }

    protected function buildDemandaPrompt($descricao, $valor): string
    {
        return <<<PROMPT
            Gere um JSON para Documento de Formalização de Demanda:

            - Objeto: {$descricao}
            - Valor: R\$ {$valor}

            Retorne os dados no formato JSON com os seguintes campos:
            {
                "setor": "<setor solicitante>",
                "departamento": "<departamento>",
                "responsavel": "<responsável pela demanda>",
                "descricaoObjeto": "<descrição do objeto>",
                "valor": "<valor estimado>",
                "origem_fonte": "<origem da fonte>",
                "unidade_nome": "<nome da unidade>",
                "justificativa": "<justificativa da demanda>",
                "impacto_meta": "<impacto na meta>",
                "criterio": "<critério de seleção>",
                "priorizacao_justificativa": "<justificativa da priorização>",
                "escopo": "<escopo do projeto>",
                "requisitos_tecnicos": "<requisitos técnicos>",
                "riscos_ocupacionais": "<riscos ocupacionais>",
                "riscos_normas": "<normas de risco>",
                "riscos_justificativa": "<justificativa dos riscos>",
                "alternativa_a": "<primeira alternativa>",
                "alternativa_b": "<segunda alternativa>",
                "alternativa_conclusao": "<conclusão das alternativas>",
                "inerciarisco": "<risco de inércia>",
                "inerciaplano": "<plano de inércia>",
                "prazo_execucao": "<prazo de execução>",
                "forma_pagamento": "<forma de pagamento>",
                "prazo_vigencia": "<prazo de vigência>",
                "condicoes_pagamento": "<condições de pagamento>",
                "ods_vinculados": "<ODS vinculados>",
                "acao_sustentavel": "<ação sustentável>",
                "ia_duplicidade": "<verificação de duplicidade>",
                "ia_validacao": "<validação>",
                "transparencia_resumo": "<resumo para transparência>",
                "transparencia_faq": "<FAQ para transparência>",
                "transparencia_prazo": "<prazo de transparência>",
                "assinatura_formato": "<formato de assinatura>",
                "cidade_maiusculo": "<nome da cidade em maiúsculas>",
                "data_extenso": "<data por extenso>",
                "nome_autoridade": "<nome da autoridade>",
                "cargo_autoridade": "<cargo da autoridade>",
                "endereco": "<endereço>",
                "cidade": "<nome da cidade>",
                "cep": "<CEP>"
            }

            Instruções:
            - Seja específico e técnico
            - Use linguagem formal
            - Apenas retorne o JSON
            PROMPT;
    }

    protected function buildRiscoPrompt($descricao): string
    {
        return <<<PROMPT
            Gere uma matriz de risco JSON com:

            - Objeto: {$descricao}

            Retorne os dados no formato JSON com a seguinte estrutura:
            {
                "processo_administrativo": "<número do processo>",
                "objeto_matriz": "<objeto da matriz>",
                "data_inicio_contratacao": "<data de início>",
                "unidade_responsavel": "<unidade responsável>",
                "fase_analise": "<fase de análise>",
                "endereco": "<endereço>",
                "cidade": "<nome da cidade>",
                "cep": "<CEP>",
                "riscos": [
                    {
                        "seq": "<número sequencial>",
                        "evento": "<descrição do evento de risco>",
                        "dano": "<descrição do dano>",
                        "impacto": "<impacto do risco>",
                        "probabilidade": "<probabilidade de ocorrência>",
                        "acao_preventiva": "<ação preventiva>",
                        "responsavel_preventiva": "<responsável pela ação preventiva>",
                        "acao_contingencia": "<ação de contingência>",
                        "responsavel_contingencia": "<responsável pela ação de contingência>"
                    }
                ]
            }

            Instruções:
            - Liste pelo menos 5 riscos relevantes
            - Seja específico e técnico
            - Use linguagem formal
            - Apenas retorne o JSON
            PROMPT;
    }

    public function setInstitutionalData($templateProcessor, Request $request)
    {
        try {
            $data = $this->generateAiData('institutional', $request);
            
            // Preenche os dados básicos
            $templateProcessor->setValue('cidade', $data['cidade']);
            $templateProcessor->setValue('cidade_maiusculo', strtoupper($data['cidade']));
            $templateProcessor->setValue('endereco', $data['endereco']);
            $templateProcessor->setValue('cep', $data['cep']);
            $templateProcessor->setValue('nome_autoridade', $data['nome_autoridade']);
            $templateProcessor->setValue('cargo_autoridade', $data['cargo_autoridade']);
            $templateProcessor->setValue('data_extenso', $data['data_extenso']);

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
