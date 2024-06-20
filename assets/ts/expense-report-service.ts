import expenseReportConfig from '../config/expense-reports.json';

const expenseReportService = {
    getExpenseReport: () => {
       
    },
    autoCalculation: {
        transportation(formStructure: any) : number {
            const transportationMode = formStructure.transport.expenseTypes.find((expenseType: any) => {
                return expenseType.slug === formStructure.transport.selectedType;
            });

            if (!transportationMode) {
                return 0;
            }

            let total: number = 0;
            // règles de calcul spécifiques au mode de transport
            // véhicule personnel
            if (transportationMode.slug === 'vehicule_personnel') {
                const distance : number = parseFloat(transportationMode.fields.find((field: any) => field.slug === 'distance').value) || 0.0;
                const toll : number = parseFloat(transportationMode.fields.find((field: any) => field.slug === 'peage').value) || 0.0;
                const passengers : number = parseInt(transportationMode.fields.find((field: any) => field.slug === 'nombre_voyageurs').value) || 0;
                // distance * taux kilométrique
                total += distance * expenseReportConfig.tauxKilometriqueVoiture;
                // péage / nombre voyageurs
                total += passengers !== 0 ? toll / passengers : 0.0;
            }

            // minibus location
            else if (transportationMode.slug === 'minibus_location') {
                const fuel : number = parseFloat(transportationMode.fields.find((field: any) => field.slug === 'prix_carburant').value) || 0.0;
                const rent : number = parseFloat(transportationMode.fields.find((field: any) => field.slug === 'prix_location').value) || 0.0;
                const toll : number = parseFloat(transportationMode.fields.find((field: any) => field.slug === 'peage').value) || 0.0;
                const passengers : number = parseInt(transportationMode.fields.find((field: any) => field.slug === 'nombre_voyageurs').value) || 0;
                // prix location
                total += rent;
                // essence / nombre voyageurs
                total += passengers !== 0 ? fuel / passengers : 0.0;
                // péage / nombre voyageurs
                total += passengers !== 0 ? toll / passengers : 0.0;
            }

            // minibus club
            else if (transportationMode.slug === 'minibus_club') {
                const fuel : number = parseFloat(transportationMode.fields.find((field: any) => field.slug === 'prix_carburant').value) || 0.0;
                const distance : number = parseFloat(transportationMode.fields.find((field: any) => field.slug === 'distance').value) || 0.0;
                const toll : number = parseFloat(transportationMode.fields.find((field: any) => field.slug === 'peage').value) || 0.0;
                const passengers : number = parseInt(transportationMode.fields.find((field: any) => field.slug === 'nombre_voyageurs').value) || 0;

                // distance * taux kilométrique
                total += distance * expenseReportConfig.tauxKilometriqueMinibus;
                // essence / nombre voyageurs
                total += passengers !== 0 ? fuel / passengers : 0.0;
                // péage / nombre voyageurs
                total += passengers !== 0 ? toll / passengers : 0;
            }

            // par défaut
            else {
                total =  transportationMode.fields.reduce((total: number, field: any) => {
                    const value : number = parseFloat(field.value) || 0.0
                    return total + (field.flags.isUsedForTotal ? value : 0.0);
                }, 0)
            }

            return total;
        },
        accommodation(formStructure: any) {
            return formStructure.hebergement.expenseTypes.reduce((total: number, expenseType: any) => {
                return total + expenseType.fields.reduce((fieldTotal: number, field: any) => {
                    const value : number = parseFloat(field.value) || 0.0;
                    const newTotal = fieldTotal + (field.flags.isUsedForTotal ? value : 0);
                    return newTotal >= expenseReportConfig.nuiteeMaxRemboursable ? expenseReportConfig.nuiteeMaxRemboursable : newTotal;
                }, 0);
            }, 0);
        },
        autres(formStructure: any) {
            return formStructure.autres.expenseTypes.reduce((total: number, expenseType: any) => {
                return total + expenseType.fields.reduce((fieldTotal: number, field: any) => {
                    return fieldTotal + (field.flags.isUsedForTotal ? parseFloat(field.value) : 0);
                }, 0);
            }, 0);
        },
    }
};

export default expenseReportService;
