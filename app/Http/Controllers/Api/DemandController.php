<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Exception;

class DemandController extends BaseDocumentController
{
    public function generate(Request $request)
    {
        try {
            $templatePath = $this->templatesPath . '/DFD_Diagnostico_Unificado_Template.docx';
            $outputFilename = 'documento_formalizacao_demanda_' . time() . '.docx';
            $outputPath = public_path('documents/' . $outputFilename);

            $templateProcessor = new TemplateProcessor($templatePath);
            
            $staticData = [
                'setor' => 'Setor de Tecnologia da Informação',
                'departamento' => 'Departamento de Sistemas e Inovação',
                'responsavel' => 'Maria Clara Oliveira',
                'descricaoObjeto' => 'Contratação de empresa especializaada em desenvolvimento de software para criação de sistema de gestão educacional.',
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
                'hab_objeto' => 'Apresentação de atestados de capacidade técnica em desenvolvimento de sistemas.',
                'hab_justificativa' => 'Comprovar experiência em projetos similares.',
                'hab_escopo' => 'Experiência no escopo apresentado neste TR.',
                'hab_requisitos' => 'Soluções compatíveis com os requisitos técnicos mínimos.',
                'hab_prazo' => 'Capacidade de cumprimento em 120 dias.',
                'hab_pagamento' => 'Aceitação da forma escalonada de pagamento.',
                'hab_selecao' => 'Critério baseado em menor preço com qualificação técnica.',
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
                'origem_fonte' => 'Demanda Interna',
                'impacto_meta' => 'Reduzir em 30% o tempo de atendimento',
                'riscos_ocupacionais' => 'Trabalho em altura, agentes biológicos',
                'riscos_normas' => 'NR-01, NR-07',
                'riscos_justificativa' => 'Conformidade com a CLT e Portaria MTP 672/2023',
                'alternativa_a' => 'Compra definitiva com manutenção própria',
                'alternativa_b' => 'Locação com suporte incluso',
                'alternativa_conclusao' => 'Locação é mais econômica e sustentável',
                'inerciarisco' => 'Paralisação do serviço educacional',
                'inerciaplano' => 'Contrato emergencial temporário',
                'ods_vinculados' => 'ODS 12, ODS 13',
                'acao_sustentavel' => 'Implementar logística reversa',
                'ia_duplicidade' => 'Nenhuma duplicidade detectada',
                'ia_validacao' => 'Validado conforme PPA e LOA',
                'transparencia_resumo' => 'Projeto de modernização educacional',
                'transparencia_faq' => 'Perguntas e respostas jurídicas anexadas',
                'transparencia_prazo' => '5',
                'assinatura_formato' => 'ICP-Brasil'
            ];
              
            foreach ($staticData as $key => $value) {
                $templateProcessor->setValue($key, $value);
            }
            
            $this->setInstitutionalData($templateProcessor, $request);
            
            $templateProcessor->saveAs($outputPath);
            
            return url('documents/' . $outputFilename);
        } catch (Exception $e) {
            throw new Exception("Error generating demand document: " . $e->getMessage());
        }
    }
} 