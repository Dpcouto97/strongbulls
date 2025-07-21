<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="h-screen flex overflow-hidden">
        <!-- Left Image -->
        <div class="w-1/2 hidden md:block">
            <img src="/images/login_background.png" alt="Login Image" class="object-cover h-full w-full" />
        </div>

        <!-- Right Form -->
        <div class=" md:w-1/2 flex items-center justify-center px-6">
            <div class="" style="width:550px">
                <!-- Logo -->
                <div class="mb-5 text-left">
                    <img src="/images/strongbulls_logo.png" alt="Timeless" class="object-cover h-32 w-auto"/>
                </div>

                <!-- Status -->
                <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                    {{ status }}
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="ml-4">
                    <h2 class="mb-10 title-font" style="font-size:25px">{{ $t('login') }}</h2>

                    <!-- Email -->
                    <div class="mb-4">
                        <label for="email" class="block text-xs uppercase tracking-wide text-gray-700 mb-2"></label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="w-full border-0 border-b-2 border-gray-400 focus:border-black focus:ring-0 pl-0"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="E-MAIL"
                        />
                        <div class="text-red-500 text-xs mt-1" v-if="form.errors.email">{{ form.errors.email }}</div>
                    </div>

                    <!-- Password -->
                    <div class="mb-4 mt-16">
                        <label for="password" class="block text-xs uppercase tracking-wide text-gray-700 mb-2"></label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="w-full border-0 border-b-2 border-gray-400 focus:border-black focus:ring-0 pl-0"
                            required
                            autocomplete="current-password"
                            :placeholder="$t('password')"
                        />
                        <div class="flex justify-end">
                            <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm text-gray-500 hover:text-gray-800 mt-1 underline">
                                {{ $t('forgot_password')}}
                            </Link>
                        </div>
                        <div class="text-red-500 text-xs mt-1" v-if="form.errors.password">{{ form.errors.password }}</div>
                    </div>

                    <!-- Submit -->
                    <div class="mt-20">
                        <button
                            type="submit"
                            class="w-48 bg-gray-900 text-white py-2 px-4 rounded-none uppercase text-md tracking-wide hover:bg-black transition duration-200"
                            :class="{ 'opacity-50': form.processing }"
                            :disabled="form.processing"
                        >
                            Log In
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style>
.title-font {
    font-family: "Georgia", "Times New Roman", serif;
}
</style>
