<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use PhpOffice\PhpWord\Settings;

class BaseDocumentController extends Controller
{
    protected $templatesPath;
    
    public function __construct()
    {
        $this->templatesPath = public_path('templates');
        Settings::setOutputEscapingEnabled(true);
    }

    protected function getStaticData()
    {
        return [
            'cidade' => 'SÃO SIMÃO',
            'cidade_maiusculo' => 'SÃO SIMÃO',
            'endereco' => 'Rua Central, 123',
            'cep' => '12345-678',
            'nome_autoridade' => 'jonatas',
            'cargo_autoridade' => 'Secretário Municipal de Administração',
            'data_extenso' => '26 de abril de 2025'
        ];
    }

    protected function setInstitutionalData($templateProcessor)
    {
        $staticData = $this->getStaticData();
        
        $templateProcessor->setValue('cidade', $staticData['cidade']);
        $templateProcessor->setValue('cidade_maiusculo', strtoupper($staticData['cidade']));
        $templateProcessor->setValue('endereco', $staticData['endereco']);
        $templateProcessor->setValue('cep', $staticData['cep']);
        $templateProcessor->setValue('nome_autoridade', $staticData['nome_autoridade']);
        $templateProcessor->setValue('cargo_autoridade', $staticData['cargo_autoridade']);
        $templateProcessor->setValue('data_extenso', $staticData['data_extenso']);

        $templateProcessor->setImageValue('brasao', [
            'path' => public_path('brasao/Brasaosaosimao-go-1.png'),
            'width' => 80,
            'ratio' => true
        ]);
    }
} 