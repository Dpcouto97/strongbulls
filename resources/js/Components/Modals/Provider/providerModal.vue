<template>
    <el-dialog :model-value="visible" @open="openModal" @close="closeModal" class="custom-dialog" top="2vh">
        <!-- Icon + Titulo -->
        <template #header>
            <div class="flex items-center space-x-2 mb-4 ml-4 mt-2">
                <span class="material-symbols-outlined" style="font-size: 30px">
                    {{ editMode ? "edit_square" : "add_box" }}
                </span>
                <h2 class="text-2xl font-semibold text-gray-800">
                    {{ editMode ? "Edit Provider" : "Create Provider" }}
                </h2>
            </div>
        </template>

        <!--------------- FORMULARIO --------->
        <el-form :model="form" :rules="rules" ref="formRef" label-position="top" label-width="120px" class="space-y-5">
            <!-- Provider Name -->
            <el-form-item label="Provider Name" prop="name">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="userIcon" alt="icon" class="w-4 h-4" />
                        <span>Provider Name</span>
                    </div>
                </template>
                <el-input v-model="form.name" placeholder="Write here..." />
            </el-form-item>

            <!-- CEO Name -->
            <el-form-item label="CEO Name" prop="ceo_name">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="userIcon" alt="icon" class="w-4 h-4" />
                        <span>CEO Name</span>
                    </div>
                </template>
                <el-input v-model="form.ceo_name" placeholder="Write here..." />
            </el-form-item>

            <!-- EMAIL -->
            <el-form-item label="Email" prop="email">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="emailIcon" alt="icon" class="w-4 h-4" />
                        <span>Email</span>
                    </div>
                </template>
                <el-input-tag v-model="form.email" placeholder="Write here..." clearable draggable />
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

            <!-- VAT -->
            <el-form-item label="VAT" prop="vat">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="vatIcon" alt="icon" class="w-4 h-4 filter brightness-1 invert" />
                        <span>VAT</span>
                    </div>
                </template>
                <el-input
                    v-model.number="form.vat"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="VAT"
                    class="w-full"
                />
            </el-form-item>

            <!-- IBAN -->
            <el-form-item label="IBAN" prop="iban">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="ibanIcon" alt="icon" class="w-4 h-4" />
                        <span>IBAN</span>
                    </div>
                </template>
                <el-input v-model="form.iban" placeholder="Write here..." />
            </el-form-item>

            <!-- SCHEDULE -->
            <el-form-item label="Schedule" prop="schedule">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="calendarIcon" alt="icon" class="w-4 h-4" />
                        <span>Schedule</span>
                    </div>
                </template>
                <el-input v-model="form.schedule" placeholder="Write here..." />
            </el-form-item>

            <!-- PAYMENT POLICIES -->
            <el-form-item label="Payment Policies" prop="payment_policies">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="reportIcon" alt="icon" class="w-4 h-4" />
                        <span>Payment Policies</span>
                    </div>
                </template>
                <el-input
                    class="no-resize-textarea"
                    type="textarea"
                    :rows="3"
                    v-model="form.payment_policies"
                    placeholder="Write here..."
                />
            </el-form-item>

            <!-- PAYMENT POLICIES -->
            <el-form-item label="Cancellation Policies" prop="cancellation_policies">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="reportIcon" alt="icon" class="w-4 h-4" />
                        <span>Cancellation Policies</span>
                    </div>
                </template>
                <el-input
                    class="no-resize-textarea"
                    type="textarea"
                    :rows="3"
                    v-model="form.cancellation_policies"
                    placeholder="Write here..."
                />
            </el-form-item>

            <!-- Location -->
            <el-form-item label="Location" prop="location_id">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="locationIcon" alt="icon" class="w-4 h-4" />
                        <span>Location</span>
                    </div>
                </template>
                <el-select v-model="form.location_id" placeholder="Choose Location" clearable filterable>
                    <el-option v-for="item in locationsList" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
            </el-form-item>

            <!-- Categories -->
            <el-form-item label="Categorys" prop="categories">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="categoryIcon" alt="icon" class="w-4 h-4" />
                        <span>Category</span>
                    </div>
                </template>
                <el-select
                    v-model="form.categories"
                    multiple
                    placeholder="Choose Categories"
                    :collapse-tags="form.categories.length > 4"
                    clearable
                    filterable
                >
                    <el-option v-for="item in categoriesList" :key="item.id" :label="item.name" :value="item.id" />
                </el-select>
            </el-form-item>

            <!-- PRIORITY -->
            <el-form-item label="Priority" prop="priority">
                <template #label>
                    <div class="flex items-center space-x-1">
                        <img :src="priorityIcon" alt="icon" class="w-4 h-4" />
                        <span>Priority</span>
                    </div>
                </template>
                <el-radio-group v-model="form.priority" size="large" class="flex space-x-2">
                    <el-radio-button :label="1" :value="1" class="priority-low">Low</el-radio-button>
                    <el-radio-button :label="2" :value="2" class="priority-medium">Medium</el-radio-button>
                    <el-radio-button :label="3" :value="3" class="priority-high">High</el-radio-button>
                    <el-radio-button :label="4" :value="4" class="priority-veryhigh">Very High</el-radio-button>
                </el-radio-group>
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
            <el-button v-loading="isLoading" type="primary" size="large" class="w-full text-white save-button" @click="submitUpdate" :disabled="isLoading">
                {{ editMode ? "SAVE CHANGES" : "CREATE PROVIDER" }}
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
import userIcon from "@/Icons/user.svg?url";
import categoryIcon from "@/Icons/car.svg?url";
import reportIcon from "@/Icons/report.svg?url";
import calendarIcon from "@/Icons/calendar.svg?url";
import locationIcon from "@/Icons/location.svg?url";
import attachmentIcon from "@/Icons/attachment.svg?url";
import emailIcon from "@/Icons/email.svg?url";
import phoneIcon from "@/Icons/phone.svg?url";
import nifIcon from "@/Icons/nif.svg?url";
import ibanIcon from "@/Icons/iban.svg?url";
import priorityIcon from "@/Icons/priority.svg?url";
import vatIcon from "@/Icons/vat.svg?url";

