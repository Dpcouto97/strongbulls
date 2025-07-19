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
                <h2 class="text-2xl font-semibold text-gray-800">{{ data.name }}</h2>
            </div>
        </template>

        <!---------- DETALHES ------>
        <div class="details-container grid grid-cols-2 md:grid-cols-2 gap-x-1 gap-y-3">
            <!-- Plan Name -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="planIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Name</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.name || "—" }}</div>
            </div>

            <!-- Type -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="typeIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Type</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.type || "—" }}</div>
            </div>

            <!-- Clientes -->
            <div v-if="data.clients && data.clients.length" class="col-span-1 md:col-span-2">
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="clientIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Clients</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ getClientNames(data.clients).join(", ") }}</div>
            </div>

            <!-- Description -->
            <div class="col-span-1 md:col-span-2">
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="reportIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>Description</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.description || "—" }}</div>
            </div>
        </div>
    </el-dialog>
</template>

<script setup>
import planIcon from "@/Icons/plan.svg?url";
import typeIcon from "@/Icons/type.svg?url";
import reportIcon from "@/Icons/report.svg?url";
import clientIcon from "@/Icons/client.svg?url";
import { nextTick } from "vue";

// Define o nome do ficheiro
defineOptions({
    name: "planDetailsModal",
});

// Define as props
const props = defineProps({
    visible: Boolean,
    data: Object,
    clientsList: Array,
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

const getClientNames = (ids) => {
    return ids
        .map((id) => props.clientsList.find((client) => client.id === id))
        .filter(Boolean)
        .map((client) => client.name);
};
</script>

<style>
.label {
    color: #ffd400;
    background-color: #1d3a32;
    font-weight: bold;
}

.details-modal .el-dialog__body {
    max-height: 70vh;
    overflow-y: auto;
    padding-right: 8px;
}
</style>
