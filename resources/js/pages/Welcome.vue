<script setup lang="ts">
import { dashboard, login } from '@/routes';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Moon, Sun } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

const page = usePage();
const user = page.props.auth?.user ?? null;

const theme = ref(localStorage.getItem('theme') || 'dark');
const toggleTheme = () => {
    theme.value = theme.value === 'dark' ? 'light' : 'dark';
};

watch(theme, (val) => {
    document.documentElement.classList.toggle('dark', val === 'dark');
    localStorage.setItem('theme', val);
});

onMounted(() => {
    document.documentElement.classList.toggle('dark', theme.value === 'dark');
});

const features = [
    {
        title: 'Smart Dashboard',
        desc: 'Your control center with quick insights from every module — leads, clients, projects, tasks, and appointments.',
        icon: '🧭',
    },
    {
        title: 'Lead Management',
        desc: 'Capture, track, and convert leads effortlessly with import/export, filtering, and direct client conversion.',
        icon: '🧩',
    },
    {
        title: 'Appointment Scheduling',
        desc: 'Never miss an opportunity with beautiful calendar views, real-time updates, and smart reminders.',
        icon: '📅',
    },
    {
        title: 'Client Management',
        desc: 'Complete client profiles with activity history, linked records, and comprehensive search capabilities.',
        icon: '👥',
    },
    {
        title: 'Project Management',
        desc: 'Plan, track, and collaborate with your team on multiple projects with document and task management.',
        icon: '🧱',
    },
    {
        title: 'Task Management',
        desc: 'Stay productive with prioritized tasks linked to leads, clients, or projects with real-time tracking.',
        icon: '✅',
    },
    {
        title: 'Notes & Documents',
        desc: 'Keep everything organized with internal notes, invoice management, and file categorization.',
        icon: '📝',
    },
    {
        title: 'Real-Time Notifications',
        desc: 'Never miss an update with in-app notifications for assignments, updates, and appointments.',
        icon: '🔔',
    },
    {
        title: 'Activity Log',
        desc: 'Transparent tracking of all actions across every module for accountability and audits.',
        icon: '🕓',
    },
    {
        title: 'User & Role Management',
        desc: 'Efficient team management with role-based permissions for Admins, Managers, and Staff.',
        icon: '👤',
    },
];

const useCases = [
    {
        title: 'Marketing Agencies',
        desc: 'Manage campaigns, leads, and clients in one unified system.',
    },
    {
        title: 'Creative Studios',
        desc: 'Handle multiple projects and collaborators with ease.',
    },
    {
        title: 'Digital Consultancies',
        desc: 'Track clients, meetings, and proposals efficiently.',
    },
    {
        title: 'Software Agencies',
        desc: 'Oversee projects, clients, and internal tasks with precision.',
    },
];

const benefits = [
    'Centralizes your entire workflow — no more spreadsheets or scattered tools',
    'Saves time through automation and real-time collaboration',
    'Improves team accountability with transparent activity tracking',
    'Enhances client experience by keeping all interactions in one place',
    'Scales effortlessly as your agency grows',
];
</script>

