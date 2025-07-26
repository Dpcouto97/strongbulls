<script setup>
import { ref } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import ApplicationMark from "@/Components/ApplicationMark.vue";
import Banner from "@/Components/Banner.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import usePermissions from '@/Composables/usePermissions';
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);
const page = usePage();
const user = page.props.auth.user;
const { canAccess } = usePermissions();

const switchToTeam = (team) => {
    router.put(
        route("current-team.update"),
        {
            team_id: team.id,
        },
        {
            preserveState: false,
        },
    );
};

const logout = () => {
    router.post(route("logout"));
};
</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />

        <div class="min-h-screen bg-gray-100 flex flex-col">
            <nav class="bg-white border-b border-gray-200  drop-shadow-md pb-8 py-8 z-30">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-10">
                        <div class="flex">
                            <!-- Logo da Empresa -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('index')">
                                    <img src="/images/strongbulls_logo.png" alt="Timeless" class="block h-20 w-auto" />
                                </Link>
                            </div>
                        </div>

                        <!-- Navigation Dropdown -->
                        <div class="flex items-center space-x-6">
                            <div class="hidden sm:flex sm:items-center sm:ms-10">
                                <Link
                                    :href="route('index')"
                                    class="group relative inline-block px-1 pb-1 text-sm uppercase tracking-wide text-black transition duration-150 hover:text-gray-700"
                                >
                                    HOME
                                    <span
                                        class="absolute left-0 bottom-0 h-0.5 w-full bg-current scale-x-0 group-hover:scale-x-100 transition-transform origin-left"
                                    />
                                </Link>
                            </div>
                            <div class="hidden sm:flex sm:items-center sm:ms-10">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="group relative inline-block px-1 pb-1 text-sm uppercase tracking-wide text-black transition duration-150 hover:text-gray-700 flex items-center"
                                        >
                                            BACKOFFICE
                                            <svg
                                                class="ms-2 -me-0.5 size-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke-width="1.5"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                                                />
                                            </svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <DropdownLink v-if="canAccess('client', 'list')" :href="route('clients')" :active="route().current('clients')">
                                            {{ $t('clients')}}
                                        </DropdownLink>
                                        <DropdownLink v-if="canAccess('evaluation', 'list')" :href="route('evaluations')" :active="route().current('evaluations')">
                                            {{ $t('evaluations')}}
                                        </DropdownLink>
                                        <DropdownLink v-if="canAccess('plan', 'list')" :href="route('plans')" :active="route().current('plans')">
                                            {{ $t('plans')}}
                                        </DropdownLink>
                                        <DropdownLink v-if="canAccess('exercise', 'list')" :href="route('exercises')" :active="route().current('exercises')">
                                            {{ $t('exercises')}}
                                        </DropdownLink>
                                        <DropdownLink
                                            v-if="canAccess('category', 'list')"
                                            :href="route('categories')"
                                            :active="route().current('categories')"
                                        >
                                            {{ $t('appointments')}}
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                            <div v-if="$page.props.auth.user" class="hidden sm:flex sm:items-center sm:ms-6">
                                <!-- Settings Dropdown -->
                                <!-- Menu para aceder ao perfil, definicoes, log out etc -->
                                <div class="ms-3 relative">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <button
                                                v-if="$page.props.jetstream.managesProfilePhotos"
                                                class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition"
                                            >
                                                <img
                                                    class="size-8 rounded-full object-cover"
                                                    :src="$page.props.auth.user.profile_photo_url"
                                                    :alt="$page.props.auth.user.name"
                                                />
                                            </button>

                                            <span v-else class="inline-flex rounded-md">
                                                <button
                                                    type="button"
                                                    class="group relative inline-block px-1 pb-1 text-sm uppercase tracking-wide text-black transition duration-150 hover:text-gray-700 flex items-center"
                                                >
                                                    {{ $page.props.auth.user.name }}

                                                    <svg
                                                        class="ms-2 -me-0.5 size-4"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke-width="1.5"
                                                        stroke="currentColor"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                                                        />
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>

                                        <template #content>
                                            <!-- Account Management - DROPDOWN COM NOME DO USER, PROFILE E LOG OUT -->
                                            <div class="block px-4 py-2 text-xs text-gray-400">{{ $t('manage_account')}}</div>

                                            <DropdownLink :href="route('profile.show')">{{ $t('profile')}}</DropdownLink>
                                            <DropdownLink v-if="user.is_admin || canAccess('user', 'list')" :href="route('users')">{{ $t('users')}}</DropdownLink>
                                            <DropdownLink v-if="user.is_admin" :href="route('user_groups')">{{ $t('user_groups')}}</DropdownLink>

                                            <DropdownLink
                                                v-if="$page.props.jetstream.hasApiFeatures"
                                                :href="route('api-tokens.index')"
                                            >
                                                API Tokens
                                            </DropdownLink>

                                            <div class="border-t border-gray-200" />

                                            <!-- Authentication -->
                                            <form @submit.prevent="logout">
                                                <DropdownLink as="button">{{ $t('logout')}}</DropdownLink>
                                            </form>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>

                            <div v-else class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <!--                                <NavLink :href="route('login')" :active="route().current('login')">LOG IN</NavLink>-->
                                <Link
                                    :href="route('login')"
                                    class="group relative inline-block px-1 pb-1 text-sm uppercase tracking-wide text-black transition duration-150 hover:text-gray-700"
                                >
                                    {{ $t('login')}}
                                    <span
                                        class="absolute left-0 bottom-0 h-0.5 w-full bg-current scale-x-0 group-hover:scale-x-100 transition-transform origin-left"
                                    />
                                </Link>
                                <!--                                <NavLink :href="route('register')" :active="route().current('register')">-->
                                <!--                                    REGISTER-->
                                <!--                                </NavLink>-->
                            </div>
                        </div>

                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main :class="['flex-1', route().current('index') ? 'bg-white' : 'bg-[#F9F9FC]']">
                <slot />
            </main>
        </div>
    </div>
</template>

<style>
.new-shadow {
    filter: drop-shadow(0 4px 3px rgba(0, 0, 0, 0.07)) drop-shadow(0 2px 2px rgba(0, 0, 0, 0.06)) !important;

}
</style>
