/**
 * Quality Cost Calculator - Sofort funktionsfähige Version
 * Einfach und robust - GARANTIERT FUNKTIONSFÄHIG
 */

console.log('QCC: Loading...');

(function() {
    'use strict';
    
    // Hilfsfunktion
    function getEl(id) {
        return document.getElementById('qcc-' + id);
    }
    
    // Hauptberechnung
    function calculate() {
        console.log('QCC: Calculating...');
        
        try {
            // Eingabewerte
            const revenue = parseFloat(getEl('revenue').value) || 0;
            const qualityPercentage = parseFloat(getEl('quality-percentage').value) || 0;
            const prevention = parseFloat(getEl('prevention').value) || 0;
            const appraisal = parseFloat(getEl('appraisal').value) || 0;
            const internalDefect = parseFloat(getEl('internal-defect').value) || 0;
            const externalDefect = parseFloat(getEl('external-defect').value) || 0;
            
            // Unit
            const unitEl = getEl('unit');
            const unit = unitEl ? parseFloat(unitEl.value) || 1000000 : 1000000;
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
            
            // Format
            function formatValue(value) {
                return (value / unit).toFixed(2);
            }
            
            // Ergebnisse setzen
            const totalQualityCostEl = getEl('total-quality-cost');
            const preventionCostEl = getEl('prevention-cost');
            const appraisalCostEl = getEl('appraisal-cost');
            const internalDefectCostEl = getEl('internal-defect-cost');
            const externalDefectCostEl = getEl('external-defect-cost');
            
            if (totalQualityCostEl) totalQualityCostEl.textContent = formatValue(totalQualityCost);
            if (preventionCostEl) preventionCostEl.textContent = formatValue(preventionCost);
            if (appraisalCostEl) appraisalCostEl.textContent = formatValue(appraisalCost);
            if (internalDefectCostEl) internalDefectCostEl.textContent = formatValue(internalDefectCost);
            if (externalDefectCostEl) externalDefectCostEl.textContent = formatValue(externalDefectCost);
            
            // COGQ/COPQ nur setzen wenn Elemente existieren
            const totalCOGQEl = getEl('total-cogq');
            const totalCOPQEl = getEl('total-copq');
            
            if (totalCOGQEl) {
                totalCOGQEl.textContent = formatValue(totalCOGQ);
                console.log('QCC: COGQ set to', formatValue(totalCOGQ));
            } else {
                console.log('QCC: COGQ element not found');
            }
            
            if (totalCOPQEl) {
                totalCOPQEl.textContent = formatValue(totalCOPQ);
                console.log('QCC: COPQ set to', formatValue(totalCOPQ));
            } else {
                console.log('QCC: COPQ element not found');
            }
            
            // Opportunity Costs wenn aktiviert
            const opportunityToggle = getEl('opportunity-toggle');
            const isOpportunityEnabled = opportunityToggle && opportunityToggle.value === 'enabled';
            
            if (isOpportunityEnabled) {
                const lostSales = parseFloat(getEl('lost-sales').value) || 0;
                const customerChurn = parseFloat(getEl('customer-churn').value) || 0;
                const marketShareLoss = parseFloat(getEl('market-share-loss').value) || 0;
                const productivityLoss = parseFloat(getEl('productivity-loss').value) || 0;
                
                const lostSalesCost = (revenueInUnit * lostSales) / 100;
                const customerChurnCost = (revenueInUnit * customerChurn) / 100;
                const marketShareCost = (revenueInUnit * marketShareLoss) / 100;
                const productivityCost = (revenueInUnit * productivityLoss) / 100;
                const totalOpportunityCost = lostSalesCost + customerChurnCost + marketShareCost + productivityCost;
                
                if (getEl('lost-sales-cost')) getEl('lost-sales-cost').textContent = formatValue(lostSalesCost);
                if (getEl('customer-churn-cost')) getEl('customer-churn-cost').textContent = formatValue(customerChurnCost);
                if (getEl('market-share-cost')) getEl('market-share-cost').textContent = formatValue(marketShareCost);
                if (getEl('productivity-cost')) getEl('productivity-cost').textContent = formatValue(productivityCost);
                if (getEl('total-opportunity-cost')) getEl('total-opportunity-cost').textContent = formatValue(totalOpportunityCost);
                
                const totalImpact = totalQualityCost + totalOpportunityCost;
                const revenuePercentage = ((totalImpact / revenueInUnit) * 100).toFixed(1);
                
                if (getEl('total-coq')) getEl('total-coq').textContent = formatValue(totalImpact);
                if (getEl('revenue-percentage')) getEl('revenue-percentage').textContent = revenuePercentage + '%';
                
                console.log('QCC: Opportunity costs calculated', {
                    totalOpportunity: formatValue(totalOpportunityCost),
                    totalImpact: formatValue(totalImpact)
                });
            }
            
            console.log('QCC: Calculation complete', {
                totalQualityCost: formatValue(totalQualityCost),
                cogq: formatValue(totalCOGQ),
                copq: formatValue(totalCOPQ),
                cogqExists: !!totalCOGQEl,
                copqExists: !!totalCOPQEl
            });
            
            // Validierung
            validatePercentages();
            
        } catch (error) {
            console.error('QCC: Calculation error:', error);
        }
    }
    
    // Validierung
    function validatePercentages() {
        try {
            const prevention = parseFloat(getEl('prevention').value) || 0;
            const appraisal = parseFloat(getEl('appraisal').value) || 0;
            const internalDefect = parseFloat(getEl('internal-defect').value) || 0;
            const externalDefect = parseFloat(getEl('external-defect').value) || 0;
            
            const total = prevention + appraisal + internalDefect + externalDefect;
            const isValid = Math.abs(total - 100) < 0.01;
            
            const container = getEl('cost-distribution-section');
            const errorMsg = getEl('percentage-error');
            
            if (!isValid) {
                if (container) container.classList.add('qcc-error');
                if (errorMsg) {
                    errorMsg.style.display = 'block';
                    errorMsg.textContent = 'Values do not add up to 100%';
                }
            } else {
                if (container) container.classList.remove('qcc-error');
                if (errorMsg) errorMsg.style.display = 'none';
            }
        } catch (error) {
            console.error('QCC: Validation error:', error);
        }
    }
    
    // Opportunity Costs Toggle
    function toggleOpportunity() {
        console.log('QCC: Toggling opportunity costs...');
        
        try {
            const toggle = getEl('opportunity-toggle');
            if (!toggle) {
                console.log('QCC: Toggle element not found');
                return;
            }
            
            const isEnabled = toggle.value === 'enabled';
            console.log('QCC: Opportunity enabled:', isEnabled);
            console.log('QCC: Toggle value:', toggle.value);
            
            const opportunitySection = getEl('opportunity-section');
            const resultsColumns = getEl('results-columns');
            const opportunityResults = getEl('opportunity-results');
            const totalImpact = getEl('total-impact');
            
            console.log('QCC: Elements found:', {
                opportunitySection: !!opportunitySection,
                resultsColumns: !!resultsColumns,
                opportunityResults: !!opportunityResults,
                totalImpact: !!totalImpact
            });
            
            if (isEnabled) {
                console.log('QCC: Enabling opportunity costs...');
                if (opportunitySection) {
                    opportunitySection.classList.remove('qcc-disabled');
                    console.log('QCC: Removed qcc-disabled class');
                }
                if (resultsColumns) {
                    resultsColumns.style.display = 'grid';
                    resultsColumns.style.gridTemplateColumns = '1fr 1fr';
                    resultsColumns.style.gap = '20px';
                    console.log('QCC: Set results columns to grid');
                }
                if (opportunityResults) {
                    opportunityResults.style.display = 'block';
                    console.log('QCC: Showed opportunity results');
                }
                if (totalImpact) {
                    totalImpact.style.display = 'block';
                    console.log('QCC: Showed total impact');
                }
            } else {
                console.log('QCC: Disabling opportunity costs...');
                if (opportunitySection) {
                    opportunitySection.classList.add('qcc-disabled');
                    console.log('QCC: Added qcc-disabled class');
                }
                if (resultsColumns) {
                    resultsColumns.style.display = 'block';
                    resultsColumns.style.gridTemplateColumns = '1fr';
                    resultsColumns.style.gap = '0';
                    console.log('QCC: Set results columns to single');
                }
                if (opportunityResults) {
                    opportunityResults.style.display = 'none';
                    console.log('QCC: Hid opportunity results');
                }
                if (totalImpact) {
                    totalImpact.style.display = 'none';
                    console.log('QCC: Hid total impact');
                }
            }
            
            calculate();
        } catch (error) {
            console.error('QCC: Toggle error:', error);
        }
    }
    
    // Toggle sofort beim Laden ausführen
    function initializeToggle() {
        console.log('QCC: Initializing toggle state...');
        setTimeout(function() {
            toggleOpportunity();
        }, 100);
    }
    
    // Reset
    function resetDefaults() {
        console.log('QCC: Resetting...');
        
        try {
            if (getEl('revenue')) getEl('revenue').value = '140';
            if (getEl('quality-percentage')) getEl('quality-percentage').value = '6';
            if (getEl('prevention')) getEl('prevention').value = '10';
            if (getEl('appraisal')) getEl('appraisal').value = '20';
            if (getEl('internal-defect')) getEl('internal-defect').value = '30';
            if (getEl('external-defect')) getEl('external-defect').value = '40';
            
            setTimeout(calculate, 50);
        } catch (error) {
            console.error('QCC: Reset error:', error);
        }
    }
    
    // Event Listeners
    function setupEvents() {
        console.log('QCC: Setting up events...');
        
        const inputIds = [
            'revenue', 'quality-percentage', 'prevention', 'appraisal', 
            'internal-defect', 'external-defect', 'lost-sales', 'customer-churn', 
            'market-share-loss', 'productivity-loss'
        ];
        
        inputIds.forEach(function(id) {
            const el = getEl(id);
            if (el) {
                el.addEventListener('input', calculate);
                console.log('QCC: Event added to', id);
            }
        });
        
        const currencyEl = getEl('currency');
        const unitEl = getEl('unit');
        const opportunityToggleEl = getEl('opportunity-toggle');
        
        if (currencyEl) currencyEl.addEventListener('change', calculate);
        if (unitEl) unitEl.addEventListener('change', calculate);
        if (opportunityToggleEl) {
            opportunityToggleEl.addEventListener('change', toggleOpportunity);
            console.log('QCC: Opportunity toggle event listener added');
        }
    }
    
    // Export CSV
    function exportCSV() {
        try {
            const revenue = getEl('revenue').value || '0';
            const qualityPercentage = getEl('quality-percentage').value || '0';
            const preventionCost = getEl('prevention-cost').textContent || '0';
            const appraisalCost = getEl('appraisal-cost').textContent || '0';
            const internalDefectCost = getEl('internal-defect-cost').textContent || '0';
            const externalDefectCost = getEl('external-defect-cost').textContent || '0';
            
            const csvContent = [
                ['Parameter', 'Value'],
                ['Revenue', revenue],
                ['Quality Cost Basis (%)', qualityPercentage],
                ['Prevention Cost', preventionCost],
                ['Appraisal Cost', appraisalCost],
                ['Internal Defect Cost', internalDefectCost],
                ['External Defect Cost', externalDefectCost]
            ].map(row => row.join(',')).join('\n');
            
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'quality_costs.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } catch (error) {
            console.error('QCC: CSV export error:', error);
        }
    }
    
    // Globales QCC Objekt
    window.QCC = {
        calculate: calculate,
        resetToDefaults: resetDefaults,
        exportToCSV: exportCSV,
        exportToPDF: function() { window.print(); },
        toggleOpportunityCosts: toggleOpportunity
    };
    
    // Initialisierung
    function init() {
        console.log('QCC: Initializing...');
        
        setupEvents();
        
        // Toggle-Status initialisieren
        setTimeout(function() {
            initializeToggle();
        }, 200);
        
        // Sofort berechnen
        setTimeout(function() {
            calculate();
            console.log('QCC: Ready!');
        }, 300);
    }
    
    // DOM Ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // jQuery Fallback
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(init);
    }
    
})();

// Globale Funktionen für HTML-Aufrufe - WICHTIG: Außerhalb der IIFE!
window.toggleOpportunityCosts = function() {
    console.log('QCC: Global toggle called');
    if (window.QCC && window.QCC.toggleOpportunityCosts) {
        window.QCC.toggleOpportunityCosts();
    } else {
        console.error('QCC: QCC object not ready for toggle');
        // Fallback - versuche es in 100ms nochmal
        setTimeout(function() {
            if (window.QCC && window.QCC.toggleOpportunityCosts) {
                window.QCC.toggleOpportunityCosts();
            }
        }, 100);
    }
};

console.log('QCC: Script loaded!');