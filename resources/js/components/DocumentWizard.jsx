import React, { useState } from 'react';
import PersonalDetails from './steps/PersonalDetails';
import OtherDetails from './steps/OtherDetails';
import ObjectDetails from './steps/ObjectDetails';
import DocumentsDownload from './steps/DocumentsDownload';
import ProgressBar from './common/ProgressBar';

const DocumentWizard = () => {
    const [step, setStep] = useState(1);
    const [formData, setFormData] = useState({
        // Personal details
        name: '',
        email: '',
        whatsapp: '',
        
        // Other details
        municipality: '',
        institution: 'Prefeitura',
        address: '',
        
        // Object details
        objectDescription: '',
        
        // Generated documents
        documents: null,
        isGenerating: false
    });

    const updateFormData = (data) => {
        setFormData(prev => ({ ...prev, ...data }));
    };

    const nextStep = () => {
        setStep(step + 1);
    };

    const prevStep = () => {
        setStep(step - 1);
    };

    // Determine which step to display
    const renderStep = () => {
        switch (step) {
            case 1:
                return <PersonalDetails 
                    formData={formData} 
                    updateFormData={updateFormData} 
                    nextStep={nextStep} 
                />;
            case 2:
                return <OtherDetails 
                    formData={formData} 
                    updateFormData={updateFormData} 
                    nextStep={nextStep} 
                    prevStep={prevStep} 
                />;
            case 3:
                return <ObjectDetails 
                    formData={formData} 
                    updateFormData={updateFormData} 
                    nextStep={nextStep} 
                    prevStep={prevStep} 
                />;
            case 4:
                return <DocumentsDownload 
                    formData={formData} 
                    updateFormData={updateFormData} 
                    prevStep={prevStep} 
                />;
            default:
                return <PersonalDetails 
                    formData={formData} 
                    updateFormData={updateFormData} 
                    nextStep={nextStep} 
                />;
        }
    };

    return (
        <div className="max-w-4xl mx-auto p-6">
            <h1 className="text-3xl font-bold text-center mb-8">
                Sistema de Construção Inteligente de DFD, ETP, Matriz de Risco, TR e Outros Documentos
            </h1>
            <h2 className="text-xl text-center text-gray-600 mb-8">
                conforme a Lei 14.133/2021
            </h2>
            
            <ProgressBar currentStep={step} totalSteps={4} />
            
            <div className="mt-8 bg-white p-6 rounded-lg shadow-md">
                {renderStep()}
            </div>
        </div>
    );
};

export default DocumentWizard; 