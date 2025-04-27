<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Exception;

class GuidelinesController extends BaseDocumentController
{
    public function generate(Request $request)
    {
        try {
            $templatePath = $this->templatesPath . '/guidelines_template.docx';
            $outputFilename = 'orientacoes_' . time() . '.docx';
            $outputPath = public_path('documents/' . $outputFilename);

            $templateProcessor = new TemplateProcessor($templatePath);
            
            // Replace variables in template
            $templateProcessor->setValue('name', $request->name);
            $templateProcessor->setValue('municipality', $request->municipality);
            $templateProcessor->setValue('institution', $request->institution);
            $templateProcessor->setValue('date', date('d/m/Y'));
            
            $this->setInstitutionalData($templateProcessor);
            
            $templateProcessor->saveAs($outputPath);
            
            return url('documents/' . $outputFilename);
        } catch (Exception $e) {
            throw new Exception("Error generating guidelines document: " . $e->getMessage());
        }
    }
} 