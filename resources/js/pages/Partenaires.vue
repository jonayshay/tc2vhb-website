<template>
    <div>
        <h1>Nos Partenaires</h1>

        <p v-if="partenaires.length === 0">
            Aucun partenaire pour le moment.
        </p>

        <div v-else class="partners-grid">
            <div
                v-for="partner in partenaires"
                :key="partner.id"
                class="partner-card"
            >
                <a
                    v-if="partner.url"
                    :href="partner.url"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <img
                        v-if="partner.logo"
                        :src="'/storage/' + partner.logo"
                        :alt="partner.name"
                    />
                    <span v-else>{{ partner.name }}</span>
                </a>
                <template v-else>
                    <img
                        v-if="partner.logo"
                        :src="'/storage/' + partner.logo"
                        :alt="partner.name"
                    />
                    <span v-else>{{ partner.name }}</span>
                </template>

                <p class="partner-name">{{ partner.name }}</p>
                <p v-if="partner.description" class="partner-description">
                    {{ partner.description }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    partenaires: {
        type: Array,
        required: true,
    },
});
</script>

<style scoped>
.partners-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .partners-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1024px) {
    .partners-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.partner-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
}

.partner-card img {
    max-width: 120px;
    max-height: 80px;
    object-fit: contain;
}

.partner-name {
    font-weight: 600;
    text-align: center;
}

.partner-description {
    font-size: 0.875rem;
    color: #6b7280;
    text-align: center;
}
</style>
