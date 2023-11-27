<template>
    <div class="field">
        <label>{{ field.name }}</label>
        <input 
            type="text"
            :name="field.slug"
            v-model="field.value"
            v-if="field.slug !== 'description'"
        />
        <textarea 
            v-else
            :name="field.slug"
            v-model="field.value"
        ></textarea>

        <div v-if="field.needsJustification" class="justification">
            <div v-if="field.justificationFile">
                <div class="filename">
                    {{ field.justificationFile.name }}
                </div>
                <a href="#" @click.prevent="removeFile(field)">Supprimer</a> |
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
                    name="{{ field.slug }}-justification"
                    @change="onFileUploadChange($event, field)"
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