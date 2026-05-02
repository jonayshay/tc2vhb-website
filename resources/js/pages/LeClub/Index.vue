<script setup>
</script>

<template>
    <div>
        <h1>Le Club</h1>

        <nav class="club-nav">
            <a href="/le-club/presentation">Présentation du club</a>
            <a href="/le-club/bureau">Bureau & Conseil d'Administration</a>
            <a href="/le-club/commissions">Commissions</a>
            <a href="/le-club/entraineurs">Entraîneurs</a>
            <a href="/le-club/arbitres">Arbitres</a>
        </nav>
    </div>
</template>

<style scoped>
.club-nav {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 2rem;
}

.club-nav a {
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    text-decoration: none;
    color: inherit;
}
</style>
