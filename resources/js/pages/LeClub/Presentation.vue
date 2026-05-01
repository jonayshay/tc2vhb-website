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

        <p class="accroche">{{ presentation.accroche }}</p>

        <img
            v-if="presentation.featured_image"
            :src="`/storage/${presentation.featured_image}`"
            :alt="presentation.title"
            class="featured-image"
        />

        <div class="content" v-html="presentation.content" />
    </div>
</template>

<style scoped>
.accroche {
    font-size: 1.125rem;
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.featured-image {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.content {
    line-height: 1.75;
}
</style>