<template>
    <Head title="AgencyCRM - The All-in-One CRM Solution for Modern Agencies" />

    <div
        class="min-h-screen bg-gradient-to-br from-white via-blue-50/30 to-indigo-50/20 transition-colors duration-500 dark:from-slate-950 dark:via-slate-900 dark:to-slate-800"
    >
        <!-- Navbar -->
        <nav
            class="sticky top-0 z-50 w-full border-b border-slate-200/50 bg-white/80 backdrop-blur-xl dark:border-slate-800/50 dark:bg-slate-950/80"
        >
            <div class="mx-auto max-w-7xl px-6 py-4">
                <div class="flex items-center justify-between">
                    <Link
                        href="/"
                        class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-2xl font-bold text-transparent"
                    >
                        AgencyCRM
                    </Link>

                    <div class="hidden items-center space-x-8 md:flex">
                        <a
                            href="#features"
                            class="font-medium text-slate-700 transition-all duration-300 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400"
                            >Features</a
                        >
                        <a
                            href="#usecases"
                            class="font-medium text-slate-700 transition-all duration-300 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400"
                            >Perfect For</a
                        >
                        <a
                            href="#tech"
                            class="font-medium text-slate-700 transition-all duration-300 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400"
                            >Tech Stack</a
                        >
                        <a
                            href="#install"
                            class="font-medium text-slate-700 transition-all duration-300 hover:text-blue-600 dark:text-slate-300 dark:hover:text-blue-400"
                            >Install</a
                        >
                    </div>

                    <div class="flex items-center space-x-4">
                        <button
                            @click="toggleTheme"
                            class="rounded-xl border border-slate-200 bg-white p-2 transition-all duration-300 hover:shadow-lg dark:border-slate-700 dark:bg-slate-800"
                        >
                            <Sun
                                v-if="theme === 'dark'"
                                class="h-5 w-5 text-amber-500"
                            />
                            <Moon v-else class="h-5 w-5 text-slate-600" />
                        </button>

                        <template v-if="user">
                            <Link
                                :href="dashboard()"
                                class="transform rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-2.5 font-semibold text-white transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
                            >
                                Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                :href="login()"
                                class="transform rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-2.5 font-semibold text-white transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
                            >
                                Demo
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section
            class="relative flex min-h-screen items-center justify-center overflow-hidden px-6"
        >
            <div
                class="bg-grid-slate-200/50 dark:bg-grid-slate-800/30 absolute inset-0 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.6))]"
            ></div>

            <div class="relative mx-auto max-w-6xl text-center">
                <div
                    class="mb-8 inline-flex items-center rounded-full border border-blue-200 bg-blue-100 px-4 py-2 dark:border-blue-800 dark:bg-blue-900/30"
                >
                    <span
                        class="text-sm font-medium text-blue-700 dark:text-blue-300"
                        >All-in-One CRM Solution for Modern Agencies</span
                    >
                </div>

                <h1 class="mb-8 text-5xl leading-tight font-bold md:text-7xl">
                    The Complete
                    <span
                        class="bg-gradient-to-r from-blue-600 via-purple-600 to-blue-800 bg-clip-text text-transparent"
                        >Agency CRM</span
                    >
                </h1>

                <p
                    class="mx-auto mb-12 max-w-3xl text-xl leading-relaxed text-slate-600 md:text-2xl dark:text-slate-400"
                >
                    Manage clients, leads, projects, and appointments in one
                    powerful platform. Built for modern agencies with Laravel,
                    Vue, and cutting-edge technology.
                </p>

                <div
                    class="flex flex-col items-center justify-center gap-4 sm:flex-row"
                >
                    <Link
                        :href="user ? dashboard() : login()"
                        class="transform rounded-2xl bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-4 text-lg font-bold text-white transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl"
                    >
                        {{ user ? 'Go to Dashboard' : 'Start Demo' }}
                        <i class="ml-2">→</i>
                    </Link>
                    <a
                        href="#features"
                        class="rounded-2xl border-2 border-slate-300 px-8 py-4 font-bold text-slate-700 transition-all duration-300 hover:border-blue-500 dark:border-slate-700 dark:text-slate-300 dark:hover:border-blue-400"
                    >
                        Explore Features
                    </a>
                </div>

                <div
                    class="mx-auto mt-16 grid max-w-2xl grid-cols-2 gap-8 md:grid-cols-4"
                >
                    <div class="text-center">
                        <div
                            class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                        >
                            100%
                        </div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">
                            Open Source
                        </div>
                    </div>
                    <div class="text-center">
                        <div
                            class="text-2xl font-bold text-purple-600 dark:text-purple-400"
                        >
                            All-in-One
                        </div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">
                            Platform
                        </div>
                    </div>
                    <div class="text-center">
                        <div
                            class="text-2xl font-bold text-green-600 dark:text-green-400"
                        >
                            Real-Time
                        </div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">
                            Updates
                        </div>
                    </div>
                    <div class="text-center">
                        <div
                            class="text-2xl font-bold text-orange-600 dark:text-orange-400"
                        >
                            v2.0
                        </div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">
                            Latest
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="bg-white px-6 py-20 dark:bg-slate-900">
            <div class="mx-auto max-w-6xl">
                <div class="mb-16 text-center">
                    <h2 class="mb-6 text-4xl font-bold md:text-5xl">
                        🌟 Core Features for
                        <span
                            class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"
                            >Modern Agencies</span
                        >
                    </h2>
                    <p
                        class="mx-auto max-w-2xl text-xl text-slate-600 dark:text-slate-400"
                    >
                        Everything you need to manage your agency operations
                        efficiently and effectively in one unified platform.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="(feature, i) in features"
                        :key="i"
                        class="group rounded-3xl border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-8 transition-all duration-500 hover:-translate-y-2 hover:border-blue-300 hover:shadow-2xl dark:border-slate-700 dark:from-slate-800 dark:to-slate-900 dark:hover:border-blue-700"
                    >
                        <div class="mb-4 text-3xl">{{ feature.icon }}</div>
                        <h3
                            class="mb-4 text-xl font-bold text-slate-900 dark:text-white"
                        >
                            {{ feature.title }}
                        </h3>
                        <p
                            class="leading-relaxed text-slate-600 dark:text-slate-400"
                        >
                            {{ feature.desc }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Use Cases Section -->
        <section id="usecases" class="bg-slate-50 px-6 py-20 dark:bg-slate-800">
            <div class="mx-auto max-w-6xl">
                <div class="mb-16 text-center">
                    <h2 class="mb-6 text-4xl font-bold md:text-5xl">
                        💼 Perfect For
                        <span
                            class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"
                            >Your Agency</span
                        >
                    </h2>
                    <p
                        class="mx-auto max-w-2xl text-xl text-slate-600 dark:text-slate-400"
                    >
                        Built specifically for agencies and service-based teams
                        who need a unified workspace.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-2">
                    <div
                        v-for="(usecase, i) in useCases"
                        :key="i"
                        class="group rounded-3xl border border-slate-200 bg-white p-8 transition-all duration-500 hover:border-purple-300 hover:shadow-xl dark:border-slate-700 dark:bg-slate-900 dark:hover:border-purple-700"
                    >
                        <h3
                            class="mb-4 text-xl font-bold text-slate-900 dark:text-white"
                        >
                            {{ usecase.title }}
                        </h3>
                        <p
                            class="leading-relaxed text-slate-600 dark:text-slate-400"
                        >
                            {{ usecase.desc }}
                        </p>
                    </div>
                </div>

                <!-- Benefits Section -->
                <div
                    class="mt-16 rounded-3xl bg-gradient-to-r from-blue-500 to-purple-500 p-8 text-white"
                >
                    <h3 class="mb-6 text-center text-2xl font-bold">
                        💡 How AgencyCRM Helps Your Agency
                    </h3>
                    <ul class="mx-auto grid max-w-4xl gap-4 md:grid-cols-2">
                        <li
                            v-for="(benefit, i) in benefits"
                            :key="i"
                            class="flex items-start gap-3"
                        >
                            <div
                                class="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-white"
                            ></div>
                            <span>{{ benefit }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Tech Stack Section -->
        <section id="tech" class="bg-white px-6 py-20 dark:bg-slate-900">
            <div class="mx-auto max-w-6xl">
                <div class="mb-16 text-center">
                    <h2 class="mb-6 text-4xl font-bold md:text-5xl">
                        Built with
                        <span
                            class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"
                            >Modern Technology</span
                        >
                    </h2>
                    <p
                        class="mx-auto max-w-2xl text-xl text-slate-600 dark:text-slate-400"
                    >
                        Leveraging Laravel 12, Vue 3, and cutting-edge tools for
                        lightning-fast performance and real-time updates.
                    </p>
                </div>

                <div
                    class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-6"
                >
                    <div
                        v-for="(tech, index) in [
                            {
                                name: 'Laravel',
                                color: 'from-red-500 to-pink-500',
                            },
                            {
                                name: 'Vue 3',
                                color: 'from-green-500 to-emerald-500',
                            },
                            {
                                name: 'MySQL',
                                color: 'from-blue-500 to-cyan-500',
                            },
                            {
                                name: 'Inertia.js',
                                color: 'from-purple-500 to-indigo-500',
                            },
                            {
                                name: 'Tailwind CSS',
                                color: 'from-cyan-500 to-blue-500',
                            },
                            {
                                name: 'Vite',
                                color: 'from-yellow-500 to-amber-500',
                            },
                        ]"
                        :key="index"
                        class="group rounded-2xl border border-slate-200 bg-slate-50 p-6 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-700 dark:bg-slate-800"
                    >
                        <div class="flex flex-col items-center text-center">
                            <div
                                class="mb-4 flex h-16 w-16 items-center justify-center rounded-xl bg-gradient-to-r transition-transform duration-300 group-hover:scale-110"
                                :class="tech.color"
                            >
                                <div class="text-sm font-bold text-white">
                                    {{ tech.name.split(' ')[0] }}
                                </div>
                            </div>
                            <span
                                class="font-semibold text-slate-900 dark:text-white"
                                >{{ tech.name }}</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Installation Section -->
        <section id="install" class="bg-slate-50 px-6 py-20 dark:bg-slate-800">
            <div class="mx-auto max-w-4xl">
                <div class="mb-16 text-center">
                    <h2 class="mb-6 text-4xl font-bold md:text-5xl">
                        Get Started in
                        <span
                            class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"
                            >Minutes</span
                        >
                    </h2>
                    <p class="text-xl text-slate-600 dark:text-slate-400">
                        Deploy your own AgencyCRM instance with just a few
                        commands.
                    </p>
                </div>

                <div
                    class="rounded-3xl bg-slate-900 p-8 shadow-2xl dark:bg-slate-800"
                >
                    <div class="mb-6 flex items-center gap-4">
                        <div class="flex gap-2">
                            <div class="h-3 w-3 rounded-full bg-red-500"></div>
                            <div
                                class="h-3 w-3 rounded-full bg-yellow-500"
                            ></div>
                            <div
                                class="h-3 w-3 rounded-full bg-green-500"
                            ></div>
                        </div>
                        <div class="font-mono text-sm text-slate-400">
                            terminal
                        </div>
                    </div>

                    <div class="space-y-4 font-mono text-sm">
                        <div class="flex items-center gap-4">
                            <span class="text-green-400">$</span>
                            <span class="text-blue-400">git clone</span>
                            <span class="text-slate-200">
                                https://github.com/kamrankhan001/CRM-for-smalll-agency.git
                            </span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-green-400">$</span>
                            <span class="text-slate-200"
                                >cd CRM-for-smalll-agency</span
                            >
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-green-400">$</span>
                            <span class="text-blue-400">composer</span>
                            <span class="text-slate-200">install</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-green-400">$</span>
                            <span class="text-blue-400">npm</span>
                            <span class="text-slate-200">install</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-green-400">$</span>
                            <span class="text-blue-400">cp</span>
                            <span class="text-slate-200"
                                >.env.example .env</span
                            >
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-green-400">$</span>
                            <span class="text-blue-400">php</span>
                            <span class="text-slate-200"
                                >artisan key:generate</span
                            >
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-green-400">$</span>
                            <span class="text-blue-400">php</span>
                            <span class="text-slate-200"
                                >artisan migrate --seed</span
                            >
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="text-green-400">$</span>
                            <span class="text-blue-400">npm</span>
                            <span class="text-slate-200">run dev</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer
            class="bg-slate-900 px-6 py-12 text-slate-400 dark:bg-slate-950"
        >
            <div class="mx-auto max-w-6xl">
                <div
                    class="flex flex-col items-center justify-between md:flex-row"
                >
                    <div class="mb-6 md:mb-0">
                        <Link
                            href="/"
                            class="bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-2xl font-bold text-transparent"
                        >
                            AgencyCRM
                        </Link>
                        <p class="mt-2 text-sm">
                            Open source CRM for modern agencies
                        </p>
                    </div>

                    <div class="text-center md:text-right">
                        <p class="text-sm">
                            Open Source under
                            <a
                                href="https://opensource.org/licenses/MIT"
                                target="_blank"
                                class="text-blue-400 transition-colors hover:text-blue-300"
                                >MIT License</a
                            >
                            • Built by
                            <a
                                href="https://kamrankhan.dev"
                                target="_blank"
                                class="text-blue-400 transition-colors hover:text-blue-300"
                                >kamrankhan.dev</a
                            >
                        </p>
                        <p class="mt-2 text-xs text-slate-500">
                            Made with ❤️ for the open source community
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.bg-grid-slate-200\/50 {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32' width='32' height='32' fill='none' stroke='rgb(226 232 240 / 0.5)'%3e%3cpath d='m0 .5h31.5v32'/%3e%3c/svg%3e");
}

.dark .bg-grid-slate-800\/30 {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32' width='32' height='32' fill='none' stroke='rgb(30 41 59 / 0.3)'%3e%3cpath d='m0 .5h31.5v32'/%3e%3c/svg%3e");
}
</style>

<style>
html {
  scroll-behavior: smooth;
  scroll-padding-top: 80px;
}
section {
  scroll-margin-top: 80px;
}
</style>