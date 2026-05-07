<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    categories: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div>
        <h1>Nos Équipes</h1>

        <p v-if="categories.length === 0">
            Aucune catégorie enregistrée pour la saison courante.
        </p>

        <div v-else class="categories-grid">
            <Link
                v-for="category in categories"
                :key="category.id"
                :href="`/equipes/${category.slug}`"
                class="category-card"
            >
                <span class="category-name">{{ category.name }}</span>
                <span class="category-gender">{{ category.gender }}</span>
            </Link>
        </div>
    </div>
</template>

<style scoped>
.categories-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-top: 1.5rem;
}

@media (min-width: 768px) {
    .categories-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1024px) {
    .categories-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.category-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.5rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    text-decoration: none;
    color: inherit;
    text-align: center;
    transition: border-color 0.15s;
}

.category-card:hover {
    border-color: #003A5D;
}

.category-name {
    font-weight: 600;
    font-size: 1rem;
}

.category-gender {
    font-size: 0.75rem;
    color: #7C878E;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
</style>
