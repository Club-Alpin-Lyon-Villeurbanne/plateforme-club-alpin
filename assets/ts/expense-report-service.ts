const expenseReportService = {
    getExpenseReport: () => {
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                resolve({
                    id: 1,
                    name: 'Expense Report 1'
                });
            }, 1000);
        });
    },
    autoCalculation: {
        transportation(formStructure: any) {
            const transportationMode = formStructure.transport.expenseTypes.find((expenseType: any) => {
                return expenseType.slug === formStructure.transport.selectedType;
            });

            if (!transportationMode) {
                return 0;
            }

            return transportationMode.fields.reduce((total: number, field: any) => {
                return total + (field.flags.isUsedForTotal ? parseFloat(field.value) : 0);
            }, 0)
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
