import React from 'react';
import { useForm } from 'react-hook-form';

const OtherDetails = ({ formData, updateFormData, nextStep, prevStep }) => {
    const { register, handleSubmit, formState: { errors } } = useForm({
        defaultValues: {
            municipality: formData.municipality,
            institution: formData.institution,
            address: formData.address
        }
    });

    const onSubmit = (data) => {
        updateFormData(data);
        nextStep();
    };

    return (
        <div>
            <h3 className="text-xl font-semibold mb-6">Outros Detalhes</h3>
            
            <form onSubmit={handleSubmit(onSubmit)}>
                <div className="mb-4">
                    <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="municipality">
                        Município
                    </label>
                    <input
                        id="municipality"
                        type="text"
                        className={`shadow appearance-none border ${errors.municipality ? 'border-red-500' : 'border-gray-300'} rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline`}
                        {...register('municipality', { required: 'Município é obrigatório' })}
                    />
                    {errors.municipality && <p className="text-red-500 text-xs italic">{errors.municipality.message}</p>}
                </div>
                
                <div className="mb-4">
                    <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="institution">
                        Instituição
                    </label>
                    <div className="relative">
                        <select
                            id="institution"
                            className={`shadow appearance-none border ${errors.institution ? 'border-red-500' : 'border-gray-300'} rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline`}
                            {...register('institution', { required: 'Instituição é obrigatória' })}
                        >
                            <option value="Prefeitura">Prefeitura</option>
                            <option value="Câmara">Câmara</option>
                            <option value="Outros">Outros</option>
                        </select>
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg className="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                            </svg>
                        </div>
                    </div>
                    {errors.institution && <p className="text-red-500 text-xs italic">{errors.institution.message}</p>}
                </div>
                
                <div className="mb-6">
                    <label className="block text-gray-700 text-sm font-bold mb-2" htmlFor="address">
                        Endereço
                    </label>
                    <input
                        id="address"
                        type="text"
                        className={`shadow appearance-none border ${errors.address ? 'border-red-500' : 'border-gray-300'} rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline`}
                        {...register('address', { required: 'Endereço é obrigatório' })}
                    />
                    {errors.address && <p className="text-red-500 text-xs italic">{errors.address.message}</p>}
                </div>
                
                <div className="flex justify-between">
                    <button
                        type="button"
                        onClick={prevStep}
                        className="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    >
                        Voltar
                    </button>
                    <button
                        type="submit"
                        className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    >
                        Próximo
                    </button>
                </div>
            </form>
        </div>
    );
};

export default OtherDetails; 