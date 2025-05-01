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
        // Validate the request
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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Set global timeout
            set_time_limit(120);

            // Create documents directory if it doesn't exist
            $documentsDir = public_path('documents');
            if (!File::exists($documentsDir)) {
                File::makeDirectory($documentsDir, 0755, true);
            }

            // Generate documents sequentially
            $documents = [];
            
            // 1. Guidelines
            try {
                $documents['guidelines'] = $this->guidelinesController->generate($request);
            } catch (Exception $e) {
                \Log::error('Error generating guidelines: ' . $e->getMessage());
            }
            
            // 2. Demand
            try {
                $documents['demand'] = $this->demandController->generate($request);
            } catch (Exception $e) {
                \Log::error('Error generating demand: ' . $e->getMessage());
            }
            
            // 3. Risk Matrix
            try {
                $documents['riskMatrix'] = $this->riskMatrixController->generate($request);
            } catch (Exception $e) {
                \Log::error('Error generating risk matrix: ' . $e->getMessage());
            }
            
            // 4. Preliminary Study
            try {
                $documents['preliminaryStudy'] = $this->preliminaryStudyController->generate($request);
            } catch (Exception $e) {
                \Log::error('Error generating preliminary study: ' . $e->getMessage());
            }
            
            // 5. Reference Terms
            try {
                $documents['referenceTerms'] = $this->referenceTermsController->generate($request);
            } catch (Exception $e) {
                \Log::error('Error generating reference terms: ' . $e->getMessage());
            }
            
            // Check if at least one document was generated
            if (empty($documents)) {
                throw new Exception('Nenhum documento foi gerado com sucesso.');
            }
            
            return response()->json([
                'success' => true,
                'documents' => $documents
            ]);
        } catch (Exception $e) {
            \Log::error('Error in document generation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar documentos: ' . $e->getMessage()
            ], 500);
        }
    }
} 