<template>
    <el-dialog :model-value="visible" @open="openModal" @close="closeModal" class="custom-dialog" top="2vh">
        <!-- Icon + Titulo -->
        <template #header>
            <div class="flex items-center space-x-2 mb-4 ml-4 mt-2">
                <span class="material-symbols-outlined" style="font-size: 30px">
                    {{ editMode ? "edit_square" : "add_box" }}
                </span>
                <h2 class="text-2xl font-semibold text-gray-800">
                    {{ editMode ? $t('edit_exercise') : $t('create_exercise') }}
                </h2>
            </div>
        </template>

        <!--------------- FORMULARIO --------->
        <el-form :model="form" ref="formRef" label-position="top" label-width="120px" class="space-y-5">
            <!-- Exercise Name -->
            <el-form-item label="Exercise Name" prop="name">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <span class="material-symbols-outlined">person</span>
                        <span>{{ $t('name') }}</span>
                    </div>
                </template>
                <el-input v-model="form.name" :placeholder="$t('write_here')"/>
            </el-form-item>

            <!-- Muscle Group -->
            <el-form-item label="Muscle Group" prop="muscle_group">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="muscleGroupIcon" alt="icon" class="w-4 h-4 filter brightness-0" />
                        <span>{{ $t('muscle_group') }}</span>
                    </div>
                </template>
                <el-select
                    v-model="form.muscle_group"
                    :placeholder="$t('select_muscle_group')"
                    clearable
                    filterable
                >
                    <el-option
                        v-for="item in muscleGroups"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-form-item>


            <!-- Description -->
            <el-form-item label="Description" prop="description">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="reportIcon" alt="icon" class="w-4 h-4" />
                        <span>{{ $t('description') }}</span>
                    </div>
                </template>
                <el-input
                    class="no-resize-textarea"
                    type="textarea"
                    :rows="3"
                    v-model="form.description"
                    :placeholder="$t('write_here')"
                />
            </el-form-item>

            <!-- ATTACHMENTS -->
            <el-form-item label="Attachments" prop="fileList">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="attachmentIcon" alt="icon" class="w-4 h-4" />
                        <span>{{ $t('attachment') }}</span>
                    </div>
                </template>
                <files-upload ref="filesUploadRef" :single-file="true"></files-upload>
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
                {{ editMode ? $t('save_changes') : $t('create_exercise') }}
            </el-button>
        </template>
    </el-dialog>
</template>
<script setup>
import { ref, nextTick } from "vue";
import { ElNotification } from "element-plus";
import axios from "axios";
import "../../../../css/form.css";
import filesUpload from "@/Components/Custom/filesUpload.vue";
import attachmentIcon from "@/Icons/attachment.svg?url";
import reportIcon from "@/Icons/report.svg?url";
import muscleGroupIcon from "@/Icons/muscle_group.svg?url";

// Define o nome do ficheiro
defineOptions({
    name: "ExerciseModal",
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
const $t = (key) => window.translations?.[key] || key;
const formRef = ref(null);
const filesUploadRef = ref(null);
const isLoading = ref(false);
const form = ref({
    id: "",
    name: "",
    muscle_group: "",
    description: "",
    attachments: [],
});

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
    { label: $t("core"), value: "core" }
];

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

        // Defino os ficheiros no componente de acordo com os dados recebidos no modal.
        filesUploadRef.value?.setFiles(form.value.attachments || []);
    });
};

const closeModal = () => {
    formRef.value?.clearValidate();
    filesUploadRef.value?.resetUpload();
    resetForm();
    emit("update:visible", false);
};

const submitUpdate = async () => {
    try {
        isLoading.value = true;
        //Como o "validate' é assincrono  é preciso usar promise para esperar pela resposta do validate.
        const valid = await new Promise((resolve) => {
            formRef.value.validate((valid) => {
                resolve(valid);
            });
        });

        if (valid) {
            // Necessário criar este objeto formData que permite a passagem de ficheiros.
            const formData = new FormData();

            const appendIfDefined = (key, value) => {
                if (value !== null && value !== undefined) {
                    formData.append(key, value);
                }
            };

            // Form Fields
            formData.append("id", form.value.id);
            formData.append("name", form.value.name);
            formData.append("muscle_group", form.value.muscle_group || "");
            formData.append("description", form.value.description || "");

            // Tratamento dos anexos
            let files = filesUploadRef.value.filesUploaded;

            // Novos ficheiros
            files.forEach((file, index) => {
                if (file.raw) {
                    formData.append(`new_attachments[${index}]`, file.raw); //Guardo o ficheiro em new_attachments, e nao _data, pois assim no backend é mais facil distinguir onde está o conteudo do ficheiro.
                    formData.append(`new_attachments_data[${index}][name]`, file.name);
                    formData.append(`new_attachments_data[${index}][size]`, file.size);
                    formData.append(`new_attachments_data[${index}][type]`, file.raw?.type);
                }
            });

            // Ficheiros existentes
            formData.append(
                "existing_attachments",
                JSON.stringify(
                    files
                        .filter((file) => file.url && file.type && !file.raw) // already uploaded
                        .map((file) => ({
                            name: file.name,
                            path: new URL(file.url).pathname, // extract just the path
                            size: file.size ?? null,
                            type: file.type ?? null,
                        })),
                ),
            );

            // Ficheiros eliminados, que ja se encontravam gravados no servidor
            const deletedPaths = filesUploadRef.value.filesDeleted;
            formData.append("deleted_attachments", JSON.stringify(deletedPaths));

            // Por causa de usarmos o formData temos que especificamente utilizar o metodo POST.
            // No caso de ser PUT temos que especificar o metodo dentro do formData.
            if (props.editMode) {
                formData.append("_method", "PUT");
            }

            const url = props.editMode
                ? `/api/exercises/${props.row.id}` // URL para editar um provider
                : "/api/exercises"; // URL para criar um provider

            // Pedido API
            const response = await axios.post(url, formData);

            if (response.data.success) {
                ElNotification({
                    title: $t('success'),
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
    form.value.muscle_group = "";
    form.value.description = "";
    form.value.attachments = [];
    isLoading.value = false;
};
</script>

<style></style>
