<template>
    <el-dialog :model-value="visible" @open="openModal" @close="closeModal" class="custom-dialog" top="2vh">
        <!-- Icon + Titulo -->
        <template #header>
            <div class="flex items-center space-x-2 mb-4 ml-4 mt-2">
                <span class="material-symbols-outlined" style="font-size: 30px">
                    {{ editMode ? "edit_square" : "add_box" }}
                </span>
                <h2 class="text-2xl font-semibold text-gray-800">
                    {{ editMode ? "Edit Client" : "Create Client" }}
                </h2>
            </div>
        </template>

        <!--------------- FORMULARIO --------->
        <el-form :model="form" :rules="rules" ref="formRef" label-position="top" label-width="120px" class="space-y-5">
            <!-- Client Name -->
            <el-form-item label="Client Name" prop="name">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <span class="material-symbols-outlined">person</span>
                        <span>Name</span>
                    </div>
                </template>
                <el-input v-model="form.name" placeholder="Write here..." />
            </el-form-item>

            <!-- EMAIL -->
            <el-form-item label="Email" prop="email">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="emailIcon" alt="icon" class="w-4 h-4 filter brightness-0" />
                        <span>Email</span>
                    </div>
                </template>
                <el-input v-model="form.email" placeholder="Write here..." clearable />
            </el-form-item>

            <!-- PHONE NUMBER -->
            <el-form-item label="Phone Number" prop="phone_number">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="phoneIcon" alt="icon" class="w-4 h-4" />
                        <span>Phone Number</span>
                    </div>
                </template>
                <el-input v-model="form.phone_number" placeholder="Write here..." />
            </el-form-item>

            <!-- ADDRESS -->
            <el-form-item label="Address" prop="address">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="locationIcon" alt="icon" class="w-4 h-4" />
                        <span>Address</span>
                    </div>
                </template>
                <el-input v-model="form.address" placeholder="Write here..." />
            </el-form-item>

            <!-- NIF -->
            <el-form-item label="NIF" prop="nif">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="nifIcon" alt="icon" class="w-4 h-4" />
                        <span>NIF</span>
                    </div>
                </template>
                <el-input v-model="form.nif" placeholder="Write here..." />
            </el-form-item>

            <!-- Height -->
            <el-form-item label="Height" prop="height">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="rullerIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>Height</span>
                    </div>
                </template>
                <el-input
                    v-model.number="form.height"
                    type="number"
                    step="1"
                    min="0"
                    max="300"
                    placeholder="0"
                >
                    <template #suffix>
                        <span style="color: #333">cm</span>
                    </template>
                </el-input>
            </el-form-item>

            <!-- Birth Date -->
            <el-form-item label="Birth Date" prop="birth_date">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="birthDateIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>Birth Date</span>
                    </div>
                </template>
                <el-date-picker
                    v-model="form.birth_date"
                    type="date"
                    placeholder="Pick a date"
                    value-format="YYYY-MM-DD"
                />
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
                {{ editMode ? "SAVE CHANGES" : "CREATE CLIENT" }}
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
import reportIcon from "@/Icons/report.svg?url";
import locationIcon from "@/Icons/location.svg?url";
import attachmentIcon from "@/Icons/attachment.svg?url";
import emailIcon from "@/Icons/email.svg?url";
import phoneIcon from "@/Icons/phone.svg?url";
import nifIcon from "@/Icons/nif.svg?url";
import birthDateIcon from "@/Icons/birthDate.svg?url";
import rullerIcon from "@/Icons/ruller.svg?url";

// Define o nome do ficheiro
defineOptions({
    name: "ClientModal",
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
const filesUploadRef = ref(null);
const isLoading = ref(false);
const form = ref({
    id: "",
    name: "",
    email: "",
    phone_number: "",
    address: "",
    nif: "",
    height: "",
    birth_date: "",
    description: "",
    attachments: [],
});

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
};

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
            formData.append("email", form.value.email || "");
            formData.append("phone_number", form.value.phone_number || "");
            formData.append("address", form.value.address || "");
            formData.append("nif", form.value.nif || "");
            formData.append("height", form.value.height || "");
            formData.append("birth_date", form.value.birth_date || "");
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
                ? `/api/clients/${props.row.id}` // URL para editar um provider
                : "/api/clients"; // URL para criar um provider

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
    form.value.email = "";
    form.value.phone_number = "";
    form.value.address = "";
    form.value.nif = "";
    form.value.height = "";
    form.value.birth_date = "";
    form.value.description = "";
    form.value.attachments = [];
    isLoading.value = false;
};
</script>

<style></style>
