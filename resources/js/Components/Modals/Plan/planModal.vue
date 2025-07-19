<template>
    <el-dialog :model-value="visible" @open="openModal" @close="closeModal" class="custom-dialog" top="2vh">
        <!-- Icon + Titulo -->
        <template #header>
            <div class="flex items-center space-x-2 mb-4 ml-4 mt-2">
                <span class="material-symbols-outlined" style="font-size: 30px">
                    {{ editMode ? "edit_square" : "add_box" }}
                </span>
                <h2 class="text-2xl font-semibold text-gray-800">
                    {{ editMode ? "Edit Plan" : "Create Plan" }}
                </h2>
            </div>
        </template>

        <!--------------- FORMULARIO --------->
        <el-form :model="form" ref="formRef" label-position="top" label-width="120px" class="space-y-5">
            <!-- Plan Name -->
            <el-form-item label="Name" prop="name">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <span class="material-symbols-outlined">flowsheet</span>
                        <span>Name</span>
                    </div>
                </template>
                <el-input v-model="form.name" placeholder="Write here..." />
            </el-form-item>

            <!-- Clientes -->
            <el-form-item label="Client" prop="clients">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="clientIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>Client</span>
                    </div>
                </template>
                <el-select v-model="form.clients" placeholder="Choose Clients" clearable filterable multiple>
                    <el-option v-for="item in clientsList" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
            </el-form-item>

            <!-- Type -->
            <el-form-item label="Type" prop="type">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="typeIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>Type</span>
                    </div>
                </template>
                <el-input v-model="form.type" placeholder="Write here..." />
            </el-form-item>

            <!-- Description -->
            <el-form-item label="Description" prop="description">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="reportIcon" alt="icon" class="w-4 h-4" />
                        <span>Description</span>
                    </div>
                </template>
                <el-input
                    class="no-resize-textarea"
                    type="textarea"
                    :rows="3"
                    v-model="form.description"
                    placeholder="Write here..."
                />
            </el-form-item>
        </el-form>

        <!-- Botao GUARDAR O MODAL-->
        <template #footer>
            <el-button
                v-loading="isLoading"
                type="primary"
                size="large"
                class="w-full text-white save-button"
                @click="submitUpdate"
            >
                {{ editMode ? "SAVE CHANGES" : "CREATE PLAN" }}
            </el-button>
        </template>
    </el-dialog>
</template>
<script setup>
import { ref, nextTick } from "vue";
import { ElNotification } from "element-plus";
import axios from "axios";
import "../../../../css/form.css";
import clientIcon from "@/Icons/client.svg?url";
import reportIcon from "@/Icons/report.svg?url";
import typeIcon from "@/Icons/type.svg?url";

// Define o nome do ficheiro
defineOptions({
    name: "planModal",
});

// Define as props
const props = defineProps({
    visible: Boolean,
    row: Object,
    editMode: Boolean,
    clientsList: Array,
});

//Define os emits
const emit = defineEmits(["update:visible", "update"]);

// Ref - define uma variavel para uso geral no componente
// Variaveis Reativas
const formRef = ref(null);
const isLoading = ref(false);
const form = ref({
    id: "",
    name: "",
    type: "",
    description: "",
    clients: [],
});

const openModal = () => {
    // Ao abrir o modal se estou em modo de edicao e tenho os dados preencho o formulario.
    if (props.editMode && props.row) {
        Object.assign(form.value, props.row);
    }

    nextTick(() => {
        // Garantir que na abertura do modal, o scroll fica sempre no topo
        const formEl = formRef.value?.$el;
        if (formEl && typeof formEl.scrollTo === "function") {
            formEl.scrollTo({ top: 0, behavior: "instant" });
        } else if (formEl) {
            formEl.scrollTop = 0;
        }
    });
};

const closeModal = () => {
    formRef.value?.clearValidate();
    resetForm();
    emit("update:visible", false);
};

const submitUpdate = async () => {
    try {
        isLoading.value = true;
        //Como o "validate' não é assincrono é preciso usar promise para esperar pela resposta do validate.
        const valid = await new Promise((resolve) => {
            formRef.value.validate((valid) => {
                resolve(valid);
            });
        });

        if (valid) {
            const url = props.editMode
                ? `/api/plans/${props.row.id}` // URL para editar um Plano
                : "/api/plans"; // URL para criar um plano

            const method = props.editMode ? "put" : "post"; // Usar PUT se editar, usar POST se criar.

            // Pedido API
            const response = await axios[method](url, { ...form.value });

            if (response.data.success) {
                ElNotification({
                    title: "Success",
                    type: "success",
                    duration: 1400,
                });
                emit("update");
                closeModal();
                isLoading.value = false;
            } else {
                isLoading.value = false;
            }
        }
    } catch (error) {
        ElNotification({
            title: `Error - ${error.response.data.message}`,
            type: "error",
            duration: 1400,
        });
        isLoading.value = false;
    }
};

const resetForm = () => {
    if (!props.editMode) {
        form.value.id = "";
    }
    form.value.name = "";
    form.value.type = "";
    form.value.description = "";
    form.value.clients = [];
    isLoading.value = false;
};
</script>

<style></style>
