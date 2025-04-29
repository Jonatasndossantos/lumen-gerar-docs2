<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Exception;

class RiskMatrixController extends BaseDocumentController
{
    public function generate(Request $request)
    {
        try {
            // Check if template directory exists
            if (!file_exists($this->templatesPath)) {
                throw new Exception("Template directory not found at: " . $this->templatesPath);
            }

            $templatePath = $this->templatesPath . '/Matriz_Risco_Template.docx';
            if (!file_exists($templatePath)) {
                throw new Exception("Template file not found at: " . $templatePath);
            }

            // Ensure documents directory exists
            $documentsDir = public_path('documents');
            if (!file_exists($documentsDir)) {
                if (!mkdir($documentsDir, 0755, true)) {
                    throw new Exception("Failed to create documents directory at: " . $documentsDir);
                }
            }

            // Check if we can write to the documents directory
            if (!is_writable($documentsDir)) {
                throw new Exception("Documents directory is not writable: " . $documentsDir);
            }

            $tempPath = public_path('documents/temp_matriz_risco.docx');
            $outputFilename = 'matriz_risco_' . time() . '.docx';
            $outputPath = public_path('documents/' . $outputFilename);

            // 1. Carregar o template
            $phpWord = IOFactory::load($templatePath, 'Word2007');
            $sections = $phpWord->getSections();
            $section = $sections[0];

            // 2. Criar e inserir a tabela de riscos + seção de assinatura
            // Adiciona espaço antes da tabela
            $section->addText('');

            // Dados da tabela
            $riscos = [
                [
                    'seq' => '1',
                    'evento' => 'Falha na integração de bases de dados.',
                    'dano' => 'Perda de dados e inconsistência de informações.',
                    'impacto' => 'Grande',
                    'probabilidade' => 'Provável',
                    'acao_preventiva' => 'Implementar testes de integração periódicos.',
                    'responsavel_preventiva' => 'João da Silva',
                    'acao_contingencia' => 'Plano de contingência para migração e recuperação de dados.',
                    'responsavel_contingencia' => 'João da Silva',
                ],
                [
                    'seq' => '2',
                    'evento' => 'Descumprimento de prazos contratuais.',
                    'dano' => 'Multas e atrasos na entrega dos sistemas.',
                    'impacto' => 'Grande',
                    'probabilidade' => 'Alta',
                    'acao_preventiva' => 'Monitoramento de cronograma semanal.',
                    'responsavel_preventiva' => 'João da Silva',
                    'acao_contingencia' => 'Aplicação de cláusulas de penalidade contratual.',
                    'responsavel_contingencia' => 'João da Silva',
                ],
                [
                    'seq' => '3',
                    'evento' => 'Erros de programação comprometendo a segurança.',
                    'dano' => 'Exposição de dados sensíveis.',
                    'impacto' => 'Grande',
                    'probabilidade' => 'Provável',
                    'acao_preventiva' => 'Auditorias de segurança antes de publicação.',
                    'responsavel_preventiva' => 'João da Silva',
                    'acao_contingencia' => 'Plano emergencial de segurança de dados.',
                    'responsavel_contingencia' => 'João da Silva',
                ],
                [
                    'seq' => '10',
                    'evento' => 'Mudança na legislação afetando o projeto.',
                    'dano' => 'Necessidade de ajustes contratuais e técnicos.',
                    'impacto' => 'Moderado',
                    'probabilidade' => 'Provável',
                    'acao_preventiva' => 'Acompanhamento legislativo contínuo.',
                    'responsavel_preventiva' => 'João da Silva',
                    'acao_contingencia' => 'Adequação dos sistemas às novas exigências legais.',
                    'responsavel_contingencia' => 'João da Silva',
                ]
            ];

            // Criar e preencher a tabela
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 80
            ]);
            
            // Estilo para o texto da tabela
            $textStyle = [
                'size' => 9,
                'name' => 'Arial'
            ];
            
            // Estilo para o cabeçalho
            $headerStyle = [
                'size' => 9,
                'name' => 'Arial',
                'bold' => true
            ];
            
            // Cabeçalho da tabela
            $table->addRow();
            $headers = ['Seq', 'Evento de Risco', 'Dano', 'Impacto', 'Probabilidade', 'Ação Preventiva', 'Responsável Preventiva', 'Ação de Contingência', 'Responsável Contingência'];
            foreach ($headers as $header) {
                $cell = $table->addCell(1500, [
                    'borderSize' => 6,
                    'borderColor' => '000000',
                    'bgColor' => 'F2F2F2'
                ]);
                $cell->addText($header, $headerStyle, ['alignment' => 'center']);
            }

            // Adicionar linhas com os dados
            foreach ($riscos as $risco) {
                $table->addRow();
                foreach ($risco as $campo) {
                    $cell = $table->addCell(1500, [
                        'borderSize' => 6,
                        'borderColor' => '000000'
                    ]);
                    $cell->addText($campo, $textStyle);
                }
            }

            // Adiciona espaço após a tabela
            $section->addText('');

            // Adiciona a seção de assinaturas
            $section->addText('Assinatura:', ['bold' => true]);
            $section->addText('${nome_autoridade}');
            $section->addText('${cargo_autoridade}');
            $section->addText('${data_aprovacao}');

            // Salvar o arquivo com a tabela
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($tempPath);

            if (!file_exists($tempPath)) {
                throw new Exception("Failed to save temporary file with table at: " . $tempPath);
            }

            // 3. Preencher todas as variáveis
            $templateProcessor = new TemplateProcessor($tempPath);
            
            $staticData = [
                'processo_administrativo' => '123/2025',
                'objeto_matriz' => 'Contratação de empresa para desenvolvimento de sistema de gestão pública.',
                'data_inicio_contratacao' => '24-04-2025',
                'unidade_responsavel' => 'Secretaria Municipal de Administração',
                'fase_analise' => 'Planejamento da Contratação',
                'nome_autoridade' => 'João da Silva',
                'cargo_autoridade' => 'Secretário Municipal de Administração',
                'data_aprovacao' => date('d/m/Y'),
            ];
            
            foreach ($staticData as $key => $value) {
                $templateProcessor->setValue($key, $value);
            }
            
            $this->setInstitutionalData($templateProcessor, $request);

            // 4. Salvar o arquivo final
            $templateProcessor->saveAs($outputPath);
            
            if (!file_exists($outputPath)) {
                throw new Exception("Failed to save final output file at: " . $outputPath);
            }

            // Limpar arquivo temporário
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            return url('documents/' . $outputFilename);
        } catch (Exception $e) {
            // Log the error
            error_log("Error in RiskMatrixController: " . $e->getMessage());
            throw new Exception("Error generating risk matrix document: " . $e->getMessage());
        }
    }
} 