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
            <!-- Name -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <span class="material-symbols-outlined" style="color: white; font-size:16px;">fitness_center</span>
                    <span>{{ $t('name') }}</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.name || "—" }}</div>
            </div>

            <!-- Grupo Muscular -->
            <div>
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="muscleGroupIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>{{ $t('muscle_group') }}</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{muscleGroups.find((g) => g.value === data.muscle_group)?.label || "—" }}</div>
            </div>

            <!-- Description -->
            <div class="col-span-1 md:col-span-2">
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="reportIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>{{ $t('description') }}</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">{{ data.description || "—" }}</div>
            </div>

            <!-- Attachment (Full Width) -->
            <div class="col-span-1 md:col-span-2">
                <div class="label px-4 py-2 flex items-center gap-2">
                    <img :src="attachmentIcon" alt="icon" class="w-4 h-4 filter brightness-0 invert" />
                    <span>{{ $t('attachment') }}</span>
                </div>
                <div class="bg-gray-100 px-4 py-2">
                    <div v-if="data.attachments?.length">
                        <div
                            v-for="(file, index) in data.attachments"
                            :key="index"
                            class="underline cursor-pointer"
                            @click="downloadAttachment(file)"
                        >
                            <img :src="file.url" alt="icon" />
                        </div>
                    </div>
                    <div v-else>—</div>
                </div>
            </div>
        </div>
    </el-dialog>
</template>

<script setup>
import attachmentIcon from "@/Icons/attachment.svg?url";
import rullerIcon from "@/Icons/ruller.svg?url";
import reportIcon from "@/Icons/report.svg?url";
import muscleGroupIcon from "@/Icons/muscle_group.svg?url";
import { nextTick } from "vue";

// Define o nome do ficheiro
defineOptions({
    name: "exerciseDetailsModal",
});

// Define as props
const props = defineProps({
    visible: Boolean,
    data: Object,
});

//Define os emits
const emit = defineEmits(["update:visible"]);

const $t = (key) => window.translations?.[key] || key;
// Lista fixa de grupos musculares
const muscleGroups = [
    { label: $t("chest"), value: "chest" },
    { label: $t("back"), value: "back" },
    { label: $t("shoulders"), value: "shoulders" },
    { label: $t("biceps"), value: "biceps" },
    { label: $t("triceps"), value: "triceps" },
    { label: $t("trapezius"), value: "trapezius" },
    { label: $t("quadriceps"), value: "quadriceps" },
    { label: $t("hamstrings"), value: "hamstrings" },
    { label: $t("glutes"), value: "glutes" },
    { label: $t("calves"), value: "calves" },
    { label: $t("core"), value: "core" },
];

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
