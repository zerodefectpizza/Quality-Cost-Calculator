class QCC_Calculation_Engine {
    private $cogq_calculator;
    private $copq_calculator;
    private $opportunity_calculator;
    private $validator;
    
    public function calculate($input_data) {
        // 1. Validiere Input
        $validation_result = $this->validator->validate($input_data);
        if (!$validation_result->is_valid()) {
            throw new QCC_Validation_Exception($validation_result->get_errors());
        }
        
        // 2. Berechne COGQ
        $cogq_result = $this->cogq_calculator->calculate($input_data);
        
        // 3. Berechne COPQ  
        $copq_result = $this->copq_calculator->calculate($input_data);
        
        // 4. Berechne Opportunity (optional)
        $opportunity_result = null;
        if ($input_data['include_opportunity']) {
            $opportunity_result = $this->opportunity_calculator->calculate($input_data);
        }
        
        // 5. Kombiniere Ergebnisse
        return new QCC_Calculation_Result([
            'cogq' => $cogq_result,
            'copq' => $copq_result,
            'opportunity' => $opportunity_result,
            'total_quality_cost' => $cogq_result->total + $copq_result->total,
            'metadata' => $this->generate_metadata($input_data)
        ]);
    }
}