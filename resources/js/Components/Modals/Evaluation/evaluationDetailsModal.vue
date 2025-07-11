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
                <span class="material-symbols-outlined" style="font-size: 30px">health_metrics</span>
                <h2 class="text-2xl font-semibold text-gray-800">{{ data.client.name }}</h2>
            </div>
        </template>

        <el-collapse v-model="activeNames" @change="handleChange">
            <el-collapse-item title="Consistency" name="1">
                <template #title>
                    <div class="custom-title">
                        <span class="material-symbols-outlined">scale</span>
                        <span class="title-text">Weight</span>
                        <span class="info-badge"> {{ data.weight + " Kg"}}</span>
                    </div>
                </template>
                <div>
                    teste
                </div>
                <div>
                    teste
                </div>
            </el-collapse-item>
            <el-collapse-item title="Feedback" name="2">
                <div>
                    Operation feedback: enable the users to clearly perceive their
                    operations by style updates and interactive effects;
                </div>
                <div>
                    Visual feedback: reflect current state by updating or rearranging
                    elements of the page.
                </div>
            </el-collapse-item>
            <el-collapse-item title="Efficiency" name="3">
                <div>
                    Simplify the process: keep operating process simple and intuitive;
                </div>
                <div>
                    Definite and clear: enunciate your intentions clearly so that the
                    users can quickly understand and make decisions;
                </div>
                <div>
                    Easy to identify: the interface should be straightforward, which helps
                    the users to identify and frees them from memorizing and recalling.
                </div>
            </el-collapse-item>
            <el-collapse-item title="Controllability" name="4">
                <div>
                    Decision making: giving advices about operations is acceptable, but do
                    not make decisions for the users;
                </div>
                <div>
                    Controlled consequences: users should be granted the freedom to
                    operate, including canceling, aborting or terminating current
                    operation.
                </div>
            </el-collapse-item>
        </el-collapse>
    </el-dialog>
</template>

<script setup>
import { ref } from 'vue'
import locationIcon from "@/Icons/location.svg?url";
import attachmentIcon from "@/Icons/attachment.svg?url";
import emailIcon from "@/Icons/email.svg?url";
import phoneIcon from "@/Icons/phone.svg?url";
import nifIcon from "@/Icons/nif.svg?url";
import birthDateIcon from "@/Icons/birthDate.svg?url";
import rullerIcon from "@/Icons/ruller.svg?url";
import { nextTick } from "vue";

// Define o nome do ficheiro
defineOptions({
    name: "evaluationDetailsModal",
});

// Define as props
const props = defineProps({
    visible: Boolean,
    data: Object,
});

const activeNames = ref([])
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
    height: 70vh;
    overflow-y: auto;
    padding-right: 8px;
}
.custom-title {
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: bold;
}

.custom-title .material-symbols-outlined {
    font-size: 22px;
}

.title-text {
    flex-grow: 1;
}

.info-badge {
    font-size: 16px;
    padding: 2px 10px;
    border-radius: 10px;
}

</style>
