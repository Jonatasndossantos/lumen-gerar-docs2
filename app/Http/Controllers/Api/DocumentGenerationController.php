<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Exception;

class DocumentGenerationController extends Controller
{
    protected $guidelinesController;
    protected $demandController;
    protected $riskMatrixController;
    protected $preliminaryStudyController;
    protected $referenceTermsController;
    
    public function __construct(
        GuidelinesController $guidelinesController,
        DemandController $demandController,
        RiskMatrixController $riskMatrixController,
        PreliminaryStudyController $preliminaryStudyController,
        ReferenceTermsController $referenceTermsController
    ) {
        $this->guidelinesController = $guidelinesController;
        $this->demandController = $demandController;
        $this->riskMatrixController = $riskMatrixController;
        $this->preliminaryStudyController = $preliminaryStudyController;
        $this->referenceTermsController = $referenceTermsController;
    }

    /**
     * Generate documents based on the submitted form data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generate(Request $request)
    {
        \Log::info('Dados recebidos no DocumentGenerationController:', $request->all());
        
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
            \Log::error('Erros de validação:', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Set global timeout
            set_time_limit(300); // Aumentado para 300 segundos

            // Create documents directory if it doesn't exist
            $documentsDir = public_path('documents');
            if (!File::exists($documentsDir)) {
                File::makeDirectory($documentsDir, 0755, true);
            }

            // Generate documents sequentially
            $documents = [];
            $errors = [];
            
            // 1. Guidelines
            try {
                $response = $this->guidelinesController->generate($request);
                $responseData = $response->getData();
                if (isset($responseData->url)) {
                    $documents['guidelines'] = $responseData->url;
                } else {
                    $errors['guidelines'] = 'URL não encontrada na resposta';
                }
            } catch (Exception $e) {
                \Log::error('Error generating guidelines: ' . $e->getMessage());
                $errors['guidelines'] = $e->getMessage();
            }
            
            // 2. Demand
            try {
                $response = $this->demandController->generate($request);
                $responseData = $response->getData();
                if (isset($responseData->url)) {
                    $documents['demand'] = $responseData->url;
                } else {
                    $errors['demand'] = 'URL não encontrada na resposta';
                }
            } catch (Exception $e) {
                \Log::error('Error generating demand: ' . $e->getMessage());
                $errors['demand'] = $e->getMessage();
            }
            
            // 3. Preliminary Study
            try {
                $response = $this->preliminaryStudyController->generate($request);
                $responseData = $response->getData();
                if (isset($responseData->url)) {
                    $documents['preliminaryStudy'] = $responseData->url;
                } else {
                    $errors['preliminaryStudy'] = 'URL não encontrada na resposta';
                }
            } catch (Exception $e) {
                \Log::error('Error generating preliminary study: ' . $e->getMessage());
                $errors['preliminaryStudy'] = $e->getMessage();
            }
            
            // 4. Risk Matrix
            try {
                $response = $this->riskMatrixController->generate($request);
                $responseData = $response->getData();
                if (isset($responseData->url)) {
                    $documents['riskMatrix'] = $responseData->url;
                } else {
                    $errors['riskMatrix'] = 'URL não encontrada na resposta';
                }
            } catch (Exception $e) {
                \Log::error('Error generating risk matrix: ' . $e->getMessage());
                $errors['riskMatrix'] = $e->getMessage();
            }
            
            // 5. Reference Terms
            try {
                $response = $this->referenceTermsController->generate($request);
                $responseData = $response->getData();
                if (isset($responseData->url)) {
                    $documents['referenceTerms'] = $responseData->url;
                } else {
                    $errors['referenceTerms'] = 'URL não encontrada na resposta';
                }
            } catch (Exception $e) {
                \Log::error('Error generating reference terms: ' . $e->getMessage());
                $errors['referenceTerms'] = $e->getMessage();
            }
            
            // Check if at least one document was generated
            if (empty($documents)) {
                throw new Exception('Nenhum documento foi gerado com sucesso.');
            }
            
            return response()->json([
                'success' => true,
                'documents' => $documents,
                'errors' => $errors
            ]);
        } catch (Exception $e) {
            \Log::error('Error in document generation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar documentos: ' . $e->getMessage(),
                'errors' => $errors ?? []
            ], 500);
        }
    }
} 
