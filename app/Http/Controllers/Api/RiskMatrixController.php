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
            $phpWord = IOFactory::load($templatePath, 'Word2007');
            $sections = $phpWord->getSections();
            $section = $sections[0];

            // 2. Criar e inserir a tabela de riscos + seção de assinatura
            // Adiciona espaço antes da tabela
            $section->addText('');

            // Gera os dados via IA
            $data = $this->baseDocument->generateAiData('risco', $request);
            
            \Log::info('Dados recebidos para matriz de risco:', ['data' => $data]);

            // Processar a string de riscos em um array estruturado
            $riscosArray = [];
            if (isset($data['data']['riscos'])) {
                $riscosData = $data['data']['riscos'];
                \Log::info('Dados de riscos recebidos:', ['riscos' => $riscosData]);
                
                // Se for uma string, processa como texto
                if (is_string($riscosData)) {
                    // Divide a string em blocos de risco (cada risco começa com um número)
                    $blocos = preg_split('/\n(?=\d+\n)/', trim($riscosData));
                    \Log::info('Blocos de risco encontrados:', ['blocos' => $blocos]);
                    
                    foreach ($blocos as $bloco) {
                        $linhas = array_values(array_filter(explode("\n", trim($bloco))));
                        if (empty($linhas)) continue;
                        
                        // O primeiro elemento é o número do risco
                        $numero = array_shift($linhas);
                        if (!is_numeric($numero)) continue;
                        
                        $risco = [
                            'seq' => $numero,
                            'evento' => $linhas[0] ?? '-',
                            'dano' => $linhas[1] ?? '-',
                            'impacto' => $linhas[2] ?? '-',
                            'probabilidade' => $linhas[3] ?? '-',
                            'acao_preventiva' => $linhas[4] ?? '-',
                            'responsavel_preventiva' => $linhas[5] ?? '-',
                            'acao_contingencia' => $linhas[6] ?? '-',
                            'responsavel_contingencia' => $linhas[7] ?? '-'
                        ];
                        
                        $riscosArray[] = $risco;
                    }
                } 
                // Se for um array, processa diretamente
                else if (is_array($riscosData)) {
                    foreach ($riscosData as $risco) {
                        if (is_array($risco)) {
                            $riscosArray[] = [
                                'seq' => $risco['seq'] ?? '-',
                                'evento' => $risco['evento'] ?? '-',
                                'dano' => $risco['dano'] ?? '-',
                                'impacto' => $risco['impacto'] ?? '-',
                                'probabilidade' => $risco['probabilidade'] ?? '-',
                                'acao_preventiva' => $risco['acao_preventiva'] ?? '-',
                                'responsavel_preventiva' => $risco['responsavel_preventiva'] ?? '-',
                                'acao_contingencia' => $risco['acao_contingencia'] ?? '-',
                                'responsavel_contingencia' => $risco['responsavel_contingencia'] ?? '-'
                            ];
                        }
                    }
                }
            }

            if (empty($riscosArray)) {
                \Log::error('Falha ao processar riscos:', [
                    'dados_originais' => $data['data']['riscos'] ?? 'não disponível',
                    'tipo_dados' => isset($data['data']['riscos']) ? gettype($data['data']['riscos']) : 'não definido'
                ]);
                throw new Exception('Nenhum risco encontrado nos dados');
            }

            \Log::info('Riscos processados com sucesso:', ['riscos' => $riscosArray]);

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
            foreach ($riscosArray as $risco) {
                $table->addRow();
                $row = [
                    'seq' => $risco['seq'],
                    'evento' => $risco['evento'],
                    'dano' => $risco['dano'],
                    'impacto' => $risco['impacto'],
                    'probabilidade' => $risco['probabilidade'],
                    'acao_preventiva' => $risco['acao_preventiva'],
                    'responsavel_preventiva' => $risco['responsavel_preventiva'],
                    'acao_contingencia' => $risco['acao_contingencia'],
                    'responsavel_contingencia' => $risco['responsavel_contingencia']
                ];
                
                foreach ($row as $value) {
                    $cell = $table->addCell(1500, [
                        'borderSize' => 6,
                        'borderColor' => '000000'
                    ]);
                    $cell->addText((string)$value, $textStyle);
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
            foreach ($data as $key => $value) {
                if ($key !== 'riscos') {
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
            
            $url = url("documents/{$outputFilename}");
            if (!$url) {
                throw new Exception("Failed to generate URL for the document");
            }
            
            \Log::info("Risk Matrix document generated successfully at: " . $url);
            
            return response()->json([
                'success' => true,
                'url' => $url
            ], 200);
        } catch (Exception $e) {
            // Log the error
            \Log::error("Error in RiskMatrixController: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => "Error generating risk matrix document: " . $e->getMessage()
            ], 500);
        }
    }
} 