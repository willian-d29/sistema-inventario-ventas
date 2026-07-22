<template>
  <footer class="block py-4">
    <div class="container mx-auto px-4">
      <hr class="mb-4 border-b-1 border-slate-200" />
      <div
        class="flex flex-wrap items-center md:justify-between justify-center"
      >
        <div class="w-full md:w-4/12 px-4">
          <div
            class="text-sm text-slate-500 font-semibold py-1 text-center md:text-left"
          >
            Copyright © {{ date }}
<!--            <a-->
<!--              href="/"-->
<!--              class="text-slate-500 hover:text-slate-700 text-sm font-semibold py-1"-->
<!--            >-->
<!--              Creative Tim-->
<!--            </a>-->
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>
<script>
export default {
  data() {
    return {
      date: new Date().getFullYear(),
    };
  },
};
</script>
