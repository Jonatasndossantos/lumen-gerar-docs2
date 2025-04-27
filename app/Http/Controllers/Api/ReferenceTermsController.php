<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Exception;

class ReferenceTermsController extends BaseDocumentController
{
    public function generate(Request $request)
    {
        try {
            $templatePath = $this->templatesPath . '/TR_Termo_Referencia_V11_3_Template.docx';
            $outputFilename = 'termo_referencia_' . time() . '.docx';
            $outputPath = public_path('documents/' . $outputFilename);

            $templateProcessor = new TemplateProcessor($templatePath);
            
            $staticData = [
                'descricao_tecnica' => 'Contratação de empresa para desenvolvimento de plataforma integrada de gestão escolar e matrícula digital.',
                'justificativa_demanda' => 'Necessidade de modernizar e integrar o processo educacional do município, promovendo eficiência e transparência.',
                'base_legal' => 'Lei 14.133/2021 – Arts. 6º, 18, 20, 22',
                'normas_aplicaveis' => 'NR-01, NR-07, ISO 14001, ABNT NBR 16150, RDC Anvisa',
                'execucao_etapas' => 'Execução dividida em fases com entregas mensais validadas por aceites parciais.',
                'tolerancia_tecnica' => 'Até 2% de variação nas entregas permitida.',
                'materiais_sustentaveis' => 'Utilização de materiais recicláveis e adoção de logística reversa para descarte de equipamentos.',
                'execucao_similar' => 'Apresentação de atestados de execução de projetos de TI de porte similar.',
                'certificacoes' => 'ISO 9001, ISO 14001, Certificação de execução técnica válida.',
                'pgr_pcmso' => 'Apresentação obrigatória do PGR (Programa de Gerenciamento de Riscos) e PCMSO (Programa de Controle Médico de Saúde Ocupacional).',
                'criterio_julgamento' => 'Menor preço global atendendo a checklist de requisitos mínimos.',
                'garantia_qualidade' => 'Garantia técnica baseada em critérios objetivos de qualidade estabelecidos no edital.',
                'painel_fiscalizacao' => 'Utilização de painel de fiscalização com integração a IA LUX para acompanhamento de performance.',
                'kpis_operacionais' => 'Entrega pontual dos módulos; satisfação do usuário ≥ 90%; manutenção corretiva em até 48h.',
                'designacao_formal_fiscal' => 'Fiscal formalmente designado, com registros e logs de acompanhamento.',
                'penalidades' => 'Multas previstas conforme Resolução TCE-SP 45/2022 por atraso, não conformidade ou falha técnica.',
                'alertas_ia' => 'IA LUX enviará alertas automáticos em casos de atraso ou descumprimento técnico.',
                'anexos_obrigatorios' => 'DFD, ETP, Matriz de Riscos, Planilha Orçamentária, Checklist de Execução.',
                'transparencia_resumo' => 'Resumo do projeto disponível no Portal da Transparência no prazo estabelecido.',
                'faq_juridico' => 'Publicação de perguntas e respostas jurídicas sobre o projeto.',
                'assinatura_formato' => 'ICP-Brasil ou 1Doc',
                'prazo_publicacao' => '5'
            ];
            
            foreach ($staticData as $key => $value) {
                $templateProcessor->setValue($key, $value);
            }
            
            $this->setInstitutionalData($templateProcessor);
            
            $templateProcessor->saveAs($outputPath);
            
            return url('documents/' . $outputFilename);
        } catch (Exception $e) {
            throw new Exception("Error generating reference terms document: " . $e->getMessage());
        }
    }
} 