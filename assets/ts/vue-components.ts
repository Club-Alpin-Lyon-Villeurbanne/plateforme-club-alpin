import {createApp} from 'vue';
import Example from '../vue/Example/Example.vue';

(window as any).vue = {
    createApp,
    // register your component here
    components: {
        Example
    }
};