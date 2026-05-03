<script setup>
defineProps({
    category: Object,
    teams: Array,
    players: Array,
})
</script>

<template>
    <div>
        <h1>{{ category.name }}</h1>
    </div>
</template>
