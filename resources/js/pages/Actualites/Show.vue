<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    article: Object,
})

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}
</script>

<template>
    <div>
        <Link href="/actualites">← Toutes les actualités</Link>

        <article>
            <img
                v-if="article.featured_image"
                :src="`/storage/${article.featured_image}`"
                :alt="article.title"
            />

            <h1>{{ article.title }}</h1>
            <time :datetime="article.published_at">
                {{ formatDate(article.published_at) }}
            </time>

            <div v-html="article.content" />
        </article>
    </div>
</template>
