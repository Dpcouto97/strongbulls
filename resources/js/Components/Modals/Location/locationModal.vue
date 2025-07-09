<template>
    <el-dialog :model-value="visible" @open="openModal" @close="closeModal" class="custom-dialog">
        <!-- Icon + Titulo -->
        <template #header>
            <div class="flex items-center space-x-2 mb-4 ml-4 mt-2">
                <span class="material-symbols-outlined" style="font-size: 30px">
                    {{ editMode ? "edit_square" : "add_box" }}
                </span>
                <h2 class="text-2xl font-semibold text-gray-800">
                    {{ editMode ? "Edit Location" : "Create Location" }}
                </h2>
            </div>
        </template>
        <!-------- FORMULARIO ------->
        <el-form
            :model="form"
            :rules="rules"
            ref="formRef"
            label-position="top"
            class="space-y-5"
        >
            <!-- Name-->
            <el-form-item label="Location Name" prop="name">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="locationIcon" alt="icon" class="w-4 h-4" />
                        <span>Location Name</span>
                    </div>
                </template>
                <el-input v-model="form.name" placeholder="Write here..." />
            </el-form-item>
        </el-form>

        <!-- Botao GUARDAR O MODAL-->
        <template #footer>
            <el-button v-loading="isLoading" type="primary" size="large" class="w-full text-white save-button" @click="submitUpdate" :disabled="isLoading">
                {{ editMode ? "SAVE CHANGES" : "CREATE LOCATION" }}
            </el-button>
        </template>
    </el-dialog>
</template>
<script setup>
import { ref, computed, onMounted } from "vue";
import { ElNotification } from "element-plus";
import axios from "axios";
import "../../../../css/form.css";
import locationIcon from "@/Icons/location.svg?url";

// Define o nome do ficheiro
defineOptions({
    name: "LocationModal",
});

// Define as props
const props = defineProps({
    visible: Boolean,
    row: Object,
    editMode: Boolean,
});

//Define os emits
const emit = defineEmits(["update:visible", "update"]);

// Ref - define uma variavel para uso geral no componente
// Variaveis Reativas
const formRef = ref(null);
const form = ref({
    id: "",
    name: "",
});
const isLoading = ref(false);

// Como as rules nao sao reactivas/nao sao alteradas entao nao precisamos de criar com 'ref'
const rules = {
    name: [
        { required: false, message: "Please input a Name", trigger: "blur" },
        {
            min: 1,
            max: 50,
            message: "Length should be 1 to 100",
            trigger: "blur",
        },
    ],
    price: [{ required: true, message: "Please choose a price", trigger: "blur" }],
};

const submitUpdate = async () => {
    try {
        isLoading.value = true;
        //Como o "validate' não é assincrono  é preciso usar promise para esperar pela resposta do validate.
        const valid = await new Promise((resolve) => {
            formRef.value.validate((valid) => {
                resolve(valid);
            });
        });

        if (valid) {
            const url = props.editMode
                ? `/api/locations/${props.row.id}` // URL para editar uma categoria
                : "/api/locations"; // URL para criar uma categoria

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
    isLoading.value = false;
};

const openModal = () => {
    // Ao abrir o modal se estou em modo de edicao e tenho os dados preencho o formulario.
    if (props.editMode && props.row) {
        Object.assign(form.value, props.row);
    }
};

const closeModal = () => {
    formRef.value?.clearValidate();
    resetForm();
    emit("update:visible", false);
};
</script>

<style scoped>
</style>
