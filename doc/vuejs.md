# Usage des composants VueJS

## Création du composant

Créer le composant dans un fichier `*.vue` dans `/assets/vue`.

Par exemple : 

`/assets/vue/Example/Example.vue`
```html
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
        props: ['foo'] // declare the properties you set in twig as props
    })
</script>
```

## Déclarer le composant

Ajouter une référence au composant dans `/assets/ts/vue-components.ts` :

```ts
import {createApp} from 'vue';
import Example from '../vue/Example/Example.vue';

(window as any).vue = {
    createApp,
    // register your component here
    components: {
        Example
    }
};
```

## Utilisation dans Twig

Pour afficher un composant Vue au sein d'un template twig, on peut utiliser l'extension `vueComponent()`.

La fonction attend trois arguments : 
- `selector` : l'id de l'élément `div` qui sera généré pour afficher le composant
- `componentName` : le nom du composant à générer, tel que défini lors de la création et de son register dans `vue-components.ts` (voir section précédente)
- `data` : les données à passer au composant sous forme d'objet. Les données transmises au composant sont accessibles directement au sein de celui-ci via ses props (voir le code dans la section précédente)

```twig
<!DOCTYPE html>
<html>
    <body>
        {{ vueComponent('example-component', 'Example', {foo: "bar"}) }}
    </body>
</html>
```



