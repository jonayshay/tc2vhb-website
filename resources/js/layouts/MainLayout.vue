<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div>
        <header>
            <nav>
                <Link href="/">TC2V Handball</Link>
                <Link href="/equipes">Nos Équipes</Link>
                <Link href="/actualites">Actualités</Link>
                <Link href="/le-club">Le Club</Link>
                <Link href="/partenaires">Partenaires</Link>
            </nav>
        </header>
        <main>
            <slot />
        </main>
        <footer>
            <p>TC2V Handball — Triel, Chanteloup, Vernouillet, Verneuil</p>
        </footer>
    </div>
</template>
