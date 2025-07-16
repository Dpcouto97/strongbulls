<template>
    <el-dialog :model-value="visible" @open="openModal" @close="closeModal" class="custom-dialog" top="2vh">
        <!-- Icon + Titulo -->
        <template #header>
            <div class="flex items-center space-x-2 mb-4 ml-4 mt-2">
                <span class="material-symbols-outlined" style="font-size: 30px">
                    {{ editMode ? "edit_square" : "add_box" }}
                </span>
                <h2 class="text-2xl font-semibold text-gray-800">
                    {{ editMode ? "Edit Evaluation" : "Create Evaluation" }}
                </h2>
            </div>
        </template>

        <!--------------- FORMULARIO --------->
        <el-form :model="form" ref="formRef" label-position="top" label-width="120px" class="space-y-5">
            <!-- Date -->
            <el-form-item label="Date" prop="date">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="calendarIcon" alt="icon" class="w-4 h-4" />
                        <span>Date</span>
                    </div>
                </template>
                <el-date-picker
                    v-model="form.date"
                    type="datetime"
                    placeholder="Pick date and time"
                    format="DD-MM-YYYY HH:mm"
                    value-format="YYYY-MM-DD HH:mm:ss"
                />

            </el-form-item>

            <!-- Cliente -->
            <el-form-item label="Client" prop="client_id">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="clientIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>Client</span>
                    </div>
                </template>
                <el-select v-model="form.client_id" placeholder="Choose Client" clearable filterable>
                    <el-option v-for="item in clientsList" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
            </el-form-item>

            <!-- Weight -->
            <el-form-item label="Weight" prop="weight">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="scaleIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>Weight</span>
                    </div>
                </template>
                <el-input v-model.number="form.weight" type="number" step="0.1" min="0" placeholder="0">
                    <template #suffix>
                        <span style="color: #333">Kg</span>
                    </template>
                </el-input>
            </el-form-item>

            <!-- IMC -->
            <el-form-item label="Imc" prop="imc">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="rullerIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>IMC</span>
                    </div>
                </template>
                <el-input v-model.number="form.imc" type="number" step="0.1" min="0" placeholder="0" />
            </el-form-item>

            <!-- Muscle Mass -->
            <el-form-item label="Muscle Mass" prop="muscle_mass">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="muscleMassIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>Muscle Mass</span>
                    </div>
                </template>
                <el-input v-model.number="form.muscle_mass" type="number" step="0.01" min="0" placeholder="0">
                    <template #suffix>
                        <span style="color: #333">Kg</span>
                    </template>
                </el-input>
            </el-form-item>

            <!-- Bone Mass -->
            <el-form-item label="Bone Mass" prop="bone_mass">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="boneIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>Bone Mass</span>
                    </div>
                </template>
                <el-input v-model.number="form.bone_mass" type="number" step="0.01" min="0" placeholder="0">
                    <template #suffix>
                        <span style="color: #333">Kg</span>
                    </template>
                </el-input>
            </el-form-item>

            <!-- Body Fat -->
            <el-form-item label="Body Fat" prop="body_fat">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="bodyFatIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>Body Fat</span>
                    </div>
                </template>
                <el-input v-model.number="form.body_fat" type="number" step="0.01" min="0" placeholder="0">
                    <template #suffix>
                        <span style="color: #333">%</span>
                    </template>
                </el-input>
            </el-form-item>

            <!-- Body Water -->
            <el-form-item label="Body Water" prop="body_water">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="waterDropIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>Body Water</span>
                    </div>
                </template>
                <el-input v-model.number="form.body_water" type="number" step="0.01" min="0" placeholder="0">
                    <template #suffix>
                        <span style="color: #333">%</span>
                    </template>
                </el-input>
            </el-form-item>

            <!-- Visceral Fat -->
            <el-form-item label="Visceral Fat" prop="visceral_fat">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="visceralFatIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>Visceral Fat</span>
                    </div>
                </template>
                <el-input v-model.number="form.visceral_fat" type="number" step="1" min="0" placeholder="0" />
            </el-form-item>

            <!-- BMR -->
            <el-form-item label="BMR" prop="bmr">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="bmrIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>BMR</span>
                    </div>
                </template>
                <el-input v-model.number="form.bmr" type="number" step="1" min="0" placeholder="0">
                    <template #suffix>
                        <span style="color: #333">Kcal</span>
                    </template>
                </el-input>
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

            <!-- ATTACHMENTS -->
            <el-form-item label="Attachments" prop="fileList">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="attachmentIcon" alt="icon" class="w-4 h-4" />
                        <span>Attachments</span>
                    </div>
                </template>
                <files-upload ref="filesUploadRef"></files-upload>
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
                {{ editMode ? "SAVE CHANGES" : "CREATE EVALUATION" }}
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
import bmrIcon from "@/Icons/bmr.svg?url";
import waterDropIcon from "@/Icons/waterDrop.svg?url";
import attachmentIcon from "@/Icons/attachment.svg?url";
import bodyFatIcon from "@/Icons/bodyFat.svg?url";
import boneIcon from "@/Icons/bone.svg?url";
import muscleMassIcon from "@/Icons/muscleMass.svg?url";
import calendarIcon from "@/Icons/calendar.svg?url";
import clientIcon from "@/Icons/client.svg?url";
import scaleIcon from "@/Icons/scale.svg?url";
import rullerIcon from "@/Icons/ruller.svg?url";
import visceralFatIcon from "@/Icons/visceralFat.svg?url";
import reportIcon from "@/Icons/report.svg?url";
import moment from "moment";

