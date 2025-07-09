<template>
    <AppLayout title="Home" >
        <div class="py-8">
            <div class="main-container max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="title text-white">
                    STRONG BULLS
                </div>
                <div class="title text-white">
                    HOME PAGE
                </div>
                <!-- Page Cards -->
<!--                <div :class="`grid ${gridColsClass} justify-center py-5`">-->
<!--                    <div-->
<!--                        v-for="(page, index) in visiblePages"-->
<!--                        :key="page.label"-->
<!--                        class="flex flex-col items-center"-->
<!--                        @mouseenter="hoveredIndex = index"-->
<!--                        @mouseleave="hoveredIndex = null"-->
<!--                    >-->
<!--                        &lt;!&ndash; Icone &ndash;&gt;-->
<!--                        <Link :href="route(page.route)">-->
<!--                            <img :src="hoveredIndex === index ? page.hoverIcon : page.icon" alt=""-->
<!--                                 :class="[ 'w-28 h-28 mb-3 transition-all duration-300', hoveredIndex === index ? 'mt-6' : '']" />-->
<!--                        </Link>-->

<!--                        &lt;!&ndash; Label &ndash;&gt;-->
<!--                        <span class="font-serif text-[15px] text-[#1A1930] mb-2">-->
<!--                            <span class="font-medium italic">By</span>-->
<!--                            {{ page.label }}-->
<!--                        </span>-->

<!--                        &lt;!&ndash; Bottom Border &ndash;&gt;-->
<!--                        <div class="w-20 h-[2px] bg-[#9a7b50]"></div>-->
<!--                    </div>-->
<!--                </div>-->
            </div>
        </div>
    </AppLayout>
</template>
<script setup>
import { ref, onMounted, computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import usePermissions from "@/Composables/usePermissions.js";
import AppLayout from "@/Layouts/AppLayout.vue";
import userIcon from "@/Icons/user.svg?url";
import locationIcon from "@/Icons/location.svg?url";
import categoryIcon from "@/Icons/car.svg?url";
import productIcon from "@/Icons/product.svg?url";
import userIconHover from "@/Icons/userHover.svg?url";
import locationIconHover from "@/Icons/locationHover.svg?url";
import categoryIconHover from "@/Icons/carHover.svg?url";
import productIconHover from "@/Icons/productHover.svg?url";
import providerIcon from "@/Icons/provider.svg?url";
import providerIconHover from "@/Icons/providerHover.svg?url";
import clientIcon from "@/Icons/client.svg?url";
import clientIconHover from "@/Icons/clientHover.svg?url";

//Define o nome dado ao ficheiro
defineOptions({
    name: "index",
});

const hoveredIndex = ref(null);
const page = usePage();
const user = page.props.auth.user;
const { canAccess } = usePermissions();

const pages = [
    { label: "Category", value: "category", route: "categories", icon: categoryIcon, hoverIcon: categoryIconHover },
    { label: "Product", value: "product", route: "products", icon: productIcon, hoverIcon: productIconHover },
    { label: "Provider", value: "provider", route: "providers", icon: providerIcon, hoverIcon: providerIconHover },
    { label: "Client", value: "client", route: "clients", icon: clientIcon, hoverIcon: clientIconHover },
];

// Filtro as paginas com base nas permissoes
const visiblePages = computed(() => {
    return pages.filter((page) => canAccess(page.value, "list"));
});

// Determino dinamicamente a grid Class
const gridColsClass = computed(() => {
    const count = visiblePages.value.length;
    if (count === 1) return "grid-cols-1";
    if (count === 2) return "grid-cols-2";
    if (count === 3) return "md:grid-cols-3";
    return "md:grid-cols-3 lg:grid-cols-4";
});
</script>
<style scoped>
.title {
    font-family: "Georgia", "Times New Roman", serif;
    font-weight: 500;
    font-size: 36px;
    color: #1D3A32;
    text-align: center;
    padding: 40px 0;
}
</style>