// Define o nome do ficheiro
defineOptions({
    name: "ProviderModal",
});

// Define as props
const props = defineProps({
    visible: Boolean,
    row: Object,
    editMode: Boolean,
    // Listas para Selects
    categoriesList: Array,
    locationsList: Array,
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
    ceo_name: "",
    email: [],
    phone_number: "",
    address: "",
    nif: "",
    vat: "",
    iban: "",
    schedule: "",
    payment_policies: "",
    cancellation_policies: "",
    priority: 1,
    location_id: null,
    categories: [],
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
        //Como o "validate' não é assincrono  é preciso usar promise para esperar pela resposta do validate.
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
            formData.append("ceo_name", form.value.ceo_name || "");
            formData.append("email", form.value.email || "");
            formData.append("phone_number", form.value.phone_number || "");
            formData.append("address", form.value.address || "");
            formData.append("nif", form.value.nif || "");
            formData.append("vat", form.value.vat || 0);
            formData.append("iban", form.value.iban || "");
            formData.append("schedule", form.value.schedule || "");
            formData.append("payment_policies", form.value.payment_policies || "");
            formData.append("cancellation_policies", form.value.cancellation_policies || "");
            formData.append("priority", form.value.priority || 1);
            formData.append("cancellation_policies", form.value.cancellation_policies || "");
            formData.append("cancellation_policies", form.value.cancellation_policies || "");
            // Necessario verificar o null neste caso porque o formData tende a enviar como string em vez de null e entra em conflito no back end
            appendIfDefined("location_id", form.value.location_id);

            // Como o email agora é um array ao transformar no formData tenho de ter atencao em manter-lo como um array.
            if (Array.isArray(form.value.email)) {
                form.value.email.forEach((email) => {
                    formData.append("email[]", email);
                });
            } else {
                formData.append("email[]", form.value.email || "");
            }

            // Categories (array de ids) - para garantir que vai para o back-end como um array de ids no formData
            if (form.value.categories) {
                form.value.categories.forEach((categoryId, index) => {
                    formData.append(`categories[${index}]`, categoryId);
                });
            }

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
                ? `/api/providers/${props.row.id}` // URL para editar um provider
                : "/api/providers"; // URL para criar um provider

            const method = props.editMode ? "put" : "post"; // Usar PUT se editar, usar POST se criar.

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
            } else{
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
    form.value.ceo_name = "";
    form.value.email = [];
    form.value.phone_number = "";
    form.value.address = "";
    form.value.nif = "";
    form.value.vat = "";
    form.value.iban = "";
    form.value.schedule = "";
    form.value.payment_policies = "";
    form.value.cancellation_policies = "";
    form.value.priority = 1;
    form.value.location_id = null;
    form.value.categories = [];
    form.value.attachments = [];
    isLoading.value = false;
};
</script>

<style>
.el-radio-button__inner {
    width:110px !important;
}

/* Nao selecionada (default) */
.el-radio-button__inner {
    /* background-color: #F2F2F2 !important; */
    border: 2px solid transparent !important;
    color: #000000 !important;
    font-weight: 500;
}

/* Remover border azul ao carregar */
.el-radio-button.is-active .el-radio-button__inner {
    border-color: transparent !important;
    box-shadow: none !important;
}
/* Low Priority */
.priority-low.is-active .el-radio-button__inner {
    background-color: #F9A171 !important;
    border: 2px solid #F9A171 !important;
    color: #000000 !important;
}

/* Medium Priority */
.priority-medium.is-active .el-radio-button__inner {
    background-color: #FFCC5C !important;
    border: 2px solid #FFCC5C !important;
    color: #000000 !important;
}

/* High Priority */
.priority-high.is-active .el-radio-button__inner {
    background-color: #C2FF3F !important;
    border: 2px solid #C2FF3F !important;
    color: #000000 !important;
}

/* Very High Priority */
.priority-veryhigh.is-active .el-radio-button__inner {
    background-color: #2DFF2B !important;
    border: 2px solid #2DFF2B !important;
    color: #000000 !important;
}
</style>
