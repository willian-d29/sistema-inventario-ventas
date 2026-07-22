<template>
    <nav
        class="top-0 absolute z-50 w-full flex flex-wrap items-center justify-between px-2 py-3 navbar-expand-lg"
    >
        <div
            class="container px-4 mx-auto flex flex-wrap items-center justify-between"
        >
            <div
                class="w-full relative flex justify-center lg:w-auto lg:static lg:block lg:justify-start"
            >
                <Link href="/" class="text-white text-sm font-bold leading-relaxed inline-block mr-4 py-2 whitespace-nowrap uppercase">
                    <ApplicationLogo class="h-7 fill-current text-gray-500" />
                </Link>
            </div>
        </div>
    </nav>
</template>
<script>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import {Link} from "@inertiajs/vue3";

export default {
    components: {
        ApplicationLogo,
        Link
    },
};
</script>
