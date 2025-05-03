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
            
            // 1. Guidelines
            try {
                $retryCount = 0;
                while ($retryCount < $maxRetries) {
                    try {
                        \Log::info('Tentando gerar Guidelines (tentativa ' . ($retryCount + 1) . ')');
                        $response = $this->guidelinesController->generate($request);
                        $responseData = $response->getData();
                        if (isset($responseData->url)) {
                            $documents['guidelines'] = $responseData->url;
                            \Log::info('Guidelines gerado com sucesso');
                            break;
                        } else {
                            throw new Exception('URL não encontrada na resposta');
                        }
                    } catch (Exception $e) {
                        $retryCount++;
                        \Log::error('Erro ao gerar Guidelines (tentativa ' . $retryCount . '): ' . $e->getMessage());
                        if ($retryCount >= $maxRetries) {
                            throw $e;
                        }
                        sleep(2); // Espera 2 segundos antes de tentar novamente
                    }
                }
            } catch (Exception $e) {
                \Log::error('Error generating guidelines: ' . $e->getMessage());
                $errors['guidelines'] = $e->getMessage();
            }
            
            // 2. Demand
            try {
                $retryCount = 0;
                while ($retryCount < $maxRetries) {
                    try {
                        \Log::info('Tentando gerar Demand (tentativa ' . ($retryCount + 1) . ')');
                        $response = $this->demandController->generate($request);
                        $responseData = $response->getData();
                        if (isset($responseData->url)) {
                            $documents['demand'] = $responseData->url;
                            \Log::info('Demand gerado com sucesso');
                            break;
                        } else {
                            throw new Exception('URL não encontrada na resposta');
                        }
                    } catch (Exception $e) {
                        $retryCount++;
                        \Log::error('Erro ao gerar Demand (tentativa ' . $retryCount . '): ' . $e->getMessage());
                        if ($retryCount >= $maxRetries) {
                            throw $e;
                        }
                        sleep(2);
                    }
                }
            } catch (Exception $e) {
                \Log::error('Error generating demand: ' . $e->getMessage());
                $errors['demand'] = $e->getMessage();
            }
            
            // 3. Preliminary Study
            try {
                $retryCount = 0;
                while ($retryCount < $maxRetries) {
                    try {
                        \Log::info('Tentando gerar Preliminary Study (tentativa ' . ($retryCount + 1) . ')');
                        $response = $this->preliminaryStudyController->generate($request);
                        $responseData = $response->getData();
                        if (isset($responseData->url)) {
                            $documents['preliminaryStudy'] = $responseData->url;
                            \Log::info('Preliminary Study gerado com sucesso');
                            break;
                        } else {
                            throw new Exception('URL não encontrada na resposta');
                        }
                    } catch (Exception $e) {
                        $retryCount++;
                        \Log::error('Erro ao gerar Preliminary Study (tentativa ' . $retryCount . '): ' . $e->getMessage());
                        if ($retryCount >= $maxRetries) {
                            throw $e;
                        }
                        sleep(2);
                    }
                }
            } catch (Exception $e) {
                \Log::error('Error generating preliminary study: ' . $e->getMessage());
                $errors['preliminaryStudy'] = $e->getMessage();
            }
            
            // 4. Risk Matrix
            try {
                $retryCount = 0;
                while ($retryCount < $maxRetries) {
                    try {
                        \Log::info('Tentando gerar Risk Matrix (tentativa ' . ($retryCount + 1) . ')');
                        $response = $this->riskMatrixController->generate($request);
                        $responseData = $response->getData();
                        if (isset($responseData->url)) {
                            $documents['riskMatrix'] = $responseData->url;
                            \Log::info('Risk Matrix gerado com sucesso');
                            break;
                        } else {
                            throw new Exception('URL não encontrada na resposta');
                        }
                    } catch (Exception $e) {
                        $retryCount++;
                        \Log::error('Erro ao gerar Risk Matrix (tentativa ' . $retryCount . '): ' . $e->getMessage());
                        if ($retryCount >= $maxRetries) {
                            throw $e;
                        }
                        sleep(2);
                    }
                }
            } catch (Exception $e) {
                \Log::error('Error generating risk matrix: ' . $e->getMessage());
                $errors['riskMatrix'] = $e->getMessage();
            }
            
            // 5. Reference Terms
            try {
                $retryCount = 0;
                while ($retryCount < $maxRetries) {
                    try {
                        \Log::info('Tentando gerar Reference Terms (tentativa ' . ($retryCount + 1) . ')');
                        $response = $this->referenceTermsController->generate($request);
                        $responseData = $response->getData();
                        if (isset($responseData->url)) {
                            $documents['referenceTerms'] = $responseData->url;
                            \Log::info('Reference Terms gerado com sucesso');
                            break;
                        } else {
                            throw new Exception('URL não encontrada na resposta');
                        }
                    } catch (Exception $e) {
                        $retryCount++;
                        \Log::error('Erro ao gerar Reference Terms (tentativa ' . $retryCount . '): ' . $e->getMessage());
                        if ($retryCount >= $maxRetries) {
                            throw $e;
                        }
                        sleep(2);
                    }
                }
            } catch (Exception $e) {
                \Log::error('Error generating reference terms: ' . $e->getMessage());
                $errors['referenceTerms'] = $e->getMessage();
            }
            
            // Check if at least one document was generated
            if (empty($documents)) {
                throw new Exception('Nenhum documento foi gerado com sucesso.');
            }
            
            \Log::info('Documentos gerados com sucesso:', $documents);
            \Log::info('Erros encontrados:', $errors);
            
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
