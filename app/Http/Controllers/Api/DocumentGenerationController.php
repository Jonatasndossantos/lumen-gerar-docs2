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

            // Generate documents sequentially with individual timeouts
            $documents = [];
            $errors = [];
            $maxRetries = 3;
            
            // Function to generate a single document with retries
            $generateDocument = function($controller, $type, $request) use (&$documents, &$errors, $maxRetries) {
                $retryCount = 0;
                while ($retryCount < $maxRetries) {
                    try {
                        \Log::info("Tentando gerar {$type} (tentativa " . ($retryCount + 1) . ")");
                        $response = $controller->generate($request);
                        
                        if (!$response->isSuccessful()) {
                            throw new Exception('Resposta não bem-sucedida: ' . $response->status());
                        }
                        
                        $responseData = $response->getData();
                        \Log::info("Resposta recebida para {$type}:", (array)$responseData);
                        
                        if (isset($responseData->success) && $responseData->success && isset($responseData->url)) {
                            $documents[$type] = $responseData->url;
                            \Log::info("{$type} gerado com sucesso: " . $responseData->url);
                            return true;
                        } else {
                            throw new Exception('URL não encontrada na resposta ou resposta inválida');
                        }
                    } catch (Exception $e) {
                        $retryCount++;
                        \Log::error("Erro ao gerar {$type} (tentativa {$retryCount}): " . $e->getMessage());
                        if ($retryCount >= $maxRetries) {
                            $errors[$type] = $e->getMessage();
                            return false;
                        }
                        sleep(2);
                    }
                }
                return false;
            };

            // Generate each document independently
            $generateDocument($this->guidelinesController, 'guidelines', $request);
            $generateDocument($this->demandController, 'demand', $request);
            $generateDocument($this->riskMatrixController, 'riskMatrix', $request);
            $generateDocument($this->preliminaryStudyController, 'preliminaryStudy', $request);
            $generateDocument($this->referenceTermsController, 'referenceTerms', $request);

            // Return the results
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
