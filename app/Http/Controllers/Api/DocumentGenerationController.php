<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

class DocumentGenerationController extends Controller
{
    private $templatesPath;
    
    public function __construct()
    {
        $this->templatesPath = public_path('templates');
        Settings::setOutputEscapingEnabled(true);
    }

    /**
     * Generate documents based on the submitted form data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generate(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'whatsapp' => 'required|string|max:20',
            'municipality' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'objectDescription' => 'required|string|min:20',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Create documents directory if it doesn't exist
            $documentsDir = public_path('documents');
            if (!File::exists($documentsDir)) {
                File::makeDirectory($documentsDir, 0755, true);
            }

            // Generate the documents using templates
            $documents = $this->generateDocuments($request->all());
            
            return response()->json([
                'success' => true,
                'documents' => $documents
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating documents: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate documents using templates.
     *
     * @param  array  $data
     * @return array
     */
    private function generateDocuments(array $data)
    {
        $documents = [
            'guidelines' => $this->generateGuidelines($data),
            'demand' => $this->generateDemandDocument($data),
            'riskMatrix' => $this->generateRiskMatrix($data),
            'preliminaryStudy' => $this->generatePreliminaryStudy($data),
            'referenceTerms' => $this->generateReferenceTerms($data),
        ];

        return $documents;
    }

    /**
     * Generate guidelines document.
     *
     * @param  array  $data
     * @return string
     */
    private function generateGuidelines(array $data)
    {
        $templatePath = $this->templatesPath . '/guidelines_template.docx';
        $outputFilename = 'orientacoes_' . time() . '.docx';
        $outputPath = public_path('documents/' . $outputFilename);

        try {
            $templateProcessor = new TemplateProcessor($templatePath);
            
            // Replace variables in template
            $templateProcessor->setValue('name', $data['name']);
            $templateProcessor->setValue('municipality', $data['municipality']);
            $templateProcessor->setValue('institution', $data['institution']);
            $templateProcessor->setValue('date', date('d/m/Y'));
            
            $templateProcessor->saveAs($outputPath);
            
            return url('documents/' . $outputFilename);
        } catch (Exception $e) {
            throw new Exception("Error generating guidelines document: " . $e->getMessage());
        }
    }

