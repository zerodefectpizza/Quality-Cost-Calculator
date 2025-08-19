/**
 * Quality Cost Calculator - FINALE LÖSUNG
 * Basiert auf der Diagnose - setzt ALLE Standardwerte korrekt
 */

console.log('QCC: Final Fix Loading...');

(function() {
    'use strict';
    
    // KORRIGIERTE STANDARDWERTE basierend auf gefundenen IDs
    const DEFAULT_VALUES = {
        'qcc-revenue': '140',
        'qcc-quality-percentage': '6',
        'qcc-prevention': '10',           // DIESE WAREN DAS PROBLEM!
        'qcc-appraisal': '20',            // DIESE WAREN DAS PROBLEM!
        'qcc-internal-defect': '30',      // DIESE WAREN DAS PROBLEM!
        'qcc-external-defect': '40',      // DIESE WAREN DAS PROBLEM!
        'qcc-lost-sales': '5',
        'qcc-customer-churn': '2',
        'qcc-market-share-loss': '1',
        'qcc-productivity-loss': '3'
    };
    
    // DIREKTE Element-Suche per ID
    function getElement(id) {
        const element = document.getElementById(id);
        if (element) {
            console.log('QCC: Found element:', id);
            return element;
        }
        console.log('QCC: Element not found:', id);
        return null;
    }
    
    // AGGRESSIVE Standardwerte-Setzung
    function setAllDefaultValues() {
        console.log('QCC: Setting all default values...');
        
        let setCount = 0;
        
        Object.entries(DEFAULT_VALUES).forEach(([id, defaultValue]) => {
            const element = getElement(id);
            if (element && element.type === 'number') {
                // IMMER setzen wenn leer oder 0
                if (!element.value || element.value === '0' || element.value === '') {
                    element.value = defaultValue;
                    setCount++;
                    console.log(`QCC: Set ${id} = ${defaultValue}`);
                    
                    // Events auslösen
                    element.dispatchEvent(new Event('input', { bubbles: true }));
                    element.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    console.log(`QCC: ${id} already has value: ${element.value}`);
                }
            }
        });
        
        console.log(`QCC: Set ${setCount} default values`);
        return setCount;
    }
    
    // VERBESSERTE Berechnung
    function calculate() {
        console.log('QCC: Calculating...');
        
        try {
            // Werte direkt per ID holen
            const revenue = parseFloat(getElement('qcc-revenue')?.value) || 140;
            const qualityPercentage = parseFloat(getElement('qcc-quality-percentage')?.value) || 6;
            const prevention = parseFloat(getElement('qcc-prevention')?.value) || 10;
            const appraisal = parseFloat(getElement('qcc-appraisal')?.value) || 20;
            const internalDefect = parseFloat(getElement('qcc-internal-defect')?.value) || 30;
            const externalDefect = parseFloat(getElement('qcc-external-defect')?.value) || 40;
            
            console.log('QCC: Calculation inputs:', {
                revenue, qualityPercentage, prevention, appraisal, internalDefect, externalDefect
            });
            
            // Unit
            const unit = 1000000;
            const revenueInUnit = revenue * unit;
            
            // Berechnungen
            const totalQualityCost = (revenueInUnit * qualityPercentage) / 100;
            const preventionCost = (totalQualityCost * prevention) / 100;
            const appraisalCost = (totalQualityCost * appraisal) / 100;
            const internalDefectCost = (totalQualityCost * internalDefect) / 100;
            const externalDefectCost = (totalQualityCost * externalDefect) / 100;
            
            // COGQ/COPQ
            const totalCOGQ = preventionCost + appraisalCost;
            const totalCOPQ = internalDefectCost + externalDefectCost;
            
            function formatValue(value) {
                return (value / unit).toFixed(2);
            }
            
            // Results setzen - mit mehreren möglichen IDs
            function setResult(possibleIds, value) {
                let success = false;
                possibleIds.forEach(id => {
                    const element = getElement(id);
                    if (element) {
                        element.textContent = value;
                        success = true;
                        console.log(`QCC: Set result ${id} = ${value}`);
                    }
                });
                return success;
            }
            
            // Ergebnisse setzen
            setResult(['qcc-total-quality-cost', 'total-quality-cost'], formatValue(totalQualityCost));
            setResult(['qcc-prevention-cost', 'prevention-cost'], formatValue(preventionCost));
            setResult(['qcc-appraisal-cost', 'appraisal-cost'], formatValue(appraisalCost));
            setResult(['qcc-internal-defect-cost', 'internal-defect-cost'], formatValue(internalDefectCost));
            setResult(['qcc-external-defect-cost', 'external-defect-cost'], formatValue(externalDefectCost));
            
            const cogqSet = setResult(['qcc-total-cogq', 'total-cogq', 'qcc-cogq-total', 'cogq-total'], formatValue(totalCOGQ));
            const copqSet = setResult(['qcc-total-copq', 'total-copq', 'qcc-copq-total', 'copq-total'], formatValue(totalCOPQ));
            
            if (cogqSet) {
                console.log('QCC: COGQ successfully set to', formatValue(totalCOGQ));
            } else {
                console.log('QCC: COGQ element not found');
            }
            
            if (copqSet) {
                console.log('QCC: COPQ successfully set to', formatValue(totalCOPQ));
            } else {
                console.log('QCC: COPQ element not found');
            }
            
            // Opportunity Costs
            const opportunityToggle = getElement('qcc-opportunity-toggle');
            const isOpportunityEnabled = opportunityToggle && (opportunityToggle.value === 'enabled' || opportunityToggle.checked);
            
            if (isOpportunityEnabled) {
                const lostSales = parseFloat(getElement('qcc-lost-sales')?.value) || 5;
                const customerChurn = parseFloat(getElement('qcc-customer-churn')?.value) || 2;
                const marketShareLoss = parseFloat(getElement('qcc-market-share-loss')?.value) || 1;
                const productivityLoss = parseFloat(getElement('qcc-productivity-loss')?.value) || 3;
                
                const lostSalesCost = (revenueInUnit * lostSales) / 100;
                const customerChurnCost = (revenueInUnit * customerChurn) / 100;
                const marketShareCost = (revenueInUnit * marketShareLoss) / 100;
                const productivityCost = (revenueInUnit * productivityLoss) / 100;
                const totalOpportunityCost = lostSalesCost + customerChurnCost + marketShareCost + productivityCost;
                
                setResult(['qcc-lost-sales-cost', 'lost-sales-cost'], formatValue(lostSalesCost));
                setResult(['qcc-customer-churn-cost', 'customer-churn-cost'], formatValue(customerChurnCost));
                setResult(['qcc-market-share-cost', 'market-share-cost'], formatValue(marketShareCost));
                setResult(['qcc-productivity-cost', 'productivity-cost'], formatValue(productivityCost));
                setResult(['qcc-total-opportunity-cost', 'total-opportunity-cost'], formatValue(totalOpportunityCost));
                
                const totalImpact = totalQualityCost + totalOpportunityCost;
                const revenuePercentage = ((totalImpact / revenueInUnit) * 100).toFixed(1);
                
                setResult(['qcc-total-coq', 'total-coq'], formatValue(totalImpact));
                setResult(['qcc-revenue-percentage', 'revenue-percentage'], revenuePercentage + '%');
                
                console.log('QCC: Opportunity costs calculated');
            }
            
            console.log('QCC: Calculation completed successfully');
            
            // Validierung
            validatePercentages();
            
        } catch (error) {
            console.error('QCC: Calculation error:', error);
        }
    }
    
    // VERBESSERTE Validierung
    function validatePercentages() {
        try {
            const prevention = parseFloat(getElement('qcc-prevention')?.value) || 0;
            const appraisal = parseFloat(getElement('qcc-appraisal')?.value) || 0;
            const internalDefect = parseFloat(getElement('qcc-internal-defect')?.value) || 0;
            const externalDefect = parseFloat(getElement('qcc-external-defect')?.value) || 0;
            
            const total = prevention + appraisal + internalDefect + externalDefect;
            const isValid = Math.abs(total - 100) < 0.01;
            
            // Error message element finden
            const errorElements = [
                getElement('qcc-percentage-error'),
                getElement('percentage-error'),
                document.querySelector('.qcc-error-message'),
                document.querySelector('[style*="color:#dc3545"]')
            ].filter(el => el !== null);
            
            if (!isValid) {
                errorElements.forEach(errorEl => {
                    if (errorEl) {
                        errorEl.style.display = 'block';
                        errorEl.textContent = `Values do not add up to 100% (currently: ${total.toFixed(1)}%)`;
                    }
                });
                console.log('QCC: Validation failed - total:', total.toFixed(1) + '%');
            } else {
                errorElements.forEach(errorEl => {
                    if (errorEl) {
                        errorEl.style.display = 'none';
                    }
                });
                console.log('QCC: Validation passed - total: 100%');
            }
        } catch (error) {
            console.error('QCC: Validation error:', error);
        }
    }
    
    // VERBESSERTE Event-Setup
    function setupEvents() {
        console.log('QCC: Setting up events...');
        
        let eventCount = 0;
        
        Object.keys(DEFAULT_VALUES).forEach(id => {
            const element = getElement(id);
            if (element && element.type === 'number') {
                // Input Event
                element.addEventListener('input', function() {
                    // Auto-restore default if empty
                    if (!element.value && DEFAULT_VALUES[id]) {
                        setTimeout(function() {
                            if (!element.value) {
                                element.value = DEFAULT_VALUES[id];
                                console.log(`QCC: Auto-restored ${id} = ${DEFAULT_VALUES[id]}`);
                            }
                        }, 100);
                    }
                    calculate();
                });
                
                // Focus Event
                element.addEventListener('focus', function() {
                    if (!element.value && DEFAULT_VALUES[id]) {
                        element.value = DEFAULT_VALUES[id];
                        console.log(`QCC: Set default on focus ${id} = ${DEFAULT_VALUES[id]}`);
                    }
                });
                
                eventCount++;
                console.log(`QCC: Events added to ${id}`);
            }
        });
        
        console.log(`QCC: Events setup complete - ${eventCount} elements`);
    }
    
    // RESET-Funktion
    function resetToDefaults() {
        console.log('QCC: Resetting to defaults...');
        
        Object.entries(DEFAULT_VALUES).forEach(([id, defaultValue]) => {
            const element = getElement(id);
            if (element) {
                element.value = defaultValue;
                console.log(`QCC: Reset ${id} = ${defaultValue}`);
            }
        });
        
        setTimeout(calculate, 50);
    }
    
    // PERIODISCHER SCHUTZ
    function setupPeriodicProtection() {
        setInterval(function() {
            let restoredCount = 0;
            
            Object.entries(DEFAULT_VALUES).forEach(([id, defaultValue]) => {
                const element = getElement(id);
                if (element && (!element.value || element.value === '0')) {
                    element.value = defaultValue;
                    restoredCount++;
                }
            });
            
            if (restoredCount > 0) {
                console.log(`QCC: Periodic protection restored ${restoredCount} values`);
                calculate();
            }
        }, 10000);
        
        console.log('QCC: Periodic protection active (every 10s)');
    }
    
    // GLOBALES QCC OBJEKT
    window.QCC = {
        calculate: calculate,
        resetToDefaults: resetToDefaults,
        setDefaults: setAllDefaultValues,
        exportToCSV: function() {
            // CSV Export Implementation
            const data = Object.entries(DEFAULT_VALUES).map(([id, defaultValue]) => {
                const element = getElement(id);
                return [id.replace('qcc-', ''), element?.value || defaultValue];
            });
            
            const csvContent = [['Field', 'Value'], ...data]
                .map(row => row.join(',')).join('\n');
            
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'quality_costs.csv';
            a.click();
            URL.revokeObjectURL(url);
        },
        exportToPDF: function() { window.print(); },
        debug: function() {
            return {
                version: 'final-fix',
                defaultValues: DEFAULT_VALUES,
                currentValues: Object.fromEntries(
                    Object.keys(DEFAULT_VALUES).map(id => [
                        id, 
                        getElement(id)?.value || 'NOT_FOUND'
                    ])
                )
            };
        }
    };
    
    // HAUPTINITIALISIERUNG
    function init() {
        console.log('QCC: Final fix initialization...');
        
        // 1. Sofort Standardwerte setzen
        const set1 = setAllDefaultValues();
        console.log('QCC: Initial defaults set:', set1);
        
        // 2. Events setup
        setupEvents();
        
        // 3. Periodischen Schutz aktivieren
        setupPeriodicProtection();
        
        // 4. Nach 200ms nochmals Standardwerte
        setTimeout(() => {
            const set2 = setAllDefaultValues();
            console.log('QCC: Secondary defaults set:', set2);
        }, 200);
        
        // 5. Nach 500ms berechnen
        setTimeout(() => {
            calculate();
            console.log('QCC: Final fix ready!');
        }, 500);
        
        // 6. Nach 2s finaler Schutz
        setTimeout(() => {
            const set3 = setAllDefaultValues();
            console.log('QCC: Final protection set:', set3);
        }, 2000);
    }
    
    // DOM READY
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // JQUERY FALLBACK
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(init);
    }
    
})();

// GLOBALE LEGACY-FUNKTIONEN
window.forceQCCDefaults = function() {
    console.log('QCC: Force defaults triggered');
    if (window.QCC && window.QCC.setDefaults) {
        return window.QCC.setDefaults();
    }
    return 0;
};

window.debugQCC = function() {
    console.log('QCC: Debug information');
    if (window.QCC && window.QCC.debug) {
        return window.QCC.debug();
    }
    return { error: 'QCC not available' };
};

console.log('QCC: Final fix script loaded - will set ALL default values correctly!');