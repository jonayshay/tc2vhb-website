<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div>
        <h1>Le Club</h1>

        <nav class="club-nav">
            <Link href="/le-club/presentation">Présentation du club</Link>
            <Link href="/le-club/bureau">Bureau & Conseil d'Administration</Link>
            <Link href="/le-club/commissions">Commissions</Link>
            <Link href="/le-club/entraineurs">Entraîneurs</Link>
            <Link href="/le-club/arbitres">Arbitres</Link>
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

.club-nav :deep(a) {
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    text-decoration: none;
    color: inherit;
}
</style>