    /**
     * Generate demand formalization document.
     *
     * @param  array  $data
     * @return string
     */
    private function generateDemandDocument(array $data)
    {
        $templatePath = $this->templatesPath . '/DFD_template.docx';
        $outputFilename = 'documento_formalizacao_demanda_' . time() . '.docx';
        $outputPath = public_path('documents/' . $outputFilename);

        try {
            $templateProcessor = new TemplateProcessor($templatePath);
            
            $data = [
                // Dados principais da demanda
                'setor' => 'Setor de Tecnologia da Informação',
                'departamento' => 'Departamento de Sistemas e Inovação',
                'responsavel' => 'Maria Clara Oliveira',
              
                'descricaoObjeto' => 'Contratação de empresa especializada em desenvolvimento de software para criação de sistema de gestão educacional.',
                'valor' => '350.000,00',
                'tipoObjeto' => 'Serviço Técnico Especializado',
                'formaContratacao' => 'Pregão Eletrônico',
              
                'unidade_orcamentaria' => '0000',
                'unidade_nome' => 'Secretaria Municipal de Administração e Planejamento',
                'atividade_codigo' => '0.000',
                'atividade_nome' => 'Manutenção das Ações da Secretaria',
                'elemento_codigo' => '0.0.0.0.00.00.00',
                'elemento_descricao' => 'Pessoa Jurídica',
                'fonte_codigo' => '00000000',
                'fonte_descricao' => 'Recursos não vinculados de impostos',
              
                'justificativa' => 'A contratação é necessária para modernizar a gestão educacional do município.',
                'especificacao_objeto' => 'Desenvolvimento de sistema com módulos de matrícula, frequência, relatórios e integração web.',
                'especificacao_justificativa' => 'A ausência de um sistema informatizado gera retrabalho e perda de dados.',
                'escopo' => '- Desenvolvimento web\n- Integração com banco de dados\n- Treinamento de usuários',
                'requisitos_tecnicos' => '- Compatível com navegadores modernos\n- Hospedagem em nuvem\n- Backup automático',
              
                'prazo_execucao' => '120 dias após assinatura do contrato',
                'forma_pagamento' => '30% na assinatura, 40% na entrega parcial, 30% na entrega final',
                'criterio' => 'Menor preço global, desde que atendidos todos os requisitos',
              
                'inicio_execucao' => '10 dias após a assinatura do contrato',
                'local_execucao' => 'Secretaria Municipal de Educação e infraestrutura em nuvem',
              
                // Habilitação técnica
                'hab_objeto' => 'Apresentação de atestados de capacidade técnica em desenvolvimento de sistemas.',
                'hab_justificativa' => 'Comprovar experiência em projetos similares.',
                'hab_escopo' => 'Experiência no escopo apresentado neste TR.',
                'hab_requisitos' => 'Soluções compatíveis com os requisitos técnicos mínimos.',
                'hab_prazo' => 'Capacidade de cumprimento em 120 dias.',
                'hab_pagamento' => 'Aceitação da forma escalonada de pagamento.',
                'hab_selecao' => 'Critério baseado em menor preço com qualificação técnica.',
              
                // Capacidade técnica profissional
                'capacidade_objeto' => 'Experiência comprovada em projetos semelhantes.',
                'capacidade_justificativa' => 'Evitar riscos de execução por empresas inexperientes.',
                'capacidade_escopo' => 'Portfólio demonstrando escopo equivalente.',
                'capacidade_requisitos' => 'Apresentação de soluções compatíveis.',
                'capacidade_prazo' => 'Histórico de entregas dentro do prazo.',
                'capacidade_pagamento' => 'Viabilidade financeira demonstrada.',
                'capacidade_criterios' => 'Documentação compatível com os critérios estabelecidos.',
                'capacidade_comprovacao' => 'Comprovação por meio de atestados e declarações de capacidade técnica.',
              
                'prazo_vigencia' => '12 meses após a assinatura',
                'condicoes_pagamento' => 'Pagamento em etapas conforme cronograma e entregas.',
              
                // Dados de cabeçalho, rodapé e assinatura
                'cidade_maiusculo' => 'CIDADE EXEMPLO',
                'cidade' => 'Cidade Exemplo',
                'data_extenso' => '25 de abril de 2025',
                'nome_autoridade' => 'João da Silva',
                'cargo_autoridade' => 'Secretário de Administração',
                'endereco' => 'Rua Central, 123',
                'cep' => '12345-678',
                'brasao' => '[IMAGEM_DO_BRASÃO]',
              ];
              
              
            // Valores principais do conteúdo
            $templateProcessor->setValue('setor', $data['setor']);
            $templateProcessor->setValue('departamento', $data['departamento']);
            $templateProcessor->setValue('responsavel', $data['responsavel']);

            $templateProcessor->setValue('descricaoObjeto', $data['descricaoObjeto']);
            $templateProcessor->setValue('valor', $data['valor']);
            $templateProcessor->setValue('tipoObjeto', $data['tipoObjeto']);
            $templateProcessor->setValue('formaContratacao', $data['formaContratacao']);

            $templateProcessor->setValue('unidade_orcamentaria', $data['unidade_orcamentaria']);
            $templateProcessor->setValue('unidade_nome', $data['unidade_nome']);
            $templateProcessor->setValue('atividade_codigo', $data['atividade_codigo']);
            $templateProcessor->setValue('atividade_nome', $data['atividade_nome']);
            $templateProcessor->setValue('elemento_codigo', $data['elemento_codigo']);
            $templateProcessor->setValue('elemento_descricao', $data['elemento_descricao']);
            $templateProcessor->setValue('fonte_codigo', $data['fonte_codigo']);
            $templateProcessor->setValue('fonte_descricao', $data['fonte_descricao']);

            $templateProcessor->setValue('justificativa', $data['justificativa']);
            $templateProcessor->setValue('especificacao_objeto', $data['especificacao_objeto']);
            $templateProcessor->setValue('especificacao_justificativa', $data['especificacao_justificativa']);
            $templateProcessor->setValue('escopo', $data['escopo']);
            $templateProcessor->setValue('requisitos_tecnicos', $data['requisitos_tecnicos']);
            $templateProcessor->setValue('prazo_execucao', $data['prazo_execucao']);
            $templateProcessor->setValue('forma_pagamento', $data['forma_pagamento']);
            $templateProcessor->setValue('criterio', $data['criterio']);

            $templateProcessor->setValue('inicio_execucao', $data['inicio_execucao']);
            $templateProcessor->setValue('local_execucao', $data['local_execucao']);

            $templateProcessor->setValue('hab_objeto', $data['hab_objeto']);
            $templateProcessor->setValue('hab_justificativa', $data['hab_justificativa']);
            $templateProcessor->setValue('hab_escopo', $data['hab_escopo']);
            $templateProcessor->setValue('hab_requisitos', $data['hab_requisitos']);
            $templateProcessor->setValue('hab_prazo', $data['hab_prazo']);
            $templateProcessor->setValue('hab_pagamento', $data['hab_pagamento']);
            $templateProcessor->setValue('hab_selecao', $data['hab_selecao']);

            $templateProcessor->setValue('capacidade_objeto', $data['capacidade_objeto']);
            $templateProcessor->setValue('capacidade_justificativa', $data['capacidade_justificativa']);
            $templateProcessor->setValue('capacidade_escopo', $data['capacidade_escopo']);
            $templateProcessor->setValue('capacidade_requisitos', $data['capacidade_requisitos']);
            $templateProcessor->setValue('capacidade_prazo', $data['capacidade_prazo']);
            $templateProcessor->setValue('capacidade_pagamento', $data['capacidade_pagamento']);
            $templateProcessor->setValue('capacidade_criterios', $data['capacidade_criterios']);
            $templateProcessor->setValue('capacidade_comprovacao', $data['capacidade_comprovacao']);

            $templateProcessor->setValue('prazo_vigencia', $data['prazo_vigencia']);
            $templateProcessor->setValue('condicoes_pagamento', $data['condicoes_pagamento']);

            // Cabeçalho e Rodapé
            $templateProcessor->setValue('cidade_maiusculo', strtoupper($data['cidade'] ?? 'CIDADE EXEMPLO'));
            $templateProcessor->setValue('data_extenso', date('d') . ' de ' . strftime('%B de %Y'));
            $templateProcessor->setValue('nome_autoridade', $data['nome_autoridade'] ?? 'João da Silva');
            $templateProcessor->setValue('cargo_autoridade', $data['cargo_autoridade'] ?? 'Secretário de Administração');
            $templateProcessor->setValue('cidade', $data['cidade'] ?? 'Cidade Exemplo');
            $templateProcessor->setValue('endereco', $data['endereco'] ?? 'Rua Central, 123');
            $templateProcessor->setValue('cep', $data['cep'] ?? '12345-678');

            $templateProcessor->setImageValue('brasao', [
                'path' => public_path('brasao/Brasaosaosimao-go-1.png'), // ou .jpg dependendo do formato
                'width' => 80,  // ajuste conforme necessidade
                'ratio' => true  // mantém a proporção
            ]);


            // Valores adicionais como data ou campos extras (se desejado)
            $templateProcessor->setValue('data_hoje', date('d/m/Y'));
            
            $templateProcessor->saveAs($outputPath);
            
            return url('documents/' . $outputFilename);
        } catch (Exception $e) {
            throw new Exception("Error generating demand document: " . $e->getMessage());
        }
    }

