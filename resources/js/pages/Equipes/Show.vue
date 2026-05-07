<script setup>
defineProps({
    category: {
        type: Object,
        required: true,
    },
    teams: {
        type: Array,
        required: true,
    },
    players: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div>
        <h1>{{ category.name }}</h1>

        <section v-if="teams.length > 0" class="section">
            <h2>Équipes</h2>
            <div class="staff-grid">
                <div
                    v-for="team in teams"
                    :key="team.id"
                    class="staff-card"
                >
                    <img
                        v-if="team.photo"
                        :src="`/storage/${team.photo}`"
                        :alt="team.name"
                        class="team-photo"
                    />
                    <div v-else class="staff-avatar-placeholder">
                        {{ (team.name ?? '?').charAt(0).toUpperCase() }}
                    </div>
                    <p class="staff-name">{{ team.name }}</p>
                </div>
            </div>
        </section>

        <section class="section">
            <h2>Joueurs</h2>

            <p v-if="players.length === 0">
                Aucun joueur enregistré pour cette catégorie.
            </p>

            <div v-else class="staff-grid">
                <div
                    v-for="player in players"
                    :key="player.id"
                    class="staff-card"
                >
                    <img
                        v-if="player.photo && player.has_image_rights"
                        :src="`/storage/${player.photo}`"
                        :alt="`${player.first_name} ${player.last_name}`"
                        class="staff-photo"
                    />
                    <div v-else class="staff-avatar-placeholder">
                        {{ (player.last_name ?? '?').charAt(0).toUpperCase() }}
                    </div>
                    <p class="staff-name">{{ player.first_name }} {{ player.last_name }}</p>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
.section {
    margin-top: 2rem;
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

.staff-photo,
.team-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.team-photo {
    border-radius: 0.25rem;
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
    font-size: 0.875rem;
}
</style>
