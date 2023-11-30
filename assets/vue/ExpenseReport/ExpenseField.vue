<template>
    <div class="field">
        <label>{{ field.name }}</label>
        
        <input
            :required="field.flags.isMandatory"
            type="number"
            :name="field.slug"
            v-model="field.value"
            v-if="field.inputType === 'numeric'"
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
                <a href="#">Voir</a>
            </div>

            <label v-else class="uploader-label bleucaf">
                <span class="emoji">
                    &#128190;
                    Joindre un justificatif
                </span>
                <input 
                    class="hidden" 
                    type="file" 
                    multiple
                    name="{{ field.slug }}-justification"
                    @change="onFileUploadChange($event)"
                >
            </label>
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent({
    name: 'expense-field',
    props: ['field'],
    methods: {
        onFileUploadChange(event: any) {
            this.field.justificationFile = event.target.files[0];
            console.log('onFileUploadChange', event, this.field);
        },
        removeFile() {
            this.field.justificationFile = null;
        }
    },
});
</script>