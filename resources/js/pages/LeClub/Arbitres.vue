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
        <h1>Nos Arbitres</h1>

        <p v-if="groupes.length === 0">
            Aucun arbitre enregistré pour le moment.
        </p>

        <div v-for="groupe in groupes" :key="groupe.categorie" class="groupe">
            <h2>{{ groupe.categorie }}</h2>

            <div class="staff-grid">
                <div
                    v-for="membre in groupe.membres"
                    :key="membre.id"
                    class="staff-card"
                >
                    <img
                        v-if="membre.photo"
                        :src="`/storage/${membre.photo}`"
                        :alt="membre.name"
                        class="staff-photo"
                    />
                    <div v-else class="staff-avatar-placeholder">
                        {{ membre.name.charAt(0).toUpperCase() }}
                    </div>

                    <p class="staff-name">{{ membre.name }}</p>
                    <p v-if="membre.bio" class="staff-bio">{{ membre.bio }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.groupe {
    margin-bottom: 3rem;
}

.staff-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-top: 1rem;
}

@media (min-width: 768px) {
    .staff-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1024px) {
    .staff-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.staff-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    text-align: center;
}

.staff-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.staff-avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #7C878E;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 600;
}

.staff-name {
    font-weight: 600;
}

.staff-bio {
    font-size: 0.875rem;
    color: #6b7280;
}
</style>
