<template>
    <div class="example-component">
        <h1>EXAMPLE</h1>
        {{ foo }}
    </div>
</template>

<script lang="ts">
    import { defineComponent } from 'vue';

    export default defineComponent({
        name: 'example',
        props: ['foo']
    })
</script>