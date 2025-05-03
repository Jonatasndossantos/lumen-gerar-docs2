<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Exception;

class RiskMatrixController extends Controller
{
    protected $baseDocument;

    public function __construct(BaseDocumentController $baseDocument)
    {
        $this->baseDocument = $baseDocument;
    }

    public function generate(Request $request)
    {
        try {
            // Check if template directory exists
            $templatesPath = public_path('templates');
            if (!file_exists($templatesPath)) {
                throw new Exception("Template directory not found at: " . $templatesPath);
            }

            $templatePath = $templatesPath . '/Matriz_Risco_Template.docx';
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
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

            // 2. Criar e inserir a tabela de riscos + seção de assinatura
            // Adiciona espaço antes da tabela
            $section->addText('');

            // Gera os dados via IA
            $data = $this->baseDocument->generateAiData('risco', $request);
            
            \Log::info('Dados recebidos:', ['data' => $data]);

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

            // Processar os riscos da string
            $riscos = $data['data']['riscos'];
            
            foreach ($riscos as $risco) {
                $table->addRow();
                $campos = [
                    $risco['seq'],
                    $risco['evento'],
                    $risco['dano'],
                    $risco['impacto'],
                    $risco['probabilidade'],
                    $risco['acao_preventiva'],
                    $risco['responsavel_preventiva'],
                    $risco['acao_contingencia'],
                    $risco['responsavel_contingencia']
                ];
                
                foreach ($campos as $campo) {
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
            
            // Preenche os dados do processo
            foreach ($data['data'] as $key => $value) {
                if ($key !== 'riscos' && !is_array($value)) {
                    $templateProcessor->setValue($key, $value);
                }
            }

            // Adiciona os dados institucionais e o brasão
            $this->baseDocument->setInstitutionalData($templateProcessor, $request);

            // 4. Salvar o arquivo final
            $templateProcessor->saveAs($outputPath);
            
            if (!file_exists($outputPath)) {
                throw new Exception("Failed to save final output file at: " . $outputPath);
            }

            // Limpar arquivo temporário
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            return response()->json([
                'success' => true,
                'url' => url("documents/{$outputFilename}")
            ]);
        } catch (Exception $e) {
            // Log the error with more details
            \Log::error("Error in RiskMatrixController: " . $e->getMessage(), [
                'data' => isset($data) ? $data : 'not set'
            ]);
            return response()->json([
                'success' => false,
                'error' => "Error generating risk matrix document: " . $e->getMessage()
            ], 500);
        }
    }
} 