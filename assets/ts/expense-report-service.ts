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
            // régles de calcul spécifiques au mode de transport
            // véhicule personnel
            if (transportationMode.slug === 'vehicule-personnel') {
                console.log(transportationMode.fields);
                const distanceField = transportationMode.fields.find((field: any) => field.slug === 'distance');
                const tollField = transportationMode.fields.find((field: any) => field.slug === 'peage');
                const passengerNumberField = transportationMode.fields.find((field: any) => field.slug === 'nombre_voyageurs');
                // nb de km AR *0.12
                total += parseFloat(distanceField.value) * 0.12;
                // peage / nombre voyageurs
                total += parseFloat(tollField.value) / parseFloat(passengerNumberField.value);
            }

            // minibus location
            else if (transportationMode.slug === 'minibus-location') {
                // prix location / km
                // nb km AR *0.3 / nombre voyageurs
                // essence / nombre voyageurs
                // péage / nombre voyageurs
            }

            // minibus club
            else if (transportationMode.slug === 'minibus-club') {
                // nb km AR *0.3 / nombre voyageurs
                // essence / nombre voyageurs
                // péage / nombre voyageurs
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
                    return newTotal >= 45 ? 45 : newTotal;
                }, 0);
            }, 0);
        }
    }
};

export default expenseReportService;
