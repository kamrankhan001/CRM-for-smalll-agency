<script setup lang="ts">
import { dashboard, login, register } from '@/routes';
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
        title: 'Leads Management',
        desc: 'Track potential clients from first contact to conversion.',
    },
    {
        title: 'Client Management',
        desc: 'Manage client profiles and communication history.',
    },
    {
        title: 'Task Tracking',
        desc: 'Assign and monitor tasks and deadlines efficiently.',
    },
    {
        title: 'Notes',
        desc: 'Collaborative documentation to keep your team in sync.',
    },
    {
        title: 'User Roles',
        desc: 'Role-based access for Admins, Managers, and Members.',
    },
    {
        title: 'Dashboard Analytics',
        desc: 'Gain insight into team and lead performance.',
    },
];
</script>

<template>
  <Head title="Agency CRM — Manage Clients & Leads" />

  <div class="min-h-screen bg-gradient-to-br from-white via-blue-50/30 to-indigo-50/20 dark:from-slate-950 dark:via-slate-900 dark:to-slate-800 transition-colors duration-500">
    <!-- Navbar -->
    <nav class="sticky top-0 z-50 w-full border-b border-slate-200/50 bg-white/80 backdrop-blur-xl dark:border-slate-800/50 dark:bg-slate-950/80">
      <div class="mx-auto max-w-7xl px-6 py-4">
        <div class="flex items-center justify-between">
          <Link href="/" class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            AgencyCRM
          </Link>

          <div class="hidden md:flex items-center space-x-8">
            <a href="#features" class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-300 font-medium">Features</a>
            <a href="#tech" class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-300 font-medium">Tech Stack</a>
            <a href="#install" class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-300 font-medium">Install</a>
            <a href="https://kamrankhan.dev" target="_blank" class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-300 font-medium">About</a>
          </div>

          <div class="flex items-center space-x-4">
            <button @click="toggleTheme" class="p-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:shadow-lg transition-all duration-300">
              <Sun v-if="theme === 'dark'" class="h-5 w-5 text-amber-500" />
              <Moon v-else class="h-5 w-5 text-slate-600" />
            </button>

            <template v-if="user">
              <Link :href="dashboard()" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl hover:shadow-lg transition-all duration-300 transform hover:-translate-y-0.5">
                Dashboard
              </Link>
            </template>
            <template v-else>
              <Link :href="login()" class="px-6 py-2.5 text-slate-700 dark:text-slate-300 font-semibold hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-300">
                Login
              </Link>
              <Link :href="register()" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl hover:shadow-lg transition-all duration-300 transform hover:-translate-y-0.5">
                Get Started
              </Link>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center px-6 overflow-hidden">
      <div class="absolute inset-0 bg-grid-slate-200/50 dark:bg-grid-slate-800/30 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.6))]"></div>
      
      <div class="relative max-w-6xl mx-auto text-center">
        <div class="inline-flex items-center px-4 py-2 rounded-full bg-blue-100 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 mb-8">
          <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Open Source CRM Solution</span>
        </div>
        
        <h1 class="text-5xl md:text-7xl font-bold mb-8 leading-tight">
          Streamline Your
          <span class="bg-gradient-to-r from-blue-600 via-purple-600 to-blue-800 bg-clip-text text-transparent">Agency Workflow</span>
        </h1>
        
        <p class="text-xl md:text-2xl text-slate-600 dark:text-slate-400 mb-12 max-w-3xl mx-auto leading-relaxed">
          Manage leads, clients, and projects in one powerful platform. Built for modern agencies with Laravel, Vue, and cutting-edge technology.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
          <Link :href="user ? dashboard() : register()" class="px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold rounded-2xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 text-lg">
            {{ user ? 'Go to Dashboard' : 'Start Free Trial' }}
            <i class="ml-2">→</i>
          </Link>
          <a href="#features" class="px-8 py-4 border-2 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-2xl hover:border-blue-500 dark:hover:border-blue-400 transition-all duration-300">
            Explore Features
          </a>
        </div>

        <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-8 max-w-2xl mx-auto">
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">100%</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Open Source</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">∞</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Users</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">24/7</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Support</div>
          </div>
          <div class="text-center">
            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">v2.0</div>
            <div class="text-sm text-slate-600 dark:text-slate-400">Latest</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 px-6 bg-white dark:bg-slate-900">
      <div class="max-w-6xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold mb-6">Powerful Features for <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Modern Agencies</span></h2>
          <p class="text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
            Everything you need to manage your agency operations efficiently and effectively.
          </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div v-for="(feature, i) in features" :key="i" 
               class="group p-8 rounded-3xl bg-gradient-to-br from-white to-slate-50 dark:from-slate-800 dark:to-slate-900 border border-slate-200 dark:border-slate-700 hover:border-blue-300 dark:hover:border-blue-700 transition-all duration-500 hover:shadow-2xl hover:-translate-y-2">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
              <div class="text-white font-bold text-lg">{{ i + 1 }}</div>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">{{ feature.title }}</h3>
            <p class="text-slate-600 dark:text-slate-400 leading-relaxed">{{ feature.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Tech Stack Section -->
    <section id="tech" class="py-20 px-6 bg-slate-50 dark:bg-slate-800">
      <div class="max-w-6xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold mb-6">Built with <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Modern Technology</span></h2>
          <p class="text-xl text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
            Leveraging the latest tools and frameworks for optimal performance and developer experience.
          </p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
          <div v-for="(tech, index) in [
            { name: 'Laravel 12', color: 'from-red-500 to-pink-500', icon: 'laravel' },
            { name: 'Vue 3', color: 'from-green-500 to-emerald-500', icon: 'vue' },
            { name: 'MySQL', color: 'from-blue-500 to-cyan-500', icon: 'database' },
            { name: 'Inertia.js', color: 'from-purple-500 to-indigo-500', icon: 'inertia' },
            { name: 'Tailwind CSS', color: 'from-cyan-500 to-blue-500', icon: 'tailwind' },
            { name: 'Vite', color: 'from-yellow-500 to-amber-500', icon: 'vite' }
          ]" :key="index"
               class="group p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-xl bg-gradient-to-r mb-4 flex items-center justify-center group-hover:scale-110 transition-transform duration-300" :class="tech.color">
                <div class="text-white font-bold text-sm">{{ tech.name.split(' ')[0] }}</div>
              </div>
              <span class="font-semibold text-slate-900 dark:text-white">{{ tech.name }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Installation Section -->
    <section id="install" class="py-20 px-6 bg-white dark:bg-slate-900">
      <div class="max-w-4xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-4xl md:text-5xl font-bold mb-6">Get Started in <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Minutes</span></h2>
          <p class="text-xl text-slate-600 dark:text-slate-400">
            Deploy your own instance with just a few commands.
          </p>
        </div>

        <div class="bg-slate-900 dark:bg-slate-800 rounded-3xl p-8 shadow-2xl">
          <div class="flex items-center gap-4 mb-6">
            <div class="flex gap-2">
              <div class="w-3 h-3 rounded-full bg-red-500"></div>
              <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
              <div class="w-3 h-3 rounded-full bg-green-500"></div>
            </div>
            <div class="text-slate-400 text-sm font-mono">terminal</div>
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
              <span class="text-slate-200">cd CRM-for-smalll-agency</span>
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
              <span class="text-slate-200">.env.example .env</span>
            </div>
            <div class="flex items-center gap-4">
              <span class="text-green-400">$</span>
              <span class="text-blue-400">php</span>
              <span class="text-slate-200">artisan key:generate</span>
            </div>
            <div class="flex items-center gap-4">
              <span class="text-green-400">$</span>
              <span class="text-blue-400">php</span>
              <span class="text-slate-200">artisan migrate --seed</span>
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
    <footer class="bg-slate-900 dark:bg-slate-950 text-slate-400 py-12 px-6">
      <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <div class="mb-6 md:mb-0">
            <Link href="/" class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
              AgencyCRM
            </Link>
            <p class="mt-2 text-sm">Open source CRM for modern agencies</p>
          </div>
          
          <div class="text-center md:text-right">
            <p class="text-sm">
              Open Source under 
              <a href="https://opensource.org/licenses/MIT" target="_blank" class="text-blue-400 hover:text-blue-300 transition-colors">MIT License</a>
              • Built by 
              <a href="https://kamrankhan.dev" target="_blank" class="text-blue-400 hover:text-blue-300 transition-colors">kamrankhan.dev</a>
            </p>
            <p class="text-xs mt-2 text-slate-500">Made with ❤️ for the open source community</p>
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
