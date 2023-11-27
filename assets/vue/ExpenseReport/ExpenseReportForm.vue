<template>
    <div class="expense-report-form white-box">
        <h2>Note de frais</h2>
        <form @submit.prevent="onFormSubmit">
            <div class="field">
                <label for="refund_required_no">
                <input type="radio" id="refund_required_no" name="refund_required" value="0" checked>
                Je fais don au club
                </label>
            </div>
            <div class="field">
                <label for="refund_required_yes">
                <input type="radio" id="refund_required_yes" name="refund_required" value="1">
                Je demande le remboursement des frais
                </label>
            </div>
            <fieldset
                v-for="expenseReportFormGroup in formStructure"
                :key="expenseReportFormGroup.slug"
            >
                <legend>{{ expenseReportFormGroup.name }}</legend>

                <div class="field" v-if="expenseReportFormGroup.type == 'unique'">
                    <select v-model="expenseReportFormGroup.selectedType" placeholder="Choisir un type de frais">
                        <option 
                            v-for="expenseType in expenseReportFormGroup.expenseTypes" 
                            :value="expenseType.slug"
                            :key="expenseType.slug"
                        >
                            {{ expenseType.name }}
                        </option>
                    </select>
                </div>

                <div v-for="expenseType in expenseReportFormGroup.expenseTypes" :key="expenseType.id">
                    <div v-if="expenseReportFormGroup.type !== 'unique' || expenseReportFormGroup.selectedType === expenseType.slug">
                        <h3>{{ expenseType.name }}</h3>
                        <div
                            v-for="field in expenseType.fields"
                            :key="field.slug"
                            class="field"
                        >
                            <label>{{ field.name }}</label>
                            <input type="text" :name="field.slug" :value="field.value" />
    
                            <div v-if="field.needsJustification" class="justification">
                                <label class="uploader-label">Joindre un justificatif
                                    <input class="hidden" type="file" name="{{ field.slug }}-justification">
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="expenseReportFormGroup.type == 'multiple'">
                    <a href="#" @click.prevent="spawnExpenseGroup(expenseReportFormGroup)">Ajouter</a>
                </div>
            </fieldset>
            <div class="green-box expense-report-summary" id="expense-report-summary">
                <h3>Résumé :</h3>
                <div>Total remboursable : <span class="refund-amount">123.00€</span></div>
                <div>Hébergement : 60.00€, Transport : 63.00€</div>
            </div>
            <div class="buttons">
                <button type="submit" class="biglink">
                    <span class="bleucaf">&gt;</span>
                    Valider
                </button> 
                <button type="submit" class="biglink">
                    <span class="emoji">&#128190;</span>
                    Sauvegarder le brouillon
            </button>
            </div>
        </form>
    </div>
</template>

<script lang="ts">
    import { defineComponent } from 'vue';

    export default defineComponent({
        name: 'expense-report-form',
        props: ['formStructureProp'],
        data() {
            return {
                formStructure: this.formStructureProp
            }
        },
        methods: {
            onFormSubmit() {
                console.log('onFormSubmit');
            },
            spawnExpenseGroup(expenseReportFormGroup: any) {
                expenseReportFormGroup.expenseTypes.push(
                    {...expenseReportFormGroup.expenseTypes[0], 
                        id: expenseReportFormGroup.expenseTypes.length + 1
                });
            }
        },
    })
</script>