<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Exception;

class PreliminaryStudyController extends BaseDocumentController
{
    public function generate(Request $request)
    {
        try {
            $templatePath = $this->templatesPath . '/ETP_Estudo_Tecnico_Preliminar_Template.docx';
            $outputFilename = 'estudo_tecnico_preliminar_' . time() . '.docx';
            $outputPath = public_path('documents/' . $outputFilename);

            $templateProcessor = new TemplateProcessor($templatePath);
            
            $staticData = [
                'etp_objeto' => 'Contratação de empresa especializada para o desenvolvimento de plataforma integrada de gestão educacional.',
                'etp_justificativa' => 'Modernizar e integrar os processos educacionais da Prefeitura, promovendo eficiência e acessibilidade digital.',
                'etp_plano_contratacao' => 'Incluso no Plano Anual de Contratações 2025, atendendo a diretrizes de transformação digital e melhoria dos serviços públicos.',
                'etp_requisitos_linguagens' => 'JavaScript, React, Node.js, Python, Django.',
                'etp_requisitos_banco' => 'PostgreSQL e MySQL.',
                'etp_requisitos_api' => 'Experiência comprovada em integrações via API RESTful.',
                'etp_experiencia_publica' => 'Experiência comprovada em projetos para a administração pública.',
                'etp_prazo_execucao' => '12 meses, com possibilidade de renovação por igual período.',
                'etp_forma_pagamento' => 'Mensal, condicionado à entrega de relatórios de atividades.',
                'etp_criterios_selecao' => 'Proposta técnica e financeira, portfólio de projetos e entrevista técnica, se necessário.',
                'etp_estimativa_quantidades' => 'Desenvolvimento de 5 módulos de sistema web, 2 aplicativos móveis e integração com 3 bases de dados existentes.',
                'etp_alternativa_a' => 'Contratação de empresa especializada com suporte contínuo.',
                'etp_alternativa_b' => 'Contratação de profissionais autônomos para cada módulo.',
                'etp_alternativa_c' => 'Desenvolvimento interno pela equipe atual da Prefeitura.',
                'etp_analise_comparativa' => 'A contratação de empresa especializada foi considerada mais adequada pela experiência multidisciplinar, menor risco de atraso e maior capacidade técnica.',
                'etp_estimativa_precos' => 'Baseada em pesquisas nos portais PNCP, Painel de Preços e cotações diretas com fornecedores. Valor estimado: R$ 350.000,00.',
                'etp_solucao_total' => 'Contratação de empresa especializada oferecendo sistema modular escalável, treinamento de servidores e suporte técnico por 12 meses.',
                'etp_parcelamento' => 'Não recomendável para este objeto por tratar-se de solução integrada, onde o parcelamento traria riscos de incompatibilidade entre módulos.',
                'etp_resultados_esperados' => 'Redução de 30% no tempo de matrícula escolar, aumento de 50% na satisfação dos servidores com os processos informatizados e redução de custos operacionais.',
                'etp_providencias_previas' => 'Definição da equipe de fiscalização e capacitação dos fiscais na gestão de contratos de tecnologia.',
                'etp_contratacoes_correlatas' => 'Aquisição de servidores de alta disponibilidade e atualização de softwares de segurança.',
                'etp_impactos_ambientais' => 'Redução de consumo de papel, adoção de servidores energicamente eficientes e práticas de descarte sustentável de equipamentos antigos.',
                'etp_viabilidade_contratacao' => 'A contratação é viável técnica, operacional e financeiramente, alinhando-se ao interesse público e às diretrizes de inovação da Administração.'
            ];
            
            foreach ($staticData as $key => $value) {
                $templateProcessor->setValue($key, $value);
            }
            
            $this->setInstitutionalData($templateProcessor);
            
            $templateProcessor->saveAs($outputPath);
            
            return url('documents/' . $outputFilename);
        } catch (Exception $e) {
            throw new Exception("Error generating preliminary study document: " . $e->getMessage());
        }
    }
} 