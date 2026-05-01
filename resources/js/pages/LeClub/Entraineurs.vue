<script setup>
defineProps({
    groupes: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div>
        <h1>Nos Entraîneurs</h1>
        <div v-for="groupe in groupes" :key="groupe.categorie">
            <h2>{{ groupe.categorie }}</h2>
            <div v-for="membre in groupe.membres" :key="membre.id">
                {{ membre.name }}
            </div>
        </div>
    </div>
</template>