// Define o nome do ficheiro
defineOptions({
    name: "evaluationModal",
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
const filesUploadRef = ref(null);
const isLoading = ref(false);
const form = ref({
    id: "",
    name: "",
    description: "",
    date: "",
    bmr: "",
    weight: "",
    imc: "",
    muscle_mass: "",
    bone_mass: "",
    visceral_fat: "",
    body_fat: "",
    body_water: "",
    attachments: [],
});

const openModal = () => {
    // Ao abrir o modal se estou em modo de edicao e tenho os dados preencho o formulario.
    if (props.editMode && props.row) {
        Object.assign(form.value, props.row);
    }else {
        // Se estiver em modo de criacao preenchemos a data automaticamente com o dia e hora atual
        form.value.date = moment().format("YYYY-MM-DD HH:mm:ss")
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
            formData.append("date", form.value.date || "");
            formData.append("bmr", form.value.bmr || "");
            formData.append("weight", form.value.weight || "");
            formData.append("imc", form.value.imc || "");
            formData.append("muscle_mass", form.value.muscle_mass || "");
            formData.append("bone_mass", form.value.bone_mass || "");
            formData.append("visceral_fat", form.value.visceral_fat || "");
            formData.append("body_fat", form.value.body_fat || "");
            formData.append("body_water", form.value.body_water || "");
            formData.append("description", form.value.description || "");

            //Necessario verificar o null e undefined neste caso porque o formData tende a enviar como string em vez de null e entra em conflito no back end
            appendIfDefined("client_id", form.value.client_id);

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
                        .filter((file) => file.url) // already uploaded
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
                ? `/api/evaluations/${props.row.id}` // URL para editar um provider
                : "/api/evaluations"; // URL para criar um provider

            // Pedido API
            const response = await axios.post(url, formData);

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
    form.value.description = "";
    form.value.date = "";
    form.value.bmr = "";
    form.value.weight = "";
    form.value.imc = "";
    form.value.muscle_mass = "";
    form.value.bone_mass = "";
    form.value.visceral_fat = "";
    form.value.body_fat = "";
    form.value.body_water = "";
    form.value.client_id = "";
    form.value.attachments = [];
    isLoading.value = false;
};
</script>

<style></style>
