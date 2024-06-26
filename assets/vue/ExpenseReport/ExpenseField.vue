<template>
    <div class="field" :class="{error: field.errors}">
        <label>{{ field.name }} {{ field.flags.isMandatory ? '*' : '' }}</label>

        <input
            :required="field.flags.isMandatory"
            type="number"
            :name="field.slug"
            v-model="field.value"
            min="0"
            v-if="field.slug === 'distance'"
        />
        <input
            :required="field.flags.isMandatory"
            type="number"
            :name="field.slug"
            v-model.number="field.value"
            min="0"
            step="0.01"
            v-else-if="field.inputType === 'numeric' && field.slug !== 'nombre_voyageurs'"
        />
        <input
            :required="field.flags.isMandatory"
            type="number"
            :name="field.slug"
            v-model.number="field.value"
            min="0"
            pattern="\d+"
            v-else-if="field.inputType === 'numeric' && field.slug === 'nombre_voyageurs'"
            @keydown="isNumber($event)"
        />
        <textarea 
            :required="field.flags.isMandatory"
            v-else-if="field.inputType === 'text'"
            :name="field.slug"
            v-model="field.value"
        ></textarea>
        <input
            :required="field.flags.isMandatory"
            type="text"
            :name="field.slug"
            v-model="field.value"
            v-else
        />

        <div v-if="field.flags.needsJustification" class="justification">
            <div v-if="field.justificationFile">
                <div class="filename">
                    {{ field.justificationFile.name }}
                </div>
                <a href="#" @click.prevent="removeFile()">Supprimer</a> |
                <a :href="field.justificationFileUrl" target="_blank">Voir</a>
            </div>

            <label v-else class="uploader-label">
                <span class="emoji">
                    &#128190;
                    Joindre un justificatif 
                </span>
                <small>(max: 8 Mo)</small>
                <input 
                    class="hidden" 
                    type="file" 
                    multiple
                    accept=".jpg, .jpeg, .png, .pdf"
                    name="{{ field.slug }}-justification"
                    @change="onFileUploadChange($event)"
                >
            </label>
        </div>
        <div class="error" v-if="field.errors">
            <div v-for="error in field.errors" :key="error">{{ error }}</div>
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent({
    name: 'expense-field',
    props: ['field', 'config', 'expenseType'],
    data: () => ({
        justificationFileUrl: '',
    }),
    methods: {
        onFileUploadChange(event: any) {
            this.field.justificationFile = event.target.files[0];
            const formData = new FormData();
            formData.append('justification_document', this.field.justificationFile);
            // perform a fetch request to upload the file
            fetch('/expense-report/justification-document', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                this.field.justificationFileUrl = data.fileUrl;
            });
        },
        removeFile() {
            this.field.justificationFile = null;
            this.field.justificationFileUrl = '';
            this.justificationFileUrl = '';
        },
        isNumber(event: KeyboardEvent) {
            const disallowed : string[] = ['.', ',', 'e', 'E'];
            let key : string = event.key;
            if (disallowed.includes(key)) {
                event.preventDefault();
            }
        }
    },
    mounted() {
        if (this.field.justificationDocument) {
            fetch (this.field.justificationDocument)
                .then(response => response.blob())
                .then(blob => {
                    const name = this.field.justificationDocument.split('/').pop();
                    const file = new File([blob], name, { type: blob.type });
                    this.field.justificationFile = file;
                });
            this.field.justificationFileUrl = this.field.justificationDocument;
        }
    }
});
</script>