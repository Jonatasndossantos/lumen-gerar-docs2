<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use PhpOffice\PhpWord\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BaseDocumentController extends Controller
{
    protected $templatesPath;
    
    public function __construct()
    {
        $this->templatesPath = public_path('templates');
        Settings::setOutputEscapingEnabled(true);
    }

    protected function getStaticData(Request $request)
    {
        $apiKey = env('OPENAI_API_KEY');

        // Monta o prompt com base no que o usuário enviou
        $prompt = $this->buildInstitutionalPrompt($request);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->timeout(30)->retry(3, 1000)->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'Você é um assistente especialista em documentos públicos.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.4,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Erro ao comunicar com a OpenAI: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        $data = json_decode($content, true);

        if (!$data) {
            throw new \Exception('Resposta inválida da OpenAI');
        }

        return $data;
    }

    protected function buildInstitutionalPrompt(Request $request)
    {
        $municipality = $request->input('municipality');
        $institution = $request->input('institution');
        $address = $request->input('address');
        $objectDescription = $request->input('objectDescription');
        $date = now()->format('d \d\e F \d\e Y');

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
            "cidade_maiusculo": "<nome do município em letras maiúsculas totalmente maiusculo>",
            "endereco": "<endereço sem a cidade>",
            "cep": "<CEP do município>",
            "nome_autoridade": "<nome do principal representante legal da instituição>",
            "cargo_autoridade": "<cargo do representante>",
            "data_extenso": "<data por extenso, ex: '26 de abril de 2025'>"
            }

            Instruções importantes:
            - O endereço deve ser o informado, complementado se necessário para realismo.
            - O nome da autoridade pode ser fictício, mas típico (ex: Maria Souza, João Silva).
            - O cargo deve ser condizente com a instituição (ex: Prefeito Municipal, Secretário de Administração).
            - Não adicione textos explicativos.
            - Não adicione comentários.
            - Apenas o JSON puro como resposta.
            PROMPT;
    }

    protected function setInstitutionalData($templateProcessor, Request $request)
    {
        $staticData = $this->getStaticData($request);
        
        $templateProcessor->setValue('cidade', $staticData['cidade']);
        $templateProcessor->setValue('cidade_maiusculo', strtoupper($staticData['cidade']));
        $templateProcessor->setValue('endereco', $staticData['endereco']);
        $templateProcessor->setValue('cep', $staticData['cep']);
        $templateProcessor->setValue('nome_autoridade', $staticData['nome_autoridade']);
        $templateProcessor->setValue('cargo_autoridade', $staticData['cargo_autoridade']);
        $templateProcessor->setValue('data_extenso', $staticData['data_extenso']);

        // 🛡️ Gerenciar brasão
        $municipality = $staticData['cidade'];

        // Limpar o nome do município para o nome do arquivo (deixar em minúsculo e tirar acentos)
        $filename = $this->normalizeMunicipalityName($municipality) . '.png';

        $brasaoPath = public_path('brasoes/' . $filename);

        if (!file_exists($brasaoPath)) {
            // Se não existir, usar um brasão padrão
            $brasaoPath = public_path('brasoes/default.png');
        }

        $templateProcessor->setImageValue('brasao', [
            'path' => $brasaoPath,
            'width' => 80,
            'ratio' => true
        ]);
    }

    /**
     * Função para normalizar o nome do município para formar o nome do arquivo
     */
    protected function normalizeMunicipalityName($municipality)
    {
        // Remove acentos, espaços e deixa tudo minúsculo
        $municipality = iconv('UTF-8', 'ASCII//TRANSLIT', $municipality);
        $municipality = preg_replace('/[^a-zA-Z0-9]/', '', $municipality);
        return strtolower($municipality);
    }
} 