import './bootstrap';
import '../css/app.css';

// styles
import "@fortawesome/fontawesome-free/css/all.min.css";
import "@/assets/styles/tailwind.css";

import 'notivue/notification.css'
import 'notivue/animations.css'

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createNotivue } from 'notivue'
import axios from 'axios'; // ✅ asegúrate que axios esté presente
import { InertiaProgress } from '@inertiajs/progress'

// ✅ CONFIGURAR axios para CSRF correctamente (esencial para evitar 403 en logout)
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const notivue = createNotivue()

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(notivue)
            .mount(el);
    },
    progress: {
        color: '#334155',
        showSpinner: true,
    },
});

InertiaProgress.init(); // opcional: progreso en navegación
