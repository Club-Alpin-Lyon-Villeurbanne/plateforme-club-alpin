<template>
    <div class="expense-report-form white-box">
        <h2>Note de frais</h2>
        <form @submit.prevent="onFormSubmit">
            <fieldset id="refund-required-group">
                <legend>Remboursement</legend>
                <div class="field">
                    <label for="refund_required_no">
                    <input type="radio" id="refund_required_no" name="refund_required" value="0" checked v-model="formStructure.refundRequired">
                    Je fais don de cette note de frais au club et recevrai en fin d'année un reçu fiscal
                    </label>
                </div>
                <div class="field">
                    <label for="refund_required_yes">
                    <input type="radio" id="refund_required_yes" name="refund_required" value="1" v-model="formStructure.refundRequired">
                    Je demande le remboursement de cette note de frais
                    </label>
                </div>
            </fieldset>
            <div 
                v-for="expenseReportFormGroup in formStructure"
                :key="expenseReportFormGroup.slug"
            >
                <fieldset
                    v-if="typeof expenseReportFormGroup === 'object'"
                    :id="'expense-group-' + expenseReportFormGroup.slug"
                >
                    <legend>
                        {{ expenseReportFormGroup.name }}
                        <a 
                            v-if="expenseReportFormGroup.type === 'multiple'"
                            class="add-more"
                            href="#"
                            @click.prevent="spawnExpenseGroup(expenseReportFormGroup)"
                        >
                            <span
                                v-if="expenseReportFormGroup.slug === 'hebergement'"
                                class="emoji"
                            >
                                &#10133;
                                Ajouter une nuitée
                            </span>
                            <span
                                v-else
                                class="emoji"
                            >
                                &#10133;
                                Ajouter
                            </span>
                        </a>
                    </legend>
                    <div class="field type-select" v-if="expenseReportFormGroup.type === 'unique'">
                        <label>Choisir le type</label>
                        <select v-model="expenseReportFormGroup.selectedType">
                            <option 
                                v-for="expenseType in expenseReportFormGroup.expenseTypes" 
                                :value="expenseType.slug"
                                :key="expenseType.slug"
                            >
                                {{ expenseType.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <div v-for="(expenseType, expenseTypeIndex) in expenseReportFormGroup.expenseTypes" :key="expenseType.id">
                            <div v-if="expenseReportFormGroup.type !== 'unique' || expenseReportFormGroup.selectedType === expenseType.slug">
                                <h4>
                                  <span v-if="expenseReportFormGroup.type !== 'unique' && expenseType.slug === 'nuitee'">{{ parseInt(expenseTypeIndex as any) + 1 }}° </span>
                                    {{ expenseType.name }}
                                  <span v-if="expenseReportFormGroup.type !== 'unique'  && expenseType.slug !== 'nuitee'">{{ parseInt(expenseTypeIndex as any) + 1 }}</span>
                                    <a
                                        v-if="expenseReportFormGroup.type === 'multiple' && expenseTypeIndex !== 0"
                                        class="delete" 

                                        href="#"
                                        @click.prevent="removeExpenseGroup(expenseReportFormGroup, expenseType)"
                                    >
                                        <span class="emoji">
                                            &#10060; 
                                            Supprimer
                                        </span>
                                    </a>
                                </h4>
                                <div class="field-list">
                                    <ExpenseField 
                                        v-for="field in expenseType.fields"
                                        :key="field.slug"
                                        :field="field"
                                        :config="expenseReportConfig"
                                        :expenseType="expenseType.slug"
                                        class="field">
                                    </ExpenseField>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <div class="green-box expense-report-summary" id="expense-report-summary">
                <h3>Résumé :</h3>
                <div>Total remboursable : <span class="refund-amount">{{ formatCurrency(refundableTotal) }}€</span></div>
                <div>Hébergement : {{ formatCurrency(accommodationTotal) }}€, Transport : {{ formatCurrency(transportationTotal) }}€, Autres : {{ formatCurrency(autresTotal) }}€</div>
            </div>
            <div class="errors" v-if="errorMessages.length">
                <h3>Erreur(s) :</h3>
                <p v-for="errorMessage in errorMessages" :key="errorMessage">{{ errorMessage }}</p>
            </div>
            <div class="success" v-if="successMessage">
                <p>{{ successMessage }}</p>
            </div>
            <div class="buttons">
                <button type="submit" class="biglink submit">
                    Valider
                </button> 
                <button @click.prevent="saveDraftExpenseReport" class="biglink">
                    Sauvegarder le brouillon
            </button>
            </div>
        </form>
    </div>
</template>

<script lang="ts">
    import { defineComponent } from 'vue';
    import ExpenseField from './ExpenseField.vue';
    import expenseReportService from '../../ts/expense-report-service';
    import expenseReportConfig from '../../config/expense-reports.json';

    export default defineComponent({
        name: 'expense-report-form',
        props: ['formStructureProp'],
        components: {
            ExpenseField
        },
        computed: {
            transportationTotal() {
                return expenseReportService.autoCalculation.transportation(this.formStructure);
            },
            accommodationTotal() {
                return expenseReportService.autoCalculation.accommodation(this.formStructure);
            },
            autresTotal() {
                return expenseReportService.autoCalculation.autres(this.formStructure);
            },
            refundableTotal() {
                return this.accommodationTotal + this.transportationTotal + this.autresTotal;
            }
        },
        data() {
            return {
                formStructure: {refundRequired: 0, ...this.formStructureProp},
                autoCalculation: {
                    refundable: 0,
                    transportation: 0,
                    accommodation: 0,
                },
                errorMessages: [] as string[],
                successMessage: '',
                expenseReportConfig: expenseReportConfig
            }
        },
        methods: {
            onFormSubmit() {
                this.saveExpenseReport((window as any).globals.enums.expenseReportStatuses.STATUS_SUBMITTED);
            },
            spawnExpenseGroup(expenseReportFormGroup: any) {
                expenseReportFormGroup.expenseTypes.push({
                    fields: expenseReportFormGroup.expenseTypes[0].fields.map((field: any) => {
                        return {
                            id: field.id,
                            name: field.name,
                            slug: field.slug,
                            inputType: field.inputType,
                            value: '',
                            flags: field.flags,
                            fieldTypeId: field.fieldTypeId,
                        }
                    }),
                    name: expenseReportFormGroup.expenseTypes[0].name,
                    slug: expenseReportFormGroup.expenseTypes[0].slug,
                    id: expenseReportFormGroup.expenseTypes.length + 1,
                    expenseTypeId: expenseReportFormGroup.expenseTypes[0].expenseTypeId,
                });
            },
            removeExpenseGroup(expenseReportFormGroup: any, expenseType: any) {
                expenseReportFormGroup.expenseTypes = expenseReportFormGroup.expenseTypes.filter((expenseTypeToFilter: any) => {
                    return expenseTypeToFilter.id !== expenseType.id;
                });
            },
            saveDraftExpenseReport() {
                this.saveExpenseReport((window as any).globals.enums.expenseReportStatuses.STATUS_DRAFT);
            },
            formatCurrency(value: number) {
                if (isNaN(value) || !isFinite(value)) {
                    return '--,--€';
                }
                return value.toFixed(2).replace('.', ',');
            },
            async saveExpenseReport(status: string) {
                const payload = {
                    status,
                    eventId: (window as any).globals.currentEventId,
                    ...this.formStructure
                };

                this.errorMessages = [];
                for (const expenseReportFormGroup in this.formStructure) {
                    if (typeof this.formStructure[expenseReportFormGroup] !== 'object') {
                        continue;
                    }
                    for (const expenseType of this.formStructure[expenseReportFormGroup].expenseTypes) {
                        for (const field of expenseType.fields) {
                           field.errors = null;
                        }
                    }
                }
                const apiUrl = (window as any).globals.apiBaseUrl ? (window as any).globals.apiBaseUrl : 'http://localhost:8000';
                
                try {
                    const response = await fetch(apiUrl + '/expense-report', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });
                    const responseJson = await response.json();
                    if (!(responseJson as any).success) {
                        for (const error of responseJson.errors) {
                            const targetGroup = this.formStructure[error.expenseGroup];

                            if (!targetGroup) {
                                this.errorMessages.push(error.message);
                                continue;
                            }

                            const targetExpenseType = targetGroup.expenseTypes.find((expenseType: any) => {
                                return expenseType.expenseTypeId === error.expenseTypeId;
                            });

                            const targetField = targetExpenseType.fields.find((field: any) => field.slug === error.field);

                            if (!targetField.errors) {
                                targetField.errors = [];
                            }
                            targetField.errors.push(error.message);
                        }
                    } else {
                        this.successMessage = 'Note de frais enregistrée avec succès !';
                        setTimeout(() => {
                            this.successMessage = '';
                        }, 5000);
                    }
                } catch (error: any) {
                    this.errorMessages.push('Une erreur est survenue lors de l\'enregistrement de la note de frais');
                    setTimeout(() => {
                            this.errorMessages = [];
                    }, 5000);
                }
            }
        }
    });
</script>