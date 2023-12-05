<template>
    <div class="expense-report-form white-box">
        <h2>Note de frais</h2>
        <form @submit.prevent="onFormSubmit">
            <fieldset>
                <legend>Remboursement</legend>
                <div class="field">
                    <label for="refund_required_no">
                    <input type="radio" id="refund_required_no" name="refund_required" value="0" checked>
                    Je fais don de cette note de frais au club et recevrai en fin d'année un reçu fiscal
                    </label>
                </div>
                <div class="field">
                    <label for="refund_required_yes">
                    <input type="radio" id="refund_required_yes" name="refund_required" value="1">
                    Je demande le remboursement de cette note de frais
                    </label>
                </div>
            </fieldset>
            <fieldset
                v-for="expenseReportFormGroup in formStructure"
                :key="expenseReportFormGroup.slug"
            >
                <legend>
                    {{ expenseReportFormGroup.name }}
                    <a 
                        v-if="expenseReportFormGroup.type == 'multiple'"
                        class="add-more"
                        href="#"
                        @click.prevent="spawnExpenseGroup(expenseReportFormGroup)"
                    >
                        <span class="emoji">
                            &#10133;
                            Ajouter
                        </span>
                    </a>
                </legend>
                <div class="field type-select" v-if="expenseReportFormGroup.type == 'unique'">
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
                                {{ expenseType.name }} {{ parseInt(expenseTypeIndex as any) + 1 }}
                                <a
                                    v-if="expenseReportFormGroup.type == 'multiple' && expenseTypeIndex !== 0"
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
                                    class="field">
                                </ExpenseField>
                            </div>
                        </div>
                    </div>
                </div>
            </fieldset>
            <div class="green-box expense-report-summary" id="expense-report-summary">
                <h3>Résumé :</h3>
                <div>Total remboursable : <span class="refund-amount">123€</span></div>
                <div>Hébergement : 60.00€, Transport : 63.00€</div>
            </div>
            <div class="errors" v-if="errorMessages.length"></div>
            <div class="buttons">
                <button type="submit" class="biglink">
                    <span class="bleucaf">&gt;</span>
                    Valider
                </button> 
                <button @click.prevent="saveDraftExpenseReport" class="biglink">
                    <span class="emoji">&#128190;</span>
                    Sauvegarder le brouillon
            </button>
            </div>
        </form>
    </div>
</template>

<script lang="ts">
    import { defineComponent } from 'vue';
    import ExpenseField from './ExpenseField.vue';

    export default defineComponent({
        name: 'expense-report-form',
        props: ['formStructureProp'],
        components: {
            ExpenseField
        },
        data() {
            return {
                formStructure: this.formStructureProp,
                errorMessages: [] as string[]
            }
        },
        methods: {
            onFormSubmit() {
                this.saveExpenseReport((window as any).enums.expenseReportStatuses.STATUS_SUBMITTED);
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
                        }
                    }),
                    name: expenseReportFormGroup.expenseTypes[0].name,
                    slug: expenseReportFormGroup.expenseTypes[0].slug,
                    id: expenseReportFormGroup.expenseTypes.length + 1
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
            async saveExpenseReport(status: string) {
                try {
                    const response = await fetch((window as any).globals.apiBaseUrl + '/expense-report', 
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            status,
                            ...this.formStructure
                        })
                    });
                    const responseJson = await response.json();

                } catch (error: any) {
                    this.errorMessages.push(error.message);
                }
            }
        }
    });
</script>