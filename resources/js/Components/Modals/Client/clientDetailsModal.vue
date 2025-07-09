<template>
    <el-dialog
        :model-value="visible"
        @open="openModal"
        @close="closeModal"
        class="details-modal"
        body-class="details-body"
        top="2vh"
    >
        <!-- Icon + Titulo -->
        <template #header>
            <div class="flex items-center space-x-2 mb-4 mt-2">
                <span class="material-symbols-outlined" style="font-size: 30px">info</span>
                <h2 class="text-2xl font-semibold text-gray-800">Details</h2>
            </div>
        </template>

        <!---------- DETALHES ------>
        <div class="details-container grid grid-cols-2 md:grid-cols-2 gap-x-1 gap-y-3">
            <!-- Client Name -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="userIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Client Name</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.name || "—" }}</div>
            </div>

            <!-- Email -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="emailIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Email</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{data.email || "—" }}</div>
            </div>

            <!-- Phone Number -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="phoneIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Phone Number</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.phone_number || "—" }}</div>
            </div>

            <!-- Address -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="locationIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Address</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.address || "—" }}</div>
            </div>

            <!-- NIF -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="nifIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>NIF</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.nif || "—" }}</div>
            </div>

            <!-- Birth dATE-->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="birthDateIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Birth Date</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.birth_date || "—" }}</div>
            </div>

            <!-- Description -->
            <div class="col-span-1 md:col-span-2">
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="locationIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Description</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.description || "—" }}</div>
            </div>

            <!-- Attachments (Full Width) -->
            <div class="col-span-1 md:col-span-2">
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="attachmentIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Attachments</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">
                    <div v-if="data.attachments?.length">
                        <div
                            v-for="(file, index) in data.attachments"
                            :key="index"
                            class="underline cursor-pointer"
                            @click="downloadAttachment(file)"
                        >
                            {{ file.name }}
                        </div>
                    </div>
                    <div v-else>—</div>
                </div>
            </div>
        </div>
    </el-dialog>
</template>

<script setup>
import userIcon from "@/Icons/user.svg?url";
import locationIcon from "@/Icons/location.svg?url";
import attachmentIcon from "@/Icons/attachment.svg?url";
import emailIcon from "@/Icons/email.svg?url";
import phoneIcon from "@/Icons/phone.svg?url";
import nifIcon from "@/Icons/nif.svg?url";
import birthDateIcon from "@/Icons/birthDate.svg?url";
import { nextTick } from "vue";

// Define o nome do ficheiro
defineOptions({
    name: "clientDetailsModal",
});

// Define as props
const props = defineProps({
    visible: Boolean,
    data: Object,
});

//Define os emits
const emit = defineEmits(["update:visible"]);

const openModal = () => {
    // Garanto que o scroll reseta para o topo
    nextTick(() => {
        const dialogBody = document.querySelector(".details-body");
        if (dialogBody) {
            dialogBody.scrollTop = 0;
        }
    });
};

const closeModal = () => {
    emit("update:visible", false);
};

const downloadAttachment = (file) => {
    // Download do ficheiro
    const link = document.createElement("a");
    link.href = file.url;
    link.download = file.name || "attachment";
    link.click();
};
</script>

<style>
.label {
    color: #FFD400;
    background-color: #1D3A32;
    font-weight: bold;
}

.details-modal .el-dialog__body {
    max-height: 70vh;
    overflow-y: auto;
    padding-right: 8px;
}
</style>
