import expenseReportConfig from '../config/expense-reports.json';

const expenseReportService = {
    getExpenseReport: () => {
       
    },
    autoCalculation: {
        transportation(formStructure: any) {
            const transportationMode = formStructure.transport.expenseTypes.find((expenseType: any) => {
                return expenseType.slug === formStructure.transport.selectedType;
            });

            if (!transportationMode) {
                return 0;
            }

            let total = 0;
            // règles de calcul spécifiques au mode de transport
            // véhicule personnel
            if (transportationMode.slug === 'vehicule-personnel') {
                const distanceField = transportationMode.fields.find((field: any) => field.slug === 'distance');
                const tollField = transportationMode.fields.find((field: any) => field.slug === 'peage');
                const passengerNumberField = transportationMode.fields.find((field: any) => field.slug === 'nombre_voyageurs');
                if (distanceField && tollField && passengerNumberField) {
                    // distance * taux kilométrique
                    total += parseFloat(distanceField.value) * expenseReportConfig.tauxKilometriqueVoiture;
                    // péage / nombre voyageurs
                    total += parseFloat(tollField.value) / parseInt(passengerNumberField.value);
                }
            }

            // minibus location
            else if (transportationMode.slug === 'minibus_location') {
                const fuelField = transportationMode.fields.find((field: any) => field.slug === 'prix_carburant');
                const rentPrice = transportationMode.fields.find((field: any) => field.slug === 'prix_location');
                const tollField = transportationMode.fields.find((field: any) => field.slug === 'peage');
                const passengerNumberField = transportationMode.fields.find((field: any) => field.slug === 'nombre_voyageurs');
                // prix location
                total += parseFloat(rentPrice.value);
                // essence / nombre voyageurs
                total += parseFloat(fuelField.value) / parseInt(passengerNumberField.value);
                // péage / nombre voyageurs
                total += parseFloat(tollField.value) / parseInt(passengerNumberField.value);
            }

            // minibus club
            else if (transportationMode.slug === 'minibus_club') {
                const fuelField = transportationMode.fields.find((field: any) => field.slug === 'prix_carburant');
                const distanceField = transportationMode.fields.find((field: any) => field.slug === 'distance');
                const tollField = transportationMode.fields.find((field: any) => field.slug === 'peage');
                const passengerNumberField = transportationMode.fields.find((field: any) => field.slug === 'nombre_voyageurs');

                // distance * taux kilométrique
                total += parseFloat(distanceField.value) * expenseReportConfig.tauxKilometriqueMinibus;
                // essence / nombre voyageurs
                total += parseFloat(fuelField.value) / parseInt(passengerNumberField.value);
                // péage / nombre voyageurs
                total += parseFloat(tollField.value) / parseInt(passengerNumberField.value);
            }

            // par défaut
            else {
                total =  transportationMode.fields.reduce((total: number, field: any) => {
                    return total + (field.flags.isUsedForTotal ? parseFloat(field.value) : 0);
                }, 0)
            }

            return total;
        },
        accommodation(formStructure: any) {
            return formStructure.hebergement.expenseTypes.reduce((total: number, expenseType: any) => {
                return total + expenseType.fields.reduce((fieldTotal: number, field: any) => {
                    const newTotal = fieldTotal + (field.flags.isUsedForTotal ? parseFloat(field.value) : 0);
                    return newTotal >= expenseReportConfig.nuiteeMaxRemboursable ? expenseReportConfig.nuiteeMaxRemboursable : newTotal;
                }, 0);
            }, 0);
        }
    }
};

export default expenseReportService;