    /**
     * Generate risk matrix document.
     *
     * @param  array  $data
     * @return string
     */
    private function generateRiskMatrix(array $data)
    {
        $templatePath = $this->templatesPath . '/risk_matrix_template.docx';
        $outputFilename = 'matriz_risco_' . time() . '.docx';
        $outputPath = public_path('documents/' . $outputFilename);

        try {
            $templateProcessor = new TemplateProcessor($templatePath);
            
            // Replace variables in template
            $templateProcessor->setValue('name', $data['name']);
            $templateProcessor->setValue('municipality', $data['municipality']);
            $templateProcessor->setValue('institution', $data['institution']);
            $templateProcessor->setValue('address', $data['address']);
            $templateProcessor->setValue('objectDescription', $data['objectDescription']);
            $templateProcessor->setValue('date', date('d/m/Y'));
            
            $templateProcessor->saveAs($outputPath);
            
            return url('documents/' . $outputFilename);
        } catch (Exception $e) {
            throw new Exception("Error generating risk matrix document: " . $e->getMessage());
        }
    }

    /**
     * Generate preliminary technical study document.
     *
     * @param  array  $data
     * @return string
     */
    private function generatePreliminaryStudy(array $data)
    {
        $templatePath = $this->templatesPath . '/preliminary_study_template.docx';
        $outputFilename = 'estudo_tecnico_preliminar_' . time() . '.docx';
        $outputPath = public_path('documents/' . $outputFilename);

        try {
            $templateProcessor = new TemplateProcessor($templatePath);
            
            // Replace variables in template
            $templateProcessor->setValue('name', $data['name']);
            $templateProcessor->setValue('municipality', $data['municipality']);
            $templateProcessor->setValue('institution', $data['institution']);
            $templateProcessor->setValue('address', $data['address']);
            $templateProcessor->setValue('objectDescription', $data['objectDescription']);
            $templateProcessor->setValue('date', date('d/m/Y'));
            
            $templateProcessor->saveAs($outputPath);
            
            return url('documents/' . $outputFilename);
        } catch (Exception $e) {
            throw new Exception("Error generating preliminary study document: " . $e->getMessage());
        }
    }

    /**
     * Generate reference terms document.
     *
     * @param  array  $data
     * @return string
     */
    private function generateReferenceTerms(array $data)
    {
        $templatePath = $this->templatesPath . '/reference_terms_template.docx';
        $outputFilename = 'termo_referencia_' . time() . '.docx';
        $outputPath = public_path('documents/' . $outputFilename);

        try {
            $templateProcessor = new TemplateProcessor($templatePath);
            
            // Replace variables in template
            $templateProcessor->setValue('name', $data['name']);
            $templateProcessor->setValue('municipality', $data['municipality']);
            $templateProcessor->setValue('institution', $data['institution']);
            $templateProcessor->setValue('address', $data['address']);
            $templateProcessor->setValue('objectDescription', $data['objectDescription']);
            $templateProcessor->setValue('date', date('d/m/Y'));
            
            $templateProcessor->saveAs($outputPath);
            
            return url('documents/' . $outputFilename);
        } catch (Exception $e) {
            throw new Exception("Error generating reference terms document: " . $e->getMessage());
        }
    }
} 