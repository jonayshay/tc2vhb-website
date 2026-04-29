<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    articles: Object,
})

function stripHtml(html) {
    const div = document.createElement('div')
    div.innerHTML = html
    return div.textContent || div.innerText || ''
}

function excerpt(content, length = 150) {
    const text = stripHtml(content).trim()
    return text.length > length ? text.slice(0, length).trimEnd() + '…' : text
}

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
        <h1>Actualités</h1>

        <p v-if="articles.data.length === 0">
            Aucune actualité pour le moment.
        </p>

        <div v-else>
            <div>
                <article v-for="article in articles.data" :key="article.id">
                    <Link :href="`/actualites/${article.slug}`">
                        <img
                            v-if="article.featured_image"
                            :src="`/storage/${article.featured_image}`"
                            :alt="article.title"
                        />
                        <h2>{{ article.title }}</h2>
                    </Link>
                    <time>{{ formatDate(article.published_at) }}</time>
                    <p>{{ excerpt(article.content) }}</p>
                </article>
            </div>

            <nav v-if="articles.links && articles.links.length > 3">
                <template v-for="link in articles.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        :aria-current="link.active ? 'page' : undefined"
                    />
                    <span v-else v-html="link.label" aria-disabled="true" />
                </template>
            </nav>
        </div>
    </div>
</template>
