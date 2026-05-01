<script setup>
defineProps({
    presentation: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div>
        <h1>{{ presentation.title }}</h1>
        <p>{{ presentation.accroche }}</p>
    </div>
</template>
