<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({ username: String })
</script>

<template>
    <Head title="Welcome" />
    <h1>Welcome</h1>
    <p>Hello {{ username }}!</p>
</template>
